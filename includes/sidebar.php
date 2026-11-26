<?php
// includes/sidebar.php
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
?>
<style>
/* ============================================
   SIDEBAR STYLE - DARK MODERN
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
}

* { margin: 0; padding: 0; box-sizing: border-box; }

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    background: var(--body-bg);
    color: var(--dark);
    line-height: 1.6;
    overflow-x: hidden;
}

.app-wrapper { display: flex; min-height: 100vh; }

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
    transition: width 0.2s ease;
}

.sidebar::-webkit-scrollbar { width: 4px; }
.sidebar::-webkit-scrollbar-thumb { background: var(--primary); border-radius: 4px; }
.sidebar { scrollbar-width: thin; scrollbar-color: var(--primary) transparent; }

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
    letter-spacing: 0.3px;
}
.sidebar-brand .brand-text span { color: var(--primary); }

.sidebar-brand .close-btn {
    background: transparent;
    border: none;
    color: var(--sidebar-text);
    font-size: 20px;
    padding: 4px 8px;
    cursor: pointer;
    margin-left: auto;
    display: none;
    border-radius: 4px;
}
.sidebar-brand .close-btn:hover { color: #fff; background: var(--sidebar-hover); }

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

.sidebar-nav .nav-item { list-style: none; }

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

.sidebar-nav .nav-link .fa-chevron-down {
    font-size: 11px;
    margin-left: auto;
    transition: transform 0.2s ease;
    color: var(--sidebar-text);
    opacity: 0.5;
}

.sidebar-nav .nav-link:hover { background: var(--sidebar-hover); color: #fff; }
.sidebar-nav .nav-link:hover i { color: var(--primary-light); }
.sidebar-nav .nav-link:hover span { color: #fff; }

.sidebar-nav .nav-item.active > .nav-link {
    background: var(--sidebar-active);
    color: #fff;
}
.sidebar-nav .nav-item.active > .nav-link i { color: var(--primary-light); }
.sidebar-nav .nav-item.active > .nav-link span { color: #fff; }

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

/* ============================================
   SUBMENU - CHECKBOX HACK
   ============================================ */
.sidebar-nav .has-submenu .submenu {
    display: none;
    padding-left: 8px;
    margin-left: 8px;
    border-left: 1px solid var(--sidebar-border);
}

.sidebar-nav .has-submenu.open .submenu {
    display: block;
}

.sidebar-nav .has-submenu.open > .nav-link .fa-chevron-down {
    transform: rotate(180deg);
}

.sidebar-nav .submenu .nav-link {
    padding: 8px 14px 8px 36px;
    font-size: 13px;
    font-weight: 400;
    min-height: 38px;
}

.sidebar-nav .submenu .nav-link i {
    font-size: 6px;
    width: 18px;
    opacity: 0.6;
    color: var(--sidebar-text);
}

