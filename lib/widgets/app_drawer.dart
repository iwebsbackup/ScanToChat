import 'package:firebase_database/firebase_database.dart';
import 'package:flutter/material.dart';
import '../screens/about_screen.dart';
import '../screens/notifications_screen.dart';
import '../screens/privacy_policy_screen.dart';
import '../services/auth_service.dart';
import '../services/db.dart';
import '../theme/app_theme.dart';

class AppDrawer extends StatelessWidget {
  final int totalScanned;
  final VoidCallback onClearHistory;
  final VoidCallback onExportCsv;

  const AppDrawer({
    super.key,
    required this.totalScanned,
    required this.onClearHistory,
    required this.onExportCsv,
  });

  Future<void> _confirm(
    BuildContext context, {
    required String title,
    required String message,
    required String action,
    required VoidCallback onYes,
  }) async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(title),
        content: Text(message),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Cancel', style: TextStyle(color: Colors.black54)),
          ),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: Text(action),
          ),
        ],
      ),
    );
    if (ok == true) onYes();
  }

  @override
  Widget build(BuildContext context) {
    final user = AuthService.instance.currentUser;
    final name = (user?.displayName ?? '').trim();
    final email = user?.email ?? '';
    final photo = user?.photoURL;
    final initial = (name.isNotEmpty ? name : email).trim();

    return Drawer(
      child: Column(
        children: [
          // ── Header ──
          Container(
            width: double.infinity,
            color: AppColors.black,
            child: SafeArea(
              bottom: false,
              child: Padding(
                padding: const EdgeInsets.fromLTRB(20, 20, 20, 18),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Container(
                          width: 64,
                          height: 64,
                          padding: const EdgeInsets.all(2.5),
                          decoration: const BoxDecoration(
                              color: AppColors.red, shape: BoxShape.circle),
                          child: CircleAvatar(
                            backgroundColor: Colors.white,
                            backgroundImage:
                                (photo != null && photo.isNotEmpty) ? NetworkImage(photo) : null,
                            child: (photo == null || photo.isEmpty)
                                ? Text(
                                    initial.isEmpty ? '?' : initial[0].toUpperCase(),
                                    style: const TextStyle(
                                        fontSize: 26,
                                        fontWeight: FontWeight.bold,
                                        color: AppColors.black),
                                  )
                                : null,
                          ),
                        ),
                        const SizedBox(width: 14),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                name.isNotEmpty ? name : 'Scan2Chat',
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: const TextStyle(
                                    color: Colors.white,
                                    fontSize: 18,
                                    fontWeight: FontWeight.bold),
                              ),
                              const SizedBox(height: 2),
                              Text(
                                email,
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: const TextStyle(color: Colors.white70, fontSize: 12.5),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 20),
                    Row(
                      children: [
                        _Stat(label: 'Contacts', value: '$totalScanned'),
                        if (user != null) ...[
                          Container(
                            width: 1,
                            height: 30,
                            margin: const EdgeInsets.symmetric(horizontal: 18),
                            color: Colors.white24,
                          ),
                          StreamBuilder<DatabaseEvent>(
                            stream: Db.ref('users/${user.uid}/scanCount').onValue,
                            builder: (_, snap) {
                              final v = snap.data?.snapshot.value;
                              return _Stat(
                                  label: 'Total scans',
                                  value: '${v is num ? v.toInt() : 0}');
                            },
                          ),
                        ],
                      ],
                    ),
                  ],
                ),
              ),
            ),
          ),
          Container(height: 3, color: AppColors.red),

          // ── Items ──
          Expanded(
            child: ListView(
              padding: const EdgeInsets.symmetric(vertical: 10),
              children: [
                _item(context, Icons.notifications_outlined, 'Notifications', () {
                  Navigator.pop(context);
                  Navigator.push(context,
                      MaterialPageRoute(builder: (_) => const NotificationsScreen()));
                }),
                _item(context, Icons.privacy_tip_outlined, 'Privacy Policy', () {
                  Navigator.pop(context);
                  Navigator.push(context,
                      MaterialPageRoute(builder: (_) => const PrivacyPolicyScreen()));
                }),
                _item(context, Icons.file_download_outlined, 'Export contacts (CSV)', () {
                  Navigator.pop(context);
                  onExportCsv();
                }),
                _item(context, Icons.delete_outline, 'Clear history', () {
                  Navigator.pop(context);
                  _confirm(
                    context,
                    title: 'Clear history?',
                    message: 'All scanned contacts on this phone will be removed.',
                    action: 'Clear',
                    onYes: onClearHistory,
                  );
                }),
                _item(context, Icons.info_outline, 'About', () {
                  Navigator.pop(context);
                  Navigator.push(context,
                      MaterialPageRoute(builder: (_) => const AboutScreen()));
                }),
              ],
            ),
          ),

          // ── Logout (pinned bottom) ──
          const Divider(height: 1),
          SafeArea(
            top: false,
            child: ListTile(
              contentPadding: const EdgeInsets.symmetric(horizontal: 22),
              leading: const Icon(Icons.logout, color: AppColors.red),
              title: const Text('Logout',
                  style: TextStyle(color: AppColors.red, fontWeight: FontWeight.w600)),
              onTap: () {
                Navigator.pop(context);
                _confirm(
                  context,
                  title: 'Logout?',
                  message: 'You will need to sign in again.',
                  action: 'Logout',
                  onYes: () => AuthService.instance.signOut(),
                );
              },
            ),
          ),
        ],
      ),
    );
  }

  Widget _item(BuildContext context, IconData icon, String title, VoidCallback onTap) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 2),
      child: ListTile(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        visualDensity: const VisualDensity(vertical: -0.5),
        leading: Icon(icon, color: AppColors.black, size: 22),
        title: Text(
          title,
          style: const TextStyle(
            color: AppColors.black,
            fontSize: 15,
            fontWeight: FontWeight.w500,
          ),
        ),
        trailing: const Icon(Icons.chevron_right, color: Colors.black26, size: 20),
        onTap: onTap,
      ),
    );
  }
}

class _Stat extends StatelessWidget {
  final String label;
  final String value;
  const _Stat({required this.label, required this.value});

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      mainAxisSize: MainAxisSize.min,
      children: [
        Text(
          value,
          style: const TextStyle(
            color: Colors.white,
            fontSize: 22,
            fontWeight: FontWeight.w700,
            height: 1.1,
          ),
        ),
        const SizedBox(height: 2),
        Text(
          label,
          style: const TextStyle(color: Colors.white60, fontSize: 12),
        ),
      ],
    );
  }
}