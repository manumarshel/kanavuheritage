<?php
// /kanav/dashboard/page-local-delights.php — edit all sections; NO BG control
ini_set('display_errors',1); error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

session_start();
if (!isset($_SESSION['id'])) { header("Location: login.php"); exit; }

include __DIR__ . '/../includes/connect.php';
date_default_timezone_set('Asia/Kolkata');

/* uploads */
$UPLOAD_DIR = realpath(__DIR__ . '/../media/local_delights');
if (!$UPLOAD_DIR) { @mkdir(__DIR__ . '/../media/local_delights', 0775, true); $UPLOAD_DIR = realpath(__DIR__ . '/../media/local_delights'); }

/* helpers */
function upsert(mysqli $c, string $k, string $v){
  $stmt=$c->prepare("INSERT INTO local_delights_settings(`key`,`value`) VALUES(?,?)
                     ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)");
  $stmt->bind_param('ss',$k,$v); $stmt->execute(); $stmt->close();
}
function getv(mysqli $c, string $k, string $d=''): string{
  $stmt=$c->prepare("SELECT `value` FROM local_delights_settings WHERE `key`=?");
  $stmt->bind_param('s',$k); $stmt->execute();
  $r=$stmt->get_result(); $row=$r?$r->fetch_assoc():null; $stmt->close();
  return $row?$row['value']:$d;
}
function safe($n){ return preg_replace('/[^a-zA-Z0-9_.-]/','_', $n); }
function save_upload($field,$dir){
  if(empty($_FILES[$field]) || $_FILES[$field]['error']!==UPLOAD_ERR_OK) return null;
  $fn=time().'_'.safe($_FILES[$field]['name']);
  @move_uploaded_file($_FILES[$field]['tmp_name'],$dir.DIRECTORY_SEPARATOR.$fn);
  return $fn;
}
function unlink_if_local($path,$dir,$prefix='media/local_delights/'){
  if($path && strpos($path,$prefix)===0){
    $full=$dir.DIRECTORY_SEPARATOR.basename($path);
    if(is_file($full)) @unlink($full);
  }
}

/* handle POST (no hero action here) */
$flash='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $action = $_POST['action'] ?? '';

  // S1 — Marottikulam (title, p1, img1)
  if($action==='save_s1'){
    upsert($conn,'s1_title', trim($_POST['s1_title'] ?? ''));
    upsert($conn,'s1_p1',    trim($_POST['s1_p1'] ?? ''));
    $cur = getv($conn,'s1_img1','');
    if(!empty($_POST['s1_img1_clear'])){ unlink_if_local($cur,$UPLOAD_DIR); upsert($conn,'s1_img1',''); }
    else{
      $fn=save_upload('s1_img1',$UPLOAD_DIR);
      if($fn){ unlink_if_local($cur,$UPLOAD_DIR); upsert($conn,'s1_img1','media/local_delights/'.$fn); }
      else{ $t=trim($_POST['s1_img1_text'] ?? ''); if($t!=='') upsert($conn,'s1_img1',$t); }
    }
    $flash='Section 1 saved.';
  }

  // S2 — Two-image strip (img1, img2)
  if($action==='save_s2'){
    foreach(['s2_img1','s2_img2'] as $k){
      $cur=getv($conn,$k,'');
      if(!empty($_POST[$k.'_clear'])){ unlink_if_local($cur,$UPLOAD_DIR); upsert($conn,$k,''); }
      else{
        $fn=save_upload($k,$UPLOAD_DIR);
        if($fn){ unlink_if_local($cur,$UPLOAD_DIR); upsert($conn,$k,'media/local_delights/'.$fn); }
        else{ $t=trim($_POST[$k.'_text'] ?? ''); if($t!=='') upsert($conn,$k,$t); }
      }
    }
    $flash='Section 2 saved.';
  }

  // S3 — Thattupara (title, p1, right image)
  if($action==='save_s3'){
    upsert($conn,'s3_title', trim($_POST['s3_title'] ?? ''));
    upsert($conn,'s3_p1',    trim($_POST['s3_p1'] ?? ''));
    $cur=getv($conn,'s3_img_r','');
    if(!empty($_POST['s3_img_r_clear'])){ unlink_if_local($cur,$UPLOAD_DIR); upsert($conn,'s3_img_r',''); }
    else{
      $fn=save_upload('s3_img_r',$UPLOAD_DIR);
      if($fn){ unlink_if_local($cur,$UPLOAD_DIR); upsert($conn,'s3_img_r','media/local_delights/'.$fn); }
      else{ $t=trim($_POST['s3_img_r_text'] ?? ''); if($t!=='') upsert($conn,'s3_img_r',$t); }
    }
    $flash='Section 3 saved.';
  }

  // S4 — Nature Walks (title, p1, img1)
  if($action==='save_s4'){
    upsert($conn,'s4_title', trim($_POST['s4_title'] ?? ''));
    upsert($conn,'s4_p1',    trim($_POST['s4_p1'] ?? ''));
    $cur=getv($conn,'s4_img1','');
    if(!empty($_POST['s4_img1_clear'])){ unlink_if_local($cur,$UPLOAD_DIR); upsert($conn,'s4_img1',''); }
    else{
      $fn=save_upload('s4_img1',$UPLOAD_DIR);
      if($fn){ unlink_if_local($cur,$UPLOAD_DIR); upsert($conn,'s4_img1','media/local_delights/'.$fn); }
      else{ $t=trim($_POST['s4_img1_text'] ?? ''); if($t!=='') upsert($conn,'s4_img1',$t); }
    }
    $flash='Section 4 saved.';
  }

  header("Location: page-local-delights.php?ok=".urlencode($flash));
  exit;
}

