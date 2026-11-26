<?php
// includes/config.php
session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'tatib_sekolah');

// Application configuration
define('APP_NAME', 'Tatib Sekolah');
define('APP_URL', 'http://localhost/tatib/');
define('APP_PATH', __DIR__ . '/../');
define('APP_VERSION', '1.0.0');

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include files
require_once APP_PATH . 'includes/db.php';
require_once APP_PATH . 'includes/functions.php';

// Check login
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

function checkLogin() {
    if (!isLoggedIn()) {
        header("Location: " . APP_URL . "login.php");
        exit();
    }
    
    // Jika sudah login, cek role dan redirect ke halaman yang sesuai
    $user = getCurrentUser();
    if (!$user) {
        session_destroy();
        header("Location: " . APP_URL . "login.php");
        exit();
    }
    
    // Tentukan halaman berdasarkan role
    $current_page = basename($_SERVER['PHP_SELF']);
    $current_dir = basename(dirname($_SERVER['PHP_SELF']));
    
    $role = $user['role'];
    
    // Jika role kosong, set default
    if (empty($role)) {
        // Cek apakah ini siswa
        $conn = getDB();
        $cek_siswa = $conn->query("SELECT id FROM siswa WHERE nisn = '{$user['username']}'");
        if ($cek_siswa && $cek_siswa->num_rows > 0) {
            $role = 'siswa';
            $conn->query("UPDATE users SET role = 'siswa' WHERE id = " . $user['id']);
        } else {
            $role = 'guru';
        }
        $_SESSION['role'] = $role;
    }
    
    // Cek apakah user mengakses halaman yang sesuai dengan rolenya
    if ($role == 'siswa' && $current_dir != 'siswa') {
        header("Location: " . APP_URL . "siswa/dashboard_siswa.php");
        exit();
    } elseif ($role == 'admin' && $current_dir != 'admin') {
        header("Location: " . APP_URL . "admin/dashboard.php");
        exit();
    } elseif ($role == 'walikelas' && $current_dir != 'walikelas') {
        header("Location: " . APP_URL . "walikelas/dashboard_walikelas.php");
        exit();
    } elseif (in_array($role, ['guru', 'bk']) && $current_dir != 'guru') {
        header("Location: " . APP_URL . "guru/dashboard_guru.php");
        exit();
    }
    
    return true;
}

// Get current user
function getCurrentUser() {
    if (isLoggedIn()) {
        $conn = getDB();
        $id = (int)$_SESSION['user_id'];
        $query = "SELECT * FROM users WHERE id = $id";
        $result = $conn->query($query);
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
    }
    return null;
}

// Get user role
function getUserRole() {
    return isset($_SESSION['role']) ? $_SESSION['role'] : null;
}

// Check role access
function hasRole($roles) {
    if (!isLoggedIn()) return false;
    $role = getUserRole();
    return in_array($role, (array)$roles);
}

// Redirect ke dashboard sesuai role
function redirectToDashboard() {
    $role = getUserRole();
    $redirects = [
        'admin' => APP_URL . 'admin/dashboard.php',
        'guru' => APP_URL . 'guru/dashboard_guru.php',
        'bk' => APP_URL . 'bk/dashboard_bk.php',
        'walikelas' => APP_URL . 'walikelas/dashboard_walikelas.php',
        'siswa' => APP_URL . 'siswa/dashboard_siswa.php'
    ];
    
    if (isset($redirects[$role])) {
        header("Location: " . $redirects[$role]);
    } else {
        header("Location: " . APP_URL . "login.php");
    }
    exit();
}
?>