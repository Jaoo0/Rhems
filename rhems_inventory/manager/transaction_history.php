<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is manager
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    header('Location: ../login.php');
    exit;
}

$search = $_GET['search'] ?? '';

$sql = "
SELECT 
    s.id,
    i.name AS item_name,
    i.picture AS item_picture,
    s.quantity_sold,
    s.total_price,
    s.sale_date,
    u.username AS sold_by,
    s.buyer_name,
    s.buyer_address,
    s.buyer_phone
FROM sales s
JOIN inventory i ON s.item_id = i.id
JOIN users u ON s.sold_by = u.id
WHERE i.name LIKE :search 
   OR u.username LIKE :search 
   OR s.buyer_name LIKE :search
ORDER BY s.sale_date DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute(['search' => "%$search%"]);
$sales = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Transaction History - Manager</title>
    <link rel="stylesheet" href="transaction_history.css" />
</head>
<body>
    <h1>Transaction History</h1>
    <a href="../dashboard.php" class="back-link">← Back to Dashboard</a>

    <form method="GET" action="transaction_history.php" role="search" aria-label="Search transaction history">
        <input type="text" name="search" placeholder="Search item, employee, or buyer" value="<?= htmlspecialchars($search) ?>" aria-label="Search input" />
        <button type="submit" aria-label="Search button">Search</button>
        <a href="transaction_history.php" class="reset-link">Reset</a>
    </form>

    <table>
        <thead>
            <tr>
                <th>Sale ID</th>
                <th>Item Image</th>
                <th>Item Name</th>
                <th>Quantity Sold</th>
                <th>Total Price</th>
                <th>Sale Date</th>
                <th>Sold By (Employee)</th>
                <th>Buyer Name</th>
                <th>Buyer Address</th>
                <th>Buyer Phone</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($sales): ?>
                <?php foreach ($sales as $sale): ?>
                <tr>
                    <td data-label="Sale ID"><?= htmlspecialchars($sale['id']) ?></td>
                    <td data-label="Item Image" class="img-cell">
                        <?php if (!empty($sale['item_picture']) && file_exists("../uploads/" . $sale['item_picture'])): ?>
                            <img src="../uploads/<?= htmlspecialchars($sale['item_picture']) ?>" alt="<?= htmlspecialchars($sale['item_name']) ?>" />
                        <?php else: ?>
                            <span class="no-image">No Image</span>
                        <?php endif; ?>
                    </td>
                    <td data-label="Item Name"><?= htmlspecialchars($sale['item_name']) ?></td>
                    <td data-label="Quantity Sold"><?= (int)$sale['quantity_sold'] ?></td>
                    <td data-label="Total Price"><?= number_format($sale['total_price'], 2) ?></td>
                    <td data-label="Sale Date"><?= htmlspecialchars($sale['sale_date']) ?></td>
                    <td data-label="Sold By (Employee)"><?= htmlspecialchars($sale['sold_by']) ?></td>
                    <td data-label="Buyer Name"><?= htmlspecialchars($sale['buyer_name']) ?></td>
                    <td data-label="Buyer Address"><?= nl2br(htmlspecialchars($sale['buyer_address'])) ?></td>
                    <td data-label="Buyer Phone"><?= htmlspecialchars($sale['buyer_phone']) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr class="no-records">
                    <td colspan="10">No records found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
