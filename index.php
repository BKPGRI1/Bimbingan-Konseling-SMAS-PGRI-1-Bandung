<?php
// index.php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// Cek login
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

// Redirect berdasarkan role
$role = $_SESSION['role'] ?? '';

switch ($role) {
    case 'admin':
        header("Location: admin/dashboard.php");
        break;
    case 'bk':
        header("Location: bk/dashboard_bk.php");
        break;
    case 'walikelas':
        header("Location: walikelas/dashboard_walikelas.php");
        break;
    case 'guru':
        header("Location: guru/dashboard_guru.php");
        break;
    default:
        // Jika role tidak dikenal, logout
        session_destroy();
        header("Location: login.php");
        break;
}
exit;
?>