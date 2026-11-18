<?php
require __DIR__ . '/includes/auth.php';
require_login();

$uid = (string)($_GET['uid'] ?? '');
if ($uid !== '' && !preg_match('/^[A-Za-z0-9_-]{6,128}$/', $uid)) {
    flash('danger', 'Invalid user id.');
    redirect('users.php');
}

/** Only http(s) image URLs allowed in <img src>. */
function safe_img(?string $u): string
{
    $u = trim((string)$u);
    return preg_match('#^https?://#i', $u) ? $u : '';
}

$scans = [];
$owner = null;
$error = null;

try {
    if ($uid !== '') {
        // One user's scans
        $owner = fb()->getDoc('users/' . $uid);
        foreach (fb()->query('users/' . $uid . '/scans', 'scannedAt', true, 1000) as $s) {
            $s['_uid'] = $uid;
            $s['_uname'] = $owner['name'] ?? '';
            $s['_uemail'] = $owner['email'] ?? '';
            $scans[] = $s;
        }
    } else {
        // All users' scans, newest first
        foreach (fb()->query('users', 'createdAt', true, 1000) as $u) {
            if (empty($u['scans']) || !is_array($u['scans'])) continue;
            foreach ($u['scans'] as $sid => $s) {
                if (!is_array($s)) continue;
                $s['id'] = (string)$sid;
                $s['_uid'] = $u['id'];
                $s['_uname'] = $u['name'] ?? '';
                $s['_uemail'] = $u['email'] ?? '';
                $scans[] = $s;
            }
        }
        usort($scans, fn($a, $b) => ($b['scannedAt'] ?? 0) <=> ($a['scannedAt'] ?? 0));
        $scans = array_slice($scans, 0, 500);
    }
} catch (FirebaseException $e) {
    $error = $e->getMessage();
}

$ownerLabel = $owner ? (($owner['name'] ?? '') !== '' ? $owner['name'] : ($owner['email'] ?? $uid)) : '';
$pageTitle = $uid !== '' ? 'Scans · ' . $ownerLabel : 'All Scans';
$active = 'scans';
require __DIR__ . '/includes/header.php';
?>
<div class="page-head">
  <div>
    <h1>
      <?= $uid !== '' ? e($ownerLabel) . '’s scans' : 'Scans' ?>
      <span class="badge-soft neutral align-middle ms-1" id="count"><?= count($scans) ?></span>
    </h1>
    <p>
      <?php if ($uid !== ''): ?>
        <a href="scans.php"><i class="bi bi-arrow-left"></i> All scans</a> ·
        <a href="users.php">Users</a>
      <?php else: ?>
        Every card users scanned: photo, name, email and numbers.
      <?php endif; ?>
    </p>
  </div>
  <div class="input-icon" style="width:100%;max-width:320px">
    <i class="bi bi-search"></i>
    <input type="search" id="q" class="form-control" placeholder="Search name, email, number" aria-label="Search scans">
  </div>
</div>

<?php if ($error): ?>
  <script>document.addEventListener('DOMContentLoaded', function(){ snack('danger', <?= json_encode($error, JSON_HEX_TAG | JSON_HEX_QUOT | JSON_HEX_APOS | JSON_HEX_AMP) ?>); });</script>
<?php endif; ?>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover table-stack align-middle" id="tbl">
      <thead>
        <tr>
          <th style="width:96px">Photo</th><th>Name</th><th>Email</th><th>Numbers</th>
          <?php if ($uid === ''): ?><th>Scanned by</th><?php endif; ?>
          <th>Scanned at</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($scans as $s):
          $img = safe_img($s['photoUrl'] ?? '');
          $nums = $s['numbers'] ?? [];
          if (!is_array($nums)) $nums = [$nums];
          $wa = $s['waNumbers'] ?? [];
          if (!is_array($wa)) $wa = [$wa];
          $nums = array_values($nums);
          $wa = array_values($wa);
          $hay = strtolower(($s['name'] ?? '') . ' ' . ($s['email'] ?? '') . ' ' . implode(' ', $nums) . ' ' . ($s['_uname'] ?? '') . ' ' . ($s['_uemail'] ?? '')); ?>
        <tr data-s="<?= e($hay) ?>">
          <td data-label="">
            <?php if ($img): ?>
              <a href="#" class="scan-thumb" data-img="<?= e($img) ?>" data-title="<?= e($s['name'] ?? 'Scan') ?>">
                <img src="<?= e($img) ?>" loading="lazy" referrerpolicy="no-referrer" alt="Scanned card"
                     style="width:80px;height:56px;object-fit:cover;border-radius:8px;border:1px solid var(--line)">
              </a>
            <?php else: ?>
              <div class="avatar-ph" style="width:80px;height:56px;border-radius:8px" title="No photo uploaded"><i class="bi bi-image"></i></div>
            <?php endif; ?>
          </td>
          <td data-label="Name" class="fw-semibold"><?= e(($s['name'] ?? '') !== '' ? $s['name'] : '—') ?></td>
          <td data-label="Email" class="small text-break"><?= e(($s['email'] ?? '') !== '' ? $s['email'] : '—') ?></td>
          <td data-label="Numbers" class="small">
            <?php if ($nums): foreach ($nums as $i => $n): $w = preg_replace('/\D+/', '', (string)($wa[$i] ?? '')); ?>
              <div>
                <?= e($n) ?>
                <?php if ($w): ?><a href="https://wa.me/<?= e($w) ?>" target="_blank" rel="noopener" class="ms-1" aria-label="Open WhatsApp"><i class="bi bi-whatsapp text-success"></i></a><?php endif; ?>
              </div>
            <?php endforeach; else: ?>—<?php endif; ?>
          </td>
          <?php if ($uid === ''): ?>
            <td data-label="Scanned by" class="small">
              <a href="scans.php?uid=<?= e(urlencode($s['_uid'])) ?>">
                <?= e(($s['_uname'] ?? '') !== '' ? $s['_uname'] : ($s['_uemail'] ?: $s['_uid'])) ?>
              </a>
            </td>
          <?php endif; ?>
          <td data-label="Scanned at" class="small text-nowrap"><?= e(fmt_date($s['scannedAt'] ?? null)) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$scans): ?><tr><td colspan="6"><div class="empty"><i class="bi bi-qr-code-scan"></i>No scans yet</div></td></tr><?php endif; ?>
      </tbody>
    </table>
    <div class="empty d-none" id="noMatch"><i class="bi bi-search"></i>No matching scans</div>
  </div>
</div>

<!-- Image viewer -->
<div class="modal fade" id="imgModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title" id="imgTitle"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <img id="imgBig" src="" alt="Scanned card" referrerpolicy="no-referrer" style="max-width:100%;max-height:75vh;border-radius:8px">
      </div>
    </div>
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
document.querySelectorAll('.scan-thumb').forEach(function (a) {
  a.addEventListener('click', function (ev) {
    ev.preventDefault();
    document.getElementById('imgBig').src = a.dataset.img;
    document.getElementById('imgTitle').textContent = a.dataset.title;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('imgModal')).show();
  });
});
</script>
<?php require __DIR__ . '/includes/footer.php';
