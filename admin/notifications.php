<?php
require __DIR__ . '/includes/auth.php';
require_login();

/** Screens the app can open when the notification is tapped (must match PushScreens in Flutter). */
const NOTE_SCREENS = [
    'notifications' => 'Notifications list',
    'home'          => 'Home',
    'privacy'       => 'Privacy Policy',
];

/**
 * Push to the devices of selected users (tokens saved by the app at users/{uid}/fcmTokens).
 * @return array{devices:int,failed:int,noToken:int}
 */
function push_to_users(array $uids, string $title, string $body, array $data): array
{
    $devices = 0; $failed = 0; $noToken = 0;
    foreach ($uids as $uid) {
        $tokens = [];
        try {
            $doc = fb()->getDoc('users/' . $uid . '/fcmTokens');
            if (is_array($doc)) {
                foreach (array_keys($doc) as $t) {
                    if ($t !== 'id') $tokens[] = (string)$t;
                }
            }
        } catch (FirebaseException $e) {
            // treat as "no token"
        }
        if (!$tokens) { $noToken++; continue; }

        foreach ($tokens as $t) {
            try {
                fb()->pushToToken($t, $title, $body, $data);
                $devices++;
            } catch (FirebaseException $e) {
                $failed++;
                // Dead token (app uninstalled / reinstalled) → clean it up.
                if ($e->getCode() === 404 || stripos($e->getMessage(), 'registration token') !== false) {
                    try { fb()->deleteDoc('users/' . $uid . '/fcmTokens/' . $t); } catch (FirebaseException $x) {}
                }
            }
        }
    }
    return ['devices' => $devices, 'failed' => $failed, 'noToken' => $noToken];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    @set_time_limit(180);
    $action = $_POST['action'] ?? '';
    $id     = (string)($_POST['id'] ?? '');
    $title  = trim($_POST['title'] ?? '');
    $body   = trim($_POST['body'] ?? '');
    $screen = (string)($_POST['screen'] ?? 'notifications');
    if (!isset(NOTE_SCREENS[$screen])) $screen = 'notifications';

    try {
        if ($action === 'add' || $action === 'edit') {
            if ($title === '' || $body === '' || mb_strlen($title) > 120 || mb_strlen($body) > 1000) {
                flash('danger', 'Title (max 120) and message (max 1000) are required.');
                redirect('notifications.php');
            }
            if ($action === 'add') {
                $target = (($_POST['target'] ?? 'all') === 'users') ? 'users' : 'all';
                $uids = [];
                if ($target === 'users') {
                    foreach ((array)($_POST['uids'] ?? []) as $u) {
                        $u = (string)$u;
                        if (preg_match('/^[A-Za-z0-9_-]{6,128}$/', $u)) $uids[$u] = true;
                    }
                    $uids = array_keys($uids);
                    if (!$uids) {
                        flash('danger', 'Select at least one user, or choose "All users".');
                        redirect('notifications.php');
                    }
                }

                $doc = [
                    'title' => $title,
                    'body' => $body,
                    'screen' => $screen,
                    'target' => $target,
                    'createdAt' => new DateTime(),
                ];
                if ($target === 'users') {
                    $doc['userIds'] = array_fill_keys($uids, true);
                }
                $created = fb()->createDoc('notifications', $doc);
                flash('success', 'Notification saved.');

                if (!empty($_POST['push'])) {
                    $data = ['screen' => $screen, 'notificationId' => $created['id'] ?? ''];
                    try {
                        if ($target === 'all') {
                            fb()->pushToTopic(FCM_TOPIC, $title, $body, $data);
                            flash('success', 'Push sent to all users.');
                        } else {
                            $r = push_to_users($uids, $title, $body, $data);
                            $msg = 'Push sent to ' . $r['devices'] . ' device(s) of ' . count($uids) . ' selected user(s).';
                            if ($r['noToken'] > 0) $msg .= ' ' . $r['noToken'] . ' user(s) have no registered device (they will still see it inside the app).';
                            if ($r['failed'] > 0)  $msg .= ' ' . $r['failed'] . ' device(s) failed.';
                            flash($r['devices'] > 0 ? 'success' : 'warning', $msg);
                        }
                    } catch (FirebaseException $e) {
                        flash('warning', 'Saved, but push failed: ' . $e->getMessage());
                    }
                }
            } else {
                if (!preg_match('/^[A-Za-z0-9_-]{6,64}$/', $id)) redirect('notifications.php');
                fb()->setDoc('notifications/' . $id, ['title' => $title, 'body' => $body, 'screen' => $screen]);
                flash('success', 'Notification updated.');
            }
        } elseif ($action === 'delete') {
            if (preg_match('/^[A-Za-z0-9_-]{6,64}$/', $id)) {
                fb()->deleteDoc('notifications/' . $id);
                flash('success', 'Notification deleted.');
            }
        }
    } catch (FirebaseException $e) {
        flash('danger', 'Firebase error: ' . $e->getMessage());
    }
    redirect('notifications.php');
}

