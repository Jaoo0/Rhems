<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch announcements with creator username
$stmt = $pdo->query(
    "SELECT a.*, u.username 
     FROM announcements a 
     JOIN users u ON a.created_by = u.id 
     ORDER BY a.created_at DESC"
);
$announcements = $stmt->fetchAll();

// Mark all fetched announcements as read by current user (insert IGNORE so no duplicates)
if ($announcements) {
    $insertStmt = $pdo->prepare("INSERT IGNORE INTO announcement_reads (user_id, announcement_id) VALUES (?, ?)");
    foreach ($announcements as $ann) {
        $insertStmt->execute([$user_id, $ann['id']]);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Announcements</title>
    <link rel="stylesheet" href="announcement.css" />
</head>
<body>
    <div class="container">
        <h1>Announcements</h1>
        <a href="../dashboard.php" class="back-link">← Back to Dashboard</a>

        <?php if (!$announcements): ?>
            <p class="no-announcements">No announcements yet.</p>
        <?php else: ?>
            <?php foreach ($announcements as $ann): ?>
                <article class="announcement">
                    <h2><?= htmlspecialchars($ann['title']) ?></h2>
                    <p><?= nl2br(htmlspecialchars($ann['body'])) ?></p>
                    <footer>
                        <small>By <?= htmlspecialchars($ann['username']) ?> on <?= date("F j, Y, g:i A", strtotime($ann['created_at'])) ?></small>
                    </footer>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
