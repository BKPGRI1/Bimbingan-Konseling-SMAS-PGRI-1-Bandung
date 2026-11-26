<?php
// guru/dashboard_guru.php
include '../includes/config.php';
checkLogin();

// Cek role
if (!hasRole(['guru', 'bk'])) {
    $_SESSION['toast'] = [
        'message' => 'Anda tidak memiliki akses ke halaman ini!',
        'type' => 'danger'
    ];
    header("Location: " . APP_URL . "login.php");
    exit();
}

$page_title = 'Dashboard Guru';

// Get data user
$user = getCurrentUser();
$guru_id = $user['id'];

// Statistik Hari Ini
$hari_ini = date('Y-m-d');
$pelanggaran_hari_ini = getPelanggaranByDate($hari_ini, $hari_ini);
$total_hari_ini = count($pelanggaran_hari_ini);

// Statistik Bulan Ini
$bulan_ini = date('Y-m');
$result = query("SELECT COUNT(*) as total FROM pelanggaran WHERE DATE_FORMAT(tanggal_pelanggaran, '%Y-%m') = '$bulan_ini'");
$total_bulan_ini = $result->fetch_assoc()['total'];

// Total Pelanggaran
$total_pelanggaran = getTotalPelanggaran();

// Pelanggaran by Guru
$result = query("SELECT COUNT(*) as total FROM pelanggaran WHERE guru_id = $guru_id");
$total_by_guru = $result->fetch_assoc()['total'];

// Grafik pelanggaran per bulan
$grafik_data = [];
for ($i = 1; $i <= 12; $i++) {
    $query = "SELECT COUNT(*) as total FROM pelanggaran 
              WHERE MONTH(tanggal_pelanggaran) = $i 
              AND YEAR(tanggal_pelanggaran) = " . date('Y');
    $result = query($query);
    $grafik_data[] = (int)$result->fetch_assoc()['total'];
}

// Pelanggaran terbaru
$pelanggaran_terbaru = getPelanggaranTerbaru(5);

// Statistik Status
$result = query("SELECT status, COUNT(*) as total FROM pelanggaran GROUP BY status");
$status_stats = [];
while ($row = $result->fetch_assoc()) {
    $status_stats[$row['status']] = $row['total'];
}

include '../includes/header_guru.php';
?>

