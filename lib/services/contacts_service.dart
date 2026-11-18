import 'package:flutter_contacts/flutter_contacts.dart';
import '../models/contact_entry.dart';

class ContactsService {
  /// Saves [entry] into the phone's contacts. Returns true on success.
  /// Returns false if permission is denied or saving fails (scan flow is never blocked).
  static Future<bool> saveToPhone(ContactEntry entry) async {
    try {
      final granted = await FlutterContacts.requestPermission();
      if (!granted) return false;

      final name = (entry.name?.trim().isNotEmpty ?? false)
          ? entry.name!.trim()
          : entry.display;

      final contact = Contact()
        ..name.first = name
        ..phones = [Phone(entry.display)]
        ..emails = (entry.email?.trim().isNotEmpty ?? false)
            ? [Email(entry.email!.trim())]
            : [];

      await contact.insert();
      return true;
    } catch (_) {
      return false;
    }
  }

  /// Saves many entries in one go (asks permission once).
  /// Returns (saved, failed). Throws [StateError] if permission denied.
  static Future<(List<ContactEntry> saved, int failed)> saveAllToPhone(
      List<ContactEntry> entries) async {
    final granted = await FlutterContacts.requestPermission();
    if (!granted) throw StateError('permission_denied');

    final saved = <ContactEntry>[];
    var failed = 0;
    for (final entry in entries) {
      try {
        final name = (entry.name?.trim().isNotEmpty ?? false)
            ? entry.name!.trim()
            : entry.display;
        final contact = Contact()
          ..name.first = name
          ..phones = [Phone(entry.display)]
          ..emails = (entry.email?.trim().isNotEmpty ?? false)
              ? [Email(entry.email!.trim())]
              : [];
        await contact.insert();
        saved.add(entry);
      } catch (_) {
        failed++;
      }
    }
    return (saved, failed);
  }
}