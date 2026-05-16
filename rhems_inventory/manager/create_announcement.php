<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
  header('Location: ../login.php');
  exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title = trim($_POST['title']);
  $body  = trim($_POST['body']);

  if ($title === '' || $body === '') {
    $error = 'Please fill in both title and body.';
  } else {
    $stmt = $pdo->prepare(
      "INSERT INTO announcements (title, body, created_by) VALUES (?, ?, ?)"
    );
    if ($stmt->execute([$title, $body, $_SESSION['user_id']])) {
      $success = 'Announcement posted successfully.';
    } else {
      $error = 'Failed to post announcement.';
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Create Announcement</title>
  <link rel="stylesheet" href="create_announcement.css" />
</head>
<body>
  <h1>Create Announcement</h1>
  <a href="../dashboard.php" class="back-link">← Back to Dashboard</a>

  <?php if ($error): ?>
    <p class="error-msg"><?= htmlspecialchars($error) ?></p>
  <?php elseif ($success): ?>
    <p class="success-msg"><?= htmlspecialchars($success) ?></p>
  <?php endif; ?>

  <form method="POST" novalidate>
    <label for="title">Title:</label>
    <input type="text" name="title" id="title" required value="<?= isset($title) ? htmlspecialchars($title) : '' ?>" />

    <label for="body">Body:</label>
    <textarea name="body" id="body" rows="5" required><?= isset($body) ? htmlspecialchars($body) : '' ?></textarea>

    <button type="submit">Post Announcement</button>
  </form>
</body>
</html>
