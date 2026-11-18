<?php
/**
 * PUBLIC, standalone page. No login, no database, no other file needed. Use this URL in Play Store "Privacy policy" field:
 *   https://YOUR-DOMAIN/admin/privacy-policy.php
 */

// ───────── EDIT THESE ─────────
const PP_COMPANY       = 'Proximatix';
const PP_SUPPORT_EMAIL = '';            // e.g. 'support@yourdomain.com'
const PP_LAST_UPDATED  = 'November 2026';
// ──────────────────────────────

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
$app = 'Scan2Chat';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="index,follow">
  <meta name="theme-color" content="#1d1b1b">
  <title>Privacy Policy · <?= h($app) ?></title>
  <style>
    :root { --red:#ec4d37; --ink:#1d1b1b; --muted:#6b6767; --bg:#f7f5f5; --line:#eae6e6; }
    * { box-sizing: border-box; }
    html { -webkit-text-size-adjust: 100%; }
    body {
      margin: 0; background: var(--bg); color: var(--ink);
      font: 16px/1.65 -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    }
    header { background: var(--ink); color: #fff; border-bottom: 4px solid var(--red); }
    .wrap { max-width: 820px; margin: 0 auto; padding: 0 20px; }
    header .wrap { padding-top: 34px; padding-bottom: 30px; }
    .brand { display: flex; align-items: center; gap: 12px; font-weight: 700; font-size: 18px; }
    .dot { width: 34px; height: 34px; border-radius: 9px; background: var(--red);
           display: grid; place-items: center; font-weight: 800; }
    h1 { margin: 18px 0 4px; font-size: 32px; line-height: 1.2; }
    .meta { color: #cfcaca; font-size: 14px; }
    main { padding: 26px 0 50px; }
    .card { background: #fff; border: 1px solid var(--line); border-radius: 14px; padding: 22px 24px; margin-bottom: 16px; }
    h2 { margin: 0 0 10px; font-size: 19px; color: var(--red); }
    p { margin: 0 0 10px; }
    ul { margin: 0 0 4px; padding-left: 22px; }
    li { margin-bottom: 6px; }
    table { width: 100%; border-collapse: collapse; font-size: 15px; }
    th, td { text-align: left; padding: 10px 8px; border-bottom: 1px solid var(--line); vertical-align: top; }
    th { color: var(--muted); font-weight: 600; font-size: 13px; text-transform: uppercase; letter-spacing: .04em; }
    .tbl { overflow-x: auto; }
    a { color: var(--red); }
    footer { text-align: center; color: var(--muted); font-size: 13px; padding: 0 20px 40px; }
    @media (max-width: 560px) { h1 { font-size: 26px; } .card { padding: 18px 16px; } }
  </style>
</head>
<body>
<header>
  <div class="wrap">
    <div class="brand"><span class="dot">S</span><?= h($app) ?></div>
    <h1>Privacy Policy</h1>
    <div class="meta">Last updated: <?= h(PP_LAST_UPDATED) ?></div>
  </div>
</header>

<main class="wrap">

  <section class="card">
    <p><?= h($app) ?> (“the app”, “we”, “us”) is provided by <?= h(PP_COMPANY) ?>. The app lets you scan a
    visiting card, save the contact and start a WhatsApp chat. This policy explains what data the app uses,
    why, and what choices you have. By using the app you agree to this policy.</p>
  </section>

  <section class="card">
    <h2>1. Information we collect</h2>
    <ul>
      <li><b>Account information:</b> your name, email address and profile photo from your Google account when you sign in with Google.</li>
      <li><b>Scanned card data:</b> the name, phone numbers and email read from cards you scan, and, if you scan with the camera, an optional photo of the card.</li>
      <li><b>Usage data:</b> number of scans, last scan time and last login time.</li>
      <li><b>Device token:</b> a Firebase Cloud Messaging (FCM) token used to deliver notifications to your device.</li>
    </ul>
  </section>

  <section class="card">
    <h2>2. Permissions the app asks for</h2>
    <div class="tbl">
      <table>
        <tr><th>Permission</th><th>Why we need it</th></tr>
        <tr><td>Camera</td><td>To scan visiting cards.</td></tr>
        <tr><td>Contacts (read / write)</td><td>To save scanned numbers to your phone when you choose to.</td></tr>
        <tr><td>Notifications</td><td>To show messages sent by the <?= h($app) ?> team.</td></tr>
        <tr><td>Install unknown apps</td><td>Only to install app updates that we publish.</td></tr>
        <tr><td>Internet</td><td>To sign in, sync scan records and receive notifications and updates.</td></tr>
      </table>
    </div>
    <p style="margin-top:10px">You can revoke any permission at any time from your phone settings.</p>
  </section>

  <section class="card">
    <h2>3. How we use your information</h2>
    <ul>
      <li>To sign you in and keep your account secure.</li>
      <li>To read text from cards and save the contacts you scan.</li>
      <li>To open a WhatsApp chat with a number when you tap the chat button.</li>
      <li>To send app updates and notifications, to all users or to selected users.</li>
      <li>To maintain the app, fix problems and prevent misuse.</li>
    </ul>
  </section>

  <section class="card">
    <h2>4. Where your data is stored and who processes it</h2>
    <div class="tbl">
      <table>
        <tr><th>Service</th><th>Used for</th></tr>
        <tr><td>Google Firebase (Authentication, Realtime Database, Cloud Messaging)</td><td>Sign-in, account and scan records, notification delivery.</td></tr>
        <tr><td>Google Sign-In</td><td>Logging in with your Google account.</td></tr>
        <tr><td>Google ML Kit</td><td>Reading text from cards. This runs on your device.</td></tr>
        <tr><td>ImgBB</td><td>Hosting the card photo. Only the image link is stored with the scan.</td></tr>
        <tr><td>WhatsApp</td><td>Opened by you to chat with a scanned number.</td></tr>
      </table>
    </div>
    <p style="margin-top:10px">Your scan history list is also kept on your phone. Clearing history in the app removes it from the phone.</p>
  </section>

  <section class="card">
    <h2>5. Sharing of data</h2>
    <p>We do not sell your personal data. We do not share it with anyone except the service providers listed
    above, which process it only to run the app. We may disclose information if required by law.</p>
  </section>

  <section class="card">
    <h2>6. Notifications</h2>
    <p>We may send notifications to all users or to selected users. You can turn notifications off in your phone
    settings. When you log out, your device stops receiving notifications meant for your account.</p>
  </section>

  <section class="card">
    <h2>7. Data retention and deletion</h2>
    <ul>
      <li>We keep your account and scan records while your account is active.</li>
      <li>You can delete scans stored on your phone with <b>Clear history</b> in the app menu.</li>
      <li>To delete your account and all data stored in our servers, contact us using the details below. We will delete it within a reasonable time.</li>
    </ul>
  </section>

  <section class="card">
    <h2>8. Security</h2>
    <p>Data travels over secure HTTPS connections and is stored with Google Firebase. No method of storage or
    transmission is completely secure, but we take reasonable steps to protect your data.</p>
  </section>

  <section class="card">
    <h2>9. Third-party contact data</h2>
    <p>The app processes details of people printed on cards you scan. Please scan and save such details only
    where you have the right to do so, and use them responsibly.</p>
  </section>

  <section class="card">
    <h2>10. Children</h2>
    <p><?= h($app) ?> is not intended for children under 13 and we do not knowingly collect data from them.</p>
  </section>

  <section class="card">
    <h2>11. Changes to this policy</h2>
    <p>We may update this policy from time to time. The date at the top shows when it was last changed. The
    latest version is always available on this page and inside the app.</p>
  </section>

  <section class="card">
    <h2>12. Contact us</h2>
    <p>For privacy questions or data deletion requests, contact <?= h(PP_COMPANY) ?>
    <?php if (PP_SUPPORT_EMAIL !== ''): ?>
      at <a href="mailto:<?= h(PP_SUPPORT_EMAIL) ?>"><?= h(PP_SUPPORT_EMAIL) ?></a>.
    <?php else: ?>
      through the app administrator.
    <?php endif; ?></p>
  </section>

</main>
<footer>© <?= date('Y') ?> <?= h(PP_COMPANY) ?>. All rights reserved.</footer>
</body>
</html>