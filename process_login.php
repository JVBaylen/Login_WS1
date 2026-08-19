<?php
require_once __DIR__ . '/db.php';

if ($user['role'] === 'admin') {
    header("Location: admin_dashboard.html");
    exit();
}

header("Location: userdash.html");
exit();
?>
