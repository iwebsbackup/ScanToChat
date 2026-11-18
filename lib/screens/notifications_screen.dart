import 'package:firebase_database/firebase_database.dart';
import 'package:flutter/material.dart';
import '../services/auth_service.dart';
import '../services/db.dart';
import '../services/push_service.dart';
import '../theme/app_theme.dart';
import '../utils/format.dart';
import '../widgets/light_app_bar.dart';

class NotificationsScreen extends StatelessWidget {
  const NotificationsScreen({super.key});

  /// Admin can target everyone or only some users (userIds map).
  static bool _isForMe(Map<String, dynamic> m, String? uid) {
    final target = (m['target'] ?? 'all').toString();
    if (target != 'users') return true;
    final ids = m['userIds'];
    return uid != null && ids is Map && ids[uid] == true;
  }

  @override
  Widget build(BuildContext context) {
    final uid = AuthService.instance.currentUser?.uid;
    return Scaffold(
      appBar: lightAppBar('Notifications'),
      body: StreamBuilder<DatabaseEvent>(
        stream:
            Db.ref('notifications').orderByChild('createdAt').limitToLast(100).onValue,
        builder: (context, snap) {
          if (snap.hasError) {
            return const _Empty(
                icon: Icons.cloud_off_outlined, text: 'Could not load notifications');
          }
          if (!snap.hasData) return const Center(child: CircularProgressIndicator());

          // RTDB returns ascending by createdAt → reverse for newest first.
          final items = snap.data!.snapshot.children
              .map((c) {
                final v = c.value;
                if (v is! Map) return null;
                return Map<String, dynamic>.from(v)..['id'] = c.key;
              })
              .whereType<Map<String, dynamic>>()
              .where((m) => _isForMe(m, uid))
              .toList()
              .reversed
              .toList();

          if (items.isEmpty) {
            return const _Empty(
                icon: Icons.notifications_none, text: 'No notifications yet');
          }
          return ListView.separated(
            padding: const EdgeInsets.fromLTRB(14, 14, 14, 24),
            itemCount: items.length,
            separatorBuilder: (_, __) => const SizedBox(height: 10),
            itemBuilder: (_, i) => _NoteTile(m: items[i]),
          );
        },
      ),
    );
  }
}

class _NoteTile extends StatelessWidget {
  final Map<String, dynamic> m;
  const _NoteTile({required this.m});

  @override
  Widget build(BuildContext context) {
    final screen = (m['screen'] ?? '').toString();
    final opensOther = screen == PushScreens.privacy;
    return Material(
      color: Colors.white,
      borderRadius: BorderRadius.circular(14),
      child: InkWell(
        borderRadius: BorderRadius.circular(14),
        onTap: opensOther ? () => PushService.openScreen(screen) : null,
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 40,
                height: 40,
                decoration: BoxDecoration(
                  color: AppColors.red.withValues(alpha: 0.10),
                  shape: BoxShape.circle,
                ),
                child: const Icon(Icons.notifications_active_outlined,
                    color: AppColors.red, size: 22),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      (m['title'] ?? '').toString(),
                      style: const TextStyle(
                          fontWeight: FontWeight.w700,
                          fontSize: 15,
                          color: AppColors.black),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      (m['body'] ?? '').toString(),
                      style: const TextStyle(color: Colors.black87, height: 1.35),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      fmtDate(m['createdAt']),
                      style: const TextStyle(color: Colors.black45, fontSize: 12),
                    ),
                  ],
                ),
              ),
              if (opensOther)
                const Padding(
                  padding: EdgeInsets.only(left: 6, top: 10),
                  child: Icon(Icons.chevron_right, color: Colors.black26),
                ),
            ],
          ),
        ),
      ),
    );
  }
}

class _Empty extends StatelessWidget {
  final IconData icon;
  final String text;
  const _Empty({required this.icon, required this.text});

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 56, color: Colors.black26),
          const SizedBox(height: 10),
          Text(text, style: const TextStyle(color: Colors.black54)),
        ],
      ),
    );
  }
}