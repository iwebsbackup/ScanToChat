<?php
require __DIR__ . '/includes/auth.php';
require_login();

// Public folder for APKs (own .htaccess allows only *.apk). storage/ stays private.
const RELEASES_DIR = __DIR__ . '/releases';
const MAX_APK_BYTES = 300 * 1024 * 1024; // 300 MB app-side cap; real limit = php.ini upload_max_filesize / post_max_size

/** Public HTTPS base URL of releases/ folder (derived from current request). */
function releases_base_url(): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
          || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
    return ($https ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $dir . '/releases/';
}

function upload_err_text(int $c): string
{
    return match ($c) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'APK bigger than server limit (upload_max_filesize / post_max_size).',
        UPLOAD_ERR_PARTIAL   => 'Upload interrupted. Try again.',
        UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE, UPLOAD_ERR_EXTENSION => 'Server could not store upload.',
        default => 'Upload failed (code ' . $c . ').',
    };
}

$cfg = [];
$error = null;
try {
    $cfg = fb()->getDoc('config/update') ?? [];
} catch (FirebaseException $e) {
    $error = $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $code  = trim($_POST['versionCode'] ?? '');
    $name  = trim($_POST['versionName'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $force = !empty($_POST['isForced']);
    $file  = $_FILES['apk'] ?? null;
    $hasFile = $file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

    $oldUrl  = (string)($cfg['downloadUrl'] ?? '');
    $oldFile = (string)($cfg['apkFile'] ?? '');

    $err = null;
    if (!ctype_digit($code) || (int)$code < 1)            $err = 'Build number must be a whole number ≥ 1.';
    elseif (!valid_version($name))                         $err = 'Version name format: 1.2.3';
    elseif (mb_strlen($notes) > 1000)                      $err = 'Notes too long (max 1000).';
    elseif (!$hasFile && $oldUrl === '')                   $err = 'Choose an .apk file to upload.';
    elseif ($hasFile && $file['error'] !== UPLOAD_ERR_OK)  $err = upload_err_text((int)$file['error']);
    elseif ($hasFile && strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'apk')
                                                           $err = 'File must be .apk';
    elseif ($hasFile && $file['size'] > MAX_APK_BYTES)     $err = 'APK too large.';

    // APK = zip → starts with "PK"
    if (!$err && $hasFile) {
        $fh = @fopen($file['tmp_name'], 'rb');
        $magic = $fh ? fread($fh, 2) : '';
        if ($fh) fclose($fh);
        if ($magic !== 'PK') $err = 'Not a valid APK file.';
    }

    if (!$err && !is_dir(RELEASES_DIR) && !@mkdir(RELEASES_DIR, 0755, true)) {
        $err = 'Cannot create releases/ folder. Create it manually, chmod 755.';
    }
    if (!$err && !is_writable(RELEASES_DIR)) {
        $err = 'releases/ folder not writable by web server.';
    }

    if ($err) {
        flash('danger', $err);
    } else {
        $url = $oldUrl;
        $fileName = $oldFile;
        $newMoved = null;
        try {
            if ($hasFile) {
                $fileName = 'release-v' . (int)$code . '.apk';
                $dest = RELEASES_DIR . '/' . $fileName;
                if (!move_uploaded_file($file['tmp_name'], $dest)) {
                    throw new RuntimeException('Could not save APK.');
                }
                @chmod($dest, 0644);
                $newMoved = $dest;
                $url = releases_base_url() . $fileName;
            }

            fb()->setDoc('config/update', [
                'versionCode' => (int)$code,
                'versionName' => $name,
                'downloadUrl' => $url,
                'apkFile'     => $fileName,
                'notes'       => $notes,
                'isForced'    => $force,
                'updatedAt'   => new DateTime(),
                // wipe legacy keys from old version-string system
                'latestVersion' => null,
                'minVersion'    => null,
            ]);

            // new APK live → delete previous APK file
            if ($hasFile && $oldFile !== '' && $oldFile !== $fileName) {
                $old = RELEASES_DIR . '/' . basename($oldFile);
                if (is_file($old)) @unlink($old);
            }
            flash('success', $hasFile
                ? 'APK uploaded & update published. Apps show it on next launch.'
                : 'Update settings saved.');
        } catch (FirebaseException $e) {
            if ($newMoved && is_file($newMoved) && $oldFile !== $fileName) @unlink($newMoved);
            flash('danger', 'Firebase error: ' . $e->getMessage());
        } catch (Throwable $e) {
            flash('danger', $e->getMessage());
        }
    }
    redirect('updates.php');
}

$apkPath = !empty($cfg['apkFile']) ? RELEASES_DIR . '/' . basename((string)$cfg['apkFile']) : null;
$apkSize = ($apkPath && is_file($apkPath)) ? round(filesize($apkPath) / 1048576, 1) . ' MB' : null;

$pageTitle = 'Manage Updates';
$active = 'updates';
require __DIR__ . '/includes/header.php';
?>
<div class="page-head">
  <div>
    <h1>App Updates</h1>
    <p>Upload new APK. App downloads &amp; installs it in-app (OTA).</p>
  </div>
</div>
<?php if ($error): ?>
  <script>document.addEventListener('DOMContentLoaded', function(){ snack('danger', <?= json_encode($error, JSON_HEX_TAG | JSON_HEX_QUOT | JSON_HEX_APOS | JSON_HEX_AMP) ?>); });</script>
<?php endif; ?>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card">
      <div class="card-body p-3 p-md-4">
        <form method="post" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <div class="row g-3">
            <div class="col-sm-6">
              <label class="form-label">Build number <span class="text-muted fw-normal">(version_code)</span></label>
              <input name="versionCode" type="number" min="1" step="1" class="form-control" required placeholder="2"
                     value="<?= e($cfg['versionCode'] ?? '') ?>">
            </div>
            <div class="col-sm-6">
              <label class="form-label">Version name</label>
              <input name="versionName" class="form-control" required placeholder="1.1.0"
                     value="<?= e($cfg['versionName'] ?? '') ?>">
            </div>
            <div class="col-12 form-text mt-1">
              Build number = number after <code>+</code> in <code>pubspec.yaml</code> (<code>1.1.0+<b>2</b></code>). Apps update only if this is higher than installed build.
            </div>
            <div class="col-12">
              <label class="form-label">APK file
                <?php if (empty($cfg['downloadUrl'])): ?><span class="text-danger">*</span>
                <?php else: ?><span class="text-muted fw-normal">(leave empty to keep current APK)</span><?php endif; ?>
              </label>
              <input name="apk" type="file" accept=".apk,application/vnd.android.package-archive" class="form-control"
                     <?= empty($cfg['downloadUrl']) ? 'required' : '' ?>>
              <div class="form-text">Server limit: upload_max_filesize <?= e(ini_get('upload_max_filesize')) ?>, post_max_size <?= e(ini_get('post_max_size')) ?>.</div>
            </div>
            <div class="col-12">
              <label class="form-label">What's new <span class="text-muted fw-normal">(optional)</span></label>
              <textarea name="notes" class="form-control" rows="4" maxlength="1000"><?= e($cfg['notes'] ?? '') ?></textarea>
            </div>
            <div class="col-12">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" id="isForced" name="isForced" value="1"
                       <?= !empty($cfg['isForced']) ? 'checked' : '' ?>>
                <label class="form-check-label" for="isForced">Force update <span class="text-muted">(user cannot skip)</span></label>
              </div>
            </div>
            <div class="col-12">
              <button class="btn btn-brand px-4"><i class="bi bi-cloud-upload me-1"></i> Publish update</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-center gap-3 mb-3">
          <div class="stat-icon blue"><i class="bi bi-clock-history"></i></div>
          <div><div class="text-muted small">Last published</div><div class="fw-semibold"><?= e(fmt_date($cfg['updatedAt'] ?? null)) ?></div></div>
        </div>
        <?php if (!empty($cfg['downloadUrl'])): ?>
          <div class="small mb-2"><span class="text-muted">Live:</span>
            <b>v<?= e($cfg['versionName'] ?? '?') ?></b> (build <?= e($cfg['versionCode'] ?? '?') ?>)
            <?= !empty($cfg['isForced']) ? '<span class="badge text-bg-danger ms-1">forced</span>' : '' ?>
          </div>
          <div class="small mb-2 text-break"><span class="text-muted">APK:</span>
            <a href="<?= e($cfg['downloadUrl']) ?>" target="_blank" rel="noopener"><?= e($cfg['apkFile'] ?? 'download') ?></a>
            <?= $apkSize ? '(' . e($apkSize) . ')' : '<span class="text-danger">(file missing on server)</span>' ?>
          </div>
          <?php if (stripos((string)$cfg['downloadUrl'], 'https://') !== 0): ?>
            <div class="small text-danger mb-2"><i class="bi bi-exclamation-triangle me-1"></i>Download URL not HTTPS. Enable SSL.</div>
          <?php endif; ?>
        <?php endif; ?>
        <div class="small text-muted">
          <i class="bi bi-info-circle me-1"></i>Sign APK with <b>same keystore</b> as installed app, else Android rejects update.
        </div>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php';