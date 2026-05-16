<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    header('Location: ../login.php');
    exit;
}

// Fetch archived items
$stmt = $pdo->query("SELECT * FROM inventory WHERE archived = 1 ORDER BY name ASC");
$archived_items = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Archived Inventory - Manager</title>
    <link rel="stylesheet" href="view_archived_inventory.css" />
</head>
<body>
    <h1>Archived Inventory</h1>
    <a href="view_inventory.php" class="back-link">← Back to Inventory</a>

    <?php if (count($archived_items) === 0): ?>
        <p class="no-data">No archived items found.</p>
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
                    <th>Restore</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($archived_items as $item): ?>
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
                        <td data-label="Restore">
                            <a href="restore_inventory.php?id=<?= $item['id'] ?>" class="action-btn" onclick="return confirm('Restore this item?');">Restore</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>
