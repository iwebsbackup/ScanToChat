<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/firebase.php';

// ───────── Session ─────────
if (session_status() === PHP_SESSION_NONE) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('s2c_admin');
    session_start();
}

// ───────── Firebase singleton ─────────
function fb(): Firebase
{
    static $f = null;
    if ($f === null) $f = new Firebase(SERVICE_ACCOUNT_PATH, FIREBASE_PROJECT_ID, STORAGE_DIR, FIREBASE_DB_URL);
    return $f;
}

// ───────── Helpers ─────────
function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function redirect(string $to): never
{
    header('Location: ' . $to);
    exit;
}

function flash(string $type, string $msg): void
{
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
}

function flash_pull(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $ok = isset($_POST['csrf']) && hash_equals($_SESSION['csrf'] ?? '', (string)$_POST['csrf']);
    if (!$ok) {
        http_response_code(400);
        exit('Invalid request (CSRF). Go back and refresh.');
    }
}

function is_logged_in(): bool { return !empty($_SESSION['admin']); }

function require_login(): void
{
    if (!is_logged_in()) redirect('login.php');
}

function fmt_date($v): string
{
    if ($v === null || $v === '') return '—';
    try {
        if (is_int($v) || is_float($v) || (is_string($v) && ctype_digit($v))) {
            // RTDB timestamps = milliseconds since epoch
            $d = (new DateTime('@' . intdiv((int)$v, 1000)));
        } else {
            $d = new DateTime(preg_replace('/\.\d+/', '', (string)$v));
        }
        $d->setTimezone(new DateTimeZone(date_default_timezone_get()));
        return $d->format('d M Y, H:i');
    } catch (Throwable $e) {
        return '—';
    }
}

/**
 * Mirror admin into Firebase Authentication (email+password). Returns uid or null.
 * Never throws: Auth mirror must not break panel login/register.
 */
function sync_admin_auth(string $email, string $password, string $name): ?string
{
    try {
        try {
            $uid = fb()->authCreateUser($email, $password, $name);
        } catch (FirebaseException $e) {
            if (stripos($e->getMessage(), 'EMAIL_EXISTS') === false) throw $e;
            $uid = fb()->authFindByEmail($email) ?? '';
        }
        return $uid !== '' ? $uid : null;
    } catch (Throwable $e) {
        $_SESSION['auth_sync_error'] = $e->getMessage();
        return null;
    }
}

function valid_username(string $u): bool { return (bool)preg_match('/^[a-z0-9_]{3,20}$/', $u); }
function valid_version(string $v): bool { return (bool)preg_match('/^\d+(\.\d+){0,2}$/', $v); }