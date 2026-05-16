<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$role = $_SESSION['role'] ?? '';
$username = htmlspecialchars($_SESSION['username']);
$user_id = $_SESSION['user_id'] ?? 0;

if (!in_array($role, ['manager', 'employee'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Get profile picture
$stmt = $pdo->prepare("SELECT profile_picture FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$profile_pic = $stmt->fetchColumn();

$avatar_path = 'uploads/default.jpg'; // default avatar
if ($profile_pic && file_exists(__DIR__ . '/uploads/' . $profile_pic)) {
    $avatar_path = 'uploads/' . $profile_pic;
}

// Check unread announcements count for employee
$unreadCount = 0;
if ($role === 'employee') {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM announcements a
        WHERE NOT EXISTS (
            SELECT 1 FROM announcement_reads ar
            WHERE ar.user_id = ? AND ar.announcement_id = a.id
        )
    ");
    $stmt->execute([$user_id]);
    $unreadCount = $stmt->fetchColumn();
}

// Auto scan employee files for menu (optional)
$employeeFiles = [];
if ($role === 'employee') {
    $employeeDir = __DIR__ . '/employee';
    if (is_dir($employeeDir)) {
        $files = scandir($employeeDir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            if (in_array($file, ['change_password.php', 'hardware_checklist.php', 'reset_password.php'])) {
                continue;
            }
            if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $employeeFiles[] = $file;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title><?= ucfirst($role) ?> Dashboard - RHEMS</title>
    <link rel="stylesheet" href="dashboard.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
</head>
<body>
    <aside class="sidebar" role="navigation" aria-label="<?= ucfirst($role) ?> Sidebar Navigation">
        <div class="user-info" role="region" aria-label="User information">
            <img src="<?= htmlspecialchars($avatar_path) ?>" alt="User Avatar" class="avatar" />
            <div class="user-details">
                <p><strong>
                    <?php
                    if ($role === 'manager') echo 'Manager';
                    elseif ($role === 'employee') echo 'Employee';
                    else echo 'User';
                    ?>:
                </strong></p>
                <p><?= $username ?></p>
            </div>
        </div>

        <nav class="sidebar-nav" role="menu">
            <a href="#" class="nav-link active" role="menuitem" aria-current="page">
                <i class="fa fa-tachometer-alt" aria-hidden="true"></i> Dashboard
            </a>

            <?php if ($role === 'manager'): ?>
                <a href="manager/view_inventory.php" class="nav-link" role="menuitem">
                    <i class="fa fa-boxes" aria-hidden="true"></i> View Inventory
                </a>
                <a href="manager/view_archived_inventory.php" class="nav-link" role="menuitem">
                    <i class="fa fa-archive" aria-hidden="true"></i> View Archived Items
                </a>
                <a href="manager/add_item.php" class="nav-link" role="menuitem">
                    <i class="fa fa-plus-square" aria-hidden="true"></i> Add New Item
                </a>
                <a href="manager/sales_report.php" class="nav-link" role="menuitem">
                    <i class="fa fa-chart-line" aria-hidden="true"></i> Sales Reports
                </a>
                <a href="manager/transaction_history.php" class="nav-link" role="menuitem">
                    <i class="fa fa-history" aria-hidden="true"></i> Transaction History
                </a>
                <a href="manager/export.php" class="nav-link" role="menuitem">
                    <i class="fa fa-file-export" aria-hidden="true"></i> Export CSV/PDF
                </a>
                <a href="manager/low_stock_alerts.php" class="nav-link" role="menuitem">
                    <i class="fa fa-exclamation-triangle" aria-hidden="true"></i> Low Stock Alerts
                </a>
                <a href="manager/add_employee.php" class="nav-link" role="menuitem">
                    <i class="fa fa-user-plus" aria-hidden="true"></i> Add New Employee
                </a>
                <a href="manager/create_announcement.php" class="nav-link" role="menuitem">
                    <i class="fa fa-bullhorn" aria-hidden="true"></i> Create Announcement
                </a>
            <?php elseif ($role === 'employee'): ?>
                <a href="employee/create_sale.php" class="nav-link" role="menuitem">
                    <i class="fa fa-cart-plus" aria-hidden="true"></i> Create Sale
                </a>
                <a href="employee/view_inventory.php" class="nav-link" role="menuitem">
                    <i class="fa fa-boxes" aria-hidden="true"></i> View Inventory
                </a>
                <a href="employee/announcements.php" class="nav-link announcement-link" role="menuitem" aria-label="Announcements">
                    <i class="fa fa-bullhorn" aria-hidden="true"></i> Announcements
                    <?php if ($unreadCount > 0): ?>
                        <span class="notification-dot" aria-hidden="true"></span>
                    <?php endif; ?>
                </a>
            <?php endif; ?>

            <a href="logout.php" class="logout-btn nav-link" role="menuitem">
                <i class="fa fa-sign-out-alt" aria-hidden="true"></i> Logout
            </a>
        </nav>
    </aside>

    <main class="main-content" role="main">
        <header class="dashboard-header">
            <div>
                <h1>Welcome, <?= $username ?>!</h1>
                <p><?= ucfirst($role) ?> Dashboard</p>
            </div>

            <?php if ($role === 'employee'): ?>
            <div class="header-buttons">
                <a href="employee/announcements.php" aria-label="Announcements">
                    <i class="fa fa-bullhorn"></i> Announcements
                    <?php if ($unreadCount > 0): ?>
                        
                    <?php endif; ?>
                </a>
                <a href="employee/change_password.php" aria-label="Change Password">
                    <i class="fa fa-key"></i> Change Password
                </a>
            </div>
            <?php endif; ?>
        </header>

        <section class="dashboard-cards" aria-label="Dashboard quick links">
            <?php if ($role === 'manager'): ?>
                <a href="manager/view_inventory.php" class="card card-inventory" role="link" aria-label="View Inventory">
                    <i class="fa fa-boxes fa-3x" aria-hidden="true"></i>
                    <span>Inventory</span>
                </a>
                <a href="manager/sales_report.php" class="card card-reports" role="link" aria-label="View Sales Reports">
                    <i class="fa fa-chart-line fa-3x" aria-hidden="true"></i>
                    <span>Sales Reports</span>
                </a>
                <a href="manager/transaction_history.php" class="card card-history" role="link" aria-label="View Transaction History">
                    <i class="fa fa-history fa-3x" aria-hidden="true"></i>
                    <span>Transaction History</span>
                </a>
                <a href="manager/low_stock_alerts.php" class="card card-alerts" role="link" aria-label="View Low Stock Alerts">
                    <i class="fa fa-exclamation-triangle fa-3x" aria-hidden="true"></i>
                    <span>Low Stock Alerts</span>
                </a>
                <a href="manager/add_employee.php" class="card card-users" role="link" aria-label="Add New Employee">
                    <i class="fa fa-user-plus fa-3x" aria-hidden="true"></i>
                    <span>Add Employee</span>
                </a>
                <a href="manager/create_announcement.php" class="card card-announcement" role="link" aria-label="Create Announcement">
                    <i class="fa fa-bullhorn fa-3x" aria-hidden="true"></i>
                    <span>Create Announcement</span>
                </a>
            <?php elseif ($role === 'employee'): ?>
                <a href="employee/create_sale.php" class="card card-custom" role="link" aria-label="Create Sale">
                    <i class="fa fa-cart-plus fa-3x" aria-hidden="true"></i>
                    <span>Create Sale</span>
                </a>
                <a href="employee/view_inventory.php" class="card card-custom" role="link" aria-label="View Inventory">
                    <i class="fa fa-boxes fa-3x" aria-hidden="true"></i>
                    <span>View Inventory</span>
                </a>
                <a href="employee/announcements.php" class="card card-announcement" role="link" aria-label="Announcements">
                    <i class="fa fa-bullhorn fa-3x" aria-hidden="true"></i>
                    <span>Announcements</span>
                    <?php if ($unreadCount > 0): ?>
                        <span class="notification-dot" aria-hidden="true"></span>
                    <?php endif; ?>
                </a>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
