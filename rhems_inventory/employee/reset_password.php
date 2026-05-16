<?php
session_start();
require_once '../config/db.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

if (!$token) {
    die('Invalid or missing token.');
}

// Check token validity
$stmt = $pdo->prepare("SELECT user_id, expires_at FROM password_reset_tokens WHERE token = ?");
$stmt->execute([$token]);
$tokenData = $stmt->fetch();

if (!$tokenData) {
    die('Invalid or expired token.');
}

// Check expiration
if (new DateTime() > new DateTime($tokenData['expires_at'])) {
    die('Token expired. Please request a new password reset.');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($new_password) || empty($confirm_password)) {
        $error = "Please fill out both password fields.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Hash the new password
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);

        // Update user's password
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$password_hash, $tokenData['user_id']]);

        // Delete the token to prevent reuse
        $stmt = $pdo->prepare("DELETE FROM password_reset_tokens WHERE token = ?");
        $stmt->execute([$token]);

        $success = "Password reset successfully! You can now <a href='../login.php'>login</a>.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Reset Password</title>
</head>
<body>
    <h1>Reset Password</h1>

    <?php if ($error): ?>
        <p style="color:red;"><?= htmlspecialchars($error) ?></p>
    <?php elseif ($success): ?>
        <p style="color:green;"><?= $success ?></p>
    <?php else: ?>
        <form method="POST">
            <label for="new_password">New Password:</label><br>
            <input type="password" id="new_password" name="new_password" required><br><br>

            <label for="confirm_password">Confirm Password:</label><br>
            <input type="password" id="confirm_password" name="confirm_password" required><br><br>

            <button type="submit">Reset Password</button>
        </form>
    <?php endif; ?>
</body>
</html>
