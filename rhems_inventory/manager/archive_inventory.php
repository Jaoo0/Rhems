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
    header('Location: view_inventory.php');
    exit;
}

$itemId = (int) $_GET['id'];

// Archive the item (set archived = 1)
try {
    $stmt = $pdo->prepare("UPDATE inventory SET archived = 1 WHERE id = ?");
    $stmt->execute([$itemId]);

    // Redirect back to inventory with success message (optional)
    header('Location: view_inventory.php?message=Item+archived+successfully');
    exit;
} catch (Exception $e) {
    // You may want to log this error
    echo "Error archiving item: " . $e->getMessage();
}
?>
