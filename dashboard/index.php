<?php
session_start();
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

include '../includes/connect.php';

if (!function_exists('valid_date')) {
    function valid_date($d) {
        $dt = DateTime::createFromFormat('Y-m-d', $d);
        return $dt && $dt->format('Y-m-d') === $d;
    }
}

date_default_timezone_set('Asia/Kolkata');
$default_date = (new DateTime('now'))->format('Y-m-d');
$from = $_GET['from'] ?? $default_date;
$to   = $_GET['to']   ?? $default_date;
if (!valid_date($from)) $from = $default_date;
if (!valid_date($to)) $to = $from;

// ---------- CSV export ----------
if (isset($_GET['export']) && $_GET['export'] == '1') {

    if ($from === $to) {
        $sql = "SELECT name, email, phone, city, enquiry_type, message, created_at
                FROM enquiry
                WHERE DATE(created_at) = ?
                ORDER BY created_at DESC";
        $params = [$from];
    } else {
        $sql = "SELECT name, email, phone, city, enquiry_type, message, created_at
                FROM enquiry
                WHERE DATE(created_at) BETWEEN ? AND ?
                ORDER BY created_at DESC";
        $params = [$from, $to];
    }

    $rows = [];
    if (isset($pdo) && $pdo instanceof PDO) {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif (isset($conn) && ($conn instanceof mysqli)) {
        $stmt = $conn->prepare($sql);
        if (count($params) === 1) $stmt->bind_param('s', $params[0]);
        else $stmt->bind_param('ss', $params[0], $params[1]);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = $res->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }

    // CSV headers
    header('Content-Type: text/csv; charset=utf-8');
    $filename = "enquiries_{$from}" . ($from === $to ? '' : "_to_{$to}") . ".csv";
    header("Content-Disposition: attachment; filename=\"$filename\"");

    $out = fopen('php://output', 'w');

    // header row (renamed Created At → Date)
    fputcsv($out, ['Name','Email','Phone','City','Enquiry Type','Message','Date']);

    foreach ($rows as $r) {
        fputcsv($out, [
            $r['name'],
            $r['email'],
            "\t" . $r['phone'], // force Excel to keep number as text
            $r['city'],
            $r['enquiry_type'],
            $r['message'],
            "\t" . date('Y-m-d', strtotime($r['created_at'])) // force text
        ]);
    }
    fclose($out);
    exit; // stop any HTML
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Enquiry List | Dashboard</title>
    <link rel="stylesheet" href="./css/style.css">
    <script src="./js/main.js"></script>

    <style>
        .content-wrapper { padding: 30px; }
        .top-header { display: flex; justify-content: space-between; align-items: center; gap:12px; flex-wrap:wrap; }
        .top-left { display:flex; gap:12px; align-items:center; }
        .top-left h2 { margin: 0; }
        .controls { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
        .controls label { font-weight:600; color:#333; margin-right:6px; }
        .controls input[type="date"] { padding:8px 10px; border-radius:6px; border:1px solid #ccc; background:#fff; }
        .add-btn, .export-btn, .small-btn {
            background-color: #b19777;
            color: black;
            padding: 8px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            display:inline-block;
        }
        .export-btn { background:#28a745; color:white; }
        .small-btn { background:transparent; color:#b19777; border:1px solid #b19777; padding:6px 10px; font-weight:600; border-radius:6px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td {
            padding: 12px; border: 1px solid #ddd;
            vertical-align: top; text-align: left;
        }
        th { background-color: #b19777; color: black; }
        td a { color: #0b6efd; text-decoration: none; }
        .icon-btn {
            display: inline-flex; align-items: center; justify-content: center;
            text-decoration: none; margin-right: 8px;
            transition: transform 0.12s ease;
        }
        .icon-btn:hover { transform: scale(1.08); }
        .type-span { background:#f4f1ee; padding:6px 10px; border-radius:6px; font-weight:600; display:inline-block; }
        .table-text-limit { max-width: 240px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        td img { display: block; max-width: 100px; height:auto; border-radius:6px; }
        td.action-col { white-space: nowrap; }
        .meta-row { color:#555; font-size:13px; margin-top:6px; }
        @media (max-width:900px){
            .top-header { flex-direction:column; align-items:flex-start; gap:10px; }
        }
    </style>
</head>
<body>
<div class="container">
    <?php include('includes/sidebar.php'); ?>
    <div class="main">
        <?php include('includes/topbar.php'); ?>

        <div class="content-wrapper">
            <div class="top-header">
                <div class="top-left">
                    <h2>Enquiry List</h2>

                    <div class="controls" style="margin-left:14px;">
                        <form method="get" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                            <?php
                                // date helpers
                                function valid_date($d) {
                                    $dt = DateTime::createFromFormat('Y-m-d', $d);
                                    return $dt && $dt->format('Y-m-d') === $d;
                                }
                                date_default_timezone_set('Asia/Kolkata');
                                $today = new DateTime('now');
                                $default_date = $today->format('Y-m-d');

                                $from = $_GET['from'] ?? $default_date;
                                $to   = $_GET['to']   ?? $default_date;
                                if (!valid_date($from)) $from = $default_date;
                                if (!valid_date($to)) $to = $from;
                            ?>
                            <label for="from">From</label>
                            <input id="from" type="date" name="from" value="<?= htmlspecialchars($from) ?>">

                            <label for="to">To</label>
                            <input id="to" type="date" name="to" value="<?= htmlspecialchars($to) ?>">

                            <button type="submit" class="small-btn">Search</button>
                        </form>
                    </div>
                </div>

                <div style="display:flex; gap:10px; align-items:center;">
                    <!-- Export CSV -->
                    <a class="export-btn" href="?<?= http_build_query(['from'=>$from,'to'=>$to,'export'=>1]) ?>">Export CSV</a>

                    <!-- Quick links -->
                    <a class="add-btn" href="?from=<?= $default_date ?>&to=<?= $default_date ?>">Today</a>
                    <a class="add-btn" href="?from=<?= date('Y-m-d', strtotime('-6 days')) ?>&to=<?= $default_date ?>">Last 7 days</a>
                </div>
            </div>

            <?php
            // Build query logic & fetch rows
            $isExport = isset($_GET['export']) && $_GET['export'] == '1';
            $singleDay = ($from === $to);

            if ($singleDay) {
                $selectSql = "SELECT id, name, email, phone, city, enquiry_type, message, created_at
                              FROM enquiry
                              WHERE DATE(created_at) = ?
                              ORDER BY created_at DESC";
                $countSql  = "SELECT COUNT(*) AS total FROM enquiry WHERE DATE(created_at) = ?";
            } else {
                $selectSql = "SELECT id, name, email, phone, city, enquiry_type, message, created_at
                              FROM enquiry
                              WHERE DATE(created_at) BETWEEN ? AND ?
                              ORDER BY created_at DESC";
                $countSql  = "SELECT COUNT(*) AS total FROM enquiry WHERE DATE(created_at) BETWEEN ? AND ?";
            }

            $rows = [];
            $total = 0;
            if (isset($pdo) && $pdo instanceof PDO) {
                if ($singleDay) {
                    $stmt = $pdo->prepare($selectSql);
                    $stmt->execute([$from]);
                    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $cstmt = $pdo->prepare($countSql);
                    $cstmt->execute([$from]);
                    $total = (int)$cstmt->fetchColumn();
                } else {
                    $stmt = $pdo->prepare($selectSql);
                    $stmt->execute([$from, $to]);
                    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $cstmt = $pdo->prepare($countSql);
                    $cstmt->execute([$from, $to]);
                    $total = (int)$cstmt->fetchColumn();
                }
            } elseif (isset($conn) && ($conn instanceof mysqli || gettype($conn) === 'object')) {
                if ($singleDay) {
                    $stmt = $conn->prepare($selectSql);
                    $stmt->bind_param('s', $from);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    $rows = $res->fetch_all(MYSQLI_ASSOC);
                    $stmt->close();
                    $cstmt = $conn->prepare($countSql);
                    $cstmt->bind_param('s', $from);
                    $cstmt->execute();
                    $cres = $cstmt->get_result();
                    $crow = $cres->fetch_assoc();
                    $total = (int)$crow['total'];
                    $cstmt->close();
                } else {
                    $stmt = $conn->prepare($selectSql);
                    $stmt->bind_param('ss', $from, $to);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    $rows = $res->fetch_all(MYSQLI_ASSOC);
                    $stmt->close();
                    $cstmt = $conn->prepare($countSql);
                    $cstmt->bind_param('ss', $from, $to);
                    $cstmt->execute();
                    $cres = $cstmt->get_result();
                    $crow = $cres->fetch_assoc();
                    $total = (int)$crow['total'];
                    $cstmt->close();
                }
            } else {
                echo "<div style='padding:12px;background:#fff3cd;border:1px solid #ffeeba;color:#856404;border-radius:6px;'>DB connection not found. Ensure <code>../includes/connect.php</code> sets <code>\$pdo</code> or <code>\$conn</code>.</div>";
            }

            // handle CSV export
            if ($isExport) {
                header('Content-Type: text/csv; charset=utf-8');
                $filename = "enquiries_{$from}" . ($from === $to ? '' : "_to_{$to}") . ".csv";
                header("Content-Disposition: attachment; filename=\"$filename\"");
                $out = fopen('php://output', 'w');
                fputcsv($out, ['ID','Name','Email','Phone','City','Enquiry Type','Message','Created At']);
                foreach ($rows as $r) {
                    fputcsv($out, [
                        
                        $r['name'],
                        $r['email'],
                        $r['phone'],
                        $r['city'],
                        $r['enquiry_type'],
                        $r['message'],
                        $r['created_at']
                    ]);
                }
                fclose($out);
                exit;
            }
            ?>

            <div class="meta-row" style="margin-top:12px;">
                <strong>Total enquiries:</strong> <?= (int)$total ?> &nbsp; &middot; &nbsp; Showing: <?= htmlspecialchars($from) ?><?= ($from === $to ? '' : ' to ' . htmlspecialchars($to)) ?>
            </div>

            <table>
                <thead>
                <tr>
                    
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>City</th>
                    <th>Type</th>
                    <th>Message</th>
                    <th style="width:170px">Date & Time</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="8" style="text-align:center; padding:30px; color:#666;">No enquiries for the selected date(s).</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            
                            <td><?= htmlspecialchars($r['name']) ?></td>
                            <td><a href="mailto:<?= htmlspecialchars($r['email']) ?>"><?= htmlspecialchars($r['email']) ?></a></td>
                            <td><?= htmlspecialchars($r['phone']) ?></td>
                            <td><?= htmlspecialchars($r['city']) ?></td>
                            <td><span class="type-span"><?= htmlspecialchars($r['enquiry_type']) ?></span></td>
                            <td class="table-text-limit" title="<?= htmlspecialchars(strip_tags($r['message'])) ?>"><?= htmlspecialchars(strip_tags($r['message'])) ?></td>
                            <td><?= htmlspecialchars($r['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>

        </div>
    </div>
</div>

<script src="./js/main.js"></script>

<script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
</body>
</html>
