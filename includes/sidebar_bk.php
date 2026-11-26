<?php
// includes/sidebar_bk.php
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

// Tentukan menu aktif
$menu_dashboard = ($current_page == 'dashboard_bk.php') ? 'active' : '';
$menu_siswa_bermasalah = ($current_page == 'siswa_bermasalah.php') ? 'active' : '';
$menu_riwayat_pelanggaran = ($current_page == 'riwayat_pelanggaran_bk.php') ? 'active' : '';
$menu_monitoring_poin = ($current_page == 'monitoring_poin.php') ? 'active' : '';
$menu_catatan_konseling = ($current_page == 'catatan_konseling.php') ? 'active' : '';
$menu_laporan_bk = ($current_page == 'laporan_bk.php') ? 'active' : '';
$menu_prestasi = ($current_page == 'prestasi_siswa.php') ? 'active' : '';
$menu_profil_bk = ($current_page == 'profil_bk.php') ? 'active' : '';
?>
<style>
/* ============================================
   SIDEBAR STYLE - BK
   ============================================ */
:root {
    --primary: #6C63FF;
    --primary-dark: #5A52D5;
    --primary-light: #8B83FF;
    --sidebar-bg: #1A1A2E;
    --sidebar-text: #A8A8C8;
    --sidebar-hover: rgba(108, 99, 255, 0.1);
    --sidebar-active: rgba(108, 99, 255, 0.15);
    --sidebar-border: rgba(255,255,255,0.05);
    --sidebar-width: 260px;
    --sidebar-collapsed: 70px;
    --body-bg: #F0F2F5;
    --dark: #1A1A2E;
    --gray-light: #E2E8F0;
    --danger: #EF4444;
    --success: #10B981;
    --warning: #F59E0B;
    --info: #3B82F6;
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

/* ============================================
   SIDEBAR
   ============================================ */
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
    transition: width 0.3s ease, transform 0.3s ease;
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
    flex-shrink: 0;
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
    transition: all 0.2s;
}

.sidebar-nav .nav-link i {
    width: 22px;
    font-size: 18px;
    text-align: center;
    flex-shrink: 0;
    color: var(--sidebar-text);
    transition: all 0.2s;
}

.sidebar-nav .nav-link span {
    flex: 1;
    color: var(--sidebar-text);
    transition: all 0.2s;
}

.sidebar-nav .nav-link:hover {
    background: var(--sidebar-hover);
    color: #fff;
}
.sidebar-nav .nav-link:hover i {
    color: var(--primary-light);
}
.sidebar-nav .nav-link:hover span {
    color: #fff;
}

