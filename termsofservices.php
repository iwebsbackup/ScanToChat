<?php
$developer    = "Hemant Kumar";
$company      = "Iwebstechnology";
$supportEmail = "iwebstechnology@gmail.com";
$lastUpdated  = date("F j, Y"); // fixed date chahiye to e.g. "November 6, 2026"
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Terms of Service | <?= htmlspecialchars($company) ?></title>
<meta name="description" content="Terms of Service for apps and services by <?= htmlspecialchars($company) ?>.">
<style>
  :root {
    --bg: #f5f8f7;
    --surface: #ffffff;
    --text: #1d2b27;
    --muted: #5d6e69;
    --accent: #0f7b6c;
    --line: #dce5e2;
  }
  @media (prefers-color-scheme: dark) {
    :root {
      --bg: #101816;
      --surface: #17211e;
      --text: #e6efec;
      --muted: #9bb0aa;
      --accent: #4cc9b0;
      --line: #263632;
    }
  }
  * { box-sizing: border-box; }
  body {
    margin: 0;
    background: var(--bg);
    color: var(--text);
    font-family: "Segoe UI", system-ui, -apple-system, Roboto, Arial, sans-serif;
    line-height: 1.7;
  }
  header { background: var(--accent); color: #fff; padding: 40px 20px; }
  header .wrap, main { max-width: 760px; margin: 0 auto; }
  header h1 { margin: 0 0 6px; font-size: 1.8rem; }
  header p { margin: 0; opacity: .9; font-size: .95rem; }
  main { padding: 28px 20px 60px; }
  section {
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: 10px;
    padding: 20px 24px;
    margin-bottom: 16px;
  }
  h2 { margin: 0 0 8px; font-size: 1.15rem; color: var(--accent); }
  p { margin: 0; }
  p + p { margin-top: 10px; }
  a { color: var(--accent); font-weight: 600; }
  a:focus-visible { outline: 3px solid var(--accent); outline-offset: 2px; }
  footer { text-align: center; color: var(--muted); font-size: .85rem; padding: 0 20px 40px; }
</style>
</head>
<body>

<header>
  <div class="wrap">
    <h1>Terms of Service for Our Apps &amp; Services</h1>
    <p><?= htmlspecialchars($company) ?> &middot; Last updated: <?= htmlspecialchars($lastUpdated) ?></p>
  </div>
</header>

<main>
  <section>
    <h2>1. Acceptance of Terms</h2>
    <p>By downloading, installing, or using any application or service provided by us (the "Developer"), you agree to be bound by these Terms of Service. If you do not agree to these terms, please do not use our applications.</p>
  </section>

  <section>
    <h2>2. Commercial &amp; Trader Status</h2>
    <p>All applications, services, and digital products offered under this developer account are operated in compliance with applicable commercial standards and EU/UK Digital Services Act (DSA) requirements for registered traders.</p>
  </section>

  <section>
    <h2>3. Use of Services</h2>
    <p>You agree to use our applications solely for lawful purposes. You shall not attempt to reverse engineer, decompile, modify, or exploit any portion of the software, APIs, or underlying system architecture.</p>
  </section>

  <section>
    <h2>4. Advertisements and Third-Party Content</h2>
    <p>Our applications may serve advertisements, offer in-app purchases, or integrate third-party services (such as ad mediation networks, analytics platforms, or external content providers). We are not responsible for the privacy practices, content, or availability of any third-party ad networks or linked platforms.</p>
  </section>

  <section>
    <h2>5. Intellectual Property</h2>
    <p>All content, code, graphic assets, trademarks, and logic contained within our applications remain the exclusive intellectual property of the Developer and its respective legal owners.</p>
  </section>

  <section>
    <h2>6. Limitation of Liability</h2>
    <p>Our applications are provided on an "AS IS" and "AS AVAILABLE" basis without warranties of any kind. The Developer is not liable for any direct, indirect, incidental, or consequential damages resulting from the use or inability to use the app.</p>
  </section>

  <section>
    <h2>7. Changes to Terms</h2>
    <p>We reserve the right to modify or update these Terms of Service at any time. Continued use of our applications after any updates constitutes acceptance of the modified terms.</p>
  </section>

  <section>
    <h2>8. Contact Information</h2>
    <p>If you have any questions or concerns regarding these terms, please contact us at our official developer support email listed on the Google Play Store app listing page.</p>
    <p>Developer: <?= htmlspecialchars($developer) ?> (<?= htmlspecialchars($company) ?>)<br>
    Email: <a href="mailto:<?= htmlspecialchars($supportEmail) ?>"><?= htmlspecialchars($supportEmail) ?></a></p>
  </section>
</main>

<footer>
  &copy; <?= date("Y") ?> <?= htmlspecialchars($company) ?>. All rights reserved.
</footer>

</body>
</html>