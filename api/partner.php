<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Referrer-Policy: no-referrer');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['error' => 'Method not allowed']);
  exit;
}

$config = require __DIR__ . '/config.php';
$SMTP_HOST = $config['SMTP_HOST'] ?? 'smtp.home.pl';
$SMTP_PORT = (int)($config['SMTP_PORT'] ?? 587);
$SMTP_USER = $config['SMTP_USER'] ?? '';
$SMTP_PASS = $config['SMTP_PASS'] ?? '';
$SMTP_FROM = $config['SMTP_FROM'] ?? $SMTP_USER;
$SMTP_FROM_NAME = $config['SMTP_FROM_NAME'] ?? 'Strona WWW';
$LEAD_TO   = $config['LEAD_TO'] ?? '';

$raw = file_get_contents('php://input');
$in  = json_decode($raw, true) ?: [];

$email   = filter_var($in['email'] ?? '', FILTER_VALIDATE_EMAIL);
$website = trim($in['website'] ?? '');

if (!empty($website)) {
  echo json_encode(['ok' => true]);
  exit;
}
if (!$email) {
  http_response_code(422);
  echo json_encode(['error' => 'Nieprawidłowy adres e-mail']);
  exit;
}

require __DIR__ . '/../vendor/phpmailer/src/PHPMailer.php';
require __DIR__ . '/../vendor/phpmailer/src/SMTP.php';
require __DIR__ . '/../vendor/phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
// ===============================================

try {
  $mail = new PHPMailer(true);
  $mail->isSMTP();
  $mail->Host       = $SMTP_HOST;
  $mail->SMTPAuth   = true;
  $mail->Username   = $SMTP_USER;
  $mail->Password   = $SMTP_PASS;

  if ($SMTP_PORT === 465) {
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;
  } else {
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
  }

  $mail->setFrom($SMTP_FROM, $SMTP_FROM_NAME);
  $mail->addAddress($LEAD_TO);
  // $mail->addReplyTo($email);

  $mail->Subject = 'Nowe zgłoszenie współpracy';
  $mail->isHTML(true);
  $mail->Body = sprintf(
    '<p>Nowe zgłoszenie:</p><ul><li>E-mail: <b>%s</b></li></ul>',
    htmlspecialchars($email, ENT_QUOTES, 'UTF-8')
  );
  $mail->AltBody = "Nowe zgłoszenie:\nE-mail: {$email}";

  $mail->send();

  echo json_encode(['ok' => true]);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['error' => 'Błąd wysyłki wiadomości.']);
}
