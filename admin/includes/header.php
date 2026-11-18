<?php
/** Expects: $pageTitle (string), $active (string), optional $pageSubtitle */
$nav = [
    'Overview' => [
        'dashboard' => ['dashboard.php', 'Dashboard', 'bi-grid-1x2-fill'],
    ],
    'Management' => [
        'users'         => ['users.php',         'Users',         'bi-people-fill'],
        'scans'         => ['scans.php',         'Scans',         'bi-qr-code-scan'],
        'notifications' => ['notifications.php', 'Notifications', 'bi-bell-fill'],
        'updates'       => ['updates.php',       'App Updates',   'bi-cloud-arrow-down-fill'],
    ],
];
$adminName = (string)($_SESSION['admin']['name'] ?? 'Admin');
$initial = mb_strtoupper(mb_substr($adminName !== '' ? $adminName : 'A', 0, 1));
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="theme-color" content="#0c2a2a">
  <title><?= e($pageTitle ?? 'Admin') ?> · <?= e(APP_NAME) ?> Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="assets/admin.css?v=2" rel="stylesheet">
</head>
<body>

<aside class="app-sidebar" id="sidebar" aria-label="Main navigation">
  <a href="dashboard.php" class="sb-brand">
    <span class="sb-logo"><i class="bi bi-qr-code-scan"></i></span>
    <span><?= e(APP_NAME) ?><small>Admin Panel</small></span>
  </a>
  <nav class="sb-scroll">
    <?php foreach ($nav as $section => $items): ?>
      <div class="sb-section"><?= e($section) ?></div>
      <?php foreach ($items as $key => [$href, $label, $icon]): ?>
        <a class="sb-link <?= ($active ?? '') === $key ? 'active' : '' ?>" href="<?= $href ?>"
           <?= ($active ?? '') === $key ? 'aria-current="page"' : '' ?>>
          <i class="bi <?= $icon ?>"></i><span><?= e($label) ?></span>
        </a>
      <?php endforeach; ?>
    <?php endforeach; ?>

    <div class="sb-section">Account</div>
    <a class="sb-link danger" href="logout.php" data-confirm-link="Log out of the admin panel?" data-confirm-ok="Logout" data-confirm-icon="bi-box-arrow-right">
      <i class="bi bi-box-arrow-right"></i><span>Logout</span>
    </a>
  </nav>
  <div class="sb-foot">
    <div class="sb-avatar"><?= e($initial) ?></div>
    <div class="sb-user"><b><?= e($adminName) ?></b><span>Administrator</span></div>
  </div>
</aside>
<div class="app-backdrop" id="backdrop"></div>

<div class="app-main">
  <header class="app-topbar">
    <button class="tb-toggle" id="sbToggle" type="button" aria-label="Open menu" aria-controls="sidebar"><i class="bi bi-list"></i></button>
    <h2 class="tb-title"><?= e($pageTitle ?? '') ?></h2>
    <div class="dropdown">
      <button class="tb-user dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" type="button">
        <span class="name small fw-semibold"><?= e($adminName) ?></span>
        <span class="sb-avatar"><?= e($initial) ?></span>
      </button>
      <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
        <li class="px-3 py-2 small text-muted">Signed in as<br><b class="text-dark">@<?= e($_SESSION['admin']['username'] ?? '') ?></b></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item text-danger" href="logout.php" data-confirm-link="Log out of the admin panel?" data-confirm-ok="Logout" data-confirm-icon="bi-box-arrow-right"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
      </ul>
    </div>
  </header>

  <main class="app-content">
