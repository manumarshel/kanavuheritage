<?php
// /kanav/dashboard/page-vicinity
ini_set('display_errors',1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

session_start();
if (!isset($_SESSION['id'])) { header("Location: login.php"); exit(); }

include '../includes/connect.php';
date_default_timezone_set('Asia/Kolkata');

/* Upload dir */
$UPLOAD_DIR = realpath(__DIR__ . '/../media/vicinity');
if (!$UPLOAD_DIR) {
  @mkdir(__DIR__ . '/../media/vicinity', 0775, true);
  $UPLOAD_DIR = realpath(__DIR__ . '/../media/vicinity');
}

/* Helpers */
function upsert($conn,$key,$val){
  $stmt=$conn->prepare("INSERT INTO vicinity_settings(`key`,`value`) VALUES (?,?)
    ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)");
  $stmt->bind_param('ss',$key,$val); $stmt->execute(); $stmt->close();
}
function getv($conn,$key,$default=''){
  $stmt=$conn->prepare("SELECT `value` FROM vicinity_settings WHERE `key`=?");
  $stmt->bind_param('s',$key); $stmt->execute();
  $res=$stmt->get_result(); $row=$res?$res->fetch_assoc():null; $stmt->close();
  return $row?$row['value']:$default;
}
function safe_name($n){ return preg_replace('/[^a-zA-Z0-9_.-]/','_', $n); }
function save_upload($field,$UPLOAD_DIR){
  if (!isset($_FILES[$field]) || $_FILES[$field]['error']!==UPLOAD_ERR_OK) return null;
  $fn = time().'_'.safe_name($_FILES[$field]['name']);
  @move_uploaded_file($_FILES[$field]['tmp_name'], $UPLOAD_DIR.DIRECTORY_SEPARATOR.$fn);
  return $fn;
}
function unlink_if_local($path,$UPLOAD_DIR){
  if ($path && strpos($path,'media/vicinity/')===0){
    $full=$UPLOAD_DIR.DIRECTORY_SEPARATOR.basename($path);
    if (is_file($full)) @unlink($full);
  }
}

/* POST */
if ($_SERVER['REQUEST_METHOD']==='POST'){
  $action = $_POST['action'] ?? '';

  if ($action==='save_hero'){
    $cur = getv($conn,'hero_bg','');
    if (!empty($_POST['hero_clear'])){
      unlink_if_local($cur,$UPLOAD_DIR);
      upsert($conn,'hero_bg','');
    } else {
      $fn = save_upload('hero_bg',$UPLOAD_DIR);
      if ($fn){ unlink_if_local($cur,$UPLOAD_DIR); upsert($conn,'hero_bg','media/vicinity/'.$fn); }
      else {
        $typed = trim($_POST['hero_bg_text'] ?? '');
        if ($typed!=='') upsert($conn,'hero_bg',$typed);
      }
    }
    header("Location: page-vicinity?ok=Hero%20saved"); exit;
  }

  // Generic section saver: expects s{N}_title, s{N}_p1, s{N}_img (or _img_left/_img_right1/_img_right2 for s10)
  if (strpos($action,'save_s')===0){
    $sec = substr($action,6);

    // texts
    foreach ($_POST as $k=>$v){
      if (preg_match('/^(s'.$sec.'_(title|p1))$/',$k)){
        upsert($conn,$k,trim($v));
      }
    }

    // image(s)
    $imgKeys = ['img']; // default
    if ($sec==='10'){ $imgKeys=['img_left','img_right1','img_right2']; }

    foreach ($imgKeys as $suffix){
      $imgKey = 's'.$sec.'_'.$suffix;
      $cur = getv($conn,$imgKey,'');
      if (!empty($_POST[$imgKey.'_clear'])){
        unlink_if_local($cur,$UPLOAD_DIR);
        upsert($conn,$imgKey,'');
      } else {
        $fn = save_upload($imgKey,$UPLOAD_DIR);
        if ($fn){ unlink_if_local($cur,$UPLOAD_DIR); upsert($conn,$imgKey,'media/vicinity/'.$fn); }
        else {
          $typed = trim($_POST[$imgKey.'_text'] ?? '');
          if ($typed!=='') upsert($conn,$imgKey,$typed);
        }
      }
    }

    header("Location: page-vicinity?ok=Section%20$sec%20saved"); exit;
  }
}

/* LOAD */
$hero_bg = getv($conn,'hero_bg','img/gallery/01.jpg');
function g($k,$d=''){ global $conn; return getv($conn,$k,$d); }

for ($i=1;$i<=12;$i++){
  ${"s{$i}_title"} = g("s{$i}_title",'');
  ${"s{$i}_p1"}    = g("s{$i}_p1",'');
}
$s1_img=g('s1_img','img/slider/Mahagony.jpg');
$s2_img=g('s2_img','img/slider/Malayattoor_Church.jpg');
$s3_img=g('s3_img','img/slider/Kurishumdi.jpg');
$s4_img=g('s4_img','img/slider/Trees.jpg');
$s5_img=g('s5_img','img/slider/Paniyeli_Poru.jpg');
$s6_img=g('s6_img','img/slider/Iringole_Kavu.jpg');
$s7_img=g('s7_img','img/slider/Kodanad.jpg');
$s8_img=g('s8_img','img/slider/k1.jpg');
$s9_img=g('s9_img','img/slider/k2.jpg');
$s10_img_left = g('s10_img_left','img/slider/l1.jpg');
$s10_img_right1 = g('s10_img_right1','img/slider/l2.jpg');
$s10_img_right2 = g('s10_img_right2','img/slider/l3.jpg');
$s11_img=g('s11_img','img/slider/Water_Falls.jpg');
$s12_img=g('s12_img','img/slider/EZHATTUMUGHAM.jpg');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Stay · Vicinity Attractions</title>
  <link rel="stylesheet" href="./css/style.css">
  <style>
    body{background:#f7f8fa}
    .content-wrapper{padding:24px}
    .page-head{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:16px;flex-wrap:wrap}
    .page-title h2{margin:0}
    .actions{display:flex;gap:8px;flex-wrap:wrap}
    .btn{display:inline-flex;align-items:center;gap:8px;padding:9px 12px;border-radius:10px;font-weight:700;border:1px solid #3b2f1b;background:#b19777;color:#111;text-decoration:none;cursor:pointer}
    .btn-lite{background:#fff;border:1px solid #d9d9d9;color:#222;cursor:pointer}
    .grid{display:grid;gap:14px}
    .two{grid-template-columns:1fr 1fr}
    @media(max-width:980px){.two{grid-template-columns:1fr}}
    .card{background:#fff;border:1px solid #e7e0d7;border-radius:14px;padding:14px}
    .field{display:grid;gap:6px;margin-bottom:10px}
    .field input[type="text"],.field textarea{padding:10px;border:1px solid #ccc;border-radius:10px;width:100%}
    .muted{color:#777;font-size:12px}
    .wrap{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    @media(max-width:1200px){.wrap{grid-template-columns:1fr}}
    .row-actions{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
    .section-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:12px}
    @media(max-width:1200px){.section-grid{grid-template-columns:1fr}}
  </style>
</head>
<body>
<div class="container">
  <?php include('includes/sidebar.php'); ?>
  <div class="main">
    <?php include('includes/topbar.php'); ?>

    <div class="content-wrapper">
      <div class="page-head">
        <div class="page-title"><h2>Vicinity Attractions</h2></div>
        <div class="actions">
          <a class="btn-lite" href="../vicinity" target="_blank">Open page</a>
          <a class="btn-lite" href="../" target="_blank">Visit site</a>
        </div>
      </div>

      <?php if(isset($_GET['ok'])): ?>
        <div style="background:#f1fff3;border:1px solid #bfe3c6;padding:8px 12px;border-radius:10px;margin-bottom:12px;color:#205d34">
          <?= htmlspecialchars($_GET['ok']) ?>
        </div>
      <?php endif; ?>

      <!-- HERO -->
      <div class="card">
        <h3 style="margin:0 0 8px">Hero banner</h3>
        <form method="post" enctype="multipart/form-data" class="grid two">
          <input type="hidden" name="action" value="save_hero">
          <div class="field">
            <label>Upload hero image</label>
            <input type="file" name="hero_bg" accept="image/*">
            <div class="muted">Wide image (e.g. 1920×1080)</div>
          </div>
          <div class="field">
            <label>Or paste an image path/URL</label>
            <input type="text" name="hero_bg_text" placeholder="e.g. img/gallery/01.jpg">
          </div>
          <div class="row-actions" style="grid-column:1/-1">
            <div class="muted">Current: <a href="../<?= htmlspecialchars($hero_bg) ?>" target="_blank"><?= htmlspecialchars($hero_bg) ?></a></div>
            <label><input type="checkbox" name="hero_clear" value="1"> Delete current image</label>
            <button class="btn">Save Hero</button>
          </div>
        </form>
      </div>

      <!-- Sections in a 2-column grid (simple, horizontal scanning) -->
      <div class="section-grid">
        <?php
          // config array to render cards
          $sections = [
            1=>['title'=>$s1_title,'p1'=>$s1_p1,'img'=>$s1_img],
            2=>['title'=>$s2_title,'p1'=>$s2_p1,'img'=>$s2_img],
            3=>['title'=>$s3_title,'p1'=>$s3_p1,'img'=>$s3_img],
            4=>['title'=>$s4_title,'p1'=>$s4_p1,'img'=>$s4_img],
            5=>['title'=>$s5_title,'p1'=>$s5_p1,'img'=>$s5_img],
            6=>['title'=>$s6_title,'p1'=>$s6_p1,'img'=>$s6_img],
            7=>['title'=>$s7_title,'p1'=>$s7_p1,'img'=>$s7_img],
            8=>['title'=>$s8_title,'p1'=>$s8_p1,'img'=>$s8_img],
            9=>['title'=>$s9_title,'p1'=>$s9_p1,'img'=>$s9_img],
          ];
          foreach ($sections as $i=>$S):
        ?>
        <div class="card">
          <h3 style="margin:0 0 8px">Section <?= $i ?></h3>
          <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save_s<?= $i ?>">
            <div class="grid two">
              <div>
                <div class="field"><label>Title</label><input type="text" name="s<?= $i ?>_title" value="<?= htmlspecialchars($S['title']) ?>"></div>
                <div class="field"><label>Paragraph</label><textarea name="s<?= $i ?>_p1" rows="5"><?= htmlspecialchars($S['p1']) ?></textarea></div>
              </div>
              <div>
                <div class="field"><label>Upload image</label><input type="file" name="s<?= $i ?>_img" accept="image/*"></div>
                <div class="field"><label>Or image path/URL</label><input type="text" name="s<?= $i ?>_img_text" placeholder="e.g. media/vicinity/s<?= $i ?>.jpg"></div>
                <div class="row-actions">
                  <div class="muted">Current: <a href="../<?= htmlspecialchars($S['img']) ?>" target="_blank"><?= htmlspecialchars($S['img']) ?></a></div>
                  <label><input type="checkbox" name="s<?= $i ?>_img_clear" value="1"> Delete current image</label>
                </div>
              </div>
            </div>
            <button class="btn">Save Section <?= $i ?></button>
          </form>
        </div>
        <?php endforeach; ?>

        <!-- Special Section 10 (has 3 images) -->
        <div class="card">
          <h3 style="margin:0 0 8px">Section 10 — Abhayaranyam (left + two right images)</h3>
          <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save_s10">
            <div class="grid two">
              <div>
                <div class="field"><label>Title</label><input type="text" name="s10_title" value="<?= htmlspecialchars($s10_title) ?>"></div>
                <div class="field"><label>Paragraph</label><textarea name="s10_p1" rows="6"><?= htmlspecialchars($s10_p1) ?></textarea></div>
              </div>
              <div>
                <div class="field"><label>Left image</label><input type="file" name="s10_img_left" accept="image/*"></div>
                <div class="field"><label>Left image path/URL</label><input type="text" name="s10_img_left_text" placeholder="e.g. media/vicinity/s10_left.jpg"></div>
                <div class="muted">Current Left: <a href="../<?= htmlspecialchars($s10_img_left) ?>" target="_blank"><?= htmlspecialchars($s10_img_left) ?></a> &nbsp; <label><input type="checkbox" name="s10_img_left_clear" value="1"> Delete</label></div>
                <hr>
                <div class="field"><label>Right image 1</label><input type="file" name="s10_img_right1" accept="image/*"></div>
                <div class="field"><label>Right image 1 URL</label><input type="text" name="s10_img_right1_text" placeholder=""></div>
                <div class="muted">Current R1: <a href="../<?= htmlspecialchars($s10_img_right1) ?>" target="_blank"><?= htmlspecialchars($s10_img_right1) ?></a> &nbsp; <label><input type="checkbox" name="s10_img_right1_clear" value="1"> Delete</label></div>
                <hr>
                <div class="field"><label>Right image 2</label><input type="file" name="s10_img_right2" accept="image/*"></div>
                <div class="field"><label>Right image 2 URL</label><input type="text" name="s10_img_right2_text" placeholder=""></div>
                <div class="muted">Current R2: <a href="../<?= htmlspecialchars($s10_img_right2) ?>" target="_blank"><?= htmlspecialchars($s10_img_right2) ?></a> &nbsp; <label><input type="checkbox" name="s10_img_right2_clear" value="1"> Delete</label></div>
              </div>
            </div>
            <button class="btn">Save Section 10</button>
          </form>
        </div>

        <!-- Sections 11 & 12 -->
        <?php for ($i=11;$i<=12;$i++): ?>
        <div class="card">
          <h3 style="margin:0 0 8px">Section <?= $i ?></h3>
          <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save_s<?= $i ?>">
            <div class="grid two">
              <div>
                <div class="field"><label>Title</label><input type="text" name="s<?= $i ?>_title" value="<?= htmlspecialchars(${"s{$i}_title"}) ?>"></div>
                <div class="field"><label>Paragraph</label><textarea name="s<?= $i ?>_p1" rows="5"><?= htmlspecialchars(${"s{$i}_p1"}) ?></textarea></div>
              </div>
              <div>
                <div class="field"><label>Upload image</label><input type="file" name="s<?= $i ?>_img" accept="image/*"></div>
                <div class="field"><label>Or image path/URL</label><input type="text" name="s<?= $i ?>_img_text" placeholder="e.g. media/vicinity/s<?= $i ?>.jpg"></div>
                <div class="row-actions">
                  <div class="muted">Current: <a href="../<?= htmlspecialchars(${"s{$i}_img"}) ?>" target="_blank"><?= htmlspecialchars(${"s{$i}_img"}) ?></a></div>
                  <label><input type="checkbox" name="s<?= $i ?>_img_clear" value="1"> Delete current image</label>
                </div>
              </div>
            </div>
            <button class="btn">Save Section <?= $i ?></button>
          </form>
        </div>
        <?php endfor; ?>

      </div><!-- /.section-grid -->

    </div>
  </div>
</div>

<script src="./js/main.js"></script>
</body>
</html>
