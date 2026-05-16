<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header('Location: ../login.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!$old_password || !$new_password || !$confirm_password) {
        $error = "Please fill in all fields.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New password and confirm password do not match.";
    } elseif (strlen($new_password) < 6) {
        $error = "New password must be at least 6 characters long.";
    } else {
        // Get current password hash from DB
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($old_password, $user['password'])) {
            $error = "Old password is incorrect.";
        } else {
            // Hash new password and update
            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($update->execute([$new_password_hash, $user_id])) {
                $success = "Password changed successfully.";
            } else {
                $error = "Failed to update password. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Change Password - Employee</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f4f8;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            background: white;
            padding: 2rem 3rem;
            border-radius: 10px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            width: 350px;
        }
        h1 {
            text-align: center;
            color: #3c3f72;
            margin-bottom: 1.5rem;
        }
        form label {
            font-weight: 600;
            display: block;
            margin-bottom: 0.5rem;
            color: #3c3f72;
        }
        form input[type="password"] {
            width: 100%;
            padding: 0.6rem 0.8rem;
            margin-bottom: 1rem;
            border: 1.5px solid #ccc;
            border-radius: 6px;
            font-size: 1rem;
            box-sizing: border-box;
        }
        form button {
            width: 100%;
            background-color: #4745d7;
            color: white;
            font-weight: 700;
            padding: 0.8rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.1rem;
            transition: background-color 0.3s ease;
        }
        form button:hover {
            background-color: #322eb3;
        }
        .message {
            margin-bottom: 1rem;
            font-weight: 600;
            text-align: center;
        }
        .error {
            color: #d63031;
        }
        .success {
            color: #27ae60;
        }
        a.back-link {
            display: block;
            text-align: center;
            margin-top: 1rem;
            color: #4745d7;
            text-decoration: none;
            font-weight: 600;
        }
        a.back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Change Password</h1>

        <?php if ($error): ?>
            <p class="message error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if ($success): ?>
            <p class="message success"><?= htmlspecialchars($success) ?></p>
        <?php endif; ?>

        <form method="POST" action="">
            <label for="old_password">Old Password</label>
            <input type="password" name="old_password" id="old_password" required />

            <label for="new_password">New Password</label>
            <input type="password" name="new_password" id="new_password" required />

            <label for="confirm_password">Confirm New Password</label>
            <input type="password" name="confirm_password" id="confirm_password" required />

            <button type="submit">Change Password</button>
        </form>

        <a href="../dashboard.php" class="back-link">← Back to Dashboard</a>
    </div>
</body>
</html>
