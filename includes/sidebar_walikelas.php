<?php
// includes/sidebar_walikelas.php - MENU WALI KELAS (BUKAN ADMIN)
$current_page = basename($_SERVER['PHP_SELF']);
?>
<style>
/* ============================================
   SIDEBAR STYLE - WALI KELAS
   ============================================ */
:root {
    --primary: #6C63FF;
    --sidebar-bg: #1A1A2E;
    --sidebar-text: #A8A8C8;
    --sidebar-hover: rgba(108, 99, 255, 0.1);
    --sidebar-active: rgba(108, 99, 255, 0.15);
    --sidebar-border: rgba(255,255,255,0.05);
    --sidebar-width: 260px;
    --body-bg: #F0F2F5;
    --dark: #1A1A2E;
    --gray-light: #E2E8F0;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    background: var(--body-bg);
    color: var(--dark);
    line-height: 1.6;
    overflow-x: hidden;
}

.app-wrapper {
    display: flex;
    min-height: 100vh;
}

.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: var(--sidebar-width);
    height: 100vh;
    background: var(--sidebar-bg);
    color: var(--sidebar-text);
    z-index: 1050;
    overflow-y: auto;
    overflow-x: hidden;
    display: flex;
    flex-direction: column;
    border-right: 1px solid var(--sidebar-border);
}

.sidebar::-webkit-scrollbar {
    width: 4px;
}
.sidebar::-webkit-scrollbar-thumb {
    background: var(--primary);
    border-radius: 4px;
}
.sidebar {
    scrollbar-width: thin;
    scrollbar-color: var(--primary) transparent;
}

.sidebar-brand {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px 20px;
    border-bottom: 1px solid var(--sidebar-border);
    min-height: 65px;
    flex-shrink: 0;
}

.sidebar-brand img {
    height: 32px;
    width: 32px;
    object-fit: contain;
    border-radius: 8px;
}

.sidebar-brand .brand-text {
    font-size: 17px;
    font-weight: 700;
    color: #fff;
    white-space: nowrap;
}
.sidebar-brand .brand-text span {
    color: var(--primary);
}

.sidebar-brand .close-btn {
    background: transparent;
    border: none;
    color: var(--sidebar-text);
    font-size: 20px;
    padding: 4px 8px;
    cursor: pointer;
    margin-left: auto;
    display: none;
}

.sidebar-nav {
    flex: 1;
    padding: 12px 12px 20px;
    overflow-y: auto;
}

.sidebar-nav .nav-section {
    padding: 12px 14px 6px;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    color: rgba(255,255,255,0.25);
}

.sidebar-nav .nav {
    flex-direction: column;
    gap: 2px;
    list-style: none;
    padding: 0;
    margin: 0;
}

.sidebar-nav .nav-item {
    list-style: none;
}

.sidebar-nav .nav-link {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 10px 14px;
    color: var(--sidebar-text);
    text-decoration: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 500;
    white-space: nowrap;
    cursor: pointer;
    position: relative;
    min-height: 44px;
}

.sidebar-nav .nav-link i {
    width: 22px;
    font-size: 18px;
    text-align: center;
    flex-shrink: 0;
    color: var(--sidebar-text);
}

.sidebar-nav .nav-link span {
    flex: 1;
    color: var(--sidebar-text);
}

.sidebar-nav .nav-link:hover {
    background: var(--sidebar-hover);
    color: #fff;
}
.sidebar-nav .nav-link:hover i {
    color: var(--primary);
}

.sidebar-nav .nav-item.active > .nav-link {
    background: var(--sidebar-active);
    color: #fff;
}
.sidebar-nav .nav-item.active > .nav-link i {
    color: var(--primary);
}
.sidebar-nav .nav-item.active > .nav-link span {
    color: #fff;
}

.sidebar-nav .nav-item.active > .nav-link::before {
    content: '';
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 3px;
    height: 24px;
    background: var(--primary);
    border-radius: 0 4px 4px 0;
}

.main-content {
    margin-left: var(--sidebar-width);
    flex: 1;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    background: var(--body-bg);
}

.navbar-custom {
    background: #fff;
    border-bottom: 1px solid var(--gray-light);
    padding: 10px 0;
    flex-shrink: 0;
}

.navbar-custom .avatar-circle {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: var(--primary);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 14px;
}

.mobile-toggle {
    display: none;
    background: transparent;
    border: none;
    color: var(--dark);
    font-size: 22px;
    padding: 4px 10px;
    cursor: pointer;
    min-height: 44px;
    min-width: 44px;
    z-index: 1060;
}

.sidebar-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1040;
}
.sidebar-overlay.show {
    display: block !important;
}

.content-wrapper {
    flex: 1;
    padding: 20px;
    background: var(--body-bg);
}

