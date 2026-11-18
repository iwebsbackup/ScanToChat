import 'package:flutter/material.dart';
import '../theme/app_theme.dart';
import '../widgets/light_app_bar.dart';

/// Put your support email here. Shown in the "Contact" section when not empty.
const String kSupportEmail = '';

const String _lastUpdated = 'November 2026';

class PrivacyPolicyScreen extends StatelessWidget {
  const PrivacyPolicyScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: lightAppBar('Privacy Policy'),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(18, 16, 18, 32),
        children: [
          const Text('Scan2Chat Privacy Policy',
              style: TextStyle(
                  fontSize: 22,
                  fontWeight: FontWeight.w800,
                  color: AppColors.black)),
          const SizedBox(height: 4),
          const Text('Last updated: $_lastUpdated',
              style: TextStyle(color: Colors.black45, fontSize: 12.5)),
          const SizedBox(height: 14),
          const _P(
              'Scan2Chat lets you scan a visiting card, save the contact and start a WhatsApp chat. '
              'This page explains what data the app uses and why.'),
          const _H('1. Information we collect'),
          const _B([
            'Google account: name, email address and profile photo, when you sign in with Google.',
            'Scanned card data: name, phone numbers and email read from the card, and (optionally) a photo of the card.',
            'Usage data: number of scans and last login / last scan time.',
            'Device notification token (FCM) so we can send you notifications.',
          ]),
          const _H('2. Permissions we ask for'),
          const _B([
            'Camera: to scan visiting cards.',
            'Contacts: to save scanned numbers to your phone when you choose to.',
            'Notifications: to show messages sent by the Scan2Chat team.',
            'Install unknown apps: only to install app updates sent by us.',
          ]),
          const _H('3. How we use your information'),
          const _B([
            'To sign you in and keep your account secure.',
            'To read text from cards and save contacts you scan.',
            'To open WhatsApp chats with numbers you scan.',
            'To send you app updates and notifications.',
            'To improve the app and prevent misuse.',
          ]),
          const _H('4. Where your data is stored'),
          const _B([
            'Your scan history list is kept on your phone. Clearing history in the app removes it from the phone.',
            'Account details, scan records and notification tokens are stored in Google Firebase (Authentication, Realtime Database, Cloud Messaging).',
            'If a card photo is attached to a scan, it is uploaded to ImgBB and only the image link is stored with the scan.',
            'Text recognition on the card runs on your device (Google ML Kit).',
          ]),
          const _H('5. Sharing'),
          const _P(
              'We do not sell your data. Data is processed only by the services listed above '
              '(Google Firebase, Google ML Kit, ImgBB) to run the app. '
              'Numbers you scan are opened in WhatsApp only when you tap the chat button.'),
          const _H('6. Notifications'),
          const _P(
              'We may send notifications to all users or to selected users. '
              'You can turn notifications off anytime from your phone settings. '
              'On logout, your device stops receiving notifications meant for your account.'),
          const _H('7. Your choices'),
          const _B([
            'Clear history from the menu to remove scans stored on this phone.',
            'Logout anytime from the menu.',
            'Revoke camera, contacts or notification permission from phone settings.',
            'Ask us to delete your account and stored data using the contact below.',
          ]),
          const _H('8. Security'),
          const _P(
              'Data is sent over secure (HTTPS) connections and stored with Google Firebase. '
              'No method of storage is 100% secure, but we take reasonable steps to protect your data.'),
          const _H('9. Children'),
          const _P('Scan2Chat is not intended for children under 13.'),
          const _H('10. Changes to this policy'),
          const _P(
              'We may update this policy from time to time. The latest version is always available in the app menu.'),
          const _H('11. Contact'),
          _P(kSupportEmail.isEmpty
              ? 'For any privacy question or data deletion request, contact the Scan2Chat admin.'
              : 'For any privacy question or data deletion request, email $kSupportEmail.'),
        ],
      ),
    );
  }
}

class _H extends StatelessWidget {
  final String text;
  const _H(this.text);
  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.only(top: 20, bottom: 6),
        child: Text(text,
            style: const TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w700,
                color: AppColors.red)),
      );
}

class _P extends StatelessWidget {
  final String text;
  const _P(this.text);
  @override
  Widget build(BuildContext context) => Text(text,
      style: const TextStyle(fontSize: 14.5, height: 1.5, color: Colors.black87));
}

class _B extends StatelessWidget {
  final List<String> items;
  const _B(this.items);
  @override
  Widget build(BuildContext context) => Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          for (final t in items)
            Padding(
              padding: const EdgeInsets.only(bottom: 6),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Padding(
                    padding: EdgeInsets.only(top: 7, right: 10),
                    child: Icon(Icons.circle, size: 5, color: AppColors.black),
                  ),
                  Expanded(
                    child: Text(t,
                        style: const TextStyle(
                            fontSize: 14.5, height: 1.5, color: Colors.black87)),
                  ),
                ],
              ),
            ),
        ],
      );
} 