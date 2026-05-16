<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header('Location: ../login.php');
    exit;
}

$error = '';
$success = '';

// Fetch only non-archived inventory items
$stmt = $pdo->prepare("SELECT id, name, quantity, price FROM inventory WHERE archived = 0 ORDER BY name ASC");
$stmt->execute();
$items = $stmt->fetchAll();

// Initialize variables
$item_id = '';
$quantity_sold = '';
$buyer_name = '';
$buyer_address = '';
$buyer_phone = '';

// Hardware checklist defaults
$checklist = [
    'screen_ok' => false,
    'keyboard_ok' => false,
    'battery_ok' => false,
    'charger_ok' => false,
    'ports_ok' => false,
    'speakers_ok' => false,
    'wifi_ok' => false,
    'software_ok' => false,
    'physical_condition_ok' => false,
    'remarks' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_id = $_POST['item_id'] ?? '';
    $quantity_sold = (int)($_POST['quantity'] ?? 0);
    $buyer_name = trim($_POST['buyer_name'] ?? '');
    $buyer_address = trim($_POST['buyer_address'] ?? '');
    $buyer_phone = trim($_POST['buyer_phone'] ?? '');

    foreach ($checklist as $key => $value) {
        if ($key === 'remarks') {
            $checklist[$key] = trim($_POST['remarks'] ?? '');
        } else {
            $checklist[$key] = isset($_POST[$key]) ? true : false;
        }
    }

    if (!$item_id || $quantity_sold <= 0) {
        $error = "Please select an item and enter a valid quantity.";
    } elseif (empty($buyer_name) || empty($buyer_phone)) {
        $error = "Please enter buyer's name and contact number.";
    } else {
        // Check item details including archived status
        $stmt = $pdo->prepare("SELECT quantity, price, archived FROM inventory WHERE id = ?");
        $stmt->execute([$item_id]);
        $item = $stmt->fetch();

        if (!$item) {
            $error = "Selected item does not exist.";
        } elseif ($item['archived'] == 1) {
            $error = "Selected item is archived and cannot be sold.";
        } elseif ($item['quantity'] < $quantity_sold) {
            $error = "Not enough stock. Available quantity: " . $item['quantity'];
        } elseif ($item['price'] <= 0) {
            $error = "The price of the selected item is invalid.";
        } else {
            $price_per_unit = (float)$item['price'];
            $total_price = $price_per_unit * $quantity_sold;

            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare("INSERT INTO sales (item_id, quantity_sold, price_per_unit, total_price, sold_by, sale_date, buyer_name, buyer_address, buyer_phone)
                    VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?)");
                $stmt->execute([
                    $item_id,
                    $quantity_sold,
                    $price_per_unit,
                    $total_price,
                    $_SESSION['user_id'],
                    $buyer_name,
                    $buyer_address,
                    $buyer_phone
                ]);

                $sale_id = $pdo->lastInsertId();

                $stmt = $pdo->prepare("INSERT INTO hardware_checklist (
                    sale_id, screen_ok, keyboard_ok, battery_ok, charger_ok,
                    ports_ok, speakers_ok, wifi_ok, software_ok, physical_condition_ok, remarks
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $sale_id,
                    $checklist['screen_ok'], $checklist['keyboard_ok'], $checklist['battery_ok'],
                    $checklist['charger_ok'], $checklist['ports_ok'], $checklist['speakers_ok'],
                    $checklist['wifi_ok'], $checklist['software_ok'],
                    $checklist['physical_condition_ok'], $checklist['remarks']
                ]);

                $stmt = $pdo->prepare("UPDATE inventory SET quantity = quantity - ? WHERE id = ?");
                $stmt->execute([$quantity_sold, $item_id]);

                $pdo->commit();

                $success = "Sale and checklist recorded successfully!";
                $item_id = '';
                $quantity_sold = '';
                $buyer_name = '';
                $buyer_address = '';
                $buyer_phone = '';
                foreach ($checklist as $key => $val) {
                    $checklist[$key] = $key === 'remarks' ? '' : false;
                }

            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Error saving sale: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Sale - Employee</title>
    <link rel="stylesheet" href="create_sale.css">
</head>
<body>
    <div class="container">
        <h1>Create Sale with Hardware Checklist</h1>
        <a href="../dashboard.php" class="back-link">← Back to Dashboard</a>

        <?php if ($error): ?>
            <p class="error-message"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if ($success): ?>
            <p class="success-message"><?= htmlspecialchars($success) ?></p>
        <?php endif; ?>

        <form method="POST" class="sale-form">
            <label for="item_id">Select Item:</label>
            <select name="item_id" id="item_id" required>
                <option value="">-- Select Item --</option>
                <?php foreach ($items as $item): ?>
                    <option value="<?= htmlspecialchars($item['id']) ?>" <?= ($item['id'] == $item_id) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($item['name']) ?> (Available: <?= (int)$item['quantity'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="quantity">Quantity Sold:</label>
            <input type="number" name="quantity" id="quantity" min="1" required value="<?= htmlspecialchars($quantity_sold) ?>">

            <label for="buyer_name">Buyer Name:</label>
            <input type="text" name="buyer_name" id="buyer_name" required value="<?= htmlspecialchars($buyer_name) ?>">

            <label for="buyer_address">Buyer Address:</label>
            <textarea name="buyer_address" id="buyer_address"><?= htmlspecialchars($buyer_address) ?></textarea>

            <label for="buyer_phone">Buyer Contact Number:</label>
            <input type="text" name="buyer_phone" id="buyer_phone" required value="<?= htmlspecialchars($buyer_phone) ?>">

            <fieldset class="checklist-fieldset">
                <legend>Hardware Checklist (Check if OK):</legend>

                <label class="select-all-label">
                    <input type="checkbox" id="select_all"> <strong>Select All</strong>
                </label>

                <div class="checklist-group">
                    <?php
                    $labels = [
                        'screen_ok' => 'Screen / Display',
                        'keyboard_ok' => 'Keyboard',
                        'battery_ok' => 'Battery',
                        'charger_ok' => 'Charger / Adapter',
                        'ports_ok' => 'Ports (USB, HDMI, etc.)',
                        'speakers_ok' => 'Speakers',
                        'wifi_ok' => 'Wi-Fi / Network',
                        'software_ok' => 'Software Installed',
                        'physical_condition_ok' => 'Physical Condition'
                    ];
                    foreach ($labels as $key => $label): ?>
                        <label>
                            <input type="checkbox" class="checklist-item" name="<?= $key ?>" <?= $checklist[$key] ? 'checked' : '' ?>> <?= $label ?>
                        </label>
                    <?php endforeach; ?>
                </div>

                <label for="remarks">Remarks / Notes:</label>
                <textarea name="remarks" id="remarks"><?= htmlspecialchars($checklist['remarks']) ?></textarea>
            </fieldset>

            <button type="submit" class="submit-btn">Submit Sale</button>
        </form>
    </div>

    <script>
        document.getElementById('select_all').addEventListener('change', function() {
            const checked = this.checked;
            document.querySelectorAll('.checklist-item').forEach(cb => cb.checked = checked);
        });
    </script>
</body>
</html>
