<?php
require __DIR__ . '/includes/auth.php';
require_login();

$total = $blocked = $notifs = 0;
$update = null;
$recent = [];
$error = null;

try {
    $users  = fb()->query('users', 'createdAt', true, 1000);
    $total  = count($users);
    $blocked = count(array_filter($users, fn($u) => ($u['disabled'] ?? false) === true));
    $recent = array_slice($users, 0, 5);
    $notifs = count(fb()->query('notifications', 'createdAt', true, 1000));
    $update = fb()->getDoc('config/update');
} catch (FirebaseException $e) {
    $error = $e->getMessage();
}

$pageTitle = 'Dashboard';
$active = 'dashboard';
require __DIR__ . '/includes/header.php';
?>
<div class="page-head">
  <div>
    <h1>Welcome back, <?= e(explode(' ', trim($_SESSION['admin']['name']))[0]) ?> 👋</h1>
    <p>Overview of your <?= e(APP_NAME) ?> app.</p>
  </div>
  <a href="notifications.php" class="btn btn-brand"><i class="bi bi-send me-1"></i> Send notification</a>
</div>

<?php if ($error): ?>
  <script>window.__flash = (window.__flash || []); document.addEventListener('DOMContentLoaded', function(){ snack('danger', <?= json_encode('Firebase error: ' . $error, JSON_HEX_TAG | JSON_HEX_QUOT | JSON_HEX_APOS | JSON_HEX_AMP) ?>); });</script>
<?php endif; ?>

<div class="row g-3 mb-4">
  <?php
  $cards = [
      ['Total users',    $total,                         'bi-people-fill',          'users.php',         ''],
      ['Blocked users',  $blocked,                       'bi-slash-circle-fill',    'users.php',         'red'],
      ['Notifications',  $notifs,                        'bi-bell-fill',            'notifications.php', 'amber'],
      ['Latest version', $update['latestVersion'] ?? '—', 'bi-cloud-arrow-down-fill', 'updates.php',     'blue'],
  ];
  foreach ($cards as [$label, $val, $icon, $href, $tone]): ?>
    <div class="col-6 col-xl-3">
      <a href="<?= $href ?>" class="d-block text-dark">
        <div class="card stat-card h-100">
          <div class="card-body d-flex align-items-center gap-3">
            <div class="stat-icon <?= $tone ?>"><i class="bi <?= $icon ?>"></i></div>
            <div class="min-w-0">
              <div class="text-muted small"><?= e($label) ?></div>
              <div class="stat-val"><?= e($val) ?></div>
            </div>
          </div>
        </div>
      </a>
    </div>
  <?php endforeach; ?>
</div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span>Recent users</span>
    <a href="users.php" class="small fw-semibold" style="color:var(--brand-dark)">View all <i class="bi bi-arrow-right"></i></a>
  </div>
  <div class="table-responsive">
    <table class="table table-stack align-middle">
      <thead><tr><th>Name</th><th>Email</th><th>Joined</th></tr></thead>
      <tbody>
      <?php foreach ($recent as $u): ?>
        <tr>
          <td data-label="Name"><span class="fw-semibold"><?= e(($u['name'] ?? '') ?: '—') ?></span></td>
          <td data-label="Email" class="text-break"><?= e($u['email'] ?? '') ?></td>
          <td data-label="Joined" class="small text-muted"><?= e(fmt_date($u['createdAt'] ?? null)) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$recent): ?><tr><td colspan="3"><div class="empty"><i class="bi bi-people"></i>No users yet</div></td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php';
