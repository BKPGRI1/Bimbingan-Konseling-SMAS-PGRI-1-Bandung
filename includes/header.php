<?php
// includes/header.php
if (!isset($page_title)) {
    $page_title = 'Dashboard';
}

$user = getCurrentUser();
$pengaturan = getPengaturan();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> - <?= $page_title ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="app-wrapper">
        <?php include APP_PATH . 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <!-- Navbar -->
            <nav class="navbar navbar-custom">
                <div class="container-fluid">
                    <div class="d-flex align-items-center">
                        <button id="mobileToggle" class="mobile-toggle" aria-label="Toggle sidebar">
                            <i class="fas fa-bars"></i>
                        </button>
                        <button class="btn btn-sm btn-light d-none d-md-block" onclick="toggleSidebar()">
                            <i class="fas fa-bars"></i>
                        </button>
                    </div>
                    
                    <div class="d-flex align-items-center ms-auto">
                        <span class="me-3 d-none d-sm-inline text-muted" style="font-size:14px;">
                            <i class="fas fa-calendar-alt text-primary me-1"></i>
                            <?= date('d F Y') ?>
                        </span>
                        
                        <div class="dropdown">
                            <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                                <div class="avatar-circle">
                                    <?= strtoupper(substr($user['nama_lengkap'] ?? 'U', 0, 1)) ?>
                                </div>
                                <span class="ms-2 d-none d-md-inline text-dark" style="font-size:14px;">
                                    <?= $user['nama_lengkap'] ?? 'User' ?>
                                </span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="#">
                                    <i class="fas fa-user me-2"></i> Profil
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="<?= APP_URL ?>logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                                </a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>
            
            <div class="content-wrapper">