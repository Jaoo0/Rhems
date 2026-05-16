<?php
session_start();
require_once '../config/db.php';

// Check login and manager role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    header('Location: ../login.php');
    exit;
}

// Fetch inventory items ordered by name, only non-archived
$stmt = $pdo->prepare("SELECT * FROM inventory WHERE archived = 0 ORDER BY name ASC");
$stmt->execute();
$items = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Inventory - Manager</title>
    <link rel="stylesheet" href="view_inventory.css" />
</head>
<body>
    <h1>Inventory List</h1>
    <a href="../dashboard.php" class="back-link">← Back to Dashboard</a>

    <?php if (count($items) === 0): ?>
        <p>No inventory items found.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Item Name</th>
                    <th>Specifications</th>
                    <th>Description</th>
                    <th>Quantity</th>
                    <th>Price (₱)</th>
                    <th>Picture</th>
                    <th>Actions</th> <!-- Added actions column -->
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td data-label="Item Name"><?= htmlspecialchars($item['name']) ?></td>
                    <td data-label="Specifications"><?= nl2br(htmlspecialchars($item['specs'])) ?></td>
                    <td data-label="Description"><?= nl2br(htmlspecialchars($item['description'])) ?></td>
                    <td data-label="Quantity"><?= (int)$item['quantity'] ?></td>
                    <td data-label="Price (₱)"><?= number_format($item['price'], 2) ?></td>
                    <td data-label="Picture">
                        <?php if (!empty($item['picture']) && file_exists("../uploads/" . $item['picture'])): ?>
                            <img src="../uploads/<?= htmlspecialchars($item['picture']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                        <?php else: ?>
                            No image
                        <?php endif; ?>
                    </td>
                    <td data-label="Actions">
                        <a href="edit_inventory.php?id=<?= $item['id'] ?>" class="action-btn edit-btn">Edit</a>
                        <a href="archive_inventory.php?id=<?= $item['id'] ?>" class="action-btn archive-btn" onclick="return confirm('Are you sure you want to archive this item?');">Archive</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>
