<?php
/**
 * Nubevest contact form handler.
 * Receives an AJAX POST from contact.html and sends the message via SMTP.
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

// Honeypot: bots fill hidden fields; humans don't.
if (!empty($_POST['botcheck'])) {
    // Pretend success so the bot moves on.
    echo json_encode(['success' => true, 'message' => 'Thanks! Your message has been sent.']);
    exit;
}

// Collect & sanitise input
$name    = trim($_POST['name']    ?? '');
$email   = trim($_POST['email']   ?? '');
$phone   = trim($_POST['phone']   ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

// Validate
$errors = [];
if ($name === '')                                   $errors[] = 'Name is required.';
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
if ($phone === '')                                  $errors[] = 'Phone is required.';
if ($subject === '')                                $errors[] = 'Subject is required.';
if ($message === '')                                $errors[] = 'Message is required.';

if ($errors) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

// Load SMTP config (gitignored)
$configPath = __DIR__ . '/mail-config.php';
if (!file_exists($configPath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Mail is not configured on the server.']);
    exit;
}
$config = require $configPath;

// Load PHPMailer
require __DIR__ . '/mail/PHPMailer/src/Exception.php';
require __DIR__ . '/mail/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/mail/PHPMailer/src/SMTP.php';

$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host       = $config['smtp_host'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $config['smtp_user'];
    $mail->Password   = $config['smtp_pass'];
    $mail->SMTPSecure = $config['smtp_secure'] === 'tls'
        ? PHPMailer::ENCRYPTION_STARTTLS
        : PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = (int) $config['smtp_port'];
    $mail->CharSet    = 'UTF-8';

    // Recipients
    $mail->setFrom($config['from_email'], $config['from_name']);
    $mail->addAddress($config['to_email'], $config['to_name']);
    $mail->addReplyTo($email, $name);

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Website enquiry: ' . $subject;

    $safeName    = htmlspecialchars($name,    ENT_QUOTES, 'UTF-8');
    $safeEmail   = htmlspecialchars($email,   ENT_QUOTES, 'UTF-8');
    $safePhone   = htmlspecialchars($phone,   ENT_QUOTES, 'UTF-8');
    $safeSubject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
    $safeMessage = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));

    $mail->Body = "
        <h2 style='color:#ED4235;margin:0 0 16px;'>New contact form enquiry</h2>
        <table cellpadding='6' style='font-family:Arial,sans-serif;font-size:15px;color:#333;'>
            <tr><td><strong>Name:</strong></td><td>{$safeName}</td></tr>
            <tr><td><strong>Email:</strong></td><td>{$safeEmail}</td></tr>
            <tr><td><strong>Phone:</strong></td><td>{$safePhone}</td></tr>
            <tr><td><strong>Subject:</strong></td><td>{$safeSubject}</td></tr>
            <tr><td valign='top'><strong>Message:</strong></td><td>{$safeMessage}</td></tr>
        </table>
    ";
    $mail->AltBody = "New contact form enquiry\n\n"
        . "Name: {$name}\nEmail: {$email}\nPhone: {$phone}\n"
        . "Subject: {$subject}\n\nMessage:\n{$message}";

    $mail->send();
    echo json_encode(['success' => true, 'message' => 'Thanks! Your message has been sent. We’ll be in touch soon.']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Sorry, the message could not be sent. Please try again or email us directly.',
    ]);
}
