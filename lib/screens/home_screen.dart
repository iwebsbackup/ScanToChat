import 'dart:async';
import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:hugeicons/hugeicons.dart';
import '../services/push_service.dart';
import '../services/auth_service.dart';
import '../models/contact_entry.dart';
import '../services/card_parser.dart';
import '../services/contacts_service.dart';
import '../services/csv_export_service.dart';
import '../services/imgbb_service.dart';
import '../services/phone_extractor.dart';
import '../services/scan_sync_service.dart';
import '../services/storage_service.dart';
import '../services/whatsapp_service.dart';
import '../theme/app_theme.dart';
import '../widgets/app_drawer.dart';
import '../widgets/edit_entry_dialog.dart';
import '../widgets/entry_tile.dart';
import '../widgets/live_scanner.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> with WidgetsBindingObserver {
  /// Ignore the same number for this long (prevents a loop when returning from WhatsApp).
  static const _cooldown = Duration(seconds: 20);

  List<ContactEntry> _entries = [];
  final Map<String, DateTime> _recent = {};
  bool _handling = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _loadEntries();
    final uid = AuthService.instance.currentUser?.uid;
    if (uid != null) PushService.registerUser(uid);
    PushService.homeOpened();
  }

  @override
  void dispose() {
    PushService.homeClosed();
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    // Coming back from WhatsApp: refresh the list so the new entry shows up.
    if (state == AppLifecycleState.resumed) _loadEntries();
  }

  Future<void> _loadEntries() async {
    final entries = await StorageService.loadAll();
    if (!mounted) return;
    setState(() => _entries = entries);
  }

  /// Scanner found a card: open WhatsApp first, then save the entry.
  /// Numbers not seen in the last [_cooldown]. Pure check - changes nothing.
  List<String> _freshNumbers(CardInfo info) {
    final now = DateTime.now();
    return info.numbers
        .where((n) => _recent[n] == null || now.difference(_recent[n]!) > _cooldown)
        .toList();
  }

  bool _canCapture(CardInfo info) => !_handling && _freshNumbers(info).isNotEmpty;

  /// Upload card photo to imgbb, then attach URL to users/{uid}/scans/{scanId}.
  Future<void> _uploadPhoto(File photo, String scanId) async {
    try {
      final url = await ImgbbService.upload(photo);
      if (url != null) await ScanSyncService.attachPhoto(scanId, url);
    } finally {
      try {
        await photo.delete();
      } catch (_) {}
    }
  }

  Future<void> _onDetected(CardInfo info, File? photo) async {
    if (_handling) return;

    final now = DateTime.now();
    _recent.removeWhere((_, t) => now.difference(t) > _cooldown);
    final fresh = _freshNumbers(info);
    if (fresh.isEmpty) return;

    _handling = true;
    try {
      for (final n in fresh) {
        _recent[n] = now;
      }

      final newEntries = fresh
          .map((n) => ContactEntry(
                id: '${DateTime.now().microsecondsSinceEpoch}_$n',
                number: PhoneExtractor.toWhatsAppFormat(n),
                display: PhoneExtractor.toDisplayFormat(n),
                name: info.name,
                email: info.email,
                scannedAt: DateTime.now(),
              ))
          .toList();

      // 0) Cloud: save scan data + count +1 (instant, no waiting), upload photo in background.
      final scanId = ScanSyncService.recordScan(info, newEntries);
      if (photo != null) {
        if (scanId != null) {
          unawaited(_uploadPhoto(photo, scanId));
        } else {
          unawaited(photo.delete().catchError((_) => photo));
        }
      }

      // 1) Open WhatsApp first (only when exactly one number was found).
      Future<bool>? opening;
      if (newEntries.length == 1) {
        opening = WhatsAppService.openChat(newEntries.first.number);
      }

      // 2) Save right after. If the number already exists, the old entry is
      //    updated and moved to the top - no duplicate is created.
      final saved = <ContactEntry>[];
      for (final entry in newEntries) {
        var s = await StorageService.upsert(entry);
        // Save to phone contacts (only once per number).
        if (!s.savedToPhone && await ContactsService.saveToPhone(s)) {
          s = ContactEntry(
            id: s.id,
            number: s.number,
            display: s.display,
            name: s.name,
            email: s.email,
            scannedAt: s.scannedAt,
            savedToPhone: true,
          );
          await StorageService.update(s);
        }
        saved.add(s);
      }
      if (mounted) {
        setState(() {
          for (final s in saved.reversed) {
            _entries.removeWhere((e) => e.id == s.id);
            _entries.insert(0, s);
          }
        });
      }
      HapticFeedback.mediumImpact();

      if (opening != null) {
        await opening;
      } else {
        _showMessage('${newEntries.length} numbers found. Tap an entry to open WhatsApp.');
      }
    } catch (e) {
      _showMessage('Error: $e');
    } finally {
      _handling = false;
    }
  }

  Future<void> _handleAction(ContactEntry entry, EntryAction action) async {
    switch (action) {
      case EntryAction.view:
        _showViewDialog(entry);
        break;
      case EntryAction.edit:
        await _handleEdit(entry);
        break;
      case EntryAction.delete:
        await _handleDelete(entry);
        break;
    }
  }

  void _showViewDialog(ContactEntry entry) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Contact Details'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (entry.name != null) Text('Name: ${entry.name}'),
            Text('Number: ${entry.display}'),
            if (entry.email != null) Text('Email: ${entry.email}'),
            const SizedBox(height: 6),
            Text('Scanned: ${formatEntryTime(entry.scannedAt)}'),
          ],
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context), child: const Text('Close')),
        ],
      ),
    );
  }

  Future<void> _handleEdit(ContactEntry entry) async {
    final result = await showEditDialog(context, entry);
    if (result == null) return;

    final newWaNumber = PhoneExtractor.toWhatsAppFormat(result.number);
    if (await StorageService.numberExists(newWaNumber, exceptId: entry.id)) {
      _showMessage('This number is already saved in another entry.');
      return;
    }

    final updated = ContactEntry(
      id: entry.id,
      number: newWaNumber,
      display: PhoneExtractor.toDisplayFormat(result.number),
      name: result.name,
      email: result.email,
      scannedAt: entry.scannedAt,
      savedToPhone: entry.savedToPhone,
    );
    await StorageService.update(updated);
    setState(() {
      final idx = _entries.indexWhere((e) => e.id == entry.id);
      if (idx != -1) _entries[idx] = updated;
    });
    _showMessage('Entry updated successfully');
  }

  Future<void> _handleDelete(ContactEntry entry) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Delete Entry?'),
        content: Text('${entry.display} will be permanently deleted.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('Cancel')),
          TextButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Delete', style: TextStyle(color: AppColors.red)),
          ),
        ],
      ),
    );
    if (confirm != true) return;

    await StorageService.delete(entry.id);
    setState(() => _entries.removeWhere((e) => e.id == entry.id));
    _showMessage('Entry deleted');
  }

  void _showMessage(String msg) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));
  }

  Future<void> _exportCsv() async {
    if (_entries.isEmpty) {
      _showMessage('No contacts to export');
      return;
    }
    try {
      await CsvExportService.exportAndShare(_entries);
    } catch (e) {
      _showMessage('Export failed: $e');
    }
  }

  /// Saves every not-yet-saved scanned contact into the phone's contacts.
  Future<void> _saveAllToPhone() async {
    final pending = _entries.where((e) => !e.savedToPhone).toList();
    if (_entries.isEmpty) {
      _showMessage('No contacts to save');
      return;
    }
    if (pending.isEmpty) {
      _showMessage('All contacts already saved');
      return;
    }
    _showMessage('Saving ${pending.length} contacts...');
    try {
      final (saved, failed) = await ContactsService.saveAllToPhone(pending);
      for (final e in saved) {
        await StorageService.update(ContactEntry(
          id: e.id,
          number: e.number,
          display: e.display,
          name: e.name,
          email: e.email,
          scannedAt: e.scannedAt,
          savedToPhone: true,
        ));
      }
      await _loadEntries();
      _showMessage(failed == 0
          ? '${saved.length} contacts saved to phone'
          : '${saved.length} saved, $failed failed');
    } on StateError {
      _showMessage('Contacts permission denied');
    } catch (e) {
      _showMessage('Save failed: $e');
    }
  }

  Future<void> _clearHistory() async {
    await StorageService.clearAll();
    setState(() => _entries = []);
    _showMessage('History cleared');
  }

  @override
  Widget build(BuildContext context) {
    const iconColor = AppColors.black;
    return Scaffold(
      appBar: AppBar(
        backgroundColor: Colors.white,
        foregroundColor: AppColors.black,
        surfaceTintColor: Colors.transparent,
        scrolledUnderElevation: 0,
        elevation: 0,
        systemOverlayStyle: SystemUiOverlayStyle.dark,
        titleTextStyle: const TextStyle(
          color: AppColors.black,
          fontSize: 20,
          fontWeight: FontWeight.w700,
        ),
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(1),
          child: Container(height: 1, color: const Color(0xFFEAE6E6)),
        ),
        leading: Builder(
          builder: (ctx) => IconButton(
            tooltip: 'Menu',
            icon: HugeIcon(
              icon: HugeIcons.strokeRoundedMenu02,
              size: 24.0,
              color: iconColor,
              strokeWidth: 1.5,
            ),
            onPressed: () => Scaffold.of(ctx).openDrawer(),
          ),
        ),
        title: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Image.asset(
              'assets/images/logo.png',
              height: 30,
              errorBuilder: (_, __, ___) => const SizedBox.shrink(),
            ),
            const SizedBox(width: 10),
            const Text('Scan2Chat'),
          ],
        ),
        actions: [
          IconButton(
            tooltip: 'Save all to phone contacts',
            icon: HugeIcon(
              icon: HugeIcons.strokeRoundedSave,
              size: 24.0,
              color: iconColor,
              strokeWidth: 1.5,
            ),
            onPressed: _saveAllToPhone,
          ),
          IconButton(
            tooltip: 'Export contacts (CSV)',
            icon: HugeIcon(
              icon: HugeIcons.strokeRoundedArrowDown03,
              size: 24.0,
              color: iconColor,
              strokeWidth: 1.5,
            ),
            onPressed: _exportCsv,
          ),
        ],
      ),
      drawer: AppDrawer(
        totalScanned: _entries.length,
        onClearHistory: _clearHistory,
        onExportCsv: _exportCsv,
      ),
      body: Column(
        children: [
          // Live scanner, always running in the middle
          Expanded(
            flex: 5,
            child: Padding(
              padding: const EdgeInsets.all(12),
              child: LiveScanner(onDetected: _onDetected, canCapture: _canCapture),
            ),
          ),
          const Divider(height: 1),
          // Scanned history below
          Expanded(
            flex: 4,
            child: _entries.isEmpty
                ? const Center(
                    child: Text('No contacts scanned yet',
                        style: TextStyle(color: Colors.grey)),
                  )
                : ListView(
                    padding: const EdgeInsets.only(top: 4, bottom: 16),
                    children: [
                      const _SectionHeader(
                        text: 'Successfully Scanned',
                        icon: Icons.check_circle,
                        color: AppColors.red,
                      ),
                      ..._entries.map(
                        (entry) => EntryTile(
                          entry: entry,
                          onTap: () => WhatsAppService.openChat(entry.number),
                          onAction: (action) => _handleAction(entry, action),
                        ),
                      ),
                    ],
                  ),
          ),
        ],
      ),
    );
  }
}

class _SectionHeader extends StatelessWidget {
  final String text;
  final IconData icon;
  final Color color;

  const _SectionHeader({required this.text, required this.icon, required this.color});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 8, 16, 4),
      child: Row(
        children: [
          Icon(icon, color: color, size: 18),
          const SizedBox(width: 6),
          Text(text, style: TextStyle(fontWeight: FontWeight.w700, color: color)),
        ],
      ),
    );
  }
}