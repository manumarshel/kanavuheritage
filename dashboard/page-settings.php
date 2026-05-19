<?php
// /dashboard/page-settings.php
ini_set('display_errors',1); error_reporting(E_ALL); mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
session_start(); if(!isset($_SESSION['id'])){ header("Location: login.php"); exit(); }
include __DIR__ . '/../includes/connect.php';
date_default_timezone_set('Asia/Kolkata');

if (empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(16));
function csrf(){ return $_SESSION['csrf']; }
function csrf_ok($t){ return hash_equals($_SESSION['csrf']??'', $t??''); }
$flash=function($k,$v=null){ if($v!==null){ $_SESSION['flash'][$k]=$v; return; } $m=$_SESSION['flash'][$k]??null; unset($_SESSION['flash'][$k]); return $m; };

function set_setting($conn,$k,$v){
  $stmt=$conn->prepare("INSERT INTO site_settings(`key`,`value`) VALUES(?,?) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)");
  $stmt->bind_param('ss',$k,$v); $stmt->execute();
}
function get_setting($conn,$k,$d=''){
  $s=$conn->prepare("SELECT `value` FROM site_settings WHERE `key`=?"); $s->bind_param('s',$k); $s->execute();
  $r=$s->get_result()->fetch_assoc(); return $r?$r['value']:$d;
}

if($_SERVER['REQUEST_METHOD']==='POST'){
  if(!csrf_ok($_POST['csrf']??'')){ $flash('err','Invalid token'); header('Location: page-settings.php'); exit; }
  set_setting($conn,'site_name', trim($_POST['site_name']??'')); 
  set_setting($conn,'contact_email', trim($_POST['contact_email']??'')); 
  set_setting($conn,'contact_phone', trim($_POST['contact_phone']??'')); 
  set_setting($conn,'address', trim($_POST['address']??'')); 
  set_setting($conn,'whatsapp', trim($_POST['whatsapp']??'')); 
  $flash('ok','Settings saved.');
  header('Location: page-settings.php'); exit;
}

$site_name = get_setting($conn,'site_name','Kanavu Heritage');
$contact_email = get_setting($conn,'contact_email','info@example.com');
$contact_phone = get_setting($conn,'contact_phone','+91-00000 00000');
$address = get_setting($conn,'address','');
$whatsapp = get_setting($conn,'whatsapp','https://wa.me/91XXXXXXXXXX');
?>
<!DOCTYPE html>
<html lang="en"><head>
<meta charset="UTF-8"><title>Settings | Dashboard</title>
<link rel="stylesheet" href="./css/style.css">
<style>
  body{background:#f7f8fa}
  .content-wrapper{padding:28px}
  .card{background:#fff;border:1px solid #e7e0d7;border-radius:14px;overflow:hidden}
  .card-hd{padding:14px 16px;border-bottom:1px solid #eee;font-weight:800}
  .card-bd{padding:16px}
  .grid{display:grid;gap:14px;grid-template-columns:1fr 1fr}
  @media(max-width:900px){.grid{grid-template-columns:1fr}}
  label{font-weight:700;margin-top:8px;display:block}
  input[type=text],input[type=email],textarea{width:100%;padding:10px;border:1px solid #ccc;border-radius:10px}
  .btn{display:inline-flex;align-items:center;gap:8px;padding:9px 12px;border-radius:10px;font-weight:700;border:1px solid #3b2f1b;background:#b19777;color:#111;text-decoration:none;cursor:pointer}
</style>
</head>
<body>
<div class="container">
  <?php include 'includes/sidebar.php'; ?>
  <div class="main">
    <?php include 'includes/topbar.php'; ?>
    <div class="content-wrapper">
      <?php if($m=$flash('ok')): ?><div style="background:#f1fff3;border:1px solid #bfe3c6;padding:8px 12px;border-radius:10px;margin-bottom:12px;color:#205d34"><?= htmlspecialchars($m) ?></div><?php endif; ?>
      <?php if($m=$flash('err')): ?><div style="background:#fff6f6;border:1px solid #f2c1c1;padding:8px 12px;border-radius:10px;margin-bottom:12px;color:#9b2d2d"><?= htmlspecialchars($m) ?></div><?php endif; ?>

      <div class="card">
        <div class="card-hd">Site Settings</div>
        <div class="card-bd">
          <form method="post" class="grid">
            <input type="hidden" name="csrf" value="<?= csrf() ?>">
            <div>
              <label>Site Name</label>
              <input type="text" name="site_name" value="<?= htmlspecialchars($site_name) ?>">
            </div>
            <div>
              <label>Contact Email</label>
              <input type="email" name="contact_email" value="<?= htmlspecialchars($contact_email) ?>">
            </div>
            <div>
              <label>Contact Phone</label>
              <input type="text" name="contact_phone" value="<?= htmlspecialchars($contact_phone) ?>">
            </div>
            <div>
              <label>WhatsApp Link</label>
              <input type="text" name="whatsapp" value="<?= htmlspecialchars($whatsapp) ?>">
            </div>
            <div style="grid-column:1/-1">
              <label>Address</label>
              <textarea name="address" rows="3"><?= htmlspecialchars($address) ?></textarea>
            </div>
            <div style="grid-column:1/-1"><button class="btn">Save Settings</button></div>
          </form>
        </div>
      </div>

    </div>
  </div>
</div>
<script src="./js/main.js"></script>
</body></html>
