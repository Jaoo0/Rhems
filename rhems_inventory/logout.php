<?php
// logout.php
session_start();

// Clear all session data
$_SESSION = [];
session_destroy();

// Redirect to login page
header('Location: login.php');
exit;
?>
