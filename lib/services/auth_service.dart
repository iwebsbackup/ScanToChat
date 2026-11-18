import 'package:firebase_auth/firebase_auth.dart';
import 'package:firebase_database/firebase_database.dart';
import 'package:google_sign_in/google_sign_in.dart';
import 'app_log.dart';
import 'db.dart';
import 'push_service.dart';

/// Web client ID (OAuth client type 3) from google-services.json / Firebase Console →
/// Authentication → Sign-in method → Google → Web SDK configuration → Web client ID.
/// Leave empty first. If logs show config/idToken error, paste it here.
const String kServerClientId = '136023349434-btvlhto1ok0vtnmgdp0ksne58e9k0hkc.apps.googleusercontent.com';

class AppException implements Exception {
  final String message;
  AppException(this.message);
  @override
  String toString() => message;
}

/// App users: Google sign-in or Email & Password (register + login).
/// Admin is NOT in app. Admin = PHP web panel.
class AuthService {
  AuthService._();
  static final instance = AuthService._();

  final _auth = FirebaseAuth.instance;
  bool _googleReady = false;

  Stream<User?> get authChanges => _auth.authStateChanges();
  User? get currentUser => _auth.currentUser;

  Future<void> _initGoogle() async {
    if (_googleReady) return;
    AppLog.d('GoogleSignIn.initialize (serverClientId ${kServerClientId.isEmpty ? "EMPTY" : "set"})');
    await GoogleSignIn.instance
        .initialize(serverClientId: kServerClientId.isEmpty ? null : kServerClientId);
    _googleReady = true;
    AppLog.d('GoogleSignIn initialized');
  }

  /// Sign in with Email and Password
  Future<void> signInWithEmailPassword({
    required String email,
    required String password,
  }) async {
    AppLog.d('--- Email sign-in start: $email ---');
    try {
      final res = await _auth
          .signInWithEmailAndPassword(
            email: email.trim(),
            password: password,
          )
          .timeout(const Duration(seconds: 30));

      final user = res.user;
      if (user != null) {
        try {
          await _upsertUser(user).timeout(const Duration(seconds: 8));
        } catch (e) {
          AppLog.d('RTDB upsert FAILED: $e');
        }
      }
    } on FirebaseAuthException catch (e) {
      AppLog.d('FirebaseAuthException code=${e.code} msg=${e.message}');
      switch (e.code) {
        case 'user-not-found':
        case 'wrong-password':
        case 'invalid-credential':
          throw AppException('Invalid email or password. Please try again.');
        case 'invalid-email':
          throw AppException('Please enter a valid email address.');
        case 'user-disabled':
          throw AppException('Your account has been blocked by admin.');
        case 'too-many-requests':
          throw AppException('Too many failed attempts. Please try again later.');
        case 'network-request-failed':
          throw AppException('No internet connection.');
        default:
          throw AppException(e.message ?? 'Login failed (${e.code}).');
      }
    } on AppException {
      rethrow;
    } catch (e, st) {
      AppLog.d('UNEXPECTED ${e.runtimeType}: $e\n$st');
      throw AppException('Login error: $e');
    }
  }

  /// Register with Name, Email and Password
  Future<void> registerWithEmailPassword({
    required String name,
    required String email,
    required String password,
  }) async {
    AppLog.d('--- Email register start: $name, $email ---');
    try {
      final cleanName = name.trim();
      final res = await _auth
          .createUserWithEmailAndPassword(
            email: email.trim(),
            password: password,
          )
          .timeout(const Duration(seconds: 30));

      final user = res.user;
      if (user != null) {
        try {
          await user.updateDisplayName(cleanName);
        } catch (e) {
          AppLog.d('updateDisplayName failed: $e');
        }

        try {
          await _upsertUser(user, nameOverride: cleanName)
              .timeout(const Duration(seconds: 8));
        } catch (e) {
          AppLog.d('RTDB upsert FAILED: $e');
        }
      }
    } on FirebaseAuthException catch (e) {
      AppLog.d('FirebaseAuthException code=${e.code} msg=${e.message}');
      switch (e.code) {
        case 'email-already-in-use':
          throw AppException('An account already exists with this email.');
        case 'weak-password':
          throw AppException('Password is too weak. Please use at least 6 characters.');
        case 'invalid-email':
          throw AppException('Please enter a valid email address.');
        case 'operation-not-allowed':
          throw AppException('Email/password sign-in is not enabled in Firebase Console.');
        case 'network-request-failed':
          throw AppException('No internet connection.');
        default:
          throw AppException(e.message ?? 'Registration failed (${e.code}).');
      }
    } on AppException {
      rethrow;
    } catch (e, st) {
      AppLog.d('UNEXPECTED ${e.runtimeType}: $e\n$st');
      throw AppException('Registration error: $e');
    }
  }

