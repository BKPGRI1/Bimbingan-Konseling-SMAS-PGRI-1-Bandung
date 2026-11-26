<?php
// bk/dashboard_bk.php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Cek login
if (!isLoggedIn()) {
    header("Location: ../login.php");
    exit;
}

// Cek role (hanya BK)
if (!hasRole(['bk'])) {
    header("Location: ../dashboard.php");
    exit;
}

$page_title = 'Dashboard BK';
$conn = getDB();

// ============================================
// AMBIL DATA STATISTIK DENGAN PERHITUNGAN BENAR
// ============================================

$total_siswa = getTotalSiswa();
$total_pelanggaran = getTotalPelanggaran();
$total_prestasi = getTotalPrestasi();
$total_poin_prestasi = getTotalPoinPrestasi();
$pelanggaran_bulan_ini = getPelanggaranBulanIni();
$pelanggaran_hari_ini = getPelanggaranHariIni();

// ============================================
// SISWA BERMASALAH DENGAN POIN AKHIR YANG BENAR
// ============================================
$batas = getBatasPoin();
$query_siswa = "SELECT s.*, k.nama_kelas
                FROM siswa s
                LEFT JOIN kelas k ON s.kelas_id = k.id";
$siswa_all = getRows($query_siswa);

$siswa_bermasalah = [];
$siswa_bermasalah_count = 0;

foreach ($siswa_all as $row) {
    // Hitung poin pelanggaran
    $q_pel = $conn->query("SELECT SUM(poin_diperoleh) as total FROM pelanggaran WHERE siswa_id = {$row['id']} AND status = 'disetujui'");
    $pel = $q_pel->fetch_assoc();
    $poin_pel = (int)$pel['total'];
    
    // Hitung poin prestasi
    $q_pres = $conn->query("SELECT SUM(poin_prestasi) as total FROM prestasi_siswa WHERE siswa_id = {$row['id']}");
    $pres = $q_pres->fetch_assoc();
    $poin_pres = (int)$pres['total'];
    
    $poin_akhir = $poin_pel - $poin_pres;
    if ($poin_akhir < 0) $poin_akhir = 0;
    
    if ($poin_akhir >= $batas['peringatan']) {
        $row['poin_akhir'] = $poin_akhir;
        $row['poin_pelanggaran'] = $poin_pel;
        $row['poin_prestasi'] = $poin_pres;
        $siswa_bermasalah[] = $row;
        $siswa_bermasalah_count++;
    }
}

// Urutkan berdasarkan poin akhir tertinggi
usort($siswa_bermasalah, function($a, $b) {
    return $b['poin_akhir'] - $a['poin_akhir'];
});

// Ambil 5 teratas
$siswa_bermasalah_top5 = array_slice($siswa_bermasalah, 0, 5);