$items = [];
$users = [];
$error = null;
try {
    $items = fb()->query('notifications', 'createdAt', true, 500);
    $users = fb()->query('users', 'createdAt', true, 2000);
} catch (FirebaseException $e) {
    $error = $e->getMessage();
}

$pageTitle = 'Manage Notifications';
$active = 'notifications';
require __DIR__ . '/includes/header.php';
?>
<div class="page-head">
  <div>
    <h1>Notifications</h1>
    <p>Messages shown in the app, with optional push to all or selected users.</p>
  </div>
  <button class="btn btn-brand" data-bs-toggle="modal" data-bs-target="#addModal">
    <i class="bi bi-plus-lg me-1"></i> New notification
  </button>
</div>

<?php if ($error): ?>
  <script>document.addEventListener('DOMContentLoaded', function(){ snack('danger', <?= json_encode($error, JSON_HEX_TAG | JSON_HEX_QUOT | JSON_HEX_APOS | JSON_HEX_AMP) ?>); });</script>
<?php endif; ?>

<div class="card">
  <?php foreach ($items as $n):
      $isUsers = ($n['target'] ?? 'all') === 'users';
      $cnt = $isUsers && is_array($n['userIds'] ?? null) ? count($n['userIds']) : 0;
      $scr = $n['screen'] ?? 'notifications'; ?>
    <div class="note-item">
      <div class="note-ico"><i class="bi bi-bell"></i></div>
      <div class="note-body">
        <div class="t"><?= e($n['title'] ?? '') ?></div>
        <div class="m"><?= e($n['body'] ?? '') ?></div>
        <div class="small text-muted mt-2 d-flex flex-wrap gap-2 align-items-center">
          <span><i class="bi bi-clock"></i> <?= e(fmt_date($n['createdAt'] ?? null)) ?></span>
          <span class="badge-soft <?= $isUsers ? 'neutral' : 'ok' ?>">
            <i class="bi <?= $isUsers ? 'bi-people' : 'bi-broadcast' ?>"></i>
            <?= $isUsers ? e($cnt . ' selected user' . ($cnt === 1 ? '' : 's')) : 'All users' ?>
          </span>
          <span class="badge-soft neutral"><i class="bi bi-box-arrow-up-right"></i> Opens: <?= e(NOTE_SCREENS[$scr] ?? $scr) ?></span>
        </div>
      </div>
      <div class="note-actions">
        <button class="btn btn-sm btn-outline-primary btn-edit icon-btn" aria-label="Edit"
                data-id="<?= e($n['id']) ?>"
                data-title="<?= e($n['title'] ?? '') ?>"
                data-body="<?= e($n['body'] ?? '') ?>"
                data-screen="<?= e($scr) ?>">
          <i class="bi bi-pencil"></i>
        </button>
        <form method="post" data-confirm="Delete “<?= e($n['title'] ?? '') ?>”? This cannot be undone."
              data-confirm-title="Delete notification" data-confirm-ok="Delete">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?= e($n['id']) ?>">
          <button class="btn btn-sm btn-outline-danger icon-btn" aria-label="Delete"><i class="bi bi-trash"></i></button>
        </form>
      </div>
    </div>
  <?php endforeach; ?>
  <?php if (!$items && !$error): ?>
    <div class="empty"><i class="bi bi-bell-slash"></i>No notifications yet</div>
  <?php endif; ?>
</div>

<!-- Add -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable"><form method="post" class="modal-content" id="addForm">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="add">
    <div class="modal-header border-0 pb-0"><h5 class="modal-title fw-bold">New notification</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
      <div class="mb-3"><label class="form-label">Title</label>
        <input name="title" class="form-control" required maxlength="120"></div>
      <div class="mb-3"><label class="form-label">Message</label>
        <textarea name="body" class="form-control" rows="3" required maxlength="1000"></textarea></div>

      <div class="mb-3">
        <label class="form-label">Send to</label>
        <div class="d-flex gap-3">
          <div class="form-check">
            <input class="form-check-input" type="radio" name="target" value="all" id="t-all" checked>
            <label class="form-check-label" for="t-all">All users</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="radio" name="target" value="users" id="t-users">
            <label class="form-check-label" for="t-users">Selected users</label>
          </div>
        </div>
      </div>

      <div id="picker" class="mb-3 d-none">
        <div class="input-icon mb-2">
          <i class="bi bi-search"></i>
          <input type="search" id="pq" class="form-control" placeholder="Search name or email" aria-label="Search users">
        </div>
        <div class="d-flex justify-content-between align-items-center small mb-1">
          <div class="form-check m-0">
            <input class="form-check-input" type="checkbox" id="pall">
            <label class="form-check-label" for="pall">Select all shown</label>
          </div>
          <span class="text-muted"><b id="pcount">0</b> selected</span>
        </div>
        <div class="border rounded-3" style="max-height:240px;overflow:auto">
          <?php foreach ($users as $u):
              $nm = (string)($u['name'] ?? '');
              $em = (string)($u['email'] ?? '');
              $blocked = ($u['disabled'] ?? false) === true; ?>
            <label class="d-flex align-items-center gap-2 px-3 py-2 border-bottom prow" style="cursor:pointer"
                   data-s="<?= e(strtolower($nm . ' ' . $em)) ?>">
              <input class="form-check-input m-0 pchk" type="checkbox" name="uids[]" value="<?= e($u['id']) ?>">
              <span class="flex-grow-1" style="min-width:0">
                <span class="d-block fw-semibold text-truncate"><?= e($nm !== '' ? $nm : '(no name)') ?><?= $blocked ? ' · blocked' : '' ?></span>
                <span class="d-block small text-muted text-truncate"><?= e($em) ?></span>
              </span>
            </label>
          <?php endforeach; ?>
          <?php if (!$users): ?><div class="p-3 text-muted small">No users yet.</div><?php endif; ?>
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label">When tapped, open</label>
        <select name="screen" class="form-select">
          <?php foreach (NOTE_SCREENS as $k => $lbl): ?>
            <option value="<?= e($k) ?>"><?= e($lbl) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="push" value="1" id="push" checked>
        <label class="form-check-label" for="push">Also send push notification</label>
      </div>
      <div class="form-text">Selected users who are not logged in on any phone will still see it inside the app.</div>
    </div>
    <div class="modal-footer border-0 pt-0">
      <button type="button" class="btn btn-soft" data-bs-dismiss="modal">Cancel</button>
      <button class="btn btn-brand"><i class="bi bi-send me-1"></i> Send</button>
    </div>
  </form></div>
