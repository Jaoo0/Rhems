<?php
session_start();
require_once '../config/db.php';

// Check if logged in and role employee
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header('Location: ../login.php');
    exit;
}

$error = '';
$success = '';

// This is a simple example checklist - you can customize the checklist items as needed
$checklist_items = [
    'Power Cable Connected',
    'Laptop Boots Successfully',
    'Wi-Fi Working',
    'Battery Charging',
    'Speakers Working',
    'Camera Working',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $checked_items = $_POST['checklist'] ?? [];
    $notes = trim($_POST['notes'] ?? '');

    // Store checklist result in DB - For simplicity, save as JSON string
    $stmt = $pdo->prepare("INSERT INTO hardware_checklists (user_id, checklist_result, notes, created_at) VALUES (?, ?, ?, NOW())");
    $saved = $stmt->execute([
        $_SESSION['user_id'],
        json_encode($checked_items),
        $notes,
    ]);

    if ($saved) {
        $success = "Checklist saved successfully.";
    } else {
        $error = "Failed to save checklist.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Hardware Checklist - Employee</title>
    <link rel="stylesheet" href="../assets/css/styles.css" />
</head>
<body>
    <h1>Hardware Checklist</h1>
    <a href="../dashboard.php">Back to Dashboard</a><br><br>

    <?php if ($error): ?>
        <p style="color:red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <?php if ($success): ?>
        <p style="color:green;"><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>

    <form method="POST">
        <fieldset>
            <legend>Checklist</legend>
            <?php foreach ($checklist_items as $item): ?>
                <label>
                    <input type="checkbox" name="checklist[]" value="<?= htmlspecialchars($item) ?>" />
                    <?= htmlspecialchars($item) ?>
                </label><br>
            <?php endforeach; ?>
        </fieldset><br>

        <label for="notes">Additional Notes:</label><br>
        <textarea name="notes" id="notes" rows="4" cols="50"></textarea><br><br>

        <button type="submit">Save Checklist</button>
    </form>
</body>
</html>
