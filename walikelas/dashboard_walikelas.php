<?php
// walikelas/dashboard_walikelas.php
include '../includes/config.php';
checkLogin();

// Cek role
if (!hasRole('walikelas')) {
    $_SESSION['toast'] = [
        'message' => 'Anda tidak memiliki akses ke halaman ini!',
        'type' => 'danger'
    ];
    header("Location: " . APP_URL . "login.php");
    exit();
}

$page_title = 'Dashboard Wali Kelas';

// Get data wali kelas
$user = getCurrentUser();
$kelas_wali = getRow("SELECT * FROM kelas WHERE wali_kelas_id = " . $user['id']);
$kelas_id = $kelas_wali ? $kelas_wali['id'] : 0;
$nama_kelas = $kelas_wali ? $kelas_wali['nama_kelas'] : 'Belum ditentukan';

// Statistik
$total_siswa = $kelas_id > 0 ? getJumlahSiswaKelas($kelas_id) : 0;
$siswa_list = $kelas_id > 0 ? getSiswaByKelas($kelas_id) : [];

// Pelanggaran
$pelanggaran_bulan_ini = 0;
$pelanggaran_hari_ini = 0;
$total_poin = 0;
$siswa_terbanyak_poin = null;

if ($kelas_id > 0 && !empty($siswa_list)) {
    $siswa_ids = array_column($siswa_list, 'id');
    $siswa_ids_str = implode(',', $siswa_ids);
    
    $bulan = date('Y-m');
    $result = query("SELECT COUNT(*) as total FROM pelanggaran WHERE siswa_id IN ($siswa_ids_str) AND DATE_FORMAT(tanggal_pelanggaran, '%Y-%m') = '$bulan'");
    $pelanggaran_bulan_ini = $result->fetch_assoc()['total'];
    
    $hari = date('Y-m-d');
    $result = query("SELECT COUNT(*) as total FROM pelanggaran WHERE siswa_id IN ($siswa_ids_str) AND tanggal_pelanggaran = '$hari'");
    $pelanggaran_hari_ini = $result->fetch_assoc()['total'];
    
    $result = query("SELECT SUM(poin) as total FROM siswa WHERE id IN ($siswa_ids_str)");
    $total_poin = $result->fetch_assoc()['total'] ?? 0;
    
    $siswa_terbanyak_poin = getRow("SELECT id, nama_siswa, poin FROM siswa WHERE id IN ($siswa_ids_str) ORDER BY poin DESC LIMIT 1");
}

// Grafik pelanggaran per bulan
$grafik_data = [];
if ($kelas_id > 0) {
    for ($i = 1; $i <= 12; $i++) {
        $query = "SELECT COUNT(*) as total FROM pelanggaran p 
                  LEFT JOIN siswa s ON p.siswa_id = s.id 
                  WHERE s.kelas_id = $kelas_id 
                  AND MONTH(p.tanggal_pelanggaran) = $i 
                  AND YEAR(p.tanggal_pelanggaran) = " . date('Y');
        $result = query($query);
        $grafik_data[] = (int)$result->fetch_assoc()['total'];
    }
}

$siswa_top5 = [];
if ($kelas_id > 0) {
    $siswa_top5 = getRows("SELECT id, nama_siswa, poin FROM siswa WHERE kelas_id = $kelas_id ORDER BY poin DESC LIMIT 5");
}