/* load defaults */
$s1_title = getv($conn,'s1_title','MAROTTIKULAM WATERSCAPE');
$s1_p1    = getv($conn,'s1_p1',"While KANAVU itself does not feature a swimming pool, our guests can enjoy a refreshing dip in the nearby ‘Marottikulam’ natural pond.");
$s1_img1  = getv($conn,'s1_img1','img/slider/s16.jpg');

$s2_img1  = getv($conn,'s2_img1','img/slider/Thattupara.jpeg');
$s2_img2  = getv($conn,'s2_img2','img/slider/Thattupara3.jpeg');

$s3_title = getv($conn,'s3_title','Thattupara Sunset Wonderland');
$s3_p1    = getv($conn,'s3_p1','Discover the hidden gem of Thattupara — serene trails and breathtaking sunsets.');
$s3_img_r = getv($conn,'s3_img_r','img/slider/Thattupara4.jpeg');

$s4_title = getv($conn,'s4_title','NATURE WALKS');
$s4_p1    = getv($conn,'s4_p1','Embark on invigorating nature walks at Kanav Heritage …');
$s4_img1  = getv($conn,'s4_img1','img/slider/Paddy_field.jpeg');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Experiences · Local Delights (Dashboard)</title>
  <link rel="stylesheet" href="./css/style.css" />
  <style>
    body{background:#f7f8fa}
    .content-wrapper{padding:24px}
    .page-head{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:16px;flex-wrap:wrap}
    .btn{display:inline-flex;align-items:center;gap:8px;padding:9px 12px;border-radius:10px;font-weight:700;border:1px solid #3b2f1b;background:#b19777;color:#111;text-decoration:none;cursor:pointer}
    .btn-lite{background:#fff;border:1px solid #d9d9d9;color:#222;text-decoration:none;padding:9px 12px;border-radius:10px}
    details.card{background:#fff;border:1px solid #e7e0d7;border-radius:14px;overflow:hidden;margin-bottom:12px}
    details.card>summary{list-style:none;cursor:pointer;display:flex;align-items:center;justify-content:space-between;padding:14px 16px;font-weight:700}
    details.card>summary::-webkit-details-marker{display:none}
    .caret{transition:transform .2s}
    details[open] .caret{transform:rotate(180deg)}
    .card-bd{padding:14px;border-top:1px solid #eee}
    .grid{display:grid;gap:14px}
    .two{grid-template-columns:1fr 1fr}
    @media(max-width:920px){.two{grid-template-columns:1fr}}
    .field{display:grid;gap:6px;margin-bottom:12px}
    .field input[type="text"], .field textarea{padding:10px;border:1px solid #ccc;border-radius:10px;width:100%}
    .muted{color:#777;font-size:12px}
    .row-actions{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
  </style>
</head>
<body>
<div class="container">
  <?php include 'includes/sidebar.php'; ?>
  <div class="main">
    <?php include 'includes/topbar.php'; ?>

    <div class="content-wrapper">
      <div class="page-head">
        <h2>Local Delights</h2>
        <div class="actions">
          <a class="btn-lite" href="../experiences.php" target="_blank">Open page</a>
          <a class="btn-lite" href="../" target="_blank">Visit site</a>
        </div>
      </div>

      <?php if(isset($_GET['ok'])): ?>
        <div style="background:#f1fff3;border:1px solid #bfe3c6;padding:8px 12px;border-radius:10px;margin-bottom:12px;color:#205d34">
          <?= htmlspecialchars($_GET['ok']) ?>
        </div>
      <?php endif; ?>

      <!-- S1 -->
      <details class="card" open>
        <summary><span>Section 1 — Marottikulam (Image Left + Text Right)</span><span class="caret">▾</span></summary>
        <div class="card-bd">
          <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save_s1">
            <div class="grid two">
              <div>
                <div class="field"><label>Title</label><input type="text" name="s1_title" value="<?= htmlspecialchars($s1_title) ?>"></div>
                <div class="field"><label>Paragraph</label><textarea name="s1_p1" rows="6"><?= htmlspecialchars($s1_p1) ?></textarea></div>
              </div>
              <div>
                <div class="field"><label>Upload image</label><input type="file" name="s1_img1" accept="image/*"></div>
                <div class="field"><label>Or image path/URL</label><input type="text" name="s1_img1_text"></div>
                <?php if($s1_img1): ?>
                  <div class="row-actions">
                    <div class="muted">Current: <a href="../<?= htmlspecialchars($s1_img1) ?>" target="_blank"><?= htmlspecialchars($s1_img1) ?></a></div>
                    <label><input type="checkbox" name="s1_img1_clear" value="1"> Delete current</label>
                  </div>
                <?php endif; ?>
              </div>
            </div>
            <button class="btn">Save Section 1</button>
          </form>
        </div>
      </details>

      <!-- S2 (two-image strip) -->
      <details class="card">
        <summary><span>Section 2 — Two Images Strip</span><span class="caret">▾</span></summary>
        <div class="card-bd">
          <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save_s2">
            <div class="grid two">
              <div>
                <div class="field"><label>Upload image 1</label><input type="file" name="s2_img1" accept="image/*"></div>
                <div class="field"><label>Or path/URL</label><input type="text" name="s2_img1_text"></div>
                <?php if($s2_img1): ?>
                  <div class="row-actions">
                    <div class="muted">Current: <a href="../<?= htmlspecialchars($s2_img1) ?>" target="_blank"><?= htmlspecialchars($s2_img1) ?></a></div>
                    <label><input type="checkbox" name="s2_img1_clear" value="1"> Delete</label>
                  </div>
                <?php endif; ?>
              </div>
              <div>
                <div class="field"><label>Upload image 2</label><input type="file" name="s2_img2" accept="image/*"></div>
                <div class="field"><label>Or path/URL</label><input type="text" name="s2_img2_text"></div>
                <?php if($s2_img2): ?>
                  <div class="row-actions">
                    <div class="muted">Current: <a href="../<?= htmlspecialchars($s2_img2) ?>" target="_blank"><?= htmlspecialchars($s2_img2) ?></a></div>
                    <label><input type="checkbox" name="s2_img2_clear" value="1"> Delete</label>
                  </div>
                <?php endif; ?>
              </div>
            </div>
            <button class="btn">Save Section 2</button>
          </form>
        </div>
      </details>

      <!-- S3 (Thattupara text + right image) -->
      <details class="card">
        <summary><span>Section 3 — Thattupara (Text + Right Image)</span><span class="caret">▾</span></summary>
        <div class="card-bd">
          <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save_s3">
            <div class="grid two">
              <div>
                <div class="field"><label>Title</label><input type="text" name="s3_title" value="<?= htmlspecialchars($s3_title) ?>"></div>
                <div class="field"><label>Paragraph</label><textarea name="s3_p1" rows="10"><?= htmlspecialchars($s3_p1) ?></textarea></div>
              </div>
              <div>
                <div class="field"><label>Upload right image</label><input type="file" name="s3_img_r" accept="image/*"></div>
                <div class="field"><label>Or path/URL</label><input type="text" name="s3_img_r_text"></div>
                <?php if($s3_img_r): ?>
                  <div class="row-actions">
                    <div class="muted">Current: <a href="../<?= htmlspecialchars($s3_img_r) ?>" target="_blank"><?= htmlspecialchars($s3_img_r) ?></a></div>
                    <label><input type="checkbox" name="s3_img_r_clear" value="1"> Delete</label>
                  </div>
                <?php endif; ?>
              </div>
            </div>
            <button class="btn">Save Section 3</button>
          </form>
        </div>
      </details>

      <!-- S4 (Nature Walks img + text) -->
      <details class="card">
        <summary><span>Section 4 — Nature Walks (Image Left + Text Right)</span><span class="caret">▾</span></summary>
        <div class="card-bd">
          <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save_s4">
            <div class="grid two">
              <div>
                <div class="field"><label>Title</label><input type="text" name="s4_title" value="<?= htmlspecialchars($s4_title) ?>"></div>
                <div class="field"><label>Paragraph</label><textarea name="s4_p1" rows="10"><?= htmlspecialchars($s4_p1) ?></textarea></div>
              </div>
              <div>
                <div class="field"><label>Upload image</label><input type="file" name="s4_img1" accept="image/*"></div>
                <div class="field"><label>Or path/URL</label><input type="text" name="s4_img1_text"></div>
                <?php if($s4_img1): ?>
                  <div class="row-actions">
                    <div class="muted">Current: <a href="../<?= htmlspecialchars($s4_img1) ?>" target="_blank"><?= htmlspecialchars($s4_img1) ?></a></div>
                    <label><input type="checkbox" name="s4_img1_clear" value="1"> Delete</label>
                  </div>
                <?php endif; ?>
              </div>
            </div>
            <button class="btn">Save Section 4</button>
          </form>
        </div>
      </details>

    </div>
  </div>
</div>
<script src="./js/main.js"></script>
</body>
</html>