.sidebar-nav .submenu .nav-item.active .nav-link {
    background: var(--sidebar-active);
    color: #fff;
}
.sidebar-nav .submenu .nav-item.active .nav-link i { opacity: 1; color: var(--primary-light); }
.sidebar-nav .submenu .nav-item.active .nav-link span { color: #fff; }

/* ============================================
   SIDEBAR COLLAPSED
   ============================================ */
.sidebar.collapsed {
    width: var(--sidebar-collapsed);
}

.sidebar.collapsed .brand-text,
.sidebar.collapsed .nav-link span,
.sidebar.collapsed .nav-link .fa-chevron-down,
.sidebar.collapsed .nav-section {
    display: none;
}

.sidebar.collapsed .nav-link {
    justify-content: center;
    padding: 10px;
    min-height: 44px;
}
.sidebar.collapsed .nav-link i { font-size: 20px; margin: 0; }
.sidebar.collapsed .submenu { display: none !important; }
.sidebar.collapsed .has-submenu.open .submenu { display: none !important; }

.sidebar.collapsed .sidebar-brand {
    justify-content: center;
    padding: 16px;
}
.sidebar.collapsed .sidebar-brand img { margin: 0; }
.sidebar.collapsed .nav-item.active > .nav-link::before { display: none; }

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
    transition: margin-left 0.2s ease;
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
.navbar-custom .dropdown-item:hover { background: rgba(108, 99, 255, 0.08); }

.mobile-toggle {
    display: none;
    background: transparent;
    border: none;
    color: var(--dark);
    font-size: 20px;
    padding: 4px 8px;
    cursor: pointer;
    min-height: 44px;
}

.content-wrapper {
    flex: 1;
    padding: 20px;
    background: var(--body-bg);
}

/* ============================================
   RESPONSIVE
   ============================================ */
@media (max-width: 992px) {
    .sidebar { width: var(--sidebar-collapsed); }
    .sidebar .brand-text,
    .sidebar .nav-link span,
    .sidebar .nav-link .fa-chevron-down,
    .sidebar .nav-section { display: none; }
    .sidebar .nav-link { justify-content: center; padding: 10px; min-height: 44px; }
    .sidebar .nav-link i { font-size: 20px; margin: 0; }
    .sidebar .submenu { display: none !important; }
    .sidebar .sidebar-brand { justify-content: center; padding: 16px; }
    .sidebar .sidebar-brand img { margin: 0; }
    .sidebar .nav-item.active > .nav-link::before { display: none; }
    .main-content { margin-left: var(--sidebar-collapsed); }
    
    .sidebar:hover { width: var(--sidebar-width); box-shadow: 2px 0 30px rgba(0,0,0,0.2); }
    .sidebar:hover .brand-text,
    .sidebar:hover .nav-link span,
    .sidebar:hover .nav-link .fa-chevron-down,
    .sidebar:hover .nav-section { display: inline; }
    .sidebar:hover .nav-link { justify-content: flex-start; padding: 10px 14px; }
    .sidebar:hover .nav-link i { font-size: 18px; margin-right: 14px; }
    .sidebar:hover .sidebar-brand { justify-content: flex-start; padding: 16px 20px; }
    .sidebar:hover .sidebar-brand img { margin-right: 12px; }
    .sidebar:hover .has-submenu.open .submenu { display: block !important; }
    .sidebar:hover .nav-item.active > .nav-link::before { display: block; }
}

@media (max-width: 768px) {
    .sidebar { width: 280px; transform: translateX(-100%); transition: transform 0.3s ease; box-shadow: 2px 0 30px rgba(0,0,0,0.2); }
    .sidebar.show { transform: translateX(0); }
    .sidebar .brand-text,
    .sidebar .nav-link span,
    .sidebar .nav-link .fa-chevron-down,
    .sidebar .nav-section { display: inline; }
    .sidebar .nav-link { justify-content: flex-start; padding: 10px 14px; min-height: 44px; }
    .sidebar .nav-link i { font-size: 18px; margin-right: 14px; }
    .sidebar .sidebar-brand { justify-content: flex-start; padding: 16px 20px; }
    .sidebar .sidebar-brand img { margin-right: 12px; }
    .sidebar .close-btn { display: block !important; }
    .sidebar .nav-item.active > .nav-link::before { display: block; }
    .sidebar.collapsed { width: 280px; }
    .sidebar.collapsed .brand-text,
    .sidebar.collapsed .nav-link span,
    .sidebar.collapsed .nav-link .fa-chevron-down,
    .sidebar.collapsed .nav-section { display: inline; }
    .sidebar.collapsed .nav-link { justify-content: flex-start; padding: 10px 14px; }
    .sidebar.collapsed .nav-link i { font-size: 18px; margin-right: 14px; }
    .sidebar.collapsed .sidebar-brand { justify-content: flex-start; padding: 16px 20px; }
    .sidebar.collapsed .sidebar-brand img { margin-right: 12px; }
    .sidebar.collapsed .submenu { display: none !important; }
    .sidebar.collapsed .has-submenu.open .submenu { display: block !important; }
    .main-content { margin-left: 0 !important; }
    
    .sidebar-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 1040;
    }
    .sidebar-overlay.show { display: block; }
    .mobile-toggle { display: block; }
    .content-wrapper { padding: 12px; }
    .card-body { padding: 16px; }
    .card-header { padding: 12px 16px; }
}