$pelanggaran_terbaru = [];
if ($kelas_id > 0) {
    $pelanggaran_terbaru = getRows("SELECT p.*, s.nama_siswa, s.nisn, jp.nama_pelanggaran, jp.poin 
                                   FROM pelanggaran p 
                                   LEFT JOIN siswa s ON p.siswa_id = s.id 
                                   LEFT JOIN jenis_pelanggaran jp ON p.jenis_pelanggaran_id = jp.id 
                                   WHERE s.kelas_id = $kelas_id 
                                   ORDER BY p.created_at DESC LIMIT 5");
}

$siswa_l = 0;
$siswa_p = 0;
foreach ($siswa_list as $s) {
    if ($s['jenis_kelamin'] == 'L') $siswa_l++;
    else $siswa_p++;
}

// ============================================
// DATA ABSENSI
// ============================================
$absensi_hari_ini = ['hadir' => 0, 'izin' => 0, 'alfa' => 0, 'sakit' => 0];

if ($kelas_id > 0 && !empty($siswa_list)) {
    $siswa_ids = array_column($siswa_list, 'id');
    $siswa_ids_str = implode(',', $siswa_ids);
    
    // Absensi hari ini
    $tanggal_hari_ini = date('Y-m-d');
    $query_absensi = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN status = 'H' THEN 1 ELSE 0 END) as hadir,
                        SUM(CASE WHEN status = 'I' THEN 1 ELSE 0 END) as izin,
                        SUM(CASE WHEN status = 'A' THEN 1 ELSE 0 END) as alfa,
                        SUM(CASE WHEN status = 'S' THEN 1 ELSE 0 END) as sakit
                      FROM absensi 
                      WHERE siswa_id IN ($siswa_ids_str) AND tanggal = '$tanggal_hari_ini'";
    $result_absensi = query($query_absensi);
    $absensi_hari_ini = $result_absensi->fetch_assoc();
}

include '../includes/header.php';
include '../includes/sidebar_walikelas.php';
?>

