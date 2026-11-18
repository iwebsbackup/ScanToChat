<?php
/**
 * PUBLIC, standalone page. No login, no database, no other file needed.
 * Use this URL in Play Store "Data deletion / Delete account URL" field:
 *   https://YOUR-DOMAIN/admin/delete-account.php
 *
 * How it works: user fills name, email, reason -> page opens a ready-made
 * email draft addressed to PP_DELETE_EMAIL (in user's mail app or Gmail).
 * User just presses Send.
 */

// ───────── EDIT THESE ─────────
const PP_COMPANY      = 'Proximatix';
const PP_DELETE_EMAIL = 'iwebstech@gmail.com';   // draft goes TO this address
const PP_LAST_UPDATED = 'November 2026';
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
  <title>Delete Account · <?= h($app) ?></title>
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
    a { color: var(--red); }

    label { display: block; font-weight: 600; font-size: 14px; margin: 14px 0 6px; }
    input[type=text], input[type=email], textarea {
      width: 100%; padding: 12px 14px; font: inherit; color: var(--ink);
      background: #fff; border: 1px solid #d9d4d4; border-radius: 10px; outline: none;
    }
    input:focus, textarea:focus { border-color: var(--red); box-shadow: 0 0 0 3px rgba(236,77,55,.15); }
    textarea { min-height: 120px; resize: vertical; }
    .err { color: #c0281a; font-size: 13px; margin-top: 4px; display: none; }
    .invalid + .err { display: block; }
    .invalid { border-color: #c0281a !important; }
    .btns { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 20px; }
    button, .btn {
      appearance: none; border: 0; cursor: pointer; font: inherit; font-weight: 700;
      padding: 13px 20px; border-radius: 10px; text-decoration: none; display: inline-block;
    }
    .primary { background: var(--red); color: #fff; }
    .primary:hover { filter: brightness(.95); }
    .ghost { background: #fff; color: var(--ink); border: 1px solid #d9d4d4; }
    .note { color: var(--muted); font-size: 14px; margin-top: 12px; }
    .ok { display: none; background: #eaf7ee; border: 1px solid #bfe3c9; color: #1f6b36;
          border-radius: 10px; padding: 12px 14px; margin-top: 16px; font-size: 15px; }
    footer { text-align: center; color: var(--muted); font-size: 13px; padding: 0 20px 40px; }
    @media (max-width: 560px) { h1 { font-size: 26px; } .card { padding: 18px 16px; } }
  </style>
</head>
<body>
<header>
  <div class="wrap">
    <div class="brand"><span class="dot">S</span><?= h($app) ?></div>
    <h1>Delete Account</h1>
    <div class="meta">Last updated: <?= h(PP_LAST_UPDATED) ?></div>
  </div>
</header>

<main class="wrap">

  <section class="card">
    <h2>What happens when you delete your account</h2>
    <p>You can ask <?= h(PP_COMPANY) ?> to delete your <?= h($app) ?> account and the data linked to it.</p>
    <ul>
      <li><b>Deleted:</b> your account details (name, email, profile photo), scan records, scanned card data, card photo links, usage data and device notification token.</li>
      <li><b>Not on our servers:</b> scan history stored only on your phone is removed with <b>Clear history</b> in the app menu, or by uninstalling the app.</li>
      <li><b>Time:</b> we delete your data within a reasonable time after we verify your request.</li>
      <li><b>Permanent:</b> deletion cannot be undone.</li>
    </ul>
  </section>

  <section class="card">
    <h2>Request account deletion</h2>
    <p>Fill the form below. Your email app will open with a ready message addressed to us. Press <b>Send</b> to submit your request.</p>

    <form id="delForm" novalidate>
      <label for="name">Your name</label>
      <input type="text" id="name" name="name" autocomplete="name" maxlength="120" required>
      <div class="err">Please enter your name.</div>

      <label for="email">Account email (the Google email you used to sign in)</label>
      <input type="email" id="email" name="email" autocomplete="email" maxlength="160" required>
      <div class="err">Please enter a valid email.</div>

      <label for="reason">Reason for deleting account</label>
      <textarea id="reason" name="reason" maxlength="1000" required></textarea>
      <div class="err">Please tell us the reason.</div>

      <div class="btns">
        <button type="submit" class="primary">Open email draft</button>
        <button type="button" class="ghost" id="gmailBtn">Open in Gmail (web)</button>
        <button type="button" class="ghost" id="copyBtn">Copy details</button>
      </div>
      <div class="ok" id="okBox"></div>
      <p class="note">Button not working? Send the same details manually to
        <a href="mailto:<?= h(PP_DELETE_EMAIL) ?>"><?= h(PP_DELETE_EMAIL) ?></a>.
        Send the email from the address registered in the app so we can verify it is you.</p>
    </form>
  </section>

  <section class="card">
    <h2>Questions?</h2>
    <p>Read our <a href="privacy-policy.php">Privacy Policy</a> or email
      <a href="mailto:<?= h(PP_DELETE_EMAIL) ?>"><?= h(PP_DELETE_EMAIL) ?></a>.</p>
  </section>

</main>
<footer>© <?= date('Y') ?> <?= h(PP_COMPANY) ?>. All rights reserved.</footer>

<script>
(function () {
  var TO      = <?= json_encode(PP_DELETE_EMAIL) ?>;
  var APP     = <?= json_encode($app) ?>;
  var SUBJECT = APP + ' - Account Deletion Request';

  var f = document.getElementById('delForm');
  var ok = document.getElementById('okBox');
  var fields = {
    name:   document.getElementById('name'),
    email:  document.getElementById('email'),
    reason: document.getElementById('reason')
  };

  function validate() {
    var good = true;
    var emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    Object.keys(fields).forEach(function (k) {
      var el = fields[k], v = el.value.trim(), bad = !v;
      if (k === 'email' && v && !emailRe.test(v)) bad = true;
      el.classList.toggle('invalid', bad);
      if (bad) good = false;
    });
    return good;
  }

  function body() {
    return 'Hello ' + <?= json_encode(PP_COMPANY) ?> + ' team,\n\n' +
      'I request deletion of my ' + APP + ' account and all data linked to it.\n\n' +
      'Name: ' + fields.name.value.trim() + '\n' +
      'Account email: ' + fields.email.value.trim() + '\n' +
      'Reason: ' + fields.reason.value.trim() + '\n\n' +
      'Thank you.';
  }

  function show(msg) { ok.textContent = msg; ok.style.display = 'block'; }

  Object.keys(fields).forEach(function (k) {
    fields[k].addEventListener('input', function () { fields[k].classList.remove('invalid'); });
  });

  // Default mail app draft
  f.addEventListener('submit', function (e) {
    e.preventDefault();
    if (!validate()) return;
    var url = 'mailto:' + TO +
      '?subject=' + encodeURIComponent(SUBJECT) +
      '&body=' + encodeURIComponent(body());
    window.location.href = url;
    show('Email draft opened. Press Send in your email app to submit the request.');
  });

  // Gmail web compose draft
  document.getElementById('gmailBtn').addEventListener('click', function () {
    if (!validate()) return;
    var url = 'https://mail.google.com/mail/?view=cm&fs=1' +
      '&to=' + encodeURIComponent(TO) +
      '&su=' + encodeURIComponent(SUBJECT) +
      '&body=' + encodeURIComponent(body());
    window.open(url, '_blank', 'noopener');
    show('Gmail draft opened in a new tab. Press Send to submit the request.');
  });

  // Copy details fallback
  document.getElementById('copyBtn').addEventListener('click', function () {
    if (!validate()) return;
    var text = 'To: ' + TO + '\nSubject: ' + SUBJECT + '\n\n' + body();
    function done() { show('Details copied. Paste in an email to ' + TO + ' and send.'); }
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(done, fallback);
    } else { fallback(); }
    function fallback() {
      var t = document.createElement('textarea');
      t.value = text; document.body.appendChild(t); t.select();
      try { document.execCommand('copy'); done(); } catch (e) {}
      document.body.removeChild(t);
    }
  });
})();
</script>
</body>
</html>