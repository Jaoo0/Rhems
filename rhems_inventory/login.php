<?php
session_start();
require_once 'config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Redirect all roles to dashboard.php
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Invalid username or password.";
        }
    } else {
        $error = "Please enter both username and password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Login - RHEMS</title>
    <link rel="stylesheet" href="login.css" />
</head>
<body>
    <div class="login-container">
        <h2>Login</h2>

        <?php if ($error): ?>
            <p class="error-message"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="POST" novalidate>
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" placeholder="Enter Username..." required autofocus>

            <label for="password">Password:</label>
            <div class="password-wrapper">
                <input type="password" id="password" name="password" placeholder="Enter Password..." required>
                <span id="togglePassword" class="toggle-password" title="Show/Hide Password">&#128065;</span>
            </div>

            <button type="submit">Login</button>
        </form>
    </div>

    <script>
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');

        togglePassword.addEventListener('click', () => {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            togglePassword.textContent = type === 'password' ? '\u{1F441}' : '\u{1F441}\u{FE0E}';
        });
    </script>
</body>
</html>
