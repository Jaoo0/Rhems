<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    header('Location: ../login.php');
    exit;
}

$period = $_GET['period'] ?? 'daily';

$now = new DateTime('now', new DateTimeZone('Asia/Manila'));
$startDate = null;
$endDate = $now->format('Y-m-d 23:59:59');

switch ($period) {
    case 'weekly':
        $start = new DateTime('monday this week', new DateTimeZone('Asia/Manila'));
        $startDate = $start->format('Y-m-d 00:00:00');
        $endDate = (clone $start)->modify('+6 days')->format('Y-m-d 23:59:59');
        break;
    case 'monthly':
        $start = new DateTime('first day of this month', new DateTimeZone('Asia/Manila'));
        $startDate = $start->format('Y-m-d 00:00:00');
        $endDate = (clone $start)->modify('last day of this month')->format('Y-m-d 23:59:59');
        break;
    case 'daily':
    default:
        $startDate = $now->format('Y-m-d 00:00:00');
        $endDate = $now->format('Y-m-d 23:59:59');
        break;
}

$sql = "SELECT s.sale_date, s.quantity_sold, s.total_price, s.price_per_unit, s.sold_by,
               inv.name AS item_name, inv.picture AS item_picture, inv.description, inv.specs,
               u.username AS sold_by_username
        FROM sales s
        LEFT JOIN inventory inv ON s.item_id = inv.id
        LEFT JOIN users u ON s.sold_by = u.id
        WHERE s.sale_date BETWEEN ? AND ?
        ORDER BY s.sale_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$startDate, $endDate]);
$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

$fromDateFormatted = (new DateTime($startDate, new DateTimeZone('Asia/Manila')))->format('F j, Y g:i A');
$toDateFormatted = (new DateTime($endDate, new DateTimeZone('Asia/Manila')))->format('F j, Y g:i A');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Detailed Sales Report - RHEMS</title>
    <link rel="stylesheet" href="sales_report.css" />
</head>
<body>
    <h1>Sales Report</h1>
    <p><a href="../dashboard.php" class="back-link">← Back to Dashboard</a></p>

    <nav>
        <a href="?period=daily" class="<?= $period === 'daily' ? 'active' : '' ?>">Daily</a> |
        <a href="?period=weekly" class="<?= $period === 'weekly' ? 'active' : '' ?>">Weekly</a> |
        <a href="?period=monthly" class="<?= $period === 'monthly' ? 'active' : '' ?>">Monthly</a>
    </nav>

    <h2><?= ucfirst(htmlspecialchars($period)) ?> Sales Report</h2>
    <p>From <strong><?= htmlspecialchars($fromDateFormatted) ?></strong> to <strong><?= htmlspecialchars($toDateFormatted) ?></strong></p>

    <?php if (empty($sales)): ?>
        <p class="no-data">No sales found for <?= htmlspecialchars($period) ?> period.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Sale Date</th>
                    <th>Item</th>
                    <th>Image</th>
                    <th>Description</th>
                    <th>Specs</th>
                    <th>Qty</th>
                    <th>Price/Unit</th>
                    <th>Total</th>
                    <th>Sold By</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sales as $sale):
                    $saleDate = new DateTime($sale['sale_date'], new DateTimeZone('Asia/Manila'));
                    $formattedDate = $saleDate->format('F j, Y g:i A');

                    $itemName = !empty($sale['item_name']) ? $sale['item_name'] : 'Unknown Item';
                    $description = !empty($sale['description']) ? $sale['description'] : '-';
                    $specs = !empty($sale['specs']) ? $sale['specs'] : '-';
                    $qty = (int)$sale['quantity_sold'];
                    $price = number_format((float)$sale['price_per_unit'], 2);
                    $total = number_format((float)$sale['total_price'], 2);
                    $soldBy = !empty($sale['sold_by_username']) ? $sale['sold_by_username'] : '-';

                    $imagePath = '../uploads/' . ($sale['item_picture'] ?? '');
                    if (!file_exists($imagePath) || empty($sale['item_picture'])) {
                        $imagePath = '../uploads/default.jpg';
                    }
                ?>
                <tr>
                    <td data-label="Sale Date"><?= htmlspecialchars($formattedDate) ?></td>
                    <td data-label="Item"><?= htmlspecialchars($itemName) ?></td>
                    <td data-label="Image"><img src="<?= htmlspecialchars($imagePath) ?>" alt="Item Image"></td>
                    <td data-label="Description"><?= htmlspecialchars($description) ?></td>
                    <td data-label="Specs"><?= htmlspecialchars($specs) ?></td>
                    <td data-label="Qty"><?= $qty ?></td>
                    <td data-label="Price/Unit">₱<?= $price ?></td>
                    <td data-label="Total">₱<?= $total ?></td>
                    <td data-label="Sold By"><?= htmlspecialchars($soldBy) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>
