<?php
// manager/add_item.php
session_start();
require_once '../config/db.php';

// Redirect if not manager
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    header('Location: ../login.php');
    exit;
}

$success = '';\$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $specs = trim($_POST['specs']);
    $quantity = intval($_POST['quantity']);
    $price = floatval($_POST['price']);

    if ($name && $specs && $quantity >= 0 && $price >= 0) {
        $stmt = $pdo->prepare("INSERT INTO inventory (name, specs, quantity, price) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$name, $specs, $quantity, $price])) {
            $success = "Item added successfully.";
        } else {
            $error = "Failed to add item.";
        }
    } else {
        $error = "Please fill in all fields correctly.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add New Item - RHEMS</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <header>
        <h1>Add New Item</h1>
        <nav>
            <a href="../dashboard.php">Dashboard</a>
            <a href="../logout.php">Logout</a>
        </nav>
    </header>
    <main>
        <?php if ($success): ?><p style="color:green;"><?= $success ?></p><?php endif; ?>
        <?php if ($error): ?><p style="color:red;"><?= $error ?></p><?php endif; ?>
        <form method="POST">
            <label>Name:</label><br>
            <input type="text" name="name" required><br><br>

            <label>Specifications:</label><br>
            <textarea name="specs" required></textarea><br><br>

            <label>Quantity:</label><br>
            <input type="number" name="quantity" min="0" required><br><br>

            <label>Price:</label><br>
            <input type="number" step="0.01" name="price" min="0" required><br><br>

            <button type="submit">Add Item</button>
        </form>
    </main>
</body>
</html>