.sidebar-nav .nav-item.active > .nav-link {
    background: var(--sidebar-active);
    color: #fff;
}
.sidebar-nav .nav-item.active > .nav-link i {
    color: var(--primary-light);
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

/* Sidebar Collapsed */
.sidebar.collapsed {
    width: var(--sidebar-collapsed);
}

.sidebar.collapsed .brand-text,
.sidebar.collapsed .nav-link span,
.sidebar.collapsed .nav-section {
    display: none;
}

.sidebar.collapsed .nav-link {
    justify-content: center;
    padding: 10px;
    min-height: 44px;
}
.sidebar.collapsed .nav-link i {
    font-size: 20px;
    margin: 0;
}

.sidebar.collapsed .sidebar-brand {
    justify-content: center;
    padding: 16px;
}
.sidebar.collapsed .sidebar-brand img {
    margin: 0;
}

.sidebar.collapsed .nav-item.active > .nav-link::before {
    display: none;
}

/* ============================================
   MAIN CONTENT
   ============================================ */
.main-content {
    margin-left: var(--sidebar-width);
    flex: 1;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    background: var(--body-bg);
    transition: margin-left 0.3s ease;
}

.sidebar.collapsed ~ .main-content {
    margin-left: var(--sidebar-collapsed);
}

/* ============================================
   NAVBAR
   ============================================ */
.navbar-custom {
    background: #fff;
    border-bottom: 1px solid var(--gray-light);
    padding: 10px 0;
    flex-shrink: 0;
    position: sticky;
    top: 0;
    z-index: 1040;
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

.navbar-custom .dropdown-menu {
    border: none;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    border-radius: 12px;
    padding: 8px;
}

.navbar-custom .dropdown-item {
    border-radius: 8px;
    padding: 8px 16px;
    font-size: 14px;
}
.navbar-custom .dropdown-item:hover {
    background: rgba(108, 99, 255, 0.08);
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

/* ============================================
   RESPONSIVE
   ============================================ */
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

@media (max-width: 768px) {
    .sidebar {
        width: 280px !important;
        transform: translateX(-100%) !important;
        transition: transform 0.3s ease !important;
        position: fixed !important;
        box-shadow: 2px 0 30px rgba(0,0,0,0.2);
        top: 0;
        left: 0;
        height: 100vh;
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

<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <img src="<?= APP_URL ?>assets/images/logo.png" alt="Logo" onerror="this.style.display='none'">
        <span class="brand-text">Tatib<span>.</span></span>
        <button class="close-btn" onclick="closeMobileSidebar()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <nav class="sidebar-nav">
        <!-- DASHBOARD -->
        <div class="nav-section">Dashboard</div>
        <ul class="nav flex-column">
            <li class="nav-item <?= $menu_dashboard ?>">
                <a href="<?= APP_URL ?>bk/dashboard_bk.php" class="nav-link">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
        </ul>

        <!-- DATA SISWA -->
        <div class="nav-section">Data Siswa</div>
        <ul class="nav flex-column">
            <li class="nav-item <?= $menu_siswa_bermasalah ?>">
                <a href="<?= APP_URL ?>bk/siswa_bermasalah.php" class="nav-link">
                    <i class="fas fa-user-graduate"></i>
                    <span>Data Siswa Bermasalah</span>
                </a>
            </li>
        </ul>

        <!-- PRESTASI -->
        <div class="nav-section">Prestasi</div>
        <ul class="nav flex-column">
            <li class="nav-item <?= $menu_prestasi ?>">
                <a href="<?= APP_URL ?>bk/prestasi_siswa.php" class="nav-link">
                    <i class="fas fa-trophy"></i>
                    <span>Prestasi Siswa</span>
                </a>
            </li>
        </ul>

        <!-- PELANGGARAN -->
        <div class="nav-section">Pelanggaran</div>
        <ul class="nav flex-column">
            <li class="nav-item <?= $menu_riwayat_pelanggaran ?>">
                <a href="<?= APP_URL ?>bk/riwayat_pelanggaran_bk.php" class="nav-link">
                    <i class="fas fa-list"></i>
                    <span>Riwayat Pelanggaran</span>
                </a>
            </li>
            <li class="nav-item <?= $menu_monitoring_poin ?>">
                <a href="<?= APP_URL ?>bk/monitoring_poin.php" class="nav-link">
                    <i class="fas fa-chart-line"></i>
                    <span>Monitoring Poin</span>
                </a>
            </li>
        </ul>

        <!-- KONSELING -->
        <div class="nav-section">Konseling</div>
        <ul class="nav flex-column">
            <li class="nav-item <?= $menu_catatan_konseling ?>">
                <a href="<?= APP_URL ?>bk/catatan_konseling.php" class="nav-link">
                    <i class="fas fa-notes-medical"></i>
                    <span>Catatan Konseling</span>
                </a>
            </li>
        </ul>

        <!-- LAPORAN -->
        <div class="nav-section">Laporan</div>
        <ul class="nav flex-column">
            <li class="nav-item <?= $menu_laporan_bk ?>">
                <a href="<?= APP_URL ?>bk/laporan_bk.php" class="nav-link">
                    <i class="fas fa-file-alt"></i>
                    <span>Laporan BK</span>
                </a>
            </li>
        </ul>

        <!-- PROFIL -->
        <div class="nav-section">Akun</div>
        <ul class="nav flex-column">
            <li class="nav-item <?= $menu_profil_bk ?>">
                <a href="<?= APP_URL ?>bk/profil_bk.php" class="nav-link">
                    <i class="fas fa-user"></i>
                    <span>Profil</span>
                </a>
            </li>
        </ul>

        <!-- LOGOUT -->
        <ul class="nav flex-column mt-3" style="border-top:1px solid var(--sidebar-border);padding-top:10px;">
            <li class="nav-item">
                <a href="<?= APP_URL ?>logout.php" class="nav-link" onclick="return confirm('Yakin ingin logout?')" style="color:#EF4444;">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </nav>
</aside>

<div id="sidebarOverlay" class="sidebar-overlay" onclick="closeMobileSidebar()"></div>

<script>
// ============================================
// SIDEBAR JAVASCRIPT
// ============================================

function toggleSidebar() {
    var sidebar = document.getElementById('sidebar');
    var isMobile = window.innerWidth <= 768;
    
    if (isMobile) {
        sidebar.classList.toggle('show');
        document.getElementById('sidebarOverlay').classList.toggle('show');
        document.body.style.overflow = sidebar.classList.contains('show') ? 'hidden' : '';
    } else {
        sidebar.classList.toggle('collapsed');
        localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
    }
}

function toggleMobileSidebar() {
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('sidebarOverlay');
    if (!sidebar || !overlay) return;
    
    sidebar.classList.toggle('show');
    overlay.classList.toggle('show');
    document.body.style.overflow = sidebar.classList.contains('show') ? 'hidden' : '';
}

function closeMobileSidebar() {
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('sidebarOverlay');
    if (!sidebar || !overlay) return;
    
    sidebar.classList.remove('show');
    overlay.classList.remove('show');
    document.body.style.overflow = '';
}

document.addEventListener('DOMContentLoaded', function() {
    var mobileToggle = document.getElementById('mobileToggle');
    if (mobileToggle) {
        mobileToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            toggleSidebar();
        });
    }
    
    // Cek collapsed state di desktop
    if (window.innerWidth > 768) {
        var isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        if (isCollapsed) {
            document.getElementById('sidebar').classList.add('collapsed');
        }
    }
    
    // Handle resize
    var resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            if (window.innerWidth > 768) {
                closeMobileSidebar();
                document.body.style.overflow = '';
            }
        }, 250);
    });
    
    // Escape key close sidebar
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeMobileSidebar();
        }
    });
    
    // Click outside close sidebar (mobile)
    document.addEventListener('click', function(e) {
        var sidebar = document.getElementById('sidebar');
        var overlay = document.getElementById('sidebarOverlay');
        var mobileToggle = document.getElementById('mobileToggle');
        
        if (window.innerWidth <= 768 && sidebar && sidebar.classList.contains('show')) {
            var isClickInside = sidebar.contains(e.target);
            var isClickToggle = mobileToggle && mobileToggle.contains(e.target);
            var isClickOverlay = overlay && overlay.contains(e.target);
            
            if (!isClickInside && !isClickToggle && !isClickOverlay) {
                closeMobileSidebar();
            }
        }
    });
});

// Buat fungsi global agar bisa dipanggil dari onclick
window.toggleSidebar = toggleSidebar;
window.closeMobileSidebar = closeMobileSidebar;
</script>