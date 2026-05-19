<?php
// /mail/send_mail.php  (or /kanav/mail/send_mail.php)
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../includes/connect.php'; // provides $conn (mysqli)
require_once __DIR__ . '/../vendor/autoload.php';  // PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * ✅ FIX: Your server does NOT support /contact (pretty URL).
 * It only works as /contact.php (as per your screenshots).
 *
 * So we:
 * 1) Detect whether site is at domain root OR inside /kanav
 * 2) Always redirect to contact.php / thank-you.php (real files)
 */
$docRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
$script  = (string)($_SERVER['SCRIPT_NAME'] ?? '');

// candidates to support both deployments
$candidates = [
  '',                 // domain root
  '/kanav',            // inside /kanav
  rtrim(dirname(dirname($script)), '/\\'), // derived from current script
];

$basePath = '';
foreach ($candidates as $c) {
  $c = rtrim((string)$c, '/\\');
  if ($c === '/' || $c === '\\') $c = '';
  if ($docRoot && is_file($docRoot . $c . '/contact.php')) {
    $basePath = $c;
    break;
  }
}

// Final targets (always real files)
$contactTarget  = ($basePath ?: '') . '/contact.php';
$thankYouTarget = ($basePath ?: '') . '/thank-you.php';

// === Redirect helpers ===
$back_with_error = function (string $msg, array $values = []) use ($contactTarget): void {
  $msg = substr($msg, 0, 300);

  $qs = http_build_query([
    'err'       => $msg,
    'v_name'    => $values['name']    ?? '',
    'v_email'   => $values['email']   ?? '',
    'v_phone'   => $values['phone']   ?? '',
    'v_city'    => $values['city']    ?? '',
    'v_enquiry' => $values['enquiry'] ?? '',
    'v_message' => $values['message'] ?? '',
  ]);

  header('Location: ' . $contactTarget . '?' . $qs, true, 302);
  exit;
};

$go_thank_you = function () use ($thankYouTarget): void {
  header('Location: ' . $thankYouTarget, true, 302);
  exit;
};

// === Basic request checks ===
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
  $back_with_error('Invalid request method.');
}

if (
  empty($_SESSION['contact_csrf']) ||
  empty($_POST['csrf']) ||
  !hash_equals($_SESSION['contact_csrf'], (string)$_POST['csrf'])
) {
  $back_with_error('Your session expired. Please try again.');
}

// Honeypot
if (!empty($_POST['hp_url'])) {
  unset($_SESSION['contact_csrf']);
  $go_thank_you();
}

// === Collect & trim input ===
$values = [
  'name'    => trim((string)($_POST['name']    ?? '')),
  'email'   => trim((string)($_POST['email']   ?? '')),
  'phone'   => trim((string)($_POST['phone']   ?? '')),
  'city'    => trim((string)($_POST['city']    ?? '')),
  'enquiry' => trim((string)($_POST['enquiry'] ?? '')),
  'message' => trim((string)($_POST['message'] ?? '')),
];

// === Validation ===
if ($values['name'] === '' || mb_strlen($values['name']) < 2 || mb_strlen($values['name']) > 120) {
  $back_with_error('Please enter your full name (2–120 characters).', $values);
}

if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($values['email']) > 160) {
  $back_with_error('Please enter a valid email address.', $values);
}

if (!preg_match('/^\d{10}$/', $values['phone'])) {
  $back_with_error('Please enter a valid 10-digit phone number.', $values);
}

$allowedEnquiries = ['One Day Stay','One Week Stay','Photoshoot','Others'];
if (!in_array($values['enquiry'], $allowedEnquiries, true)) {
  $back_with_error('Please select a valid enquiry type.', $values);
}

if (mb_strlen($values['city']) > 100) {
  $back_with_error('City is too long (max 100 characters).', $values);
}
if (mb_strlen($values['message']) > 2000) {
  $back_with_error('Message is too long (max 2000 characters).', $values);
}

// === Insert into DB ===
try {
  $stmt = $conn->prepare("
    INSERT INTO enquiry (name, email, phone, city, enquiry_type, message)
    VALUES (?, ?, ?, ?, ?, ?)
  ");
  if (!$stmt) {
    $back_with_error('Could not prepare statement.', $values);
  }

  $stmt->bind_param(
    'ssssss',
    $values['name'],
    $values['email'],
    $values['phone'],
    $values['city'],
    $values['enquiry'],
    $values['message']
  );

  if (!$stmt->execute()) {
    $back_with_error('Could not save your enquiry. Please try again later.', $values);
  }
  $stmt->close();
} catch (\Throwable $e) {
  $back_with_error('Unexpected error while saving. Please try again later.', $values);
}

// === Send email (optional) ===
try {
  $mail = new PHPMailer(true);
  $mail->isSMTP();
  $mail->Host       = 'smtp.gmail.com';
  $mail->SMTPAuth   = true;
  $mail->Username   = 'mail@kanavuheritage.com';
  $mail->Password   = 'swhc jiha ntyy rsqu';
  $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
  $mail->Port       = 587;

  $mail->setFrom('mail@kanavuheritage.com', 'Kanavu Heritage Website');
  $mail->addAddress('mail@kanavuheritage.com', 'Kanavu Heritage');
  $mail->addReplyTo($values['email'], $values['name']);

  $mail->isHTML(true);
  $mail->Subject = 'New Enquiry from Website';
  $mail->Body = sprintf(
    '<h3>New Enquiry</h3>
     <p><b>Name:</b> %s</p>
     <p><b>Email:</b> %s</p>
     <p><b>Phone:</b> %s</p>
     <p><b>City:</b> %s</p>
     <p><b>Type:</b> %s</p>
     <p><b>Message:</b><br>%s</p>',
    htmlspecialchars($values['name'], ENT_QUOTES, 'UTF-8'),
    htmlspecialchars($values['email'], ENT_QUOTES, 'UTF-8'),
    htmlspecialchars($values['phone'], ENT_QUOTES, 'UTF-8'),
    htmlspecialchars($values['city'], ENT_QUOTES, 'UTF-8'),
    htmlspecialchars($values['enquiry'], ENT_QUOTES, 'UTF-8'),
    nl2br(htmlspecialchars($values['message'], ENT_QUOTES, 'UTF-8'))
  );

  $mail->AltBody =
    "New Enquiry\n" .
    "Name: {$values['name']}\n" .
    "Email: {$values['email']}\n" .
    "Phone: {$values['phone']}\n" .
    "City: {$values['city']}\n" .
    "Type: {$values['enquiry']}\n" .
    "Message:\n{$values['message']}";

  $mail->send();
} catch (Exception $e) {
  // ignore mail failure
}

// === Prevent resubmits & redirect ===
unset($_SESSION['contact_csrf']);
$go_thank_you();
