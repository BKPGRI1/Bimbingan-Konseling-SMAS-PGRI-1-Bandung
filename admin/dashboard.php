<?php
// admin/dashboard.php
include '../includes/config.php';
checkLogin();

$page_title = 'Dashboard';
$conn = getDB();

// ============================================
// AMBIL DATA DENGAN PERHITUNGAN POIN BENAR
// ============================================

$total_siswa = getTotalSiswa();
$total_guru = getTotalGuru();
$total_kelas = getTotalKelas();
$total_pelanggaran = getTotalPelanggaran();
$total_prestasi = getTotalPrestasi();
$total_poin_prestasi = getTotalPoinPrestasi();
$pelanggaran_bulan_ini = getPelanggaranBulanIni();
$pelanggaran_hari_ini = getPelanggaranHariIni();
$grafik_data = getGrafikPelanggaran();
$pelanggaran_terbaru = getPelanggaranTerbaru(5);
$statistik_kelas = getStatistikKelas();

// ============================================
// AMBIL SISWA DENGAN POIN AKHIR YANG BENAR
// ============================================
$query_siswa = "SELECT s.*, k.nama_kelas
                FROM siswa s
                LEFT JOIN kelas k ON s.kelas_id = k.id";
$siswa_all = getRows($query_siswa);

$siswa_with_poin = [];
foreach ($siswa_all as $row) {
    // Hitung poin pelanggaran
    $q_pel = $conn->query("SELECT SUM(poin_diperoleh) as total FROM pelanggaran WHERE siswa_id = {$row['id']} AND status = 'disetujui'");
    $pel = $q_pel->fetch_assoc();
    $poin_pel = (int)$pel['total'];
    
    // Hitung poin prestasi
    $q_pres = $conn->query("SELECT SUM(poin_prestasi) as total FROM prestasi_siswa WHERE siswa_id = {$row['id']}");
    $pres = $q_pres->fetch_assoc();
    $poin_pres = (int)$pres['total'];
    
    // Poin akhir = pelanggaran - prestasi
    $poin_akhir = $poin_pel - $poin_pres;
    if ($poin_akhir < 0) $poin_akhir = 0;
    
    $row['poin_akhir'] = $poin_akhir;
    $row['poin_pelanggaran'] = $poin_pel;
    $row['poin_prestasi'] = $poin_pres;
    $row['total_prestasi'] = $conn->query("SELECT COUNT(*) as total FROM prestasi_siswa WHERE siswa_id = {$row['id']}")->fetch_assoc()['total'] ?? 0;
    $siswa_with_poin[] = $row;
}

// Urutkan berdasarkan poin akhir tertinggi
usort($siswa_with_poin, function($a, $b) {
    return $b['poin_akhir'] - $a['poin_akhir'];
});

// Ambil 5 teratas
$siswa_poin_tertinggi = array_slice($siswa_with_poin, 0, 5);

// ============================================
// PRESTASI TERBARU
// ============================================
$prestasi_terbaru = getRows("SELECT p.*, s.nama_siswa, s.nisn, k.nama_kelas
                             FROM prestasi_siswa p
                             LEFT JOIN siswa s ON p.siswa_id = s.id
                             LEFT JOIN kelas k ON s.kelas_id = k.id
                             ORDER BY p.created_at DESC LIMIT 5");

// ============================================
// DATA ABSENSI
// ============================================
$absensi_hari_ini = ['hadir' => 0, 'izin' => 0, 'alfa' => 0, 'sakit' => 0];

// Absensi hari ini (semua siswa)
$tanggal_hari_ini = date('Y-m-d');
$query_absensi = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'H' THEN 1 ELSE 0 END) as hadir,
                    SUM(CASE WHEN status = 'I' THEN 1 ELSE 0 END) as izin,
                    SUM(CASE WHEN status = 'A' THEN 1 ELSE 0 END) as alfa,
                    SUM(CASE WHEN status = 'S' THEN 1 ELSE 0 END) as sakit
                  FROM absensi 
                  WHERE tanggal = '$tanggal_hari_ini'";
$result_absensi = $conn->query($query_absensi);
$absensi_hari_ini = $result_absensi->fetch_assoc();

include '../includes/header.php';
?>

