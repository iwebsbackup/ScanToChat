import 'package:firebase_database/firebase_database.dart';
import 'auth_service.dart';
import 'card_parser.dart';
import 'db.dart';
import '../models/contact_entry.dart';

/// Saves every scan under  users/{uid}/scans/{scanId}  and bumps  users/{uid}/scanCount.
class ScanSyncService {
  ScanSyncService._();

  /// Writes scan data + increments count in ONE atomic update.
  /// Returns the scanId (used later to attach the photo URL), or null if not logged in.
  /// Does not wait for the server, so it never blocks the scanner when offline.
  static String? recordScan(CardInfo info, List<ContactEntry> entries) {
    final uid = AuthService.instance.currentUser?.uid;
    if (uid == null) return null;

    final root = Db.ref('users/$uid');
    final scanId = root.child('scans').push().key;
    if (scanId == null) return null;

    root.update({
      'scans/$scanId': {
        'name': info.name ?? '',
        'email': info.email ?? '',
        'numbers': entries.map((e) => e.display).toList(),
        'waNumbers': entries.map((e) => e.number).toList(),
        'photoUrl': '',
        'scannedAt': ServerValue.timestamp,
      },
      'scanCount': ServerValue.increment(1),
      'lastScanAt': ServerValue.timestamp,
    }).catchError((_) {});

    return scanId;
  }

  static Future<void> attachPhoto(String scanId, String url) async {
    final uid = AuthService.instance.currentUser?.uid;
    if (uid == null) return;
    try {
      await Db.ref('users/$uid/scans/$scanId/photoUrl').set(url);
    } catch (_) {}
  }
}
