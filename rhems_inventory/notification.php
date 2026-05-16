<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if (isset($_GET['notif_id'])) {
    $notif_id = (int)$_GET['notif_id'];
    // Mark notification as read if it belongs to the logged in user
    $update = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $update->execute([$notif_id, $user_id]);

    // Redirect to notification link or dashboard if none
    $stmt = $pdo->prepare("SELECT link FROM notifications WHERE id = ? AND user_id = ?");
    $stmt->execute([$notif_id, $user_id]);
    $notif = $stmt->fetch();

    if ($notif && !empty($notif['link'])) {
        header("Location: " . $notif['link']);
        exit;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>All Notifications</title>
    <link rel="stylesheet" href="assets/css/styles.css" />
</head>
<body>
    <h1>All Notifications</h1>
    <a href="dashboard.php">Back to Dashboard</a><br><br>

    <?php
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll();

    if (count($notifications) === 0) {
        echo "<p>No notifications found.</p>";
    } else {
        echo '<ul>';
        foreach ($notifications as $notif) {
            echo '<li' . ($notif['is_read'] == 0 ? ' style="font-weight:bold;"' : '') . '>';
            if ($notif['link']) {
                echo '<a href="notifications.php?notif_id=' . $notif['id'] . '">' . htmlspecialchars($notif['message']) . '</a>';
            } else {
                echo htmlspecialchars($notif['message']);
            }
            echo '<br><small>' . $notif['created_at'] . '</small></li>';
        }
        echo '</ul>';
    }
    ?>
</body>
</html>
