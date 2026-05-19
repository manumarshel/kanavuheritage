<?php
// /dashboard/page-admin-users.php
ini_set('display_errors',1); error_reporting(E_ALL); mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
session_start(); if(!isset($_SESSION['id'])){ header("Location: login.php"); exit(); }

include __DIR__ . '/../includes/connect.php';
date_default_timezone_set('Asia/Kolkata');

if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
function csrf(){ return $_SESSION['csrf']; }
function csrf_ok($t){ return hash_equals($_SESSION['csrf']??'', $t??''); }
$flash=function($k,$v=null){ if($v!==null){ $_SESSION['flash'][$k]=$v; return; } $m=$_SESSION['flash'][$k]??null; unset($_SESSION['flash'][$k]); return $m; };

/* ---------- Actions ---------- */
if($_SERVER['REQUEST_METHOD']==='POST'){
  if(!csrf_ok($_POST['csrf']??'')){ $flash('err','Invalid token'); header('Location: page-admin-users.php'); exit; }
  $action = $_POST['action'] ?? '';

  if($action==='create'){
    $full = trim($_POST['full_name']??'');
    $user = trim($_POST['username']??'');
    $email= trim($_POST['email']??'');
    $role = in_array($_POST['role']??'editor', ['super','editor']) ? $_POST['role'] : 'editor';
    $pass = $_POST['password']??'';
    if($full && $user && $email && $pass){
      $hash = password_hash($pass, PASSWORD_BCRYPT);
      $stmt = $conn->prepare("INSERT INTO admin_users(full_name,username,email,password_hash,role) VALUES(?,?,?,?,?)");
      $stmt->bind_param('sssss',$full,$user,$email,$hash,$role); $stmt->execute();
      $flash('ok','Admin created.');
    } else { $flash('err','All fields are required.'); }
    header('Location: page-admin-users.php'); exit;
  }

  if($action==='update'){
    $id   = (int)($_POST['id']??0);
    $full = trim($_POST['full_name']??'');
    $user = trim($_POST['username']??'');
    $email= trim($_POST['email']??'');
    $role = in_array($_POST['role']??'editor', ['super','editor']) ? $_POST['role'] : 'editor';
    $active = isset($_POST['is_active']) ? 1 : 0;

    $stmt = $conn->prepare("UPDATE admin_users SET full_name=?, username=?, email=?, role=?, is_active=? WHERE id=?");
    $stmt->bind_param('ssssii',$full,$user,$email,$role,$active,$id); $stmt->execute();
    $flash('ok','Admin updated.');
    header('Location: page-admin-users.php'); exit;
  }

  if($action==='reset_pass'){
    $id = (int)($_POST['id']??0);
    $pass = $_POST['new_password']??'';
    if($pass){
      $hash = password_hash($pass, PASSWORD_BCRYPT);
      $stmt=$conn->prepare("UPDATE admin_users SET password_hash=? WHERE id=?");
      $stmt->bind_param('si',$hash,$id); $stmt->execute();
      $flash('ok','Password reset.');
    } else { $flash('err','Password cannot be empty.'); }
    header('Location: page-admin-users.php'); exit;
  }
}

if(isset($_GET['delete'], $_GET['csrf'])){
  if(!csrf_ok($_GET['csrf'])){ $flash('err','Invalid token'); header('Location: page-admin-users.php'); exit; }
  $id=(int)$_GET['delete'];
  // optional: prevent deleting yourself
  if($id==(int)$_SESSION['id']){ $flash('err','You cannot delete your own account.'); header('Location: page-admin-users.php'); exit; }
  $conn->query("DELETE FROM admin_users WHERE id={$id} LIMIT 1");
  $flash('ok','Deleted.');
  header('Location: page-admin-users.php'); exit;
}

