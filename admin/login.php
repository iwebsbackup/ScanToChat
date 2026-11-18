<?php
// Admin login: Username + Password
require __DIR__ . '/includes/auth.php';

if (is_logged_in()) redirect('dashboard.php');

$fatal = null;
$admin = null;
try {
    $admin = fb()->getDoc('admin_panel/admin');
    if ($admin === null) redirect('register.php'); // no admin yet → first-time setup
} catch (FirebaseException $e) {
    $fatal = $e->getMessage();
}

if (!$fatal && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    // Basic brute-force slow-down (per session)
    $_SESSION['fails'] = $_SESSION['fails'] ?? 0;
    $_SESSION['lock_until'] = $_SESSION['lock_until'] ?? 0;
    if (time() < $_SESSION['lock_until']) {
        flash('danger', 'Too many attempts. Try again in a few minutes.');
        redirect('login.php');
    }

    $username = strtolower(trim($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    $ok = hash_equals((string)$admin['username'], $username)
        && password_verify($password, (string)$admin['passwordHash']);

    if ($ok) {
        session_regenerate_id(true);
        $_SESSION['admin'] = ['name' => $admin['name'], 'username' => $admin['username']];
        $_SESSION['fails'] = 0;
        if (password_needs_rehash($admin['passwordHash'], PASSWORD_DEFAULT)) {
            try { fb()->setDoc('admin_panel/admin', ['passwordHash' => password_hash($password, PASSWORD_DEFAULT)]); }
            catch (Throwable $e) {}
        }
        // Backfill: admin registered before Auth mirror existed → create Auth user now.
        if (empty($admin['authUid']) && !empty($admin['email'])) {
            $authUid = sync_admin_auth((string)$admin['email'], $password, (string)$admin['name']);
            if ($authUid) {
                try { fb()->setDoc('admin_panel/admin', ['authUid' => $authUid]); } catch (Throwable $e) {}
            } else {
                flash('warning', 'Firebase Auth user not created: ' . ($_SESSION['auth_sync_error'] ?? 'unknown error'));
            }
        }
        redirect('dashboard.php');
    }

    usleep(600000);
    $_SESSION['fails']++;
    if ($_SESSION['fails'] >= 5) {
        $_SESSION['lock_until'] = time() + 300;
        $_SESSION['fails'] = 0;
    }
    flash('danger', 'Wrong username or password.');
    redirect('login.php');
}

$pageTitle = 'Admin Login';
require __DIR__ . '/includes/guest_header.php';
?>
<div class="card guest-card">
  <div class="card-body p-4 p-sm-5">
    <div class="text-center mb-4">
      <div class="guest-logo"><i class="bi bi-qr-code-scan"></i></div>
      <h4 class="fw-bold mb-1"><?= e(APP_NAME) ?> Admin</h4>
      <p class="text-muted small mb-0">Sign in to manage your app.</p>
    </div>

    <?php if ($fatal): ?>
      <script>document.addEventListener('DOMContentLoaded', function(){ snack('danger', <?= json_encode('Firebase connection failed: ' . $fatal, JSON_HEX_TAG | JSON_HEX_QUOT | JSON_HEX_APOS | JSON_HEX_AMP) ?>, {duration: 9000}); });</script>
      <div class="text-center text-muted small">Cannot reach Firebase. Check config and try again.</div>
    <?php else: ?>
    <form method="post" data-lock>
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label">Username</label>
        <div class="input-icon">
          <i class="bi bi-person"></i>
          <input type="text" name="username" class="form-control" required autofocus autocomplete="username" autocapitalize="none">
        </div>
      </div>
      <div class="mb-4">
        <label class="form-label">Password</label>
        <div class="input-icon has-toggle">
          <i class="bi bi-lock"></i>
          <input type="password" name="password" class="form-control" required autocomplete="current-password">
          <button type="button" class="pw-toggle" aria-label="Show password"><i class="bi bi-eye"></i></button>
        </div>
      </div>
      <button class="btn btn-brand w-100 py-2" type="submit">Sign in</button>
    </form>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/includes/guest_footer.php';