/* DESKTOP */
@media (min-width: 769px) {
    .sidebar {
        width: var(--sidebar-width) !important;
        transform: none !important;
        position: fixed !important;
    }
    .sidebar .brand-text,
    .sidebar .nav-link span,
    .sidebar .nav-section {
        display: inline !important;
    }
    .sidebar .nav-link {
        justify-content: flex-start !important;
        padding: 10px 14px !important;
    }
    .sidebar .sidebar-brand {
        justify-content: flex-start !important;
        padding: 16px 20px !important;
    }
    .sidebar .sidebar-brand img {
        margin-right: 12px !important;
    }
    .main-content {
        margin-left: var(--sidebar-width) !important;
    }
    .mobile-toggle {
        display: none !important;
    }
    .sidebar-overlay {
        display: none !important;
    }
}

/* MOBILE */
@media (max-width: 768px) {
    .sidebar {
        width: 280px !important;
        transform: translateX(-100%) !important;
        transition: transform 0.3s ease !important;
        position: fixed !important;
        box-shadow: 2px 0 30px rgba(0,0,0,0.2);
    }
    .sidebar.show {
        transform: translateX(0) !important;
    }
    .sidebar .brand-text,
    .sidebar .nav-link span,
    .sidebar .nav-section {
        display: inline !important;
    }
    .sidebar .nav-link {
        justify-content: flex-start !important;
        padding: 10px 14px !important;
        min-height: 44px;
    }
    .sidebar .nav-link i {
        font-size: 18px;
        margin-right: 14px;
    }
    .sidebar .sidebar-brand {
        justify-content: flex-start !important;
        padding: 16px 20px !important;
    }
    .sidebar .sidebar-brand img {
        margin-right: 12px;
    }
    .sidebar .close-btn {
        display: block !important;
    }
    .sidebar .nav-item.active > .nav-link::before {
        display: block;
    }
    .main-content {
        margin-left: 0 !important;
    }
    .mobile-toggle {
        display: block !important;
    }
    .sidebar-overlay.show {
        display: block !important;
    }
}

@media (max-width: 480px) {
    .sidebar {
        width: 260px !important;
    }
    .sidebar .nav-link {
        font-size: 13px;
        padding: 8px 12px;
        min-height: 38px;
    }
    .sidebar .nav-link i {
        font-size: 16px;
        margin-right: 12px;
        width: 20px;
    }
    .sidebar-brand .brand-text {
        font-size: 15px;
    }
    .sidebar-brand img {
        height: 28px;
        width: 28px;
    }
    .sidebar-brand {
        padding: 12px 16px;
        min-height: 56px;
    }
    .sidebar .nav-section {
        font-size: 9px;
        padding: 8px 12px 4px;
    }
}
</style>

