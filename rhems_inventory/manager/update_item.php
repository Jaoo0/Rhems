<?php
session_start();
require_once '../config/db.php';

// Only manager can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    header('Location: ../login.php');
    exit;
}

$item_id = $_GET['id'] ?? null;
if (!$item_id) {
    header('Location: view_inventory.php');
    exit;
}

$error = '';
$success = '';

// Fetch item data
$stmt = $pdo->prepare("SELECT * FROM inventory WHERE id = ?");
$stmt->execute([$item_id]);
$item = $stmt->fetch();

if (!$item) {
    header('Location: view_inventory.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $specs = trim($_POST['specs']);
    $quantity = intval($_POST['quantity']);
    $price = floatval($_POST['price']);

    if ($name === '' || $quantity < 0 || $price < 0) {
        $error = "Please fill all fields correctly.";
    } else {
        // Update the database
        $stmt = $pdo->prepare("UPDATE inventory SET name = ?, specs = ?, quantity = ?, price = ? WHERE id = ?");
        $updated = $stmt->execute([$name, $specs, $quantity, $price, $item_id]);

        if ($updated) {
            $success = "Item updated successfully.";
            // Reload updated item data
            $stmt = $pdo->prepare("SELECT * FROM inventory WHERE id = ?");
            $stmt->execute([$item_id]);
            $item = $stmt->fetch();
        } else {
            $error = "Failed to update item.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Update Item - RHEMS</title>
</head>
<body>
    <h2>Update Item</h2>
    <?php if ($error): ?>
        <p style="color:red;"><?= htmlspecialchars($error) ?></p>
    <?php elseif ($success): ?>
        <p style="color:green;"><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>
    <form method="POST">
        <label>Name:</label><br>
        <input type="text" name="name" value="<?= htmlspecialchars($item['name']) ?>" required><br><br>

        <label>Specifications:</label><br>
        <textarea name="specs"><?= htmlspecialchars($item['specs']) ?></textarea><br><br>

        <label>Quantity:</label><br>
        <input type="number" name="quantity" value="<?= htmlspecialchars($item['quantity']) ?>" min="0" required><br><br>

        <label>Price:</label><br>
        <input type="number" name="price" step="0.01" value="<?= htmlspecialchars($item['price']) ?>" min="0" required><br><br>

        <button type="submit">Update Item</button>
    </form>

    <p><a href="view_inventory.php">Back to Inventory</a></p>
</body>
</html>
