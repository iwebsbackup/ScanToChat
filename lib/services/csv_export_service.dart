import 'dart:io';
import 'package:path_provider/path_provider.dart';
import 'package:share_plus/share_plus.dart';
import '../models/contact_entry.dart';

class CsvExportService {
  static String _esc(String? v) {
    final s = v ?? '';
    if (s.contains(',') || s.contains('"') || s.contains('\n') || s.contains('\r')) {
      return '"${s.replaceAll('"', '""')}"';
    }
    return s;
  }

  static String buildCsv(List<ContactEntry> entries) {
    final b = StringBuffer('\uFEFF'); // BOM so Excel reads UTF-8 correctly
    b.writeln('Name,Phone,Email,Scanned At');
    for (final e in entries) {
      b.writeln([
        _esc(e.name),
        _esc(e.display),
        _esc(e.email),
        _esc(e.scannedAt.toIso8601String()),
      ].join(','));
    }
    return b.toString();
  }

  /// Writes the CSV to a temp file and opens the share sheet (save to Drive/Files, WhatsApp, etc).
  static Future<void> exportAndShare(List<ContactEntry> entries) async {
    final dir = await getTemporaryDirectory();
    final d = DateTime.now();
    String two(int n) => n.toString().padLeft(2, '0');
    final fileName =
        'scan2chat_contacts_${d.year}${two(d.month)}${two(d.day)}_${two(d.hour)}${two(d.minute)}.csv';
    final file = File('${dir.path}/$fileName');
    await file.writeAsString(buildCsv(entries));
    await Share.shareXFiles(
      [XFile(file.path, mimeType: 'text/csv')],
      subject: 'Scan2Chat contacts',
    );
  }
}
