// ─────────────────────────────────────────────────────────────────────────────
// OTA UpdateChecker — temporarily disabled.
// To re-enable: uncomment the import block and the _UpdateDialog class below,
// and restore the _run() body in _UpdateCheckerState.
// ─────────────────────────────────────────────────────────────────────────────

// import 'dart:io';
// import '../services/update_service.dart';

import 'package:flutter/material.dart';

/// Wrapper widget that checks for OTA updates once per app run.
/// Currently disabled — passes child through without any update logic.
class UpdateChecker extends StatefulWidget {
  final Widget child;
  const UpdateChecker({super.key, required this.child});

  @override
  State<UpdateChecker> createState() => _UpdateCheckerState();
}

class _UpdateCheckerState extends State<UpdateChecker> {
  // static bool _checked = false; // OTA disabled

  @override
  void initState() {
    super.initState();
    // OTA update check disabled temporarily.
    // if (_checked) return;
    // _checked = true;
    // WidgetsBinding.instance.addPostFrameCallback((_) => _run());
  }

  // Future<void> _run() async {
  //   try {
  //     final info = await UpdateService.check();
  //     if (info == null || !mounted) return;
  //     await showDialog<void>(
  //       context: context,
  //       barrierDismissible: false,
  //       builder: (_) => _UpdateDialog(info: info),
  //     );
  //   } catch (_) {
  //     // Silent: update check must never break the app.
  //   }
  // }

  @override
  Widget build(BuildContext context) => widget.child;
}

// ─── _UpdateDialog (disabled along with OTA) ─────────────────────────────────
//
// enum _Phase { idle, downloading, ready, error }
//
// class _UpdateDialog extends StatefulWidget {
//   final UpdateInfo info;
//   const _UpdateDialog({required this.info});
//
//   @override
//   State<_UpdateDialog> createState() => _UpdateDialogState();
// }
//
// class _UpdateDialogState extends State<_UpdateDialog> {
//   _Phase _phase = _Phase.idle;
//   double _progress = 0;
//   String? _error;
//   File? _apk;
//   DownloadCancel? _cancel;
//
//   bool get _force => widget.info.force;
//   bool get _busy => _phase == _Phase.downloading;
//
//   @override
//   void dispose() {
//     _cancel?.cancel();
//     super.dispose();
//   }
//
//   Future<void> _start() async {
//     final cancel = DownloadCancel();
//     _cancel = cancel;
//     setState(() {
//       _phase = _Phase.downloading;
//       _progress = 0;
//       _error = null;
//     });
//     try {
//       final f = await UpdateService.download(
//         widget.info,
//         cancel: cancel,
//         onProgress: (p) {
//           if (mounted) setState(() => _progress = p);
//         },
//       );
//       if (!mounted) return;
//       _apk = f;
//       setState(() => _phase = _Phase.ready);
//       await _install();
//     } on UpdateCancelled {
//       if (mounted) setState(() => _phase = _Phase.idle);
//     } catch (e) {
//       if (!mounted) return;
//       setState(() {
//         _phase = _Phase.error;
//         _error = 'Download failed. Check internet and retry.';
//       });
//     }
//   }
//
//   Future<void> _install() async {
//     final f = _apk;
//     if (f == null) return;
//     final err = await UpdateService.install(f);
//     if (!mounted) return;
//     setState(() {
//       _phase = _Phase.ready;
//       _error = err;
//     });
//   }
//
//   @override
//   Widget build(BuildContext context) {
//     final info = widget.info;
//     final pct = _progress >= 0 ? (_progress * 100).clamp(0, 100).toStringAsFixed(0) : null;
//
//     return PopScope(
//       canPop: !_force && !_busy,
//       child: AlertDialog(
//         title: Text(_force ? 'Update required' : 'Update available'),
//         content: SingleChildScrollView(
//           child: Column(
//             mainAxisSize: MainAxisSize.min,
//             crossAxisAlignment: CrossAxisAlignment.start,
//             children: [
//               if (info.versionName.isNotEmpty)
//                 Text('Version ${info.versionName}',
//                     style: const TextStyle(fontWeight: FontWeight.w600)),
//               if (info.notes.isNotEmpty) ...[
//                 const SizedBox(height: 8),
//                 Text(info.notes),
//               ],
//               if (_busy) ...[
//                 const SizedBox(height: 16),
//                 LinearProgressIndicator(value: _progress >= 0 ? _progress : null),
//                 const SizedBox(height: 6),
//                 Text(pct == null ? 'Downloading…' : 'Downloading… $pct%'),
//               ],
//               if (_phase == _Phase.ready && _error == null) ...[
//                 const SizedBox(height: 16),
//                 const Text('Download complete. Follow installer prompt.'),
//               ],
//               if (_error != null) ...[
//                 const SizedBox(height: 12),
//                 Text(_error!,
//                     style: TextStyle(color: Theme.of(context).colorScheme.error)),
//               ],
//             ],
//           ),
//         ),
//         actions: [
//           if (!_force && !_busy)
//             TextButton(
//               onPressed: () => Navigator.pop(context),
//               child: const Text('Later'),
//             ),
//           if (_busy)
//             TextButton(
//               onPressed: () => _cancel?.cancel(),
//               child: const Text('Cancel'),
//             )
//           else
//             FilledButton(
//               onPressed: _phase == _Phase.ready ? _install : _start,
//               child: Text(switch (_phase) {
//                 _Phase.ready => 'Install',
//                 _Phase.error => 'Retry',
//                 _ => 'Update now',
//               }),
//             ),
//         ],
//       ),
//     );
//   }
// }