</div>

<!-- Edit -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable"><form method="post" class="modal-content">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="edit">
    <input type="hidden" name="id" id="e-id">
    <div class="modal-header border-0 pb-0"><h5 class="modal-title fw-bold">Edit notification</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
      <div class="mb-3"><label class="form-label">Title</label>
        <input name="title" id="e-title" class="form-control" required maxlength="120"></div>
      <div class="mb-3"><label class="form-label">Message</label>
        <textarea name="body" id="e-body" class="form-control" rows="4" required maxlength="1000"></textarea></div>
      <div class="mb-3"><label class="form-label">When tapped, open</label>
        <select name="screen" id="e-screen" class="form-select">
          <?php foreach (NOTE_SCREENS as $k => $lbl): ?>
            <option value="<?= e($k) ?>"><?= e($lbl) ?></option>
          <?php endforeach; ?>
        </select></div>
      <div class="form-text">Editing does not re-send push or change who receives it.</div>
    </div>
    <div class="modal-footer border-0 pt-0">
      <button type="button" class="btn btn-soft" data-bs-dismiss="modal">Cancel</button>
      <button class="btn btn-brand">Save</button>
    </div>
  </form></div>
</div>

<script>
window.addEventListener('load', function () {
  // ── edit ──
  var em = new bootstrap.Modal(document.getElementById('editModal'));
  document.querySelectorAll('.btn-edit').forEach(function (b) {
    b.addEventListener('click', function () {
      document.getElementById('e-id').value = b.dataset.id;
      document.getElementById('e-title').value = b.dataset.title;
      document.getElementById('e-body').value = b.dataset.body;
      document.getElementById('e-screen').value = b.dataset.screen || 'notifications';
      em.show();
    });
  });

  // ── user picker ──
  var picker = document.getElementById('picker');
  var rows = Array.prototype.slice.call(document.querySelectorAll('.prow'));
  var chks = Array.prototype.slice.call(document.querySelectorAll('.pchk'));
  var cnt = document.getElementById('pcount');
  var all = document.getElementById('pall');
  var q = document.getElementById('pq');

  function visibleChecks() {
    return rows.filter(function (r) { return r.style.display !== 'none'; })
               .map(function (r) { return r.querySelector('.pchk'); });
  }
  function refresh() {
    cnt.textContent = chks.filter(function (c) { return c.checked; }).length;
    var v = visibleChecks();
    all.checked = v.length > 0 && v.every(function (c) { return c.checked; });
  }
  document.querySelectorAll('input[name="target"]').forEach(function (r) {
    r.addEventListener('change', function () {
      picker.classList.toggle('d-none', document.getElementById('t-users').checked === false);
    });
  });
  q.addEventListener('input', function () {
    var t = q.value.trim().toLowerCase();
    rows.forEach(function (r) { r.style.display = (!t || r.dataset.s.indexOf(t) !== -1) ? '' : 'none'; });
    refresh();
  });
  all.addEventListener('change', function () {
    visibleChecks().forEach(function (c) { c.checked = all.checked; });
    refresh();
  });
  chks.forEach(function (c) { c.addEventListener('change', refresh); });
  document.getElementById('addForm').addEventListener('submit', function (ev) {
    if (document.getElementById('t-users').checked && !chks.some(function (c) { return c.checked; })) {
      ev.preventDefault();
      snack('danger', 'Select at least one user, or choose "All users".');
    }
  });
  refresh();
});
</script>
<?php require __DIR__ . '/includes/footer.php';