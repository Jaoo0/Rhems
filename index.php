<?php
// index.php
session_start();

// If user is logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Otherwise, redirect to login
header('Location: login.php');
exit;
?>
