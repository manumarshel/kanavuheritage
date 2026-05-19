<?php
// /kanav/mail/send_mail.php
session_start();

// Helper: redirect with preserved values on error
function back_with_error($msg, $vals){
  $q = http_build_query([
    'err' => $msg,
    'v_name' => $vals['name'] ?? '',
    'v_email' => $vals['email'] ?? '',
    'v_phone' => $vals['phone'] ?? '',
    'v_city' => $vals['city'] ?? '',
    'v_enquiry' => $vals['enquiry'] ?? '',
    'v_message' => $vals['message'] ?? '',
  ]);
  header("Location: ../contact?$q");
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: ../contact"); exit;
}

// CSRF
if (empty($_POST['csrf']) || empty($_SESSION['contact_csrf']) || !hash_equals($_SESSION['contact_csrf'], $_POST['csrf'])) {
  back_with_error('Invalid session. Please try again.', $_POST);
}

// Honeypot
if (!empty($_POST['hp_url'])) {
  back_with_error('Unexpected input detected.', $_POST);
}

// Collect & sanitize (very basic)
$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$phone   = trim($_POST['phone'] ?? '');
$city    = trim($_POST['city'] ?? '');
$enquiry = trim($_POST['enquiry'] ?? '');
$message = trim($_POST['message'] ?? '');

// Basic validation
if ($name === '' || $email === '' || $phone === '' || $enquiry === '') {
  back_with_error('Please fill all required fields.', $_POST);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  back_with_error('Please enter a valid email address.', $_POST);
}
if (!preg_match('/^[0-9]{10}$/', $phone)) {
  back_with_error('Please enter a valid 10 digit phone number.', $_POST);
}

// Prevent header injection
foreach ([$name,$email,$phone,$city,$enquiry] as $field) {
  if (preg_match('/[\r\n]/', $field)) {
    back_with_error('Invalid characters detected in input.', $_POST);
  }
}

// Build email
$to = 'mail@kanavuheritage.com'; // primary inbox
$subject = 'New enquiry from Kanavu Heritage website';
$bodyLines = [
  "Name: $name",
  "Email: $email",
  "Phone: $phone",
  "City: " . ($city ?: '-'),
  "Type of Enquiry: $enquiry",
  "Message:",
  $message !== '' ? $message : '-',
  "",
  "Sent: " . date('Y-m-d H:i:s')
];
$body = implode("\r\n", $bodyLines);

$headers = [];
$headers[] = 'MIME-Version: 1.0';
$headers[] = 'Content-Type: text/plain; charset=UTF-8';
$headers[] = 'From: Kanavu Website <no-reply@kanavuheritage.com>';
$headers[] = 'Reply-To: ' . $email;
$headersStr = implode("\r\n", $headers);

// Send: use mail() (works on many hosts). For PHPMailer/SMTP, replace below.
$sent = @mail($to, $subject, $body, $headersStr);

// Optional: also send a copy to the Gmail address
@mail('kanavuheritage@gmail.com', $subject, $body, $headersStr);

if ($sent) {
  // Invalidate token used (one-time)
  unset($_SESSION['contact_csrf']);
  header('Location: ../contact?ok=1'); // success
  exit;
}

back_with_error('Sorry, we could not send your message at the moment. Please contact us by phone or try again later.', $_POST);
