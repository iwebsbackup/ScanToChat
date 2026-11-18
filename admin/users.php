<?php
require __DIR__ . '/includes/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $uid = (string)($_POST['uid'] ?? '');

    if (!preg_match('/^[A-Za-z0-9_-]{6,128}$/', $uid)) {
        flash('danger', 'Invalid user id.');
        redirect('users.php');
    }

    try {
        if ($action === 'block' || $action === 'unblock') {
            $block = ($action === 'block');
            // 1) Disable the Firebase Auth account (real ban, can't sign in)
            fb()->authSetDisabled($uid, $block);
            // 2) Flag in Realtime Database (app watches this live → instant blocked screen)
            fb()->setDoc('users/' . $uid, ['disabled' => $block]);
            flash('success', $block ? 'User blocked.' : 'User unblocked.');
        } elseif ($action === 'delete') {
            // Real delete: Auth account + RTDB record
            fb()->authDelete($uid);
            fb()->deleteDoc('users/' . $uid);
            flash('success', 'User deleted permanently.');
        } else {
            flash('danger', 'Unknown action.');
        }
    } catch (FirebaseException $e) {
        flash('danger', 'Firebase error: ' . $e->getMessage());
    }
    redirect('users.php');
}

$users = [];
$error = null;
try {
    $users = fb()->query('users', 'createdAt', true, 1000);
} catch (FirebaseException $e) {
    $error = $e->getMessage();
}

$pageTitle = 'Manage Users';
$active = 'users';
require __DIR__ . '/includes/header.php';
?>
<div class="page-head">
  <div>
    <h1>Users <span class="badge-soft neutral align-middle ms-1" id="count"><?= count($users) ?></span></h1>
    <p>Block, unblock or remove app users.</p>
  </div>
  <div class="input-icon" style="width:100%;max-width:320px">
    <i class="bi bi-search"></i>
    <input type="search" id="q" class="form-control" placeholder="Search name or email" aria-label="Search users">
  </div>
</div>

<?php if ($error): ?>
  <script>document.addEventListener('DOMContentLoaded', function(){ snack('danger', <?= json_encode($error, JSON_HEX_TAG | JSON_HEX_QUOT | JSON_HEX_APOS | JSON_HEX_AMP) ?>); });</script>
<?php endif; ?>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover table-stack align-middle" id="tbl">
      <thead>
        <tr><th>User</th><th>Joined</th><th>Last login</th><th>Scans</th><th>Status</th><th class="text-end">Actions</th></tr>
      </thead>
      <tbody>
      <?php foreach ($users as $u):
          $blocked = ($u['disabled'] ?? false) === true;
          $name = $u['name'] ?? '';
          $email = $u['email'] ?? '';
          $photo = $u['photoUrl'] ?? '';
          $label = $name !== '' ? $name : $email; ?>
        <tr data-s="<?= e(strtolower($name . ' ' . $email)) ?>">
          <td data-label="">
            <div class="d-flex align-items-center gap-3">
              <?php if ($photo): ?>
                <img class="avatar" src="<?= e($photo) ?>" referrerpolicy="no-referrer" alt="">
              <?php else: ?>
                <div class="avatar-ph"><?= e(mb_strtoupper(mb_substr($label ?: '?', 0, 1))) ?></div>
              <?php endif; ?>
              <div class="min-w-0">
                <div class="fw-semibold"><?= e($name ?: '—') ?></div>
                <div class="text-muted small text-break"><?= e($email) ?></div>
              </div>
            </div>
          </td>
          <td data-label="Joined" class="small"><?= e(fmt_date($u['createdAt'] ?? null)) ?></td>
          <td data-label="Last login" class="small"><?= e(fmt_date($u['lastLogin'] ?? null)) ?></td>
          <td data-label="Scans"><a href="scans.php?uid=<?= e(urlencode($u['id'])) ?>" class="text-decoration-none" title="View scans"><span class="badge-soft neutral"><?= (int)($u['scanCount'] ?? 0) ?> <i class="bi bi-chevron-right small"></i></span></a></td>
          <td data-label="Status"><?= $blocked ? '<span class="badge-soft bad">Blocked</span>' : '<span class="badge-soft ok">Active</span>' ?></td>
          <td class="text-end text-nowrap actions">
            <form method="post" class="d-inline"
                  data-confirm="<?= $blocked ? 'Allow ' : 'Block ' ?><?= e($label) ?><?= $blocked ? ' to use the app again?' : '? They will be signed out and cannot log in.' ?>"
                  data-confirm-title="<?= $blocked ? 'Unblock user' : 'Block user' ?>"
                  data-confirm-ok="<?= $blocked ? 'Unblock' : 'Block' ?>"
                  data-confirm-icon="<?= $blocked ? 'bi-unlock' : 'bi-slash-circle' ?>"
                  data-confirm-btn="<?= $blocked ? 'btn-success' : 'btn-warning' ?>">
              <?= csrf_field() ?>
              <input type="hidden" name="uid" value="<?= e($u['id']) ?>">
              <input type="hidden" name="action" value="<?= $blocked ? 'unblock' : 'block' ?>">
              <button class="btn btn-sm <?= $blocked ? 'btn-outline-success' : 'btn-outline-warning' ?>">
                <i class="bi <?= $blocked ? 'bi-unlock' : 'bi-slash-circle' ?>"></i> <?= $blocked ? 'Unblock' : 'Block' ?>
              </button>
            </form>
            <form method="post" class="d-inline shrink"
                  data-confirm="Delete <?= e($label) ?> permanently? Auth account and data will be removed. This cannot be undone."
                  data-confirm-title="Delete user" data-confirm-ok="Delete">
              <?= csrf_field() ?>
              <input type="hidden" name="uid" value="<?= e($u['id']) ?>">
              <input type="hidden" name="action" value="delete">
              <button class="btn btn-sm btn-outline-danger" aria-label="Delete user"><i class="bi bi-trash"></i><span class="d-md-none ms-1">Delete</span></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$users): ?><tr><td colspan="6"><div class="empty"><i class="bi bi-people"></i>No users</div></td></tr><?php endif; ?>
      </tbody>
    </table>
    <div class="empty d-none" id="noMatch"><i class="bi bi-search"></i>No matching users</div>
  </div>
</div>

<script>
document.getElementById('q').addEventListener('input', function () {
  var v = this.value.trim().toLowerCase(), n = 0;
  document.querySelectorAll('#tbl tbody tr[data-s]').forEach(function (tr) {
    var hit = tr.dataset.s.includes(v);
    tr.style.display = hit ? '' : 'none';
    if (hit) n++;
  });
  document.getElementById('count').textContent = n;
  document.getElementById('noMatch').classList.toggle('d-none', n > 0 || !document.querySelector('#tbl tbody tr[data-s]'));
});
</script>
<?php require __DIR__ . '/includes/footer.php';
