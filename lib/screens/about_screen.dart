import 'package:flutter/material.dart';
import 'package:package_info_plus/package_info_plus.dart';
import '../theme/app_theme.dart';
import '../widgets/light_app_bar.dart';
import 'privacy_policy_screen.dart';

const String kDeveloper = 'Proximatix';

class AboutScreen extends StatefulWidget {
  const AboutScreen({super.key});

  @override
  State<AboutScreen> createState() => _AboutScreenState();
}

class _AboutScreenState extends State<AboutScreen> {
  String _version = '';
  String _build = '';

  @override
  void initState() {
    super.initState();
    _loadInfo();
  }

  Future<void> _loadInfo() async {
    try {
      final info = await PackageInfo.fromPlatform();
      if (!mounted) return;
      setState(() {
        _version = info.version;
        _build = info.buildNumber;
      });
    } catch (_) {}
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: lightAppBar('About'),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(16, 24, 16, 32),
        children: [
          // ── Logo + name ──
          Center(
            child: Column(
              children: [
                ClipRRect(
                  borderRadius: BorderRadius.circular(22),
                  child: Image.asset(
                    'assets/images/logo.png',
                    width: 88,
                    height: 88,
                    fit: BoxFit.cover,
                    errorBuilder: (_, __, ___) => const Icon(
                        Icons.document_scanner_outlined,
                        size: 72,
                        color: AppColors.red),
                  ),
                ),
                const SizedBox(height: 14),
                const Text('Scan2Chat',
                    style: TextStyle(
                        fontSize: 24,
                        fontWeight: FontWeight.w800,
                        color: AppColors.black)),
                const SizedBox(height: 4),
                Text(
                  _version.isEmpty ? 'Version —' : 'Version $_version ($_build)',
                  style: const TextStyle(color: Colors.black54, fontSize: 13),
                ),
                const SizedBox(height: 12),
                const Padding(
                  padding: EdgeInsets.symmetric(horizontal: 12),
                  child: Text(
                    'Scan a visiting card, save the contact and chat on WhatsApp instantly.',
                    textAlign: TextAlign.center,
                    style: TextStyle(height: 1.4, color: Colors.black87),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 26),

          // ── Features ──
          const _Section('What you can do'),
          const _Card(children: [
            _Feature(Icons.document_scanner_outlined, 'Live card scanning',
                'Point the camera at a card. Name, phone numbers and email are picked up automatically.'),
            _Feature(Icons.chat_outlined, 'One-tap WhatsApp chat',
                'Start a WhatsApp chat with a scanned number without saving it first.'),
            _Feature(Icons.save_alt, 'Save to phone contacts',
                'Save one contact or all scanned contacts to your phone in one tap.'),
            _Feature(Icons.file_download_outlined, 'Export to CSV',
                'Download all scanned contacts as a CSV file and share it anywhere.'),
            _Feature(Icons.notifications_none, 'Updates & notifications',
                'Get in-app updates and messages from the Scan2Chat team.'),
          ]),
          const SizedBox(height: 22),

          // ── App info ──
          const _Section('App info'),
          _Card(children: [
            _InfoRow('Version', _version.isEmpty ? '—' : _version),
            _InfoRow('Build number', _build.isEmpty ? '—' : _build),
            const _InfoRow('Developed by', kDeveloper),
          ]),
          const SizedBox(height: 22),

          // ── Links ──
          const _Section('Legal'),
          _Card(children: [
            ListTile(
              leading: const Icon(Icons.privacy_tip_outlined,
                  color: AppColors.black, size: 22),
              title: const Text('Privacy Policy',
                  style: TextStyle(fontWeight: FontWeight.w500)),
              trailing: const Icon(Icons.chevron_right, color: Colors.black26),
              onTap: () => Navigator.push(
                context,
                MaterialPageRoute(builder: (_) => const PrivacyPolicyScreen()),
              ),
            ),
          ]),
          const SizedBox(height: 28),
          Center(
            child: Text(
              '\u00a9 ${DateTime.now().year} $kDeveloper. All rights reserved.',
              style: const TextStyle(color: Colors.black45, fontSize: 12),
            ),
          ),
        ],
      ),
    );
  }
}

class _Section extends StatelessWidget {
  final String text;
  const _Section(this.text);
  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.only(left: 4, bottom: 8),
        child: Text(
          text.toUpperCase(),
          style: const TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w700,
              letterSpacing: 0.8,
              color: Colors.black45),
        ),
      );
}

class _Card extends StatelessWidget {
  final List<Widget> children;
  const _Card({required this.children});
  @override
  Widget build(BuildContext context) => Container(
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(14),
        ),
        child: Column(
          children: [
            for (var i = 0; i < children.length; i++) ...[
              if (i > 0) const Divider(height: 1, indent: 16, endIndent: 16),
              children[i],
            ],
          ],
        ),
      );
}

class _Feature extends StatelessWidget {
  final IconData icon;
  final String title;
  final String text;
  const _Feature(this.icon, this.title, this.text);
  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.all(14),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              width: 38,
              height: 38,
              decoration: BoxDecoration(
                color: AppColors.red.withValues(alpha: 0.10),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Icon(icon, color: AppColors.red, size: 20),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(title,
                      style: const TextStyle(
                          fontWeight: FontWeight.w700,
                          fontSize: 14.5,
                          color: AppColors.black)),
                  const SizedBox(height: 3),
                  Text(text,
                      style: const TextStyle(
                          color: Colors.black54, height: 1.35, fontSize: 13)),
                ],
              ),
            ),
          ],
        ),
      );
}

class _InfoRow extends StatelessWidget {
  final String label;
  final String value;
  const _InfoRow(this.label, this.value);
  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
        child: Row(
          children: [
            Text(label, style: const TextStyle(color: Colors.black54)),
            const Spacer(),
            Text(value,
                style: const TextStyle(
                    fontWeight: FontWeight.w600, color: AppColors.black)),
          ],
        ),
      );
}