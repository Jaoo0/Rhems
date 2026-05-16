<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    header('Location: ../login.php');
    exit;
}

$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

if ($start_date && $end_date) {
    $start_date = htmlspecialchars($start_date);
    $end_date = htmlspecialchars($end_date);

    $date_filter = "s.sale_date BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'";
    $title = "sales_report_{$start_date}_to_{$end_date}";

    $query = "SELECT 
                DATE(s.sale_date) AS sale_date,
                i.name AS item_name,
                s.quantity_sold,
                i.price,
                (s.quantity_sold * i.price) AS total_price,
                s.buyer_name,
                s.buyer_phone,
                s.buyer_address
              FROM sales s
              JOIN inventory i ON s.item_id = i.id
              WHERE $date_filter
              ORDER BY s.sale_date DESC, s.id DESC";

    $stmt = $pdo->query($query);
    $sales = $stmt->fetchAll();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . strtolower(str_replace(' ', '_', $title)) . '.csv');

    $output = fopen('php://output', 'w');

    fputcsv($output, [
        'Sale Date',
        'Item Name',
        'Quantity Sold',
        'Price per Item',
        'Total Price',
        'Buyer Name',
        'Buyer Contact',
        'Buyer Address'
    ]);

    $total_quantity_all = 0;
    $total_sales_all = 0;

    foreach ($sales as $row) {
        fputcsv($output, [
            $row['sale_date'],
            $row['item_name'],
            $row['quantity_sold'],
            number_format($row['price'], 2),
            number_format($row['total_price'], 2),
            $row['buyer_name'],
            $row['buyer_phone'],
            $row['buyer_address']
        ]);
        $total_quantity_all += $row['quantity_sold'];
        $total_sales_all += $row['total_price'];
    }

    fputcsv($output, []);
    fputcsv($output, ['TOTAL', '', $total_quantity_all, '', number_format($total_sales_all, 2), '', '', '']);

    fclose($output);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Export Sales Report</title>
    <link rel="stylesheet" href="../manager/export.css" />
</head>
<body>
    <h1>Export Sales Report</h1>
    <form method="GET" action="">
        <label for="start_date">Start Date:</label>
        <input type="date" name="start_date" id="start_date" value="<?= htmlspecialchars($start_date) ?>" required>

        <label for="end_date" style="margin-left:1rem;">End Date:</label>
        <input type="date" name="end_date" id="end_date" value="<?= htmlspecialchars($end_date) ?>" required>

        <div class="form-actions">
            <button type="submit">Export CSV</button>
            <a href="../dashboard.php" class="back-btn" role="button">Back to Dashboard</a>
        </div>
    </form>
</body>
</html>