<div class="container-fluid dashboard-container">
    <!-- Header -->
    <div class="dashboard-header">
        <div>
            <h4 class="dashboard-title">
                <i class="fas fa-chalkboard-teacher text-primary me-2"></i> Dashboard Guru
            </h4>
            <small class="text-muted dashboard-subtitle">
                <i class="fas fa-calendar-alt me-1"></i> <?= date('d F Y') ?>
            </small>
        </div>
        <div class="header-right">
            <span class="text-muted user-name">
                <i class="fas fa-user me-1"></i> <?= htmlspecialchars($user['nama_lengkap'] ?? 'Guru') ?>
            </span>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card bg-primary text-white">
            <div class="stat-card-body">
                <div class="stat-content">
                    <div>
                        <h6 class="stat-label">Pelanggaran Hari Ini</h6>
                        <h2 class="stat-number"><?= $total_hari_ini ?></h2>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                </div>
                <div class="stat-footer">
                    <small><i class="fas fa-clock me-1"></i> <?= date('d/m/Y') ?></small>
                </div>
            </div>
        </div>
        
        <div class="stat-card bg-warning text-white">
            <div class="stat-card-body">
                <div class="stat-content">
                    <div>
                        <h6 class="stat-label">Bulan Ini</h6>
                        <h2 class="stat-number"><?= number_format($total_bulan_ini) ?></h2>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                </div>
                <div class="stat-footer">
                    <small><i class="fas fa-calendar-alt me-1"></i> <?= date('F Y') ?></small>
                </div>
            </div>
        </div>
        
        <div class="stat-card bg-success text-white">
            <div class="stat-card-body">
                <div class="stat-content">
                    <div>
                        <h6 class="stat-label">Total Pelanggaran</h6>
                        <h2 class="stat-number"><?= number_format($total_pelanggaran) ?></h2>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-database"></i>
                    </div>
                </div>
                <div class="stat-footer">
                    <small><i class="fas fa-file-alt me-1"></i> Semua Waktu</small>
                </div>
            </div>
        </div>
        
        <div class="stat-card bg-danger text-white">
            <div class="stat-card-body">
                <div class="stat-content">
                    <div>
                        <h6 class="stat-label">Pelanggaran Saya</h6>
                        <h2 class="stat-number"><?= number_format($total_by_guru) ?></h2>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                </div>
                <div class="stat-footer">
                    <small><i class="fas fa-user me-1"></i> <?= htmlspecialchars($user['nama_lengkap']) ?></small>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Stats -->
    <div class="quick-stats-grid">
        <div class="quick-stat-card">
            <div class="quick-stat-icon bg-warning">
                <i class="fas fa-clock"></i>
            </div>
            <div class="quick-stat-info">
                <h6 class="quick-stat-label">Pending</h6>
                <h4 class="quick-stat-number"><?= $status_stats['pending'] ?? 0 ?></h4>
                <small class="text-muted">Menunggu</small>
            </div>
        </div>
        
        <div class="quick-stat-card">
            <div class="quick-stat-icon bg-success">
                <i class="fas fa-check"></i>
            </div>
            <div class="quick-stat-info">
                <h6 class="quick-stat-label">Disetujui</h6>
                <h4 class="quick-stat-number"><?= $status_stats['disetujui'] ?? 0 ?></h4>
                <small class="text-muted">Terverifikasi</small>
            </div>
        </div>
        
        <div class="quick-stat-card">
            <div class="quick-stat-icon bg-danger">
                <i class="fas fa-times"></i>
            </div>
            <div class="quick-stat-info">
                <h6 class="quick-stat-label">Ditolak</h6>
                <h4 class="quick-stat-number"><?= $status_stats['ditolak'] ?? 0 ?></h4>
                <small class="text-muted">Ditolak</small>
            </div>
        </div>
        
        <div class="quick-stat-card">
            <div class="quick-stat-icon bg-info">
                <i class="fas fa-users"></i>
            </div>
            <div class="quick-stat-info">
                <h6 class="quick-stat-label">Total Siswa</h6>
                <h4 class="quick-stat-number"><?= getTotalSiswa() ?></h4>
                <small class="text-muted">Seluruh Siswa</small>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="charts-grid">
        <div class="card chart-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title">
                    <i class="fas fa-chart-bar text-primary me-2"></i> Grafik Pelanggaran
                </h5>
                <span class="badge bg-primary"><?= date('Y') ?></span>
            </div>
            <div class="card-body chart-body">
                <div class="chart-wrapper">
                    <canvas id="pelanggaranChart"></canvas>
                </div>
            </div>
        </div>
        
        <div class="card top-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title">
                    <i class="fas fa-clock text-info me-2"></i> Pelanggaran Terbaru
                </h5>
                <a href="<?= APP_URL ?>guru/riwayat_hari_ini.php" class="btn btn-sm btn-outline-primary">
                    Lihat Semua
                </a>
            </div>
            <div class="card-body top-list">
                <?php if (!empty($pelanggaran_terbaru)): ?>
                    <?php foreach ($pelanggaran_terbaru as $p): ?>
                        <div class="top-item">
                            <div class="top-item-left">
                                <div class="top-name-wrap">
                                    <span class="top-name"><?= htmlspecialchars($p['nama_siswa']) ?></span>
                                    <small class="top-class"><?= htmlspecialchars($p['nama_pelanggaran']) ?></small>
                                </div>
                            </div>
                            <span class="badge bg-<?= getStatusBadge($p['status']) ?> top-poin">
                                <?= getStatusLabel($p['status']) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-2x mb-2 d-block opacity-25"></i>
                        <p>Belum ada pelanggaran</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent Violations Table -->
    <div class="card recent-card mt-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title">
                <i class="fas fa-list text-primary me-2"></i> Riwayat Pelanggaran Terbaru
            </h5>
            <a href="<?= APP_URL ?>guru/riwayat_hari_ini.php" class="btn btn-sm btn-outline-primary">
                Lihat Semua <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0 recent-table">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center">No</th>
                            <th class="d-none d-sm-table-cell">NISN</th>
                            <th>Siswa</th>
                            <th class="d-none d-md-table-cell">Pelanggaran</th>
                            <th class="d-none d-sm-table-cell">Tanggal</th>
                            <th class="text-center">Poin</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($pelanggaran_terbaru)): ?>
                            <?php $no = 1; foreach ($pelanggaran_terbaru as $p): ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?></td>
                                    <td class="d-none d-sm-table-cell">
                                        <span class="badge bg-light text-dark"><?= htmlspecialchars($p['nisn'] ?? '-') ?></span>
                                    </td>
                                    <td><strong><?= htmlspecialchars($p['nama_siswa']) ?></strong></td>
                                    <td class="d-none d-md-table-cell"><?= htmlspecialchars($p['nama_pelanggaran']) ?></td>
                                    <td class="d-none d-sm-table-cell"><?= formatTanggal($p['tanggal_pelanggaran']) ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-danger"><?= $p['poin_diperoleh'] ?? 0 ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-<?= getStatusBadge($p['status']) ?>">
                                            <?= getStatusLabel($p['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block opacity-25"></i>
                                    Belum ada pelanggaran
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('pelanggaranChart').getContext('2d');
    const chartData = <?= json_encode($grafik_data) ?>;
    
    const gradient = ctx.createLinearGradient(0, 0, 0, 250);
    gradient.addColorStop(0, 'rgba(108, 99, 255, 0.8)');
    gradient.addColorStop(1, 'rgba(108, 99, 255, 0.1)');
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
            datasets: [{
                label: 'Jumlah Pelanggaran',
                data: chartData,
                backgroundColor: gradient,
                borderColor: '#6C63FF',
                borderWidth: 2,
                borderRadius: 4,
                barPercentage: 0.6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        font: {
                            size: 10
                        }
                    },
                    grid: {
                        display: true,
                        color: 'rgba(0,0,0,0.05)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            size: 10
                        }
                    }
                }
            }
        }
    });
});
</script>