<div class="container-fluid dashboard-container">
    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card bg-primary text-white">
            <div class="stat-card-body">
                <div class="stat-content">
                    <div>
                        <h6 class="stat-label">Total Siswa</h6>
                        <h2 class="stat-number"><?= number_format($total_siswa) ?></h2>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <div class="stat-footer">
                    <small><i class="fas fa-user-graduate me-1"></i> Aktif</small>
                </div>
            </div>
        </div>
        
        <div class="stat-card bg-success text-white">
            <div class="stat-card-body">
                <div class="stat-content">
                    <div>
                        <h6 class="stat-label">Total Guru</h6>
                        <h2 class="stat-number"><?= number_format($total_guru) ?></h2>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                </div>
                <div class="stat-footer">
                    <small><i class="fas fa-user-check me-1"></i> Aktif</small>
                </div>
            </div>
        </div>
        
        <div class="stat-card bg-info text-white">
            <div class="stat-card-body">
                <div class="stat-content">
                    <div>
                        <h6 class="stat-label">Total Kelas</h6>
                        <h2 class="stat-number"><?= number_format($total_kelas) ?></h2>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-school"></i>
                    </div>
                </div>
                <div class="stat-footer">
                    <small><i class="fas fa-building me-1"></i> Rombel</small>
                </div>
            </div>
        </div>
        
        <div class="stat-card bg-warning text-white">
            <div class="stat-card-body">
                <div class="stat-content">
                    <div>
                        <h6 class="stat-label">Pelanggaran</h6>
                        <h2 class="stat-number"><?= number_format($pelanggaran_bulan_ini) ?></h2>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                </div>
                <div class="stat-footer">
                    <small><i class="fas fa-calendar-alt me-1"></i> Bulan Ini</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="quick-stats-grid">
        <div class="quick-stat-card">
            <div class="quick-stat-icon bg-danger">
                <i class="fas fa-calendar-day"></i>
            </div>
            <div class="quick-stat-info">
                <h6 class="quick-stat-label">Hari Ini</h6>
                <h4 class="quick-stat-number"><?= $pelanggaran_hari_ini ?></h4>
                <small class="text-muted">Pelanggaran</small>
            </div>
        </div>
        
        <div class="quick-stat-card">
            <div class="quick-stat-icon bg-primary">
                <i class="fas fa-users"></i>
            </div>
            <div class="quick-stat-info">
                <h6 class="quick-stat-label">Rata-rata</h6>
                <h4 class="quick-stat-number"><?= $total_siswa > 0 && $total_kelas > 0 ? round($total_siswa / $total_kelas) : 0 ?></h4>
                <small class="text-muted">Siswa/Kelas</small>
            </div>
        </div>
        
        <div class="quick-stat-card">
            <div class="quick-stat-icon bg-success">
                <i class="fas fa-star"></i>
            </div>
            <div class="quick-stat-info">
                <h6 class="quick-stat-label">Prestasi</h6>
                <h4 class="quick-stat-number"><?= $total_prestasi ?></h4>
                <small class="text-muted">+<?= $total_poin_prestasi ?> poin</small>
            </div>
        </div>
        
        <div class="quick-stat-card">
            <div class="quick-stat-icon bg-warning">
                <i class="fas fa-trophy"></i>
            </div>
            <div class="quick-stat-info">
                <h6 class="quick-stat-label">Poin Tertinggi</h6>
                <h4 class="quick-stat-number">
                    <?= !empty($siswa_poin_tertinggi) ? $siswa_poin_tertinggi[0]['poin_akhir'] ?? 0 : 0 ?>
                </h4>
                <small class="text-muted">Maksimal</small>
            </div>
        </div>
    </div>

    <!-- Card Absensi -->
    <div class="card absensi-card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title">
                <i class="fas fa-clipboard-check text-primary me-2"></i> Rekap Absensi Hari Ini
            </h5>
            <a href="<?= APP_URL ?>admin/laporan_absensi.php" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-arrow-right me-1"></i> Detail
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
                    <i class="fas fa-trophy text-warning me-2"></i> Top 5 Siswa
                </h5>
                <span class="badge bg-warning text-dark">Poin Tertinggi</span>
            </div>
            <div class="card-body top-list">
                <?php if (!empty($siswa_poin_tertinggi)): ?>
                    <?php foreach ($siswa_poin_tertinggi as $index => $siswa): ?>
                        <div class="top-item">
                            <div class="top-item-left">
                                <span class="top-rank">
                                    <?php if ($index == 0): ?>🥇
                                    <?php elseif ($index == 1): ?>🥈
                                    <?php elseif ($index == 2): ?>🥉
                                    <?php else: ?>#<?= $index + 1 ?><?php endif; ?>
                                </span>
                                <div class="top-name-wrap">
                                    <span class="top-name"><?= htmlspecialchars($siswa['nama_siswa']) ?></span>
                                    <small class="top-class">
                                        <?= htmlspecialchars($siswa['nama_kelas'] ?? 'Tanpa Kelas') ?>
                                        <?php if ($siswa['poin_prestasi'] > 0): ?>
                                            <span class="badge bg-success" style="font-size:0.5rem;">-<?= $siswa['poin_prestasi'] ?></span>
                                        <?php endif; ?>
                                    </small>
                                </div>
                            </div>
                            <span class="badge bg-danger top-poin"><?= $siswa['poin_akhir'] ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-user-slash fa-2x mb-2 d-block opacity-25"></i>
                        <p>Belum ada data siswa</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Prestasi Terbaru -->
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title">
                <i class="fas fa-trophy text-success me-2"></i> Prestasi Terbaru
            </h5>
            <a href="<?= APP_URL ?>admin/prestasi_siswa.php" class="btn btn-sm btn-outline-success">
                Lihat Semua <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0 recent-table">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center">No</th>
                            <th class="d-none d-sm-table-cell">No. Prestasi</th>
                            <th class="d-none d-sm-table-cell">NISN</th>
                            <th>Siswa</th>
                            <th class="d-none d-md-table-cell">Prestasi</th>
                            <th class="text-center">Poin</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($prestasi_terbaru)): ?>
                            <?php $no = 1; foreach ($prestasi_terbaru as $p): ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?></td>
                                    <td class="d-none d-sm-table-cell">
                                        <span class="badge bg-primary" style="font-size:0.55rem;"><?= $p['no_prestasi'] ?? 'PRS' ?></span>
                                    </td>
                                    <td class="d-none d-sm-table-cell">
                                        <span class="badge bg-light text-dark"><?= htmlspecialchars($p['nisn'] ?? '-') ?></span>
                                    </td>
                                    <td><strong><?= htmlspecialchars($p['nama_siswa']) ?></strong></td>
                                    <td class="d-none d-md-table-cell"><?= htmlspecialchars($p['nama_prestasi']) ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-success">+<?= $p['poin_prestasi'] ?? 0 ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block opacity-25"></i>
                                    Belum ada prestasi
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent & Class Stats -->
    <div class="bottom-grid">
        <div class="card recent-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title">
                    <i class="fas fa-clock text-info me-2"></i> Pelanggaran Terbaru
                </h5>
                <a href="<?= APP_URL ?>admin/riwayat.php" class="btn btn-sm btn-outline-primary">
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
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
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
        
        <div class="card class-stats-card">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="fas fa-chart-pie text-success me-2"></i> Statistik Kelas
                </h5>
            </div>
            <div class="card-body class-stats-body">
                <?php if (!empty($statistik_kelas)): ?>
                    <div class="class-stats-grid">
                        <?php foreach ($statistik_kelas as $kelas): ?>
                            <div class="class-stat-item">
                                <div class="class-stat-name"><?= htmlspecialchars($kelas['nama_kelas']) ?></div>
                                <div class="class-stat-count"><?= $kelas['jumlah'] ?></div>
                                <small>Siswa</small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-school fa-2x mb-2 d-block opacity-25"></i>
                        <p>Belum ada data kelas</p>
                    </div>
                <?php endif; ?>
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
   DASHBOARD STYLES - MODERN & RESPONSIF
   ============================================ */

