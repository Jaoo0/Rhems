<?php
session_start();
require_once '../config/db.php';

// Check if logged in and is manager
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    header('Location: ../login.php');
    exit;
}

// Check if ID is passed
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: archive_inventory.php');
    exit;
}

$itemId = (int) $_GET['id'];

// Restore the item (set archived = 0)
try {
    $stmt = $pdo->prepare("UPDATE inventory SET archived = 0 WHERE id = ?");
    $stmt->execute([$itemId]);

    // Redirect back to archived inventory list with success message
    header('Location: archive_inventory.php?message=Item+restored+successfully');
    exit;
} catch (Exception $e) {
    echo "Error restoring item: " . $e->getMessage();
}
?>