@media (max-width: 480px) {
    .sidebar { width: 260px; }
    .sidebar .nav-link { font-size: 13px; padding: 8px 12px; min-height: 38px; }
    .sidebar .nav-link i { font-size: 16px; margin-right: 12px; width: 20px; }
    .sidebar-brand .brand-text { font-size: 15px; }
    .sidebar-brand img { height: 28px; width: 28px; }
    .sidebar-brand { padding: 12px 16px; min-height: 56px; }
    .sidebar .submenu .nav-link { padding: 6px 12px 6px 32px; font-size: 12px; min-height: 34px; }
    .sidebar .submenu .nav-link i { font-size: 5px; width: 16px; }
    .sidebar .nav-section { font-size: 9px; padding: 8px 12px 4px; }
    .content-wrapper { padding: 8px; }
    .card-body { padding: 12px; }
    .card-header { padding: 10px 12px; }
}

@media (max-width: 360px) {
    .sidebar { width: 240px; }
    .sidebar .nav-link { font-size: 12px; padding: 6px 10px; min-height: 34px; }
    .sidebar .nav-link i { font-size: 14px; margin-right: 10px; width: 18px; }
    .sidebar-brand .brand-text { font-size: 13px; }
    .sidebar-brand img { height: 24px; width: 24px; }
    .sidebar-brand { padding: 10px 14px; min-height: 48px; }
    .sidebar .submenu .nav-link { padding: 4px 10px 4px 28px; font-size: 11px; min-height: 30px; }
    .sidebar .submenu .nav-link i { font-size: 4px; width: 14px; }
    .sidebar .nav-section { font-size: 8px; padding: 6px 10px 3px; }
}
</style>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <img src="<?= APP_URL ?>assets/images/logo.png" alt="Logo" onerror="this.style.display='none'">
        <span class="brand-text">Tatib<span>.</span></span>
        <button class="close-btn" onclick="closeMobileSidebar()" aria-label="Close sidebar">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <nav class="sidebar-nav">
        <!-- General -->
        <div class="nav-section">General</div>
        <ul class="nav flex-column">
            <li class="nav-item <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
                <a href="<?= APP_URL ?>admin/dashboard.php" class="nav-link">
                    <i class="fas fa-home"></i> <span>Dashboard</span>
                </a>
            </li>
        </ul>

        <!-- Store -->
        <div class="nav-section">Store</div>
        <ul class="nav flex-column">
            <li class="nav-item has-submenu <?= in_array($current_page, ['kelas.php', 'siswa.php', 'guru.php', 'jenis_pelanggaran.php']) ? 'open' : '' ?>">
                <a href="#" class="nav-link" onclick="toggleSubMenu(this); return false;">
                    <i class="fas fa-layer-group"></i> <span>Kelola Data</span>
                    <i class="fas fa-chevron-down ms-auto"></i>
                </a>
                <ul class="submenu">
                    <li class="nav-item <?= $current_page == 'kelas.php' ? 'active' : '' ?>">
                        <a href="<?= APP_URL ?>admin/kelas.php" class="nav-link"><i class="fas fa-circle"></i> Kelas</a>
                    </li>
                    <li class="nav-item <?= $current_page == 'siswa.php' ? 'active' : '' ?>">
                        <a href="<?= APP_URL ?>admin/siswa.php" class="nav-link"><i class="fas fa-circle"></i> Siswa</a>
                    </li>
                    <li class="nav-item <?= $current_page == 'guru.php' ? 'active' : '' ?>">
                        <a href="<?= APP_URL ?>admin/guru.php" class="nav-link"><i class="fas fa-circle"></i> Guru</a>
                    </li>
                    <li class="nav-item <?= $current_page == 'jenis_pelanggaran.php' ? 'active' : '' ?>">
                        <a href="<?= APP_URL ?>admin/jenis_pelanggaran.php" class="nav-link"><i class="fas fa-circle"></i> Jenis Pelanggaran</a>
                    </li>
                </ul>
            </li>
            
            <li class="nav-item <?= $current_page == 'riwayat.php' ? 'active' : '' ?>">
                <a href="<?= APP_URL ?>admin/riwayat.php" class="nav-link">
                    <i class="fas fa-history"></i> <span>Riwayat Pelanggaran</span>
                </a>
            </li>
            
            <li class="nav-item <?= $current_page == 'riwayat_prestasi.php' ? 'active' : '' ?>">
                <a href="<?= APP_URL ?>admin/riwayat_prestasi.php" class="nav-link">
                    <i class="fas fa-trophy"></i> <span>Riwayat Prestasi</span>
                </a>
            </li>
            
            <li class="nav-item <?= $current_page == 'laporan.php' ? 'active' : '' ?>">
                <a href="<?= APP_URL ?>admin/laporan.php" class="nav-link">
                    <i class="fas fa-file-alt"></i> <span>Laporan</span>
                </a>
            </li>
            
            <!-- ========================================== -->
            <!-- MENU LAPORAN ABSENSI -->
            <!-- ========================================== -->
            <li class="nav-item <?= $current_page == 'laporan_absensi.php' ? 'active' : '' ?>">
                <a href="<?= APP_URL ?>admin/laporan_absensi.php" class="nav-link">
                    <i class="fas fa-clipboard-list"></i> <span>Laporan Absensi</span>
                </a>
            </li>
        </ul>

        <!-- Tools -->
        <div class="nav-section">Tools</div>
        <ul class="nav flex-column">
            <li class="nav-item has-submenu <?= in_array($current_page, ['profil.php', 'api_wa.php', 'sistem.php']) ? 'open' : '' ?>">
                <a href="#" class="nav-link" onclick="toggleSubMenu(this); return false;">
                    <i class="fas fa-cog"></i> <span>Pengaturan</span>
                    <i class="fas fa-chevron-down ms-auto"></i>
                </a>
                <ul class="submenu">
                    <li class="nav-item <?= $current_page == 'profil.php' ? 'active' : '' ?>">
                        <a href="<?= APP_URL ?>admin/pengaturan/profil.php" class="nav-link"><i class="fas fa-circle"></i> Profil Sekolah</a>
                    </li>
                    <li class="nav-item <?= $current_page == 'api_wa.php' ? 'active' : '' ?>">
                        <a href="<?= APP_URL ?>admin/pengaturan/api_wa.php" class="nav-link"><i class="fas fa-circle"></i> API WhatsApp</a>
                    </li>
                    <li class="nav-item <?= $current_page == 'sistem.php' ? 'active' : '' ?>">
                        <a href="<?= APP_URL ?>admin/pengaturan/sistem.php" class="nav-link"><i class="fas fa-circle"></i> Sistem</a>
                    </li>
                </ul>
            </li>
        </ul>

        <!-- Logout -->
        <ul class="nav flex-column mt-3" style="border-top:1px solid var(--sidebar-border);padding-top:10px;">
            <li class="nav-item">
                <a href="<?= APP_URL ?>logout.php" class="nav-link" onclick="return confirm('Yakin ingin logout?')" style="color:#EF4444;">
                    <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                </a>
            </li>
        </ul>
    </nav>
