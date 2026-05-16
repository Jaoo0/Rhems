<?php
session_start();
require_once '../config/db.php';

// Check if employee is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header('Location: ../login.php');
    exit;
}

$search = $_GET['search'] ?? '';
$searchParam = '%' . $search . '%';

// Fetch inventory data excluding archived items and filter by search if provided
if ($search) {
    $stmt = $pdo->prepare("SELECT * FROM inventory WHERE archived = 0 AND (name LIKE ? OR specs LIKE ?) ORDER BY name ASC");
    $stmt->execute([$searchParam, $searchParam]);
} else {
    $stmt = $pdo->prepare("SELECT * FROM inventory WHERE archived = 0 ORDER BY name ASC");
    $stmt->execute();
}
$items = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Inventory - Employee View</title>
    <link rel="stylesheet" href="view_inventory.css" />
</head>
<body>
    <div class="container">
        <h1>Inventory List</h1>
        <a href="../dashboard.php" class="back-link">← Back to Dashboard</a>

        <form method="GET" class="search-form" action="">
            <input type="text" name="search" class="search-input" placeholder="Search items..." value="<?= htmlspecialchars($search) ?>" />
            <button type="submit" class="search-button">Search</button>
        </form>

        <?php if (count($items) === 0): ?>
            <p class="no-data">No items found.</p>
        <?php else: ?>
            <table class="inventory-table">
                <thead>
                    <tr>
                        <th>Picture</th>
                        <th>Item Name</th>
                        <th>Specifications</th>
                        <th>Description</th>
                        <th>Quantity</th>
                        <th>Price (₱)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td class="img-cell">
                                <?php if (!empty($item['picture'])): ?>
                                    <img src="../uploads/<?= htmlspecialchars($item['picture']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" />
                                <?php else: ?>
                                    <span class="no-image">No Image</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($item['name']) ?></td>
                            <td><?= nl2br(htmlspecialchars($item['specs'])) ?></td>
                            <td><?= nl2br(htmlspecialchars($item['description'])) ?></td>
                            <td><?= (int)$item['quantity'] ?></td>
                            <td><?= number_format($item['price'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
