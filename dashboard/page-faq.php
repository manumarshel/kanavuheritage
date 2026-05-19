<?php
// /kanav/dashboard/page-faq
session_start();
if (!isset($_SESSION['id'])) { header("Location: login.php"); exit(); }
include __DIR__.'/../includes/connect.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
date_default_timezone_set('Asia/Kolkata');

function e($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

/* Actions */
if ($_SERVER['REQUEST_METHOD']==='POST'){
  $act = $_POST['act'] ?? '';

  if ($act==='add'){
    $q = trim($_POST['question'] ?? '');
    $a = trim($_POST['answer'] ?? '');
    if ($q!=='' && $a!==''){
      // pick max sort + 10
      $max=1000;
      $r = $conn->query("SELECT COALESCE(MAX(sort),0) m FROM faqs");
      if($row=$r->fetch_assoc()) $max = (int)$row['m'] + 10;
      $stmt=$conn->prepare("INSERT INTO faqs(question,answer,sort,is_active) VALUES (?,?,?,1)");
      $stmt->bind_param('ssi',$q,$a,$max); $stmt->execute(); $stmt->close();
    }
    header("Location: page-faq?ok=Added"); exit;
  }

  if ($act==='update'){
    $id = (int)$_POST['id'];
    $q = trim($_POST['question'] ?? '');
    $a = trim($_POST['answer'] ?? '');
    $stmt=$conn->prepare("UPDATE faqs SET question=?, answer=? WHERE id=?");
    $stmt->bind_param('ssi',$q,$a,$id); $stmt->execute(); $stmt->close();
    header("Location: page-faq?ok=Saved#row$id"); exit;
  }

  if ($act==='delete'){
    $id = (int)$_POST['id'];
    $stmt=$conn->prepare("DELETE FROM faqs WHERE id=?");
    $stmt->bind_param('i',$id); $stmt->execute(); $stmt->close();
    header("Location: page-faq?ok=Deleted"); exit;
  }

  if ($act==='toggle'){
    $id = (int)$_POST['id'];
    $stmt=$conn->prepare("UPDATE faqs SET is_active=1-is_active WHERE id=?");
    $stmt->bind_param('i',$id); $stmt->execute(); $stmt->close();
    header("Location: page-faq?ok=Toggled#row$id"); exit;
  }

  if ($act==='move'){
    $id = (int)$_POST['id'];
    $dir = $_POST['dir'] === 'up' ? 'up' : 'down';

    // get this item
    $stmt=$conn->prepare("SELECT id, sort FROM faqs WHERE id=?");
    $stmt->bind_param('i',$id); $stmt->execute();
    $r=$stmt->get_result(); $me=$r->fetch_assoc(); $stmt->close();
    if ($me){
      if ($dir==='up'){
        $stmt=$conn->prepare("SELECT id, sort FROM faqs WHERE sort < ? ORDER BY sort DESC LIMIT 1");
        $stmt->bind_param('i',$me['sort']);
      } else {
        $stmt=$conn->prepare("SELECT id, sort FROM faqs WHERE sort > ? ORDER BY sort ASC LIMIT 1");
        $stmt->bind_param('i',$me['sort']);
      }
      $stmt->execute(); $r=$stmt->get_result(); $oth=$r->fetch_assoc(); $stmt->close();
      if ($oth){
        // swap sort
        $conn->begin_transaction();
        $stmt=$conn->prepare("UPDATE faqs SET sort=? WHERE id=?");
        $stmt->bind_param('ii',$oth['sort'],$me['id']); $stmt->execute();
        $stmt->bind_param('ii',$me['sort'],$oth['id']); $stmt->execute();
        $stmt->close();
        $conn->commit();
      }
    }
    header("Location: page-faq?ok=Reordered#row$id"); exit;
  }
}

/* Load */
$rows=[];
$res=$conn->query("SELECT id, question, answer, sort, is_active FROM faqs ORDER BY sort ASC, id ASC");
while($row=$res->fetch_assoc()) $rows[]=$row;

$public = '../faq';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard · FAQs</title>
  <link rel="stylesheet" href="./css/style.css">
  <style>
    .content-wrapper{padding:24px}
    .head{display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;margin-bottom:12px}
    .btn{display:inline-flex;align-items:center;gap:8px;padding:8px 12px;border-radius:8px;border:1px solid #3b2f1b;background:#b19777;color:#111;text-decoration:none;font-weight:700;cursor:pointer}
    .btn-lite{background:#fff;border:1px solid #d8d2c8}
    .grid{display:grid;gap:12px}
    .two{grid-template-columns:1fr 1fr}
    @media(max-width:1000px){.two{grid-template-columns:1fr}}
    .card{background:#fff;border:1px solid #eadfcd;border-radius:12px;padding:12px}
    .field{display:grid;gap:6px;margin-bottom:8px}
    .field input,.field textarea{padding:9px;border:1px solid #ccc;border-radius:10px}
    .row{display:grid;grid-template-columns:60px 1fr;gap:12px;align-items:start}
    .row + .row{margin-top:8px}
    .row .q{font-weight:700}
    .muted{color:#666;font-size:12px}
    .pill{display:inline-flex;gap:6px;align-items:center;padding:6px 9px;border:1px solid #d0c6b7;border-radius:999px;background:#fff;text-decoration:none;color:#333}
    .actions{display:flex;gap:6px;flex-wrap:wrap}
    .hr{height:1px;background:#eee;margin:10px 0}
  </style>
</head>
<body>
<div class="container">
  <?php include 'includes/sidebar.php'; ?>
  <div class="main">
    <?php include 'includes/topbar.php'; ?>
    <div class="content-wrapper">
      <div class="head">
        <h2 style="margin:0">FAQs</h2>
        <div>
          <a class="btn-lite" target="_blank" href="<?= $public ?>">Open page</a>
          <a class="btn-lite" target="_blank" href="<?= $public ?>#q<?= isset($rows[0]['id'])?(int)$rows[0]['id']:'' ?>">Open first item</a>
        </div>
      </div>

      <?php if(isset($_GET['ok'])): ?>
        <div style="background:#f2fff4;border:1px solid #cde8d2;padding:8px 10px;border-radius:10px;margin-bottom:10px;color:#1b6a31">
          <?= e($_GET['ok']) ?>
        </div>
      <?php endif; ?>

      <!-- Add new -->
      <div class="card">
        <h3 style="margin:0 0 8px">Add FAQ</h3>
        <form method="post">
          <input type="hidden" name="act" value="add">
          <div class="grid two">
            <div class="field">
              <label>Question</label>
              <input type="text" name="question" required>
            </div>
            <div class="field">
              <label>Answer</label>
              <textarea name="answer" rows="3" required></textarea>
            </div>
          </div>
          <button class="btn">Add</button>
        </form>
      </div>

      <!-- List -->
      <div class="card" style="margin-top:12px">
        <h3 style="margin:0 0 8px">All FAQs</h3>
        <?php if(!$rows): ?>
          <p class="muted">No FAQs yet.</p>
        <?php else: foreach($rows as $r): ?>
          <div id="row<?= (int)$r['id'] ?>" class="row">
            <div style="text-align:center">
              <form method="post" style="display:inline">
                <input type="hidden" name="act" value="move"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="dir" value="up">
                <button class="pill" title="Move up">▲</button>
              </form>
              <form method="post" style="display:inline">
                <input type="hidden" name="act" value="move"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="dir" value="down">
                <button class="pill" title="Move down">▼</button>
              </form>
            </div>
            <div>
              <div class="q"><?= e($r['question']) ?></div>
              <div class="muted" style="margin:4px 0 6px"><?= mb_strimwidth(strip_tags($r['answer']),0,160,'…','UTF-8') ?></div>

              <div class="actions">
                <a class="pill" target="_blank" href="<?= $public ?>#q<?= (int)$r['id'] ?>">Open on Site</a>

                <!-- Toggle -->
                <form method="post" style="display:inline">
                  <input type="hidden" name="act" value="toggle"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <button class="pill" title="Toggle active"><?= $r['is_active']?'Active':'Inactive' ?></button>
                </form>

                <!-- Edit inline -->
                <details>
                  <summary class="pill">Edit</summary>
                  <form method="post" style="margin-top:8px">
                    <input type="hidden" name="act" value="update">
                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <div class="field"><label>Question</label><input type="text" name="question" value="<?= e($r['question']) ?>" required></div>
                    <div class="field"><label>Answer</label><textarea name="answer" rows="4" required><?= e($r['answer']) ?></textarea></div>
                    <button class="btn">Save</button>
                  </form>
                </details>

                <!-- Delete -->
                <form method="post" onsubmit="return confirm('Delete this FAQ?');" style="display:inline">
                  <input type="hidden" name="act" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <button class="pill" style="border-color:#f2c4c4;color:#b00020">Delete</button>
                </form>
              </div>
              <div class="hr"></div>
            </div>
          </div>
        <?php endforeach; endif; ?>
      </div>

    </div>
  </div>
</div>

<script src="./js/main.js"></script>
</body>
</html>
