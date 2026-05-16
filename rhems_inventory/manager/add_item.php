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
    $name = $_POST['name'] ?? '';
    $specs = $_POST['specs'] ?? '';
    $quantity    = (int) $_POST['quantity'];
    $price       = (float) $_POST['price'];
    $description = trim($_POST['description']);

    // File upload
    $picture = null;
    if (isset($_FILES['picture']) && $_FILES['picture']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $filename   = basename($_FILES['picture']['name']);
        $targetFile = $uploadDir . $filename;

        $fileType = pathinfo($filename, PATHINFO_EXTENSION);
        $allowed  = ['jpg','jpeg','png','gif'];
        if (!in_array(strtolower($fileType), $allowed)) {
            $error = "Only JPG, JPEG, PNG, GIF files allowed for picture.";
        } elseif (!move_uploaded_file($_FILES['picture']['tmp_name'], $targetFile)) {
            $error = "Failed to upload picture.";
        } else {
            $picture = $filename;
        }
    }

    if (!$error) {
        if ($name === '' || $specs === '' || $quantity < 0 || $price < 0) {
            $error = "Please fill in all required fields correctly.";
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO inventory (name, specs, quantity, price, description, picture) 
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            if ($stmt->execute([$name, $specs, $quantity, $price, $description, $picture])) {
                $success = "Item added successfully!";
            } else {
                $error = "Database error: failed to add item.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Add Item - Manager</title>
    <link rel="stylesheet" href="add_item.css" />
</head>
<body>
    <h1>Add New Inventory Item</h1>
    <a href="../dashboard.php" class="back-link">← Back to Dashboard</a>

    <?php if ($error): ?>
        <p class="error-msg"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <?php if ($success): ?>
        <p class="success-msg"><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <label>Item Name:</label>
        <input type="text" name="name" required value="<?= isset($name) ? htmlspecialchars($name) : '' ?>">

        <label>Specifications:</label>
        <textarea name="specs" required><?= isset($specs) ? htmlspecialchars($specs) : '' ?></textarea>

        <label>Description (optional):</label>
        <textarea name="description"><?= isset($description) ? htmlspecialchars($description) : '' ?></textarea>

        <label>Quantity:</label>
        <input type="number" name="quantity" min="0" required value="<?= isset($quantity) ? (int)$quantity : '0' ?>">

        <label>Price:</label>
        <input type="number" step="0.01" name="price" min="0" required value="<?= isset($price) ? htmlspecialchars($price) : '' ?>">

        <label>Picture (optional):</label>
        <input type="file" name="picture" accept="image/*">

        <button type="submit">Add Item</button>
    </form>
</body>
</html>