<style>
/* ============================================
   DASHBOARD STYLES - GURU
   ============================================ */

.dashboard-container {
    padding: 12px !important;
    max-width: 100%;
}

/* Header */
.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    margin-bottom: 20px;
    gap: 8px;
}

.dashboard-title {
    font-size: 1.25rem;
    margin-bottom: 0;
}

.dashboard-subtitle {
    font-size: 0.75rem;
}

.header-right {
    text-align: right;
}

.user-name {
    font-size: 0.85rem;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    margin-bottom: 16px;
}

.stat-card {
    border-radius: 10px;
    border: none;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}

.stat-card-body {
    padding: 14px 18px;
}

.stat-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.stat-label {
    font-size: 0.6rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    opacity: 0.7;
    margin-bottom: 0;
}

.stat-number {
    font-size: 1.6rem;
    font-weight: 700;
    margin-bottom: 0;
}

.stat-icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    background: rgba(255,255,255,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}

.stat-footer {
    margin-top: 4px;
    font-size: 0.55rem;
    opacity: 0.7;
}

/* Quick Stats */
.quick-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    margin-bottom: 20px;
}

.quick-stat-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}

.quick-stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 16px;
    flex-shrink: 0;
}

.quick-stat-icon.bg-warning { background: #F59E0B; }
.quick-stat-icon.bg-success { background: #10B981; }
.quick-stat-icon.bg-danger { background: #EF4444; }
.quick-stat-icon.bg-info { background: #3B82F6; }

.quick-stat-info {
    flex: 1;
    min-width: 0;
}

.quick-stat-label {
    font-size: 0.6rem;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    color: #6B7280;
    margin-bottom: 0;
}

.quick-stat-number {
    font-size: 1.2rem;
    font-weight: 700;
    margin-bottom: 0;
}

.quick-stat-info small {
    font-size: 0.55rem;
}

/* Charts Grid */
.charts-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 12px;
    margin-bottom: 0;
}

.chart-card {
    min-height: 280px;
}

.chart-body {
    padding: 12px;
}

.chart-wrapper {
    position: relative;
    height: 220px;
}

.top-card .card-body {
    padding: 0;
}

.top-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 14px;
    border-bottom: 1px solid #f0f0f0;
}

.top-item:last-child {
    border-bottom: none;
}

.top-item-left {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
    flex: 1;
}

.top-name-wrap {
    min-width: 0;
    flex: 1;
}

.top-name {
    font-weight: 600;
    font-size: 0.85rem;
    display: block;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.top-class {
    font-size: 0.6rem;
    color: #6B7280;
    display: block;
}

.top-poin {
    font-size: 0.65rem;
    flex-shrink: 0;
}

/* Recent Table */
.recent-card {
    margin-bottom: 0;
}

.recent-table {
    font-size: 0.75rem;
}

.recent-table th {
    font-size: 0.6rem;
    padding: 6px 10px;
}

.recent-table td {
    padding: 5px 10px;
    vertical-align: middle;
}

.recent-table .badge {
    font-size: 0.6rem;
    padding: 2px 6px;
}

/* Card */
.card {
    border: none;
    border-radius: 10px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    margin-bottom: 0;
}

.card-header {
    background: transparent;
    border-bottom: 1px solid #f0f0f0;
    padding: 10px 16px;
}

.card-title {
    font-size: 0.9rem;
    font-weight: 600;
    margin-bottom: 0;
}

/* ============================================
   RESPONSIVE
   ============================================ */

/* Tablet */
@media (max-width: 992px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
    }
    
    .quick-stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
    }
    
    .charts-grid {
        grid-template-columns: 1fr;
        gap: 12px;
    }
    
    .chart-wrapper {
        height: 200px;
    }
    
    .stat-number {
        font-size: 1.4rem;
    }
}

/* Mobile */
@media (max-width: 576px) {
    .dashboard-container {
        padding: 6px !important;
    }
    
    .dashboard-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 4px;
        margin-bottom: 12px;
    }
    
    .header-right {
        text-align: left;
        width: 100%;
    }
    
    .dashboard-title {
        font-size: 1rem;
    }
    
    .dashboard-subtitle {
        font-size: 0.65rem;
    }
    
    .user-name {
        font-size: 0.7rem;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 6px;
        margin-bottom: 10px;
    }
    
    .stat-card-body {
        padding: 8px 10px;
    }
    
    .stat-number {
        font-size: 1.1rem;
    }
    
    .stat-icon {
        width: 30px;
        height: 30px;
        font-size: 14px;
    }
    
    .stat-label {
        font-size: 0.5rem;
    }
    
    .stat-footer {
        font-size: 0.45rem;
    }
    
    .quick-stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 6px;
        margin-bottom: 10px;
    }
    
    .quick-stat-card {
        padding: 8px 10px;
        gap: 8px;
    }
    
    .quick-stat-icon {
        width: 30px;
        height: 30px;
        font-size: 12px;
    }
    
    .quick-stat-number {
        font-size: 1rem;
    }
    
    .quick-stat-label {
        font-size: 0.5rem;
    }
    
    .quick-stat-info small {
        font-size: 0.45rem;
    }
    
    .chart-wrapper {
        height: 150px;
    }
    
    .chart-card {
        min-height: 200px;
    }
    
    .recent-table {
        font-size: 0.6rem;
    }
    
    .recent-table th {
        font-size: 0.5rem;
        padding: 4px 6px;
    }
    
    .recent-table td {
        padding: 3px 6px;
    }
    
    .recent-table .badge {
        font-size: 0.5rem;
        padding: 1px 4px;
    }
    
    .top-item {
        padding: 4px 10px;
    }
    
    .top-name {
        font-size: 0.7rem;
    }
    
    .top-poin {
        font-size: 0.55rem;
    }
    
    .card-header {
        padding: 6px 10px !important;
    }
    
    .card-header h5 {
        font-size: 0.75rem !important;
    }
    
    .card-body {
        padding: 6px 10px !important;
    }
}

/* Very Small Mobile */
@media (max-width: 400px) {
    .stats-grid {
        gap: 4px;
    }
    
    .stat-card-body {
        padding: 4px 6px;
    }
    
    .stat-number {
        font-size: 0.9rem;
    }
    
    .stat-icon {
        width: 24px;
        height: 24px;
        font-size: 11px;
    }
    
    .stat-label {
        font-size: 0.4rem;
    }
    
    .stat-footer {
        font-size: 0.4rem;
    }
    
    .quick-stats-grid {
        gap: 4px;
    }
    
    .quick-stat-card {
        padding: 4px 6px;
        gap: 4px;
    }
    
    .quick-stat-icon {
        width: 24px;
        height: 24px;
        font-size: 10px;
    }
    
    .quick-stat-number {
        font-size: 0.8rem;
    }
    
    .chart-wrapper {
        height: 120px;
    }
}
</style>

<?php include '../includes/footer.php'; ?>