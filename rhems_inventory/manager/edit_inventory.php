<?php
session_start();
require_once '../config/db.php';

// Check login and manager role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    header('Location: ../login.php');
    exit;
}

// Check if ID is provided in URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "Invalid item ID.";
    exit;
}

$item_id = (int) $_GET['id'];

// Fetch existing item data
$stmt = $pdo->prepare("SELECT * FROM inventory WHERE id = ?");
$stmt->execute([$item_id]);
$item = $stmt->fetch();

if (!$item) {
    echo "Item not found.";
    exit;
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $specs = trim($_POST['specs']);
    $description = trim($_POST['description']);
    $quantity = (int) $_POST['quantity'];
    $price = (float) $_POST['price'];

    // Validate required fields
    if (empty($name)) {
        $error = "Item name is required.";
    } elseif ($quantity < 0) {
        $error = "Quantity cannot be negative.";
    } elseif ($price < 0) {
        $error = "Price cannot be negative.";
    } else {
        // Handle file upload if exists
        $picture = $item['picture']; // keep old picture by default

        if (isset($_FILES['picture']) && $_FILES['picture']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($_FILES['picture']['type'], $allowed_types)) {
                $error = "Invalid image type. Allowed: JPG, PNG, GIF.";
            } else {
                // Upload the image
                $ext = pathinfo($_FILES['picture']['name'], PATHINFO_EXTENSION);
                $new_filename = uniqid('img_') . '.' . $ext;
                $upload_dir = '../uploads/';
                $upload_path = $upload_dir . $new_filename;

                if (move_uploaded_file($_FILES['picture']['tmp_name'], $upload_path)) {
                    // Delete old picture if exists
                    if (!empty($picture) && file_exists($upload_dir . $picture)) {
                        unlink($upload_dir . $picture);
                    }
                    $picture = $new_filename;
                } else {
                    $error = "Failed to upload image.";
                }
            }
        }

        if (empty($error)) {
            // Update database
            $update_stmt = $pdo->prepare("UPDATE inventory SET name = ?, specs = ?, description = ?, quantity = ?, price = ?, picture = ? WHERE id = ?");
            $updated = $update_stmt->execute([$name, $specs, $description, $quantity, $price, $picture, $item_id]);

            if ($updated) {
                $success = "Item updated successfully.";
                // Refresh item data
                $stmt->execute([$item_id]);
                $item = $stmt->fetch();
            } else {
                $error = "Failed to update item.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Edit Inventory Item</title>
    <link rel="stylesheet" href="../assets/css/styles.css" />
    <style>
        form {
            max-width: 600px;
            margin: auto;
        }
        label {
            display: block;
            margin-top: 15px;
        }
        input[type=text], input[type=number], textarea {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            box-sizing: border-box;
        }
        input[type=file] {
            margin-top: 5px;
        }
        .btn {
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #3498db;
            border: none;
            color: white;
            cursor: pointer;
            border-radius: 4px;
        }
        .btn:hover {
            opacity: 0.9;
        }
        .error {
            color: #e74c3c;
            margin-top: 10px;
        }
        .success {
            color: #2ecc71;
            margin-top: 10px;
        }
        img {
            max-width: 150px;
            margin-top: 10px;
            border-radius: 6px;
        }
    </style>
</head>
<body>
    <h1>Edit Inventory Item</h1>
    <a href="view_inventory.php">Back to Inventory List</a>

    <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php elseif ($success): ?>
        <p class="success"><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>

    <form action="" method="POST" enctype="multipart/form-data">
        <label for="name">Item Name *</label>
        <input type="text" name="name" id="name" value="<?= htmlspecialchars($item['name']) ?>" required>

        <label for="specs">Specifications</label>
        <textarea name="specs" id="specs" rows="4"><?= htmlspecialchars($item['specs']) ?></textarea>

        <label for="description">Description</label>
        <textarea name="description" id="description" rows="4"><?= htmlspecialchars($item['description']) ?></textarea>

        <label for="quantity">Quantity *</label>
        <input type="number" name="quantity" id="quantity" value="<?= (int)$item['quantity'] ?>" min="0" required>

        <label for="price">Price (₱) *</label>
        <input type="number" name="price" id="price" value="<?= number_format($item['price'], 2) ?>" step="0.01" min="0" required>

        <label for="picture">Picture (leave blank to keep current)</label>
        <?php if (!empty($item['picture']) && file_exists("../uploads/" . $item['picture'])): ?>
            <img src="../uploads/<?= htmlspecialchars($item['picture']) ?>" alt="Current Picture">
        <?php else: ?>
            <p>No image</p>
        <?php endif; ?>
        <input type="file" name="picture" id="picture" accept="image/*">

        <button type="submit" class="btn">Update Item</button>
    </form>
</body>
</html>
