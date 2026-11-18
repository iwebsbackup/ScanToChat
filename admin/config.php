<?php
// ───────── EDIT THIS FILE ─────────
define('APP_NAME', 'Scan2Chat');
define('FIREBASE_PROJECT_ID', 'scan2chat-e7e20');

// Realtime Database URL. Firebase Console → Build → Realtime Database → copy URL shown at top.
// Default (US) looks like: https://<project-id>-default-rtdb.firebaseio.com
// Other regions look like: https://<project-id>-default-rtdb.<region>.firebasedatabase.app
define('FIREBASE_DB_URL', 'https://scan2chat-e7e20-default-rtdb.asia-southeast1.firebasedatabase.app');

// Service account JSON. First existing path wins.
// BEST: keep it OUTSIDE public_html (first path). Fallback: admin/storage/ (protected by .htaccess).
$__saCandidates = [
    '/var/www/2ce8c9cf-b099-4134-a14b-0f403da054b1/Scan2Chat/service-account.json',
    '/var/www/2ce8c9cf-b099-4134-a14b-0f403da054b1/service-account.json',
    __DIR__ . '/storage/service-account.json',
];
$__saFound = null;
foreach ($__saCandidates as $__p) {
    if (is_file($__p)) { $__saFound = $__p; break; }
}
define('SERVICE_ACCOUNT_PATH', $__saFound ?? implode(' | ', $__saCandidates));
define('STORAGE_DIR', __DIR__ . '/storage');

// Push topic. Must match PushService in Flutter app ("all").
define('FCM_TOPIC', 'all');

date_default_timezone_set('Asia/Kolkata');