$list = $conn->query("SELECT * FROM admin_users ORDER BY id DESC");
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><title>Admin Users | Dashboard</title>
<link rel="stylesheet" href="./css/style.css">
<style>
  body{background:#f7f8fa}
  .content-wrapper{padding:28px}
  .page-head{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:16px}
  .page-title h2{margin:0}
  .grid{display:grid;gap:14px}
  .two{grid-template-columns:1fr 1fr}
  @media(max-width:900px){.two{grid-template-columns:1fr}}
  .card{background:#fff;border:1px solid #e7e0d7;border-radius:14px;overflow:hidden}
  .card-hd{display:flex;justify-content:space-between;align-items:center;padding:14px 16px;border-bottom:1px solid #eee}
  .card-bd{padding:16px}
  .table{width:100%;border-collapse:collapse}
  .table th,.table td{padding:10px;border-bottom:1px solid #eee;text-align:left}
  .muted{color:#777}
  .actions{display:flex;gap:8px;flex-wrap:wrap}
  .btn{display:inline-flex;align-items:center;gap:8px;padding:9px 12px;border-radius:10px;font-weight:700;border:1px solid #3b2f1b;background:#b19777;color:#111;text-decoration:none;cursor:pointer}
  .btn-lite{background:#fff;border:1px solid #d9d9d9;color:#222}
  input[type=text],input[type=email],input[type=password],select{width:100%;padding:10px;border:1px solid #ccc;border-radius:10px}
  label{font-weight:700;margin-top:8px;display:block}
</style>
</head><body>
<div class="container">
  <?php include 'includes/sidebar.php'; ?>
  <div class="main">
    <?php include 'includes/topbar.php'; ?>
    <div class="content-wrapper">
      <div class="page-head">
        <div class="page-title"><h2>Admin Users</h2></div>
        <div class="actions">
          <a class="btn-lite" href="photo">Back to Users & Settings</a>
        </div>
      </div>

      <?php if($m=$flash('ok')): ?><div style="background:#f1fff3;border:1px solid #bfe3c6;padding:8px 12px;border-radius:10px;margin-bottom:12px;color:#205d34"><?= htmlspecialchars($m) ?></div><?php endif; ?>
      <?php if($m=$flash('err')): ?><div style="background:#fff6f6;border:1px solid #f2c1c1;padding:8px 12px;border-radius:10px;margin-bottom:12px;color:#9b2d2d"><?= htmlspecialchars($m) ?></div><?php endif; ?>

      <!-- Create -->
      <div class="card" style="margin-bottom:12px">
        <div class="card-hd"><strong>Add New Admin</strong></div>
        <div class="card-bd">
          <form method="post" class="grid two">
            <input type="hidden" name="csrf" value="<?= csrf() ?>">
            <input type="hidden" name="action" value="create">
            <div>
              <label>Full Name</label>
              <input type="text" name="full_name" required>
            </div>
            <div>
              <label>Username</label>
              <input type="text" name="username" required>
            </div>
            <div>
              <label>Email</label>
              <input type="email" name="email" required>
            </div>
            <div>
              <label>Role</label>
              <select name="role"><option value="editor">Editor</option><option value="super">Super</option></select>
            </div>
            <div>
              <label>Password</label>
              <input type="password" name="password" required>
            </div>
            <div style="align-self:end">
              <button class="btn">Create</button>
            </div>
          </form>
        </div>
      </div>

      <!-- List -->
      <div class="card">
        <div class="card-hd"><strong>All Admins</strong></div>
        <div class="card-bd">
          <table class="table">
            <thead><tr><th>#</th><th>Name</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th style="width:230px">Actions</th></tr></thead>
            <tbody>
            <?php if($list->num_rows): while($r=$list->fetch_assoc()): ?>
              <tr>
                <td><?= (int)$r['id'] ?></td>
                <td><?= htmlspecialchars($r['full_name']) ?></td>
                <td><?= htmlspecialchars($r['username']) ?></td>
                <td><?= htmlspecialchars($r['email']) ?></td>
                <td><?= htmlspecialchars($r['role']) ?></td>
                <td><?= $r['is_active']?'Active':'Blocked' ?></td>
                <td class="actions">
                  <!-- Edit inline form -->
                  <form method="post" style="display:inline-grid;grid-template-columns:repeat(6,1fr);gap:6px;align-items:center">
                    <input type="hidden" name="csrf" value="<?= csrf() ?>">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <input type="text"   name="full_name"  value="<?= htmlspecialchars($r['full_name']) ?>" placeholder="Name">
                    <input type="text"   name="username"   value="<?= htmlspecialchars($r['username']) ?>" placeholder="Username">
                    <input type="email"  name="email"      value="<?= htmlspecialchars($r['email']) ?>" placeholder="Email">
                    <select name="role">
                      <option value="editor" <?= $r['role']==='editor'?'selected':'' ?>>Editor</option>
                      <option value="super"  <?= $r['role']==='super'?'selected':'' ?>>Super</option>
                    </select>
                    <label style="display:flex;gap:6px;align-items:center;margin:0;font-weight:600">
                      <input type="checkbox" name="is_active" value="1" <?= $r['is_active']?'checked':'' ?>> Active
                    </label>
                    <button class="btn-lite">Save</button>
                  </form>
                  <!-- Reset pass small form -->
                  <form method="post" style="display:inline-flex;gap:6px;align-items:center;margin-left:6px">
                    <input type="hidden" name="csrf" value="<?= csrf() ?>">
                    <input type="hidden" name="action" value="reset_pass">
                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <input type="password" name="new_password" placeholder="New Password">
                    <button class="btn-lite">Reset</button>
                  </form>
                  <a class="btn-lite" style="color:#b00020;border-color:#e4b6b6;margin-left:6px"
                     onclick="return confirm('Delete this admin?')"
                     href="page-admin-users.php?delete=<?= (int)$r['id'] ?>&csrf=<?= csrf() ?>">Delete</a>
                </td>
              </tr>
            <?php endwhile; else: ?>
              <tr><td colspan="7" class="muted">No admins yet.</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="./js/main.js"></script>
</body></html>