</aside>

<!-- Mobile Overlay -->
<div id="sidebarOverlay" class="sidebar-overlay" onclick="closeMobileSidebar()"></div>

<script>
// ============================================
// SIDEBAR JAVASCRIPT - FUNGSI TOGGLE SUBMENU
// ============================================

// Fungsi toggle submenu - PASTI JALAN
function toggleSubMenu(element) {
    var parent = element.closest('.has-submenu');
    if (!parent) return;
    
    // Toggle class open
    parent.classList.toggle('open');
    
    // Toggle submenu display
    var submenu = parent.querySelector('.submenu');
    if (submenu) {
        submenu.style.display = parent.classList.contains('open') ? 'block' : 'none';
    }
    
    // Toggle chevron
    var chevron = element.querySelector('.fa-chevron-down');
    if (chevron) {
        chevron.style.transform = parent.classList.contains('open') ? 'rotate(180deg)' : 'rotate(0deg)';
    }
}

// Toggle sidebar collapse
function toggleSidebar() {
    var sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('collapsed');
    localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
}

// Mobile sidebar
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

// Document ready
document.addEventListener('DOMContentLoaded', function() {
    var mobileToggle = document.getElementById('mobileToggle');
    if (mobileToggle) {
        mobileToggle.addEventListener('click', function(e) {
            e.preventDefault();
            toggleMobileSidebar();
        });
    }
    
    if (window.innerWidth > 768) {
        var isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        if (isCollapsed) {
            document.getElementById('sidebar').classList.add('collapsed');
        }
    }
    
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
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeMobileSidebar();
    });
    
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

var originalToggleSidebar = window.toggleSidebar;
window.toggleSidebar = function() {
    if (window.innerWidth <= 768) {
        toggleMobileSidebar();
        return;
    }
    originalToggleSidebar();
};
</script>