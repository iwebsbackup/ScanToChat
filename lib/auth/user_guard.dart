import 'package:firebase_database/firebase_database.dart';
import 'package:flutter/material.dart';
import '../services/auth_service.dart';
import '../services/db.dart';

/// Live-watches users/{uid}/disabled. If admin blocks user → blocked screen instantly.
class UserGuard extends StatelessWidget {
  final String uid;
  final Widget child;
  const UserGuard({super.key, required this.uid, required this.child});

  @override
  Widget build(BuildContext context) {
    return StreamBuilder<DatabaseEvent>(
      stream: Db.ref('users/$uid/disabled').onValue,
      builder: (context, snap) {
        final blocked = snap.data?.snapshot.value == true;
        if (!blocked) return child;
        return Scaffold(
          body: Center(
            child: Padding(
              padding: const EdgeInsets.all(24),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Icon(Icons.block, size: 64, color: Colors.red),
                  const SizedBox(height: 16),
                  const Text('Your account is blocked by admin.',
                      textAlign: TextAlign.center,
                      style: TextStyle(fontSize: 18, fontWeight: FontWeight.w600)),
                  const SizedBox(height: 16),
                  OutlinedButton(
                    onPressed: AuthService.instance.signOut,
                    child: const Text('Sign out'),
                  ),
                ],
              ),
            ),
          ),
        );
      },
    );
  }
}
