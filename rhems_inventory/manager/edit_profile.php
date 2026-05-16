<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Fetch current user data
$stmt = $pdo->prepare("SELECT username, email, full_name, profile_picture FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    die("User not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle profile picture upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = mime_content_type($_FILES['profile_picture']['tmp_name']);
        if (!in_array($file_type, $allowed_types)) {
            $error = "Only JPG, PNG, and GIF images are allowed.";
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
                // Update user profile picture in database
                $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE user_id = ?");
                $stmt->execute([$new_filename, $user_id]);
                $success = "Profile picture updated successfully.";
                $user['profile_picture'] = $new_filename; // update current data
            }
        }
    } else {
        $error = "Please select a file to upload.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Edit Profile Picture</title>
</head>
<body>
    <h1>Edit Profile Picture</h1>
    <p>Username: <?= htmlspecialchars($user['username']) ?></p>
    <p>Current Picture:</p>
    <?php if (!empty($user['profile_picture'])): ?>
        <img src="../uploads/<?= htmlspecialchars($user['profile_picture']) ?>" alt="Profile Picture" style="max-width:150px;">
    <?php else: ?>
        <p>No profile picture uploaded yet.</p>
    <?php endif; ?>

    <?php if ($error): ?>
        <p style="color: red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if ($success): ?>
        <p style="color: green;"><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <label for="profile_picture">Choose new profile picture:</label><br>
        <input type="file" name="profile_picture" id="profile_picture" accept="image/*" required><br><br>
        <button type="submit">Upload</button>
    </form>

    <br>
    <a href="../dashboard.php">Back to Dashboard</a>
</body>
</html>
