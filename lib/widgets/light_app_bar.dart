import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:hugeicons/hugeicons.dart';
import '../theme/app_theme.dart';

/// White app bar (same look as Home screen) for secondary screens.
/// Back icon is drawn explicitly: the global theme sets a white iconTheme,
/// which made the default back arrow invisible on a white bar.
PreferredSizeWidget lightAppBar(String title) {
  return AppBar(
    title: Text(title),
    backgroundColor: Colors.white,
    foregroundColor: AppColors.black,
    surfaceTintColor: Colors.transparent,
    scrolledUnderElevation: 0,
    elevation: 0,
    iconTheme: const IconThemeData(color: AppColors.black),
    actionsIconTheme: const IconThemeData(color: AppColors.black),
    systemOverlayStyle: SystemUiOverlayStyle.dark,
    automaticallyImplyLeading: false,
    leading: Builder(
      builder: (context) => Navigator.of(context).canPop()
          ? IconButton(
              tooltip: 'Back',
              icon: HugeIcon(
                icon: HugeIcons.strokeRoundedArrowLeft02,
                size: 24.0,
                color: AppColors.black,
                strokeWidth: 1.5,
              ),
              onPressed: () => Navigator.of(context).maybePop(),
            )
          : const SizedBox.shrink(),
    ),
    titleTextStyle: const TextStyle(
      color: AppColors.black,
      fontSize: 20,
      fontWeight: FontWeight.w700,
    ),
    bottom: PreferredSize(
      preferredSize: const Size.fromHeight(1),
      child: Container(height: 1, color: const Color(0xFFEAE6E6)),
    ),
  );
}