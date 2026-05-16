<?php
session_start();
require_once '../config/db.php';

// Check if logged in and is manager
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    header('Location: ../login.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'Employee';

    // Validate required fields
    if ($username === '' || $email === '' || $password === '') {
        $error = "Username, email, and password are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        // Check for existing username or email
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetchColumn() > 0) {
            $error = "Username or email already exists.";
        }
    }

    // Handle profile picture upload
    $profile_picture = null;
    if (!$error && isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = mime_content_type($_FILES['profile_picture']['tmp_name']);
        if (!in_array($file_type, $allowed_types)) {
            $error = "Only JPG, PNG, and GIF images are allowed for profile picture.";
        } else {
            $ext = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
            $new_filename = uniqid('profile_', true) . '.' . $ext;
            $upload_dir = __DIR__ . '/../uploads/';

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $dest_path = $upload_dir . $new_filename;
            if (!move_uploaded_file($_FILES['profile_picture']['tmp_name'], $dest_path)) {
                $error = "Failed to upload profile picture.";
            } else {
                $profile_picture = $new_filename;
            }
        }
    }

    if (!$error) {
        // Hash password securely
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, email, full_name, password, role, profile_picture, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([
                $username,
                $email,
                $full_name,
                $hashed_password,
                $role,
                $profile_picture
            ]);
            $success = "Employee added successfully.";
            // Clear POST data to reset form
            $_POST = [];
        } catch (Exception $e) {
            $error = "Error adding employee: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Add Employee - RHEMS</title>
    <link rel="stylesheet" href="add_employee.css" />
</head>
<body>
    <a href="../dashboard.php" class="back-link" style="display: inline-block; margin-bottom: 1.5rem; color: #585bbf; font-weight: 600; text-decoration: none;">
        &larr; Back to Dashboard
    </a>

    <h1>Add New Employee</h1>

    <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if ($success): ?>
        <p class="success"><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" novalidate>
        <label for="username">Username:</label><br>
        <input type="text" name="username" id="username" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"><br><br>

        <label for="email">Email:</label><br>
        <input type="email" name="email" id="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"><br><br>

        <label for="full_name">Full Name:</label><br>
        <input type="text" name="full_name" id="full_name" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>"><br><br>

        <label for="password">Password:</label><br>
        <input type="password" name="password" id="password" required><br><br>

        <label for="role">Role:</label><br>
        <select name="role" id="role" required>
            <option value="Employee" <?= (($_POST['role'] ?? '') === 'Employee') ? 'selected' : '' ?>>Employee</option>
            <option value="Manager" <?= (($_POST['role'] ?? '') === 'Manager') ? 'selected' : '' ?>>Manager</option>
        </select><br><br>

        <label for="profile_picture">Profile Picture (optional):</label><br>
        <input type="file" name="profile_picture" id="profile_picture" accept="image/*"><br><br>

        <button type="submit">Add Employee</button>
    </form>
</body>
</html>
