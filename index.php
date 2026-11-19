<?php
require_once __DIR__ . '/config/database.php';

// Redirect to dashboard if logged in, otherwise to login
if (isLoggedIn()) {
    redirect('/dashboard.php');
} else {
    redirect('/login.php');
}
?>