// ============================================
// PRESTASI TERBARU (DENGAN NILAI POSITIF)
// ============================================
$prestasi_terbaru = getRows("SELECT p.*, s.nama_siswa, s.nisn, k.nama_kelas
                             FROM prestasi_siswa p
                             LEFT JOIN siswa s ON p.siswa_id = s.id
                             LEFT JOIN kelas k ON s.kelas_id = k.id
                             ORDER BY p.created_at DESC LIMIT 5");

// ============================================
// PELANGGARAN TERBARU
// ============================================
$pelanggaran_terbaru = getPelanggaranTerbaru(5);

// ============================================
// STATUS KONSELING
// ============================================
$status_konseling = getStatusKonseling();
$konseling_pending = $status_konseling['pending'] ?? 0;
$konseling_proses = $status_konseling['proses'] ?? 0;
$konseling_selesai = $status_konseling['selesai'] ?? 0;

// ============================================
// GRAFIK PELANGGARAN PER BULAN
// ============================================
$grafik_bulan = getGrafikPelanggaran();

include __DIR__ . '/../includes/header_bk.php';
?>

<div class="container-fluid dashboard-container">
    <!-- Header -->
    <div class="dashboard-header">
        <div>
            <h4 class="dashboard-title">
                <i class="fas fa-chart-pie text-primary me-2"></i> Dashboard BK
            </h4>
            <small class="text-muted dashboard-subtitle">
                <i class="fas fa-calendar-alt me-1"></i> <?= date('d F Y') ?>
            </small>
        </div>
        <div>
            <span class="badge bg-info p-2">
                <i class="fas fa-user-check me-1"></i> <?= $_SESSION['nama'] ?? 'BK' ?>
            </span>
        </div>
    </div>

    <!-- Statistik Cards -->
    <div class="row g-2 mb-3">
        <div class="col-6 col-md-3">
            <div class="card stat-card">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="stat-label">Total Siswa</h6>
                        <h4 class="stat-number"><?= $total_siswa ?></h4>
                    </div>
                    <div class="stat-icon bg-primary">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="stat-label">Pelanggaran</h6>
                        <h4 class="stat-number"><?= $total_pelanggaran ?></h4>
                        <small class="stat-sub">Bulan ini: <?= $pelanggaran_bulan_ini ?></small>
                    </div>
                    <div class="stat-icon bg-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="stat-label">Prestasi</h6>
                        <h4 class="stat-number"><?= $total_prestasi ?></h4>
                        <small class="stat-sub">+<?= $total_poin_prestasi ?> poin</small>
                    </div>
                    <div class="stat-icon bg-success">
                        <i class="fas fa-star"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="stat-label">Konseling</h6>
                        <h4 class="stat-number"><?= $konseling_pending + $konseling_proses + $konseling_selesai ?></h4>
                        <small class="stat-sub">Pending: <?= $konseling_pending ?></small>
                    </div>
                    <div class="stat-icon bg-info">
                        <i class="fas fa-notes-medical"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Grafik dan Status -->
    <div class="row g-2 mb-3">
        <div class="col-12 col-md-8">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-bar me-2"></i> Grafik Pelanggaran per Bulan
                </div>
                <div class="card-body">
                    <div style="position:relative; height:180px;">
                        <canvas id="chartPelanggaran"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-pie me-2"></i> Status Konseling
                </div>
                <div class="card-body">
                    <div style="position:relative; height:120px;">
                        <canvas id="chartKonseling"></canvas>
                    </div>
                    <div class="row mt-2 text-center">
                        <div class="col-4">
                            <span class="badge bg-secondary">Pending</span>
                            <div class="fw-bold"><?= $konseling_pending ?></div>
                        </div>
                        <div class="col-4">
                            <span class="badge bg-warning">Proses</span>
                            <div class="fw-bold"><?= $konseling_proses ?></div>
                        </div>
                        <div class="col-4">
                            <span class="badge bg-success">Selesai</span>
                            <div class="fw-bold"><?= $konseling_selesai ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel Data Terbaru -->
    <div class="row g-2">
        <!-- Siswa Bermasalah -->
        <div class="col-12 col-md-4">
            <div class="card h-100">
                <div class="card-header">
                    <i class="fas fa-user-graduate me-2"></i> Siswa Bermasalah
                    <span class="badge bg-danger ms-2"><?= $siswa_bermasalah_count ?></span>
                </div>
                <div class="card-body p-0 d-flex flex-column">
                    <ul class="list-group list-group-flush flex-grow-1">
                        <?php if (count($siswa_bermasalah_top5) > 0): ?>
                            <?php foreach ($siswa_bermasalah_top5 as $row): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div class="list-item-text">
                                        <strong><?= htmlspecialchars($row['nama_siswa']) ?></strong>
                                        <br>
                                        <small class="text-muted">
                                            <?= $row['nisn'] ?> | <?= $row['nama_kelas'] ?? '-' ?>
                                            <?php if ($row['poin_prestasi'] > 0): ?>
                                                <span class="badge bg-success" style="font-size:6px;">-<?= $row['poin_prestasi'] ?></span>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    <span class="badge bg-danger rounded-pill"><?= $row['poin_akhir'] ?> poin</span>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="list-group-item text-center text-muted py-3">
                                <i class="fas fa-check-circle text-success"></i> Tidak ada siswa bermasalah
                            </li>
                        <?php endif; ?>
                    </ul>
                    <div class="card-footer text-center">
                        <a href="laporan_bk.php?jenis=siswa" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Prestasi Terbaru -->
        <div class="col-12 col-md-4">
            <div class="card h-100">
                <div class="card-header">
                    <i class="fas fa-trophy me-2"></i> Prestasi Terbaru
                    <span class="badge bg-success ms-2">+poin</span>
                </div>
                <div class="card-body p-0 d-flex flex-column">
                    <ul class="list-group list-group-flush flex-grow-1">
                        <?php if (count($prestasi_terbaru) > 0): ?>
                            <?php foreach ($prestasi_terbaru as $row): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div class="list-item-text">
                                        <strong><?= htmlspecialchars($row['nama_siswa']) ?></strong>
                                        <br>
                                        <small class="text-muted"><?= htmlspecialchars($row['nama_prestasi']) ?></small>
                                    </div>
                                    <span class="badge bg-success rounded-pill">+<?= $row['poin_prestasi'] ?></span>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="list-group-item text-center text-muted py-3">
                                <i class="fas fa-inbox"></i> Belum ada prestasi
                            </li>
                        <?php endif; ?>
                    </ul>
                    <div class="card-footer text-center">
                        <a href="prestasi_siswa.php" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pelanggaran Terbaru -->
        <div class="col-12 col-md-4">
            <div class="card h-100">
                <div class="card-header">
                    <i class="fas fa-list me-2"></i> Pelanggaran Terbaru
                </div>
                <div class="card-body p-0 d-flex flex-column">
                    <ul class="list-group list-group-flush flex-grow-1">
                        <?php if (count($pelanggaran_terbaru) > 0): ?>
                            <?php foreach ($pelanggaran_terbaru as $row): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div class="list-item-text">
                                        <strong><?= htmlspecialchars($row['nama_siswa']) ?></strong>
                                        <br>
                                        <small class="text-muted"><?= htmlspecialchars($row['nama_pelanggaran']) ?></small>
                                    </div>
                                    <span class="badge bg-danger rounded-pill"><?= $row['poin_diperoleh'] ?></span>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="list-group-item text-center text-muted py-3">
                                <i class="fas fa-inbox"></i> Belum ada pelanggaran
                            </li>
                        <?php endif; ?>
                    </ul>
                    <div class="card-footer text-center">
                        <a href="laporan_bk.php?jenis=semua_pelanggaran" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Catatan Cepat -->
    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-notes-medical me-2"></i> Catatan Konseling Aktif
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Siswa</th>
                                    <th>Topik</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $konseling_aktif = getRows("SELECT c.*, s.nama_siswa 
                                                           FROM catatan_konseling c 
                                                           LEFT JOIN siswa s ON c.siswa_id = s.id 
                                                           WHERE c.status IN ('pending','proses')
                                                           ORDER BY c.tanggal DESC LIMIT 5");
                                if (count($konseling_aktif) > 0):
                                ?>
                                    <?php foreach ($konseling_aktif as $row): ?>
                                        <tr>
                                            <td><?= date('d-m-Y', strtotime($row['tanggal'])) ?></td>
                                            <td><?= htmlspecialchars($row['nama_siswa'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($row['topik'] ?? '-') ?></td>
                                            <td>
                                                <span class="badge bg-<?= $row['status'] == 'proses' ? 'warning' : 'secondary' ?>">
                                                    <?= ucfirst($row['status']) ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="text-center text-muted py-2">Tidak ada konseling aktif</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer text-center">
                    <a href="konseling.php" class="btn btn-sm btn-outline-primary">Kelola Konseling</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Grafik Pelanggaran per Bulan
    var ctx1 = document.getElementById('chartPelanggaran').getContext('2d');
    new Chart(ctx1, {
        type: 'bar',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
            datasets: [{
                label: 'Jumlah Pelanggaran',
                data: <?= json_encode($grafik_bulan) ?>,
                backgroundColor: 'rgba(79, 70, 229, 0.6)',
                borderColor: '#4F46E5',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1 }
                }
            }
        }
    });

    // Grafik Status Konseling
    var ctx2 = document.getElementById('chartKonseling').getContext('2d');
    new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: ['Pending', 'Proses', 'Selesai'],
            datasets: [{
                data: [<?= $konseling_pending ?>, <?= $konseling_proses ?>, <?= $konseling_selesai ?>],
                backgroundColor: ['#6c757d', '#F59E0B', '#10B981'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } }
        }
    });
});
</script>

