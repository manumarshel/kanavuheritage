    <?php
// /kanav/dashboard/booking_status_update.php
// Handles Approve / Reject / Cancel actions from bookings.php

session_start();
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/../includes/connect.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

$id     = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$action = $_POST['action'] ?? '';

if ($id <= 0 || !in_array($action, ['approve','reject','cancel'], true)) {
    header('Location: bookings.php?msg=invalid');
    exit();
}

switch ($action) {
    case 'approve':
        $booking_status = 'approved';
        $payment_status = 'Paid';      // after manual verification
        break;
    case 'reject':
        $booking_status = 'rejected';
        $payment_status = 'Failed';
        break;
    case 'cancel':
        $booking_status = 'cancelled';
        $payment_status = 'Refunded';  // adjust as per your policy
        break;
    default:
        header('Location: bookings.php?msg=invalid');
        exit();
}

$sql = "UPDATE bookings
        SET booking_status = ?, payment_status = ?, updated_at = NOW()
        WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ssi', $booking_status, $payment_status, $id);
$stmt->execute();
$stmt->close();

header('Location: bookings.php?msg=updated');
exit();