<div class="container-fluid dashboard-container">
    <!-- Header -->
    <div class="dashboard-header">
        <div class="header-left">
            <h4 class="dashboard-title">
                <i class="fas fa-chalkboard text-primary me-2"></i> Dashboard Wali Kelas
            </h4>
            <small class="text-muted dashboard-subtitle">
                <i class="fas fa-users me-1"></i> Kelas: <strong><?= htmlspecialchars($nama_kelas) ?></strong>
                <?php if ($kelas_id > 0): ?>
                    <span class="badge bg-primary ms-1">ID: <?= $kelas_id ?></span>
                <?php endif; ?>
            </small>
        </div>
        <div class="header-right">
            <div class="text-muted user-name">
                <i class="fas fa-user me-1"></i> <?= htmlspecialchars(substr($user['nama_lengkap'] ?? 'Wali Kelas', 0, 15)) ?>
            </div>
            <div class="text-muted user-date">
                <i class="fas fa-calendar-alt me-1"></i> <?= date('d/m/Y') ?>
            </div>
        </div>
    </div>

    <?php if ($kelas_id == 0): ?>
        <div class="alert alert-warning alert-dismissible fade show">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Anda belum ditugaskan sebagai wali kelas. Silahkan hubungi administrator.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php else: ?>

    <!-- Statistik Cards -->
    <div class="stats-grid">
        <div class="stat-card bg-primary text-white">
            <div class="stat-card-body">
                <div class="stat-content">
                    <div>
                        <h6 class="stat-label">Siswa</h6>
                        <h2 class="stat-number"><?= $total_siswa ?></h2>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <div class="stat-footer">
                    <small><?= $siswa_l ?> L | <?= $siswa_p ?> P</small>
                </div>
            </div>
        </div>
        
        <div class="stat-card bg-warning text-white">
            <div class="stat-card-body">
                <div class="stat-content">
                    <div>
                        <h6 class="stat-label">Hari Ini</h6>
                        <h2 class="stat-number"><?= $pelanggaran_hari_ini ?></h2>
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
        
        <div class="stat-card bg-danger text-white">
            <div class="stat-card-body">
                <div class="stat-content">
                    <div>
                        <h6 class="stat-label">Bulan Ini</h6>
                        <h2 class="stat-number"><?= $pelanggaran_bulan_ini ?></h2>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                </div>
                <div class="stat-footer">
                    <small><i class="fas fa-calendar-alt me-1"></i> <?= date('M Y') ?></small>
                </div>
            </div>
        </div>
        
        <div class="stat-card bg-success text-white">
            <div class="stat-card-body">
                <div class="stat-content">
                    <div>
                        <h6 class="stat-label">Total Poin</h6>
                        <h2 class="stat-number"><?= number_format($total_poin) ?></h2>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-star"></i>
                    </div>
                </div>
                <div class="stat-footer">
                    <small>
                        <?php if ($siswa_terbanyak_poin): ?>
                            Tertinggi: <?= htmlspecialchars(substr($siswa_terbanyak_poin['nama_siswa'], 0, 12)) ?>
                        <?php endif; ?>
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Card Absensi -->
    <div class="card absensi-card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title">
                <i class="fas fa-clipboard-check text-primary me-2"></i> Rekap Absensi Hari Ini
            </h5>
            <a href="<?= APP_URL ?>walikelas/absensi_siswa.php" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-edit me-1"></i> Input
            </a>
        </div>
        <div class="card-body">
            <div class="row g-2">
                <div class="col-6 col-md-3">
                    <div class="absensi-box bg-success">
                        <div class="absensi-box-number"><?= $absensi_hari_ini['hadir'] ?? 0 ?></div>
                        <div class="absensi-box-label">Hadir</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="absensi-box bg-warning">
                        <div class="absensi-box-number"><?= $absensi_hari_ini['izin'] ?? 0 ?></div>
                        <div class="absensi-box-label">Izin</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="absensi-box bg-danger">
                        <div class="absensi-box-number"><?= $absensi_hari_ini['alfa'] ?? 0 ?></div>
                        <div class="absensi-box-label">Alfa</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="absensi-box bg-info">
                        <div class="absensi-box-number"><?= $absensi_hari_ini['sakit'] ?? 0 ?></div>
                        <div class="absensi-box-label">Sakit</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts & Top Students -->
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
                    <i class="fas fa-trophy text-warning me-2"></i> Top 5 Poin
                </h5>
                <span class="badge bg-warning text-dark">Peringatan</span>
            </div>
            <div class="card-body top-list">
                <?php if (!empty($siswa_top5)): ?>
                    <?php foreach ($siswa_top5 as $index => $siswa): ?>
                        <div class="top-item">
                            <div class="top-item-left">
                                <span class="top-rank">
                                    <?php if ($index == 0): ?>🥇
                                    <?php elseif ($index == 1): ?>🥈
                                    <?php elseif ($index == 2): ?>🥉
                                    <?php else: ?>#<?= $index + 1 ?><?php endif; ?>
                                </span>
                                <span class="top-name"><?= htmlspecialchars($siswa['nama_siswa']) ?></span>
                            </div>
                            <span class="badge bg-danger"><?= $siswa['poin'] ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center text-muted py-3">
                        <i class="fas fa-user-slash fa-2x mb-2 d-block opacity-25"></i>
                        <p>Belum ada data siswa</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent Violations -->
    <div class="card recent-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title">
                <i class="fas fa-clock text-info me-2"></i> Pelanggaran Terbaru
            </h5>
            <a href="<?= APP_URL ?>walikelas/riwayat_pelanggaran_walikelas.php" class="btn btn-sm btn-outline-primary">
                Lihat Semua <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0 recent-table">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center">No</th>
                            <th>Tanggal</th>
                            <th class="d-none d-sm-table-cell">NISN</th>
                            <th>Siswa</th>
                            <th class="d-none d-md-table-cell">Pelanggaran</th>
                            <th class="text-center">Poin</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($pelanggaran_terbaru)): ?>
                            <?php $no = 1; foreach ($pelanggaran_terbaru as $p): ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?></td>
                                    <td><?= formatTanggalIndo($p['tanggal_pelanggaran']) ?></td>
                                    <td class="d-none d-sm-table-cell">
                                        <span class="badge bg-light text-dark"><?= htmlspecialchars($p['nisn'] ?? '-') ?></span>
                                    </td>
                                    <td><strong><?= htmlspecialchars($p['nama_siswa']) ?></strong></td>
                                    <td class="d-none d-md-table-cell"><?= htmlspecialchars($p['nama_pelanggaran']) ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-danger"><?= $p['poin'] ?></span>
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

    <?php endif; ?>
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
   DASHBOARD STYLES - RESPONSIF OTOMATIS
   ============================================ */

/* Container */
.dashboard-container {
    padding: 12px !important;
    max-width: 100%;
}

/* Header */
.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    margin-bottom: 16px;
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
    font-size: 0.8rem;
}

.user-date {
    font-size: 0.65rem;
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
    padding: 12px 16px;
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
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0;
}

.stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: rgba(255,255,255,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}

.stat-footer {
    margin-top: 4px;
    font-size: 0.55rem;
    opacity: 0.7;
}

/* Absensi Card */
.absensi-card {
    margin-bottom: 16px;
}

.absensi-card .card-body {
    padding: 12px 16px;
}

.absensi-box {
    padding: 10px 8px;
    border-radius: 8px;
    text-align: center;
    color: #fff;
}

.absensi-box.bg-success { background: #10B981; }
.absensi-box.bg-warning { background: #F59E0B; }
.absensi-box.bg-danger { background: #EF4444; }
.absensi-box.bg-info { background: #3B82F6; }

.absensi-box-number {
    font-size: 1.5rem;
    font-weight: 700;
    line-height: 1.2;
}

.absensi-box-label {
    font-size: 0.65rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    opacity: 0.9;
}

/* Charts Grid */
.charts-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 12px;
    margin-bottom: 12px;
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
    padding: 6px 12px;
    border-bottom: 1px solid #f0f0f0;
    font-size: 0.8rem;
}

.top-item:last-child {
    border-bottom: none;
}

.top-item-left {
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 0;
}

.top-rank {
    font-size: 0.85rem;
    flex-shrink: 0;
}

.top-name {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.top-item .badge {
    font-size: 0.65rem;
    flex-shrink: 0;
}

/* Recent Table */
.recent-card {
    margin-top: 0;
}

.recent-table {
    font-size: 0.75rem;
}

.recent-table th {
    font-size: 0.6rem;
    padding: 6px 8px;
}

.recent-table td {
    padding: 4px 8px;
    vertical-align: middle;
}

.recent-table .badge {
    font-size: 0.6rem;
    padding: 2px 6px;
}

/* Alert */
.alert {
    font-size: 0.8rem;
    padding: 8px 12px;
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
    
    .charts-grid {
        grid-template-columns: 1fr;
        gap: 12px;
    }
    
    .chart-wrapper {
        height: 180px;
    }
    
    .stat-number {
        font-size: 1.3rem;
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
        margin-bottom: 8px;
    }
    
    .header-right {
        text-align: left;
        width: 100%;
    }
    
    .user-name {
        font-size: 0.7rem;
    }
    
    .user-date {
        font-size: 0.6rem;
    }
    
    .dashboard-title {
        font-size: 1rem;
    }
    
    .dashboard-subtitle {
        font-size: 0.65rem;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 6px;
        margin-bottom: 8px;
    }
    
    .stat-card-body {
        padding: 8px 10px;
    }
    
    .stat-number {
        font-size: 1rem;
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
    
    .absensi-box-number {
        font-size: 1.2rem;
    }
    
    .absensi-box-label {
        font-size: 0.55rem;
    }
    
    .absensi-box {
        padding: 8px 6px;
    }
    
    .chart-wrapper {
        height: 140px;
    }
    
    .chart-card {
        min-height: 200px;
    }
    
    .recent-table {
        font-size: 0.6rem;
    }
    
    .recent-table th {
        font-size: 0.5rem;
        padding: 4px 4px;
    }
    
    .recent-table td {
        padding: 3px 4px;
    }
    
    .recent-table .badge {
        font-size: 0.5rem;
        padding: 1px 4px;
    }
    
    .top-item {
        font-size: 0.7rem;
        padding: 4px 8px;
    }
    
    .top-rank {
        font-size: 0.7rem;
    }
    
    .top-item .badge {
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
        grid-template-columns: repeat(2, 1fr);
        gap: 4px;
    }
    
    .stat-card-body {
        padding: 4px 6px;
    }
    
    .stat-number {
        font-size: 0.85rem;
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
    
    .absensi-box-number {
        font-size: 1rem;
    }
    
    .absensi-box-label {
        font-size: 0.5rem;
    }
    
    .chart-wrapper {
        height: 120px;
    }
    
    .recent-table {
        font-size: 0.5rem;
    }
    
    .recent-table th,
    .recent-table td {
        padding: 2px 3px;
    }
}
</style>

<?php include '../includes/footer.php'; ?>