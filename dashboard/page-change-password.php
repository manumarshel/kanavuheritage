<?php
session_start();
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

require __DIR__ . '/../includes/connect.php'; // Database connection
date_default_timezone_set('Asia/Kolkata');

// ----------------- Helper Functions -----------------
function col_exists(mysqli $c, string $table, string $col): bool {
    $table_esc = $c->real_escape_string($table);
    $col_esc = $c->real_escape_string($col);
    $res = $c->query("SHOW COLUMNS FROM `$table_esc` LIKE '$col_esc'");
    return ($res && $res->num_rows > 0);
}

function csrf_token() {
    return $_SESSION['csrf'] ?? '';
}

function csrf_ok(string $t = null): bool {
    return hash_equals($_SESSION['csrf'] ?? '', $t ?? '');
}

function set_flash(string $k, string $v): void {
    $_SESSION['flash'][$k] = $v;
}

function get_flash(string $k): ?string {
    $m = $_SESSION['flash'][$k] ?? null;
    if (isset($_SESSION['flash'][$k])) unset($_SESSION['flash'][$k]);
    return $m;
}

function e($s){
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

// Ensure 'admin_users' table exists and password_hash column is present
$c = $conn;
$c->query("CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(190) UNIQUE,
    password_hash VARCHAR(255) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

if (!col_exists($c, 'admin_users', 'password_hash')) {
    $c->query("ALTER TABLE admin_users ADD COLUMN password_hash VARCHAR(255) NULL");
}

// ----------------- POST: Change Password -----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok($_POST['csrf'] ?? '')) {
        set_flash('err', 'Invalid session token. Please try again.');
        header('Location: page-change-password.php');
        exit;
    }

    $curr = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $conf = $_POST['confirm_password'] ?? '';

    // Input validation
    if ($curr === '' || $new === '' || $conf === '') {
        set_flash('err', 'All fields are required.');
        header('Location: page-change-password.php');
        exit;
    }
    if ($new !== $conf) {
        set_flash('err', 'New password and Confirm password do not match.');
        header('Location: page-change-password.php');
        exit;
    }
    if (strlen($new) < 8) {
        set_flash('err', 'Password must be at least 8 characters.');
        header('Location: page-change-password.php');
        exit;
    }

    // Get current user
    $user_id = (int)$_SESSION['id'];

    // Fetch the modern hash
    $stmt = $c->prepare("SELECT password_hash FROM admin_users WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $stored_hash = $row['password_hash'] ?? null;

    $verified = false;

    if ($stored_hash) {
        // Verify password if hashed
        $verified = password_verify($curr, $stored_hash);
    } else {
        // Check legacy password column
        $legacy = null;
        $has_legacy = col_exists($c, 'admin_users', 'password');
        if ($has_legacy) {
            $stmt = $c->prepare("SELECT password FROM admin_users WHERE id = ? LIMIT 1");
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $legacyRow = $stmt->get_result()->fetch_assoc();
            $legacy_plain = $legacyRow['password'] ?? null;
            $stmt->close();

            // If legacy plain password exists, compare it
            if ($legacy_plain !== null && hash_equals($legacy_plain, $curr)) {
                $verified = true;
                // Migrate to hashed password
                $upgrade_hash = password_hash($curr, PASSWORD_BCRYPT);
                $u = $c->prepare("UPDATE admin_users SET password_hash = ? WHERE id = ?");
                $u->bind_param('si', $upgrade_hash, $user_id);
                $u->execute();
                $u->close();
            }
        }
    }

    if (!$verified) {
        set_flash('err', 'Current password is incorrect.');
        header('Location: page-change-password.php');
        exit;
    }

    // Update password with new hash
    $new_hash = password_hash($new, PASSWORD_BCRYPT);
    $u = $c->prepare("UPDATE admin_users SET password_hash = ? WHERE id = ?");
    $u->bind_param('si', $new_hash, $user_id);
    $u->execute();
    $u->close();

    set_flash('ok', 'Password updated successfully.');
    header('Location: page-change-password.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Password</title>
    <link rel="stylesheet" href="./css/style.css">
    <style>
        body{background:#f7f8fa}
        .content-wrapper{padding:28px}
        .card{background:#fff;border:1px solid #e7e0d7;border-radius:14px;overflow:hidden}
        .card-hd{padding:14px 16px;border-bottom:1px solid #eee;font-weight:800}
        .card-bd{padding:16px}
        label{font-weight:700;margin-top:8px;display:block}
        input[type=password]{width:100%;padding:10px;border:1px solid #ccc;border-radius:10px}
        .btn{display:inline-flex;align-items:center;gap:8px;padding:9px 12px;border-radius:10px;font-weight:700;border:1px solid #3b2f1b;background:#b19777;color:#111;text-decoration:none;cursor:pointer}
        .alert{padding:10px 12px;border-radius:10px;margin-bottom:12px}
        .ok{background:#f1fff3;border:1px solid #bfe3c6;color:#205d34}
        .err{background:#fff6f6;border:1px solid #f2c1c1;color:#9b2d2d}
    </style>
</head>
<body>
    <div class="container">
        <?php include 'includes/sidebar.php'; ?>
        <div class="main">
            <?php include 'includes/topbar.php'; ?>
            <div class="content-wrapper">
                <?php if ($m = get_flash('ok')): ?>
                    <div class="alert ok"><?= e($m) ?></div>
                <?php endif; ?>
                <?php if ($m = get_flash('err')): ?>
                    <div class="alert err"><?= e($m) ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-hd">Change Password</div>
                    <div class="card-bd">
                        <form method="post" autocomplete="off">
                            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                            <label for="current_password">Current password</label>
                            <input id="current_password" type="password" name="current_password" required>

                            <label for="new_password">New password</label>
                            <input id="new_password" type="password" name="new_password" required>

                            <label for="confirm_password">Confirm new password</label>
                            <input id="confirm_password" type="password" name="confirm_password" required>

                            <div style="margin-top:12px">
                                <button class="btn" type="submit">Update Password</button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <script src="./js/main.js"></script>
</body>
</html>
