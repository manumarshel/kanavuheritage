<?php
// Standalone thank-you page. No includes, no DB, no dependencies.
// Works at domain root or inside /kanav.

$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
if ($basePath === '/' || $basePath === '\\') { $basePath = ''; }

/**
 * ✅ FIX: your server does NOT support /contact (pretty URL)
 * It works only as /contact.php (as per your screenshot).
 */
$contactUrl = $basePath . '/contact.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Thank You — Kanavu Heritage</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <!-- ✅ redirect to contact.php -->
  <meta http-equiv="refresh" content="5;url=<?= htmlspecialchars($contactUrl, ENT_QUOTES, 'UTF-8') ?>">

  <style>
    :root { --bg:#fafdfb; --panel:#f7fff9; --border:#cfe9d5; --text:#1f2937; --accent:#205d34; }
    html,body { margin:0; padding:0; background:var(--bg); color:var(--text); font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,'Helvetica Neue',Arial,'Noto Sans',sans-serif; }
    .wrap { min-height:100vh; display:flex; align-items:center; justify-content:center; padding:24px; }
    .card {
      width:min(720px, 92vw);
      background:var(--panel);
      border:1px solid var(--border);
      border-radius:16px;
      padding:40px 28px;
      box-shadow:0 6px 14px rgba(0,0,0,.06);
      text-align:center;
      animation:pop .25s ease-out;
    }
    h1 { margin:0 0 10px; font-size:28px; color:var(--accent); }
    p  { margin:6px 0; line-height:1.5; }
    .muted { color:#6b7280; }
    .btn {
      display:inline-block; margin-top:14px; padding:10px 18px; border-radius:10px;
      text-decoration:none; background:#b19777; color:#fff; font-weight:600;
    }
    @keyframes pop { from { transform:translateY(8px); opacity:0 } to { transform:none; opacity:1 } }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="card" role="status" aria-live="polite">
      <h1>Thank you!</h1>
      <p>Your enquiry has been sent. We’ll get back to you soon.</p>
      <p class="muted">You’ll be redirected to the contact page in <strong>5 seconds</strong>…</p>
      <p><a class="btn" href="<?= htmlspecialchars($contactUrl, ENT_QUOTES, 'UTF-8') ?>">Go back now</a></p>
    </div>
  </div>

  <script>
    // JS fallback in case meta refresh is blocked
    setTimeout(function () {
      window.location.href = "<?= htmlspecialchars($contactUrl, ENT_QUOTES, 'UTF-8') ?>";
    }, 5000);
  </script>
</body>
</html>
