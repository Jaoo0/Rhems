<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    header('Location: ../login.php');
    exit;
}

$threshold = 5;

$stmt = $pdo->prepare("SELECT * FROM inventory WHERE quantity <= ? ORDER BY quantity ASC");
$stmt->execute([$threshold]);
$low_stock_items = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Low Stock Alerts - Manager</title>
    <link rel="stylesheet" href="low_stock_alerts.css" />
</head>
<body>
    <h1>Low Stock Alerts (Quantity ≤ <?= $threshold ?>)</h1>
    <a href="../dashboard.php" class="back-link">← Back to Dashboard</a>

    <?php if (count($low_stock_items) === 0): ?>
        <p>All items have sufficient stock.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Picture</th>
                    <th>Name</th>
                    <th>Specs</th>
                    <th>Quantity</th>
                    <th>Price (₱)</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($low_stock_items as $item): ?>
                    <tr>
                        <td data-label="ID"><?= htmlspecialchars($item['id']) ?></td>
                        <td data-label="Picture">
                            <?php if (!empty($item['picture']) && file_exists("../uploads/" . $item['picture'])): ?>
                                <img src="../uploads/<?= htmlspecialchars($item['picture']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="thumbnail" />
                            <?php else: ?>
                                N/A
                            <?php endif; ?>
                        </td>
                        <td data-label="Name"><?= htmlspecialchars($item['name']) ?></td>
                        <td data-label="Specs"><?= nl2br(htmlspecialchars($item['specs'])) ?></td>
                        <td data-label="Quantity"><?= (int)$item['quantity'] ?></td>
                        <td data-label="Price (₱)"><?= number_format($item['price'], 2) ?></td>
                        <td data-label="Description"><?= nl2br(htmlspecialchars($item['description'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>
