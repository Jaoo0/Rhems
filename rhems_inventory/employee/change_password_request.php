<?php
session_start();
require_once '../config/db.php';

// PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/PHPMailer/Exception.php';
require '../vendor/PHPMailer/PHPMailer.php';
require '../vendor/PHPMailer/SMTP.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user email from database
$stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || empty($user['email'])) {
    die('Email address not found. Please contact admin.');
}

$user_email = $user['email'];

// Generate a secure random token
$token = bin2hex(random_bytes(16));
$expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

// Insert token into password_reset_tokens table
$stmt = $pdo->prepare("INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
if (!$stmt->execute([$user_id, $token, $expires_at])) {
    die('Failed to generate verification token. Please try again.');
}

// Build the password reset link dynamically based on current server info
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$basePath = dirname($_SERVER['PHP_SELF']);
$reset_link = $protocol . $host . $basePath . "/reset_password.php?token=" . urlencode($token);

// Initialize PHPMailer and configure SMTP
$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host       = 'sandbox.smtp.mailtrap.io'; // Replace with your SMTP server
    $mail->SMTPAuth   = true;
    $mail->Username   = '11e78ba257db31';          // Your SMTP username
    $mail->Password   = 'e63b9ccb7e3d74';            // Your SMTP password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    // Recipients
    $mail->setFrom('no-reply@yourdomain.com', 'RHEMS Support');
    $mail->addAddress($user_email);

    // Content
    $mail->isHTML(true); // <-- Set to true for HTML email
    $mail->Subject = 'Password Change Verification';
    $mail->Body    = "
        <p>Hi,</p>
        <p>We received a request to change your password. Please click the link below to verify and change your password:</p>
        <p><a href=\"$reset_link\">Reset Password</a></p>
        <p>This link will expire in 1 hour.</p>
        <p>If you did not request this, please ignore this email.</p>
    ";

    $mail->AltBody = "Hi,\n\nWe received a request to change your password. Please use the link below to verify and change your password:\n\n$reset_link\n\nThis link will expire in 1 hour.\n\nIf you did not request this, please ignore this email.";

    $mail->send();
    echo "Verification email sent to " . htmlspecialchars($user_email) . ". Please check your inbox.";
} catch (Exception $e) {
    echo "Failed to send verification email. Mailer Error: {$mail->ErrorInfo}";
}
