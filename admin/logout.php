<?php
// Start admin session
session_name('admin_session');
session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: admin-login.php');
exit;
