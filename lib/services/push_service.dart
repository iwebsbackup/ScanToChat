import 'dart:async';

import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/material.dart';
import '../screens/notifications_screen.dart';
import '../screens/privacy_policy_screen.dart';
import 'db.dart';

/// Global keys so a notification tap can open a screen from anywhere.
final GlobalKey<NavigatorState> rootNavigatorKey = GlobalKey<NavigatorState>();
final GlobalKey<ScaffoldMessengerState> rootMessengerKey =
    GlobalKey<ScaffoldMessengerState>();

/// Screens admin can choose in the panel (FCM data key "screen").
class PushScreens {
  static const home = 'home';
  static const notifications = 'notifications';
  static const privacy = 'privacy';
}

/// FCM handling.
///  • Topic "all"  → admin "send to all users".
///  • Token list   → admin "send to selected users". Each device token is saved at
///                   users/{uid}/fcmTokens/{token} after login, removed on logout.
///  • Tap (app killed / background / foreground) → opens data["screen"].
class PushService {
  PushService._();

  static bool _inited = false;
  static bool _homeReady = false;
  static String? _pendingScreen;
  static String? _uid;
  static String? _token;

  static Future<void> init() async {
    if (_inited) return;
    _inited = true;
    try {
      final m = FirebaseMessaging.instance;
      await m.requestPermission();
      await m.subscribeToTopic('all');

      FirebaseMessaging.onMessage.listen(_onForeground);
      FirebaseMessaging.onMessageOpenedApp.listen(_onTap);
      m.onTokenRefresh.listen((t) {
        final uid = _uid;
        if (uid != null) _saveToken(uid, t);
      });

      // App was killed and opened by tapping a notification.
      final initial = await m.getInitialMessage();
      if (initial != null) _onTap(initial);
    } catch (_) {
      // Push is optional. Never break app.
    }
  }

  // ───────── token ↔ user ─────────

  /// Call after login (HomeScreen). Registers this device for [uid].
  static Future<void> registerUser(String uid) async {
    _uid = uid;
    try {
      final t = await FirebaseMessaging.instance.getToken();
      if (t != null) await _saveToken(uid, t);
    } catch (_) {}
  }

  /// Call BEFORE sign out. Stops targeted pushes of this user reaching this phone.
  static Future<void> unregisterUser() async {
    final uid = _uid;
    final t = _token;
    _uid = null;
    _token = null;
    if (uid == null || t == null) return;
    try {
      await Db.ref('users/$uid/fcmTokens/$t')
          .remove()
          .timeout(const Duration(seconds: 4));
    } catch (_) {}
  }

  static Future<void> _saveToken(String uid, String token) async {
    _token = token;
    try {
      await Db.ref('users/$uid/fcmTokens/$token').set(true);
    } catch (_) {}
  }

  // ───────── home gate (open screen only after Home is shown) ─────────

  static void homeOpened() {
    _homeReady = true;
    WidgetsBinding.instance.addPostFrameCallback((_) => _dispatchPending());
  }

  static void homeClosed() => _homeReady = false;

  // ───────── messages ─────────

  static void _onTap(RemoteMessage m) {
    _pendingScreen = (m.data['screen'] ?? PushScreens.notifications).toString();
    if (_homeReady) _dispatchPending();
    // else: HomeScreen calls homeOpened() → dispatches (e.g. after login/splash).
  }

  static void _dispatchPending() {
    final s = _pendingScreen;
    if (s == null) return;
    _pendingScreen = null;
    _open(s);
  }

  /// App is open: no system banner → show in-app snackbar with OPEN.
  static void _onForeground(RemoteMessage m) {
    final n = m.notification;
    if (n == null) return;
    final screen = (m.data['screen'] ?? PushScreens.notifications).toString();
    final ms = rootMessengerKey.currentState;
    if (ms == null) return;
    ms
      ..hideCurrentSnackBar()
      ..showSnackBar(SnackBar(
        behavior: SnackBarBehavior.floating,
        duration: const Duration(seconds: 6),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if ((n.title ?? '').isNotEmpty)
              Text(n.title!,
                  style: const TextStyle(fontWeight: FontWeight.w700)),
            if ((n.body ?? '').isNotEmpty)
              Text(n.body!, maxLines: 2, overflow: TextOverflow.ellipsis),
          ],
        ),
        action: SnackBarAction(label: 'OPEN', onPressed: () => _open(screen)),
      ));
  }

  static void _open(String screen) {
    final nav = rootNavigatorKey.currentState;
    if (nav == null) return;
    nav.popUntil((r) => r.isFirst); // back to Home, then push target
    switch (screen) {
      case PushScreens.home:
        break;
      case PushScreens.privacy:
        nav.push(MaterialPageRoute(builder: (_) => const PrivacyPolicyScreen()));
        break;
      case PushScreens.notifications:
      default:
        nav.push(MaterialPageRoute(builder: (_) => const NotificationsScreen()));
    }
  }

  /// Used by the in-app notification list when a row is tapped.
  static void openScreen(String? screen) => _open(screen ?? PushScreens.notifications);
}