  /// Send password reset email
  Future<void> sendPasswordReset(String email) async {
    try {
      await _auth.sendPasswordResetEmail(email: email.trim());
    } on FirebaseAuthException catch (e) {
      switch (e.code) {
        case 'user-not-found':
          throw AppException('No account found with this email.');
        case 'invalid-email':
          throw AppException('Please enter a valid email address.');
        default:
          throw AppException(e.message ?? 'Failed to send reset email.');
      }
    } catch (e) {
      if (e is AppException) rethrow;
      throw AppException('Failed to send reset email: $e');
    }
  }

  /// Returns false if user cancelled.
  Future<bool> signInWithGoogle() async {
    AppLog.d('--- Google sign-in start ---');
    try {
      await _initGoogle();
      AppLog.d('authenticate() → opening account picker');
      final account = await GoogleSignIn.instance
          .authenticate()
          .timeout(const Duration(seconds: 90));
      AppLog.d('account picked: ${account.email}');

      final idToken = account.authentication.idToken;
      AppLog.d('idToken ${idToken == null ? "NULL" : "ok (${idToken.length} chars)"}');
      if (idToken == null) {
        throw AppException(
            'Google gave no idToken. Set kServerClientId (web client ID) in auth_service.dart.');
      }

      final cred = GoogleAuthProvider.credential(idToken: idToken);
      AppLog.d('Firebase signInWithCredential...');
      final res = await _auth
          .signInWithCredential(cred)
          .timeout(const Duration(seconds: 30));
      AppLog.d('Firebase login OK uid=${res.user?.uid}');

      // Profile save must never block/undo login (RTDB rules/URL issue etc).
      try {
        AppLog.d('RTDB upsert users/${res.user!.uid} ...');
        await _upsertUser(res.user!).timeout(const Duration(seconds: 8));
        AppLog.d('RTDB upsert OK');
      } catch (e) {
        AppLog.d('RTDB upsert FAILED: $e');
      }
      return true;
    } on GoogleSignInException catch (e) {
      AppLog.d('GoogleSignInException code=${e.code.name} desc=${e.description} details=${e.details}');
      if (e.code == GoogleSignInExceptionCode.canceled) {
        // Android Credential Manager also reports config errors (SHA/clientId) as "canceled".
        throw AppException(
            'Sign-in cancelled or not configured. Please try again.');
      }
      throw AppException('Google sign-in failed (${e.code.name}). Please try again.');
    } on FirebaseAuthException catch (e) {
      AppLog.d('FirebaseAuthException code=${e.code} msg=${e.message}');
      if (e.code == 'user-disabled') {
        throw AppException('Your account is blocked by admin.');
      }
      if (e.code == 'network-request-failed') {
        throw AppException('No internet connection.');
      }
      throw AppException(e.message ?? 'Sign-in error (${e.code}).');
    } on AppException {
      rethrow;
    } catch (e, st) {
      AppLog.d('UNEXPECTED ${e.runtimeType}: $e\n$st');
      throw AppException('Login error: $e');
    }
  }

  Future<void> _upsertUser(User u, {String? nameOverride}) async {
    final ref = Db.ref('users/${u.uid}');
    final snap = await ref.get();
    final displayName = (nameOverride != null && nameOverride.trim().isNotEmpty)
        ? nameOverride.trim()
        : (u.displayName ?? '');
    final base = <String, Object?>{
      'name': displayName,
      'email': u.email ?? '',
      'photoUrl': u.photoURL ?? '',
      'lastLogin': ServerValue.timestamp,
    };
    if (snap.exists) {
      await ref.update(base);
    } else {
      await ref.update({
        ...base,
        'disabled': false,
        'createdAt': ServerValue.timestamp,
      });
    }
  }

  Future<void> signOut() async {
    await PushService.unregisterUser(); // before auth is cleared
    try {
      await _initGoogle();
      await GoogleSignIn.instance.signOut();
    } catch (_) {}
    await _auth.signOut();
  }
}