<?php
// includes/header_siswa.php - Header untuk Halaman Siswa
if (!isset($_SESSION)) {
    session_start();
}

// Cek login
if (!isset($_SESSION['user_id'])) {
    header("Location: " . APP_URL . "login.php");
    exit;
}

// Ambil data user
$user_id = $_SESSION['user_id'];
$user = getRow("SELECT * FROM users WHERE id = $user_id");
$nama_user = $_SESSION['nama'] ?? 'Siswa';
$role = $_SESSION['role'] ?? 'siswa';

// $page_title harus didefinisikan di halaman sebelum include
$page_title = $page_title ?? 'Dashboard Siswa';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars($page_title) ?> - <?= APP_NAME ?></title>
    
    <!-- CSS - Dimuat di HEAD agar tidak flicker -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        /* ============================================
           RESET & GLOBAL - Dimuat pertama agar tidak flicker
           ============================================ */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f0f2f5;
            color: #1a1a2e;
            line-height: 1.6;
            overflow-x: hidden;
        }
        
        .app-wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        /* ============================================
           MAIN CONTENT
           ============================================ */
        .main-content {
            margin-left: 260px;
            flex: 1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background: #f0f2f5;
            transition: margin-left 0.3s ease;
        }
        
        /* ============================================
           NAVBAR
           ============================================ */
        .navbar-custom {
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            padding: 8px 12px;
            flex-shrink: 0;
            position: sticky;
            top: 0;
            z-index: 1040;
            min-height: 56px;
        }
        
        .navbar-custom .container-fluid {
            padding-left: 8px;
            padding-right: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .navbar-custom .brand-text {
            font-size: 16px;
            font-weight: 700;
            color: #1a1a2e;
            text-decoration: none;
        }
        .navbar-custom .brand-text span {
            color: #6C63FF;
        }
        
        .navbar-custom .avatar-circle {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: #6C63FF;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            flex-shrink: 0;
        }
        
        .navbar-custom .dropdown-menu {
            border: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border-radius: 12px;
            padding: 8px;
            min-width: 180px;
        }
        
        .navbar-custom .dropdown-item {
            border-radius: 8px;
            padding: 8px 14px;
            font-size: 13px;
            color: #1a1a2e;
        }
        .navbar-custom .dropdown-item:hover {
            background: rgba(108, 99, 255, 0.08);
        }
        .navbar-custom .dropdown-item i {
            width: 20px;
            text-align: center;
            color: #6C63FF;
        }
        .navbar-custom .dropdown-divider {
            margin: 4px 0;
        }
        
        .navbar-custom .user-info {
            font-size: 12px;
            color: #6b7280;
            margin-right: 8px;
            text-align: right;
            line-height: 1.3;
        }
        .navbar-custom .user-info .name {
            font-weight: 600;
            color: #1a1a2e;
            font-size: 13px;
        }
        .navbar-custom .user-info .role {
            font-size: 10px;
            color: #6C63FF;
            font-weight: 500;
        }
        
        .mobile-toggle {
            display: none;
            background: transparent;
            border: none;
            color: #1a1a2e;
            font-size: 20px;
            padding: 4px 8px;
            cursor: pointer;
            z-index: 1060;
        }
        
        .mobile-toggle:hover {
            color: #6C63FF;
        }
        
        /* ============================================
           CONTENT WRAPPER
           ============================================ */
        .content-wrapper {
            flex: 1;
            padding: 16px 20px 20px;
            background: #f0f2f5;
        }
        
        /* ============================================
           RESPONSIVE
           ============================================ */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0 !important;
            }
            
            .mobile-toggle {
                display: block !important;
            }
            
            .navbar-custom .user-info .name {
                font-size: 12px;
            }
            .navbar-custom .user-info .role {
                font-size: 9px;
            }
            
            .content-wrapper {
                padding: 12px 12px 16px;
            }
        }
        
        @media (max-width: 576px) {
            .navbar-custom {
                padding: 6px 8px;
                min-height: 48px;
            }
            
            .navbar-custom .container-fluid {
                padding-left: 4px;
                padding-right: 4px;
            }
            
            .navbar-custom .user-info {
                display: none;
            }
            
            .navbar-custom .brand-text {
                font-size: 14px;
            }
            
            .navbar-custom .avatar-circle {
                width: 30px;
                height: 30px;
                font-size: 11px;
            }
            
            .content-wrapper {
                padding: 8px 8px 12px;
            }
        }
    </style>
</head>
<body>
<div class="app-wrapper">
    <!-- SIDEBAR -->
    <?php include __DIR__ . '/sidebar_siswa.php'; ?>
    
    <!-- MAIN CONTENT -->
    <div class="main-content" id="mainContent">
        <!-- NAVBAR -->
        <nav class="navbar-custom">
            <div class="container-fluid">
                <div class="d-flex align-items-center gap-2">
                    <button class="mobile-toggle" id="mobileToggle" aria-label="Toggle Sidebar">
                        <i class="fas fa-bars"></i>
                    </button>
                    <a href="<?= APP_URL ?>siswa/dashboard_siswa.php" class="brand-text d-none d-sm-inline">
                        Tatib<span>.</span>
                    </a>
                </div>
                
                <div class="d-flex align-items-center gap-2">
                    <div class="user-info">
                        <div class="name"><?= htmlspecialchars($nama_user) ?></div>
                        <div class="role"><?= ucfirst($role) ?></div>
                    </div>
                    
                    <div class="dropdown">
                        <div class="avatar-circle" data-bs-toggle="dropdown" aria-expanded="false">
                            <?= strtoupper(substr($nama_user, 0, 1)) ?>
                        </div>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="<?= APP_URL ?>siswa/profil_siswa.php">
                                    <i class="fas fa-user me-2"></i> Profil
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= APP_URL ?>siswa/profil_siswa.php#ganti_password">
                                    <i class="fas fa-key me-2"></i> Ganti Password
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="<?= APP_URL ?>logout.php" onclick="return confirm('Yakin ingin logout?')">
                                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>
        
        <!-- CONTENT -->
        <div class="content-wrapper">