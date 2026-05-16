<?php
// manager/update_item.php
session_start();
require_once '../config/db.php';

// Redirect if not manager
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    header('Location: ../login.php');
    exit;
}

if (!isset($_GET['id'])) {
    echo "No item selected.";
    exit;
}

$id = intval($_GET['id']);
$stmt = $pdo->prepare("SELECT * FROM inventory WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item) {
    echo "Item not found.";
    exit;
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $specs = trim($_POST['specs']);
    $quantity = intval($_POST['quantity']);
    $price = floatval($_POST['price']);

    if ($name && $specs && $quantity >= 0 && $price >= 0) {
        $update = $pdo->prepare("UPDATE inventory SET name = ?, specs = ?, quantity = ?, price = ? WHERE id = ?");
        if ($update->execute([$name, $specs, $quantity, $price, $id])) {
            $success = "Item updated successfully.";
            $stmt->execute([$id]);
            $item = $stmt->fetch();
        } else {
            $error = "Failed to update item.";
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
    <title>Update Item - RHEMS</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <header>
        <h1>Update Item</h1>
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
            <input type="text" name="name" value="<?= htmlspecialchars($item['name']) ?>" required><br><br>

            <label>Specifications:</label><br>
            <textarea name="specs" required><?= htmlspecialchars($item['specs']) ?></textarea><br><br>

            <label>Quantity:</label><br>
            <input type="number" name="quantity" value="<?= htmlspecialchars($item['quantity']) ?>" min="0" required><br><br>

            <label>Price:</label><br>
            <input type="number" step="0.01" name="price" value="<?= htmlspecialchars($item['price']) ?>" min="0" required><br><br>

            <button type="submit">Update Item</button>
        </form>
    </main>
</body>
</html>