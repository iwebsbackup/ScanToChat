// ─────────────────────────────────────────────────────────────────────────────
// OTA UPDATE SERVICE — temporarily disabled.
// To re-enable: uncomment the entire file body below and remove this block.
// ─────────────────────────────────────────────────────────────────────────────

// import 'dart:async';
// import 'dart:io';
//
// import 'package:open_filex/open_filex.dart';
// import 'package:package_info_plus/package_info_plus.dart';
// import 'package:path_provider/path_provider.dart';
// import 'db.dart';

class UpdateInfo {
  final int versionCode;
  final String versionName;
  final String url;
  final String notes;
  final bool force;
  const UpdateInfo({
    required this.versionCode,
    required this.versionName,
    required this.url,
    required this.notes,
    required this.force,
  });
}

class UpdateCancelled implements Exception {
  const UpdateCancelled();
}

/// Lets UI abort a running download.
class DownloadCancel {
  bool _c = false;
  bool get cancelled => _c;
  void cancel() => _c = true;
}

class UpdateService {
  // static const _apkMime = 'application/vnd.android.package-archive';

  // static int _asInt(dynamic v) {
  //   if (v is int) return v;
  //   if (v is num) return v.toInt();
  //   return int.tryParse(v?.toString() ?? '') ?? 0;
  // }

  /// OTA update check — disabled temporarily.
  /// To re-enable: restore the full implementation from git history or admin panel.
  static Future<UpdateInfo?> check() async {
    return null; // OTA disabled
  }

  // static Future<File> _target(UpdateInfo info) async {
  //   final dir = await getTemporaryDirectory();
  //   return File('${dir.path}/scan2chat-update-${info.versionCode}.apk');
  // }

  /// Downloads APK — disabled temporarily.
  static Future<dynamic> download(
    UpdateInfo info, {
    required void Function(double progress) onProgress,
    DownloadCancel? cancel,
  }) async {
    throw UnimplementedError('OTA download is currently disabled.');
  }

  /// Installs APK — disabled temporarily.
  static Future<String?> install(dynamic apk) async {
    return 'OTA install is currently disabled.';
  }
}