.dashboard-container {
    padding: 12px !important;
    max-width: 100%;
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

.quick-stat-icon.bg-danger { background: #EF4444; }
.quick-stat-icon.bg-primary { background: #4F46E5; }
.quick-stat-icon.bg-success { background: #10B981; }
.quick-stat-icon.bg-warning { background: #F59E0B; }

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
    margin-bottom: 16px;
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

.top-rank {
    font-size: 0.9rem;
    flex-shrink: 0;
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

/* Bottom Grid */
.bottom-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 12px;
}

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

.class-stats-body {
    padding: 12px 16px;
}

.class-stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
}

.class-stat-item {
    background: #f8f9fa;
    padding: 8px 12px;
    border-radius: 8px;
    text-align: center;
}

.class-stat-name {
    font-weight: 600;
    font-size: 0.7rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.class-stat-count {
    font-size: 1.2rem;
    font-weight: 700;
    color: #4F46E5;
}

.class-stat-item small {
    font-size: 0.5rem;
    color: #6B7280;
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
    
    .bottom-grid {
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
    
    .top-rank {
        font-size: 0.7rem;
    }
    
    .top-poin {
        font-size: 0.55rem;
    }
    
    .class-stats-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 4px;
    }
    
    .class-stat-item {
        padding: 4px 6px;
    }
    
    .class-stat-name {
        font-size: 0.55rem;
    }
    
    .class-stat-count {
        font-size: 0.9rem;
    }
    
    .class-stat-item small {
        font-size: 0.4rem;
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
    
    .absensi-box-number {
        font-size: 1rem;
    }
    
    .absensi-box-label {
        font-size: 0.5rem;
    }
    
    .chart-wrapper {
        height: 120px;
    }
    
    .class-stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<?php include '../includes/footer.php'; ?>