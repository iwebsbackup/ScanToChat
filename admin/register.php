<?php
// First-time admin registration. Works only while no admin exists.
require __DIR__ . '/includes/auth.php';

if (is_logged_in()) redirect('dashboard.php');

$fatal = null;
$old = ['name' => '', 'username' => '', 'email' => ''];

try {
    if (fb()->getDoc('admin_panel/admin') !== null) {
        flash('info', 'Admin already registered. Please login.');
        redirect('login.php');
    }
} catch (FirebaseException $e) {
    $fatal = $e->getMessage();
}

if (!$fatal && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name     = trim($_POST['name'] ?? '');
    $username = strtolower(trim($_POST['username'] ?? ''));
    $email    = trim($_POST['email'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $old = ['name' => $name, 'username' => $username, 'email' => $email];

    $err = null;
    if ($name === '' || mb_strlen($name) > 80)              $err = 'Enter your name.';
    elseif (!valid_username($username))                     $err = 'Username: 3-20 chars, a-z, 0-9, underscore only.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))     $err = 'Enter a valid email.';
    elseif (strlen($password) < 8)                          $err = 'Password must be at least 8 characters.';

    if ($err) {
        flash('danger', $err);
    } else {
        try {
            // documentId=admin → fails with 409 if it already exists (race-safe)
            fb()->createDoc('admin_panel', [
                'name'         => $name,
                'username'     => $username,
                'email'        => $email,
                'passwordHash' => password_hash($password, PASSWORD_DEFAULT),
                'createdAt'    => new DateTime(),
            ], 'admin');

            // Mirror into Firebase Authentication (Auth tab)
            $authUid = sync_admin_auth($email, $password, $name);
            if ($authUid) {
                try { fb()->setDoc('admin_panel/admin', ['authUid' => $authUid]); } catch (Throwable $e) {}
            } else {
                flash('warning', 'Admin saved, but Firebase Auth user not created: ' . ($_SESSION['auth_sync_error'] ?? 'unknown error'));
            }

            session_regenerate_id(true);
            $_SESSION['admin'] = ['name' => $name, 'username' => $username];
            flash('success', 'Admin registered. Welcome!');
            redirect('dashboard.php');
        } catch (FirebaseException $e) {
            if ($e->getCode() === 409) {
                flash('info', 'Admin already registered. Please login.');
                redirect('login.php');
            }
            flash('danger', 'Firebase error: ' . $e->getMessage());
        }
    }
    redirect('register.php');
}

$pageTitle = 'Admin Registration';
require __DIR__ . '/includes/guest_header.php';
?>
<div class="card guest-card">
  <div class="card-body p-4 p-sm-5">
    <div class="text-center mb-4">
      <div class="guest-logo"><i class="bi bi-shield-lock"></i></div>
      <h4 class="fw-bold mb-1">Create admin account</h4>
      <p class="text-muted small mb-0">First-time setup. This page disappears once the admin exists.</p>
    </div>

    <?php if ($fatal): ?>
      <script>document.addEventListener('DOMContentLoaded', function(){ snack('danger', <?= json_encode('Firebase connection failed: ' . $fatal, JSON_HEX_TAG | JSON_HEX_QUOT | JSON_HEX_APOS | JSON_HEX_AMP) ?>, {duration: 9000}); });</script>
      <div class="text-center text-muted small">Cannot reach Firebase. Check config and try again.</div>
    <?php else: ?>
    <form method="post" autocomplete="off" data-lock>
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label">Name</label>
        <div class="input-icon"><i class="bi bi-person-badge"></i>
          <input type="text" name="name" class="form-control" required maxlength="80" value="<?= e($old['name']) ?>"></div>
      </div>
      <div class="mb-3">
        <label class="form-label">Username</label>
        <div class="input-icon"><i class="bi bi-at"></i>
          <input type="text" name="username" class="form-control" required pattern="[a-zA-Z0-9_]{3,20}" autocapitalize="none"
                 value="<?= e($old['username']) ?>"></div>
        <div class="form-text">3-20 chars: letters, numbers, underscore.</div>
      </div>
      <div class="mb-3">
        <label class="form-label">Email</label>
        <div class="input-icon"><i class="bi bi-envelope"></i>
          <input type="email" name="email" class="form-control" required value="<?= e($old['email']) ?>"></div>
      </div>
      <div class="mb-4">
        <label class="form-label">Password</label>
        <div class="input-icon has-toggle"><i class="bi bi-lock"></i>
          <input type="password" name="password" class="form-control" required minlength="8" autocomplete="new-password">
          <button type="button" class="pw-toggle" aria-label="Show password"><i class="bi bi-eye"></i></button></div>
        <div class="form-text">Minimum 8 characters.</div>
      </div>
      <button class="btn btn-brand w-100 py-2" type="submit">Create account</button>
    </form>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/includes/guest_footer.php';