<aside class="sidebar" id="sidebarWalikelas">
    <div class="sidebar-brand">
        <img src="<?= APP_URL ?>assets/images/logo.png" alt="Logo" onerror="this.style.display='none'">
        <span class="brand-text">Tatib<span>.</span></span>
        <button class="close-btn" onclick="closeSidebar()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <nav class="sidebar-nav">
        <!-- DASHBOARD -->
        <div class="nav-section">Dashboard</div>
        <ul class="nav flex-column">
            <li class="nav-item <?= $current_page == 'dashboard_walikelas.php' ? 'active' : '' ?>">
                <a href="<?= APP_URL ?>walikelas/dashboard_walikelas.php" class="nav-link">
                    <i class="fas fa-home"></i> <span>Dashboard</span>
                </a>
            </li>
        </ul>

        <!-- DATA SISWA -->
        <div class="nav-section">Data Siswa</div>
        <ul class="nav flex-column">
            <li class="nav-item <?= $current_page == 'data_siswa_kelas.php' ? 'active' : '' ?>">
                <a href="<?= APP_URL ?>walikelas/data_siswa_kelas.php" class="nav-link">
                    <i class="fas fa-users"></i> <span>Data Siswa Kelas</span>
                </a>
            </li>
            <li class="nav-item <?= $current_page == 'rekap_poin.php' ? 'active' : '' ?>">
                <a href="<?= APP_URL ?>walikelas/rekap_poin.php" class="nav-link">
                    <i class="fas fa-star"></i> <span>Rekap Poin Siswa</span>
                </a>
            </li>
        </ul>

        <!-- ABSENSI -->
        <div class="nav-section">Absensi</div>
        <ul class="nav flex-column">
            <li class="nav-item <?= $current_page == 'absensi_siswa.php' ? 'active' : '' ?>">
                <a href="<?= APP_URL ?>walikelas/absensi_siswa.php" class="nav-link">
                    <i class="fas fa-clipboard-check"></i> <span>Input Absensi</span>
                </a>
            </li>
            <li class="nav-item <?= $current_page == 'rekap_absensi.php' ? 'active' : '' ?>">
                <a href="<?= APP_URL ?>walikelas/rekap_absensi.php" class="nav-link">
                    <i class="fas fa-file-alt"></i> <span>Rekap Absensi</span>
                </a>
            </li>
        </ul>

        <!-- PELANGGARAN -->
        <div class="nav-section">Pelanggaran</div>
        <ul class="nav flex-column">
            <li class="nav-item <?= $current_page == 'riwayat_pelanggaran_walikelas.php' ? 'active' : '' ?>">
                <a href="<?= APP_URL ?>walikelas/riwayat_pelanggaran_walikelas.php" class="nav-link">
                    <i class="fas fa-history"></i> <span>Riwayat Pelanggaran</span>
                </a>
            </li>
            <li class="nav-item <?= $current_page == 'catatan_pembinaan.php' ? 'active' : '' ?>">
                <a href="<?= APP_URL ?>walikelas/catatan_pembinaan.php" class="nav-link">
                    <i class="fas fa-edit"></i> <span>Catatan Pembinaan</span>
                </a>
            </li>
            <li class="nav-item <?= $current_page == 'monitoring_siswa.php' ? 'active' : '' ?>">
                <a href="<?= APP_URL ?>walikelas/monitoring_siswa.php" class="nav-link">
                    <i class="fas fa-exclamation-triangle"></i> <span>Monitoring Siswa</span>
                </a>
            </li>
        </ul>

        <!-- LAPORAN -->
        <div class="nav-section">Laporan</div>
        <ul class="nav flex-column">
            <li class="nav-item <?= $current_page == 'cetak_rekap.php' ? 'active' : '' ?>">
                <a href="<?= APP_URL ?>walikelas/cetak_rekap.php" class="nav-link">
                    <i class="fas fa-file-alt"></i> <span>Cetak Rekap Siswa</span>
                </a>
            </li>
        </ul>

        <!-- PENGATURAN -->
        <div class="nav-section">Pengaturan</div>
        <ul class="nav flex-column">
            <li class="nav-item <?= $current_page == 'profil_walikelas.php' ? 'active' : '' ?>">
                <a href="<?= APP_URL ?>walikelas/profil_walikelas.php" class="nav-link">
                    <i class="fas fa-user"></i> <span>Profil</span>
                </a>
            </li>
        </ul>

        <!-- LOGOUT -->
        <ul class="nav flex-column mt-3" style="border-top:1px solid var(--sidebar-border);padding-top:10px;">
            <li class="nav-item">
                <a href="<?= APP_URL ?>logout.php" class="nav-link" onclick="return confirm('Yakin ingin logout?')" style="color:#EF4444;">
                    <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                </a>
            </li>
        </ul>
    </nav>
</aside>

<div id="sidebarOverlay" class="sidebar-overlay" onclick="closeSidebar()"></div>

<script>
// ============================================
// SIDEBAR JAVASCRIPT - WALI KELAS
// ============================================

function toggleSidebar() {
    var sidebar = document.getElementById('sidebarWalikelas');
    var overlay = document.getElementById('sidebarOverlay');
    
    if (!sidebar) return;
    
    if (window.innerWidth <= 768) {
        sidebar.classList.toggle('show');
        if (overlay) {
            overlay.classList.toggle('show');
        }
        document.body.style.overflow = sidebar.classList.contains('show') ? 'hidden' : '';
    }
}

function toggleSidebarPC() {
    // Untuk desktop - tidak digunakan (sidebar selalu terbuka)
    // Fungsi ini hanya untuk menghindari error
}

function closeSidebar() {
    var sidebar = document.getElementById('sidebarWalikelas');
    var overlay = document.getElementById('sidebarOverlay');
    
    if (!sidebar) return;
    
    sidebar.classList.remove('show');
    if (overlay) {
        overlay.classList.remove('show');
    }
    document.body.style.overflow = '';
}

// Document ready
document.addEventListener('DOMContentLoaded', function() {
    var mobileToggle = document.getElementById('mobileToggle');
    if (mobileToggle) {
        mobileToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            toggleSidebar();
        });
    }
    
    var resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            if (window.innerWidth > 768) {
                closeSidebar();
            }
        }, 250);
    });
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeSidebar();
        }
    });
    
    document.addEventListener('click', function(e) {
        var sidebar = document.getElementById('sidebarWalikelas');
        var overlay = document.getElementById('sidebarOverlay');
        var mobileToggle = document.getElementById('mobileToggle');
        
        if (window.innerWidth <= 768 && sidebar && sidebar.classList.contains('show')) {
            var isClickInside = sidebar.contains(e.target);
            var isClickToggle = mobileToggle && mobileToggle.contains(e.target);
            var isClickOverlay = overlay && overlay.contains(e.target);
            
            if (!isClickInside && !isClickToggle && !isClickOverlay) {
                closeSidebar();
            }
        }
    });
});

window.toggleSidebar = toggleSidebar;
window.closeSidebar = closeSidebar;
</script>