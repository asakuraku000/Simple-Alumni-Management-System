<?php
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}

// Get admin info
$admin_info = fetchRow("SELECT * FROM admins WHERE id = ?", [$_SESSION['admin_id']]);
if (!$admin_info || !$admin_info['is_active']) {
    session_destroy();
    header('Location: index.php');
    exit();
}
?>