<style>
.dashboard-container {
    padding: 8px !important;
    max-width: 100%;
    overflow-x: hidden;
}

.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    margin-bottom: 12px;
    gap: 6px;
}

.dashboard-title {
    font-size: 1rem;
    margin-bottom: 0;
}

.dashboard-subtitle {
    font-size: 0.65rem;
}

.card {
    border: none;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    margin-bottom: 12px;
    background: #fff;
}

.card-header {
    background: transparent;
    border-bottom: 1px solid #e9ecef;
    padding: 10px 14px;
    font-weight: 600;
    font-size: 12px;
}

.card-body {
    padding: 14px;
}

/* Stat Cards */
.stat-card {
    margin-bottom: 0;
    height: 100%;
}

.stat-card .card-body {
    padding: 10px 12px;
}

.stat-label {
    font-size: 10px;
    color: #6c757d;
    margin-bottom: 0;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.stat-number {
    font-size: 22px;
    font-weight: 700;
    margin-bottom: 0;
    line-height: 1.2;
}

.stat-sub {
    font-size: 9px;
    color: #6c757d;
    display: block;
    margin-top: 2px;
}

.stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 18px;
    flex-shrink: 0;
}
.bg-primary { background: #4F46E5; }
.bg-danger { background: #EF4444; }
.bg-success { background: #10B981; }
.bg-info { background: #3B82F6; }

.list-group-item {
    font-size: 11px;
    padding: 8px 12px;
}
.list-group-item small {
    font-size: 10px;
}
.list-item-text {
    flex: 1;
    min-width: 0;
}
.list-item-text strong {
    display: block;
    font-size: 12px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.list-item-text small {
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.badge {
    font-size: 9px !important;
    padding: 3px 8px !important;
}

.table-sm {
    font-size: 10px;
}
.table-sm th {
    font-size: 9px;
    text-transform: uppercase;
    letter-spacing: 0.2px;
}
.table-sm td {
    padding: 4px 8px;
    vertical-align: middle;
}

.btn-sm {
    font-size: 10px !important;
    padding: 3px 10px !important;
}

/* ===== MOBILE FRIENDLY ===== */
@media (max-width: 768px) {
    .dashboard-container {
        padding: 6px !important;
    }
    .dashboard-header {
        flex-direction: column;
        align-items: stretch;
        gap: 4px;
    }
    .dashboard-title {
        font-size: 0.95rem;
    }
    .dashboard-subtitle {
        font-size: 0.6rem;
    }
    .stat-number {
        font-size: 18px;
    }
    .stat-icon {
        width: 34px;
        height: 34px;
        font-size: 15px;
    }
    .stat-label {
        font-size: 9px;
    }
    .stat-sub {
        font-size: 8px;
    }
    .stat-card .card-body {
        padding: 8px 10px;
    }
    .card-header {
        padding: 8px 12px;
        font-size: 11px;
    }
    .card-body {
        padding: 10px;
    }
    .list-group-item {
        font-size: 10px;
        padding: 6px 10px;
    }
    .list-item-text strong {
        font-size: 11px;
    }
    .list-item-text small {
        font-size: 9px;
    }
    .badge {
        font-size: 8px !important;
        padding: 2px 6px !important;
    }
    .btn-sm {
        font-size: 9px !important;
        padding: 2px 8px !important;
    }
}

@media (max-width: 576px) {
    .dashboard-container {
        padding: 4px !important;
    }
    .dashboard-header {
        flex-direction: column;
        align-items: stretch;
        gap: 3px;
    }
    .dashboard-title {
        font-size: 0.85rem;
    }
    .dashboard-title i {
        font-size: 0.9rem;
    }
    .dashboard-subtitle {
        font-size: 0.55rem;
    }
    .stat-number {
        font-size: 16px;
    }
    .stat-icon {
        width: 30px;
        height: 30px;
        font-size: 13px;
    }
    .stat-label {
        font-size: 8px;
    }
    .stat-sub {
        font-size: 7px;
    }
    .stat-card .card-body {
        padding: 6px 8px;
    }
    .card-header {
        padding: 6px 10px;
        font-size: 10px;
    }
    .card-header i {
        font-size: 10px;
    }
    .card-body {
        padding: 8px;
    }
    .list-group-item {
        font-size: 9px;
        padding: 5px 8px;
    }
    .list-item-text strong {
        font-size: 10px;
    }
    .list-item-text small {
        font-size: 8px;
    }
    .badge {
        font-size: 7px !important;
        padding: 2px 5px !important;
        border-radius: 10px !important;
    }
    .btn-sm {
        font-size: 8px !important;
        padding: 2px 6px !important;
    }
    .table-sm {
        font-size: 9px;
    }
    .table-sm th {
        font-size: 8px;
    }
    .table-sm td {
        padding: 3px 6px;
    }
    .row.g-2 {
        --bs-gutter-x: 0.5rem;
        --bs-gutter-y: 0.5rem;
    }
    .card-body canvas {
        max-height: 120px !important;
    }
}

#chartPelanggaran, #chartKonseling {
    width: 100% !important;
    height: auto !important;
}
</style>

<?php include '../includes/footer.php'; ?>