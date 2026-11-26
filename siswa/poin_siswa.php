<?php
// siswa/poin_siswa.php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Cek login
if (!isLoggedIn()) {
    header("Location: ../login.php");
    exit;
}

// Cek role siswa
if (!hasRole(['siswa'])) {
    header("Location: " . APP_URL . "dashboard.php");
    exit;
}

$page_title = 'Monitoring Poin';

// Include header siswa
include __DIR__ . '/../includes/header_siswa.php';

// Ambil data siswa berdasarkan username (NISN)
$nisn = $_SESSION['username'];
$siswa = getRow("SELECT s.*, k.nama_kelas 
                 FROM siswa s 
                 LEFT JOIN kelas k ON s.kelas_id = k.id 
                 WHERE s.nisn = '$nisn'");

if (!$siswa) {
    echo '<div class="alert alert-danger">Data siswa tidak ditemukan!</div>';
    include __DIR__ . '/../includes/footer.php';
    exit;
}

$siswa_id = $siswa['id'];
$nama_siswa = $siswa['nama_siswa'];
$kelas = $siswa['nama_kelas'] ?? 'Belum ditentukan';

// ============================================
// AMBIL DATA PELANGGARAN
// ============================================
$pelanggaran_list = getPelanggaranBySiswa($siswa_id);
$total_pelanggaran = count($pelanggaran_list);

// Hitung total poin pelanggaran (yang disetujui)
$total_poin_pelanggaran = 0;
foreach ($pelanggaran_list as $p) {
    if ($p['status'] == 'disetujui') {
        $total_poin_pelanggaran += $p['poin'];
    }
}

// ============================================
// AMBIL DATA PRESTASI
// ============================================
$conn = getDB();
$query_prestasi = "SELECT * FROM prestasi_siswa WHERE siswa_id = $siswa_id ORDER BY tahun DESC, created_at DESC";
$prestasi_list = getRows($query_prestasi);
$total_prestasi = count($prestasi_list);

// Hitung total poin prestasi
$total_poin_prestasi = 0;
foreach ($prestasi_list as $p) {
    $total_poin_prestasi += $p['poin_prestasi'];
}

// ============================================
// HITUNG TOTAL POIN AKHIR (PELANGGARAN - PRESTASI)
// ============================================
$total_poin = $total_poin_pelanggaran - $total_poin_prestasi;
if ($total_poin < 0) $total_poin = 0;

// ============================================
// LEVEL POIN
// ============================================
$level = getPoinLevel($total_poin);
$batas = getBatasPoin();

// Filter berdasarkan tanggal
$filter_tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : '';
$filter_kategori = isset($_GET['kategori']) ? $_GET['kategori'] : '';

if (!empty($filter_tanggal)) {
    $pelanggaran_list = array_filter($pelanggaran_list, function($p) use ($filter_tanggal) {
        return date('Y-m-d', strtotime($p['tanggal_pelanggaran'])) == $filter_tanggal;
    });
}

if (!empty($filter_kategori)) {
    $pelanggaran_list = array_filter($pelanggaran_list, function($p) use ($filter_kategori) {
        $jp = getJenisPelanggaranById($p['jenis_pelanggaran_id']);
        return $jp && $jp['kategori'] == $filter_kategori;
    });
}

// Ambil data untuk chart kategori
$kategori_count = ['ringan' => 0, 'sedang' => 0, 'berat' => 0];
foreach ($pelanggaran_list as $p) {
    $jp = getJenisPelanggaranById($p['jenis_pelanggaran_id']);
    if ($jp && isset($kategori_count[$jp['kategori']])) {
        $kategori_count[$jp['kategori']]++;
    }
}
?>

<div class="dashboard-container">
    <!-- Toast Notification -->
    <?php if (isset($_SESSION['toast'])): ?>
        <div id="toastNotification" class="toast-container position-fixed top-0 end-0 p-2" style="z-index: 9999;">
            <div class="toast align-items-center text-white bg-<?= $_SESSION['toast']['type'] ?> border-0 show" role="alert" aria-live="assertive" aria-atomic="true" style="min-width: 280px; max-width: 380px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.12); font-size: 13px;">
                <div class="d-flex align-items-center p-2">
                    <div class="flex-shrink-0 ms-1">
                        <i class="fas fa-<?= $_SESSION['toast']['type'] == 'success' ? 'check-circle' : ($_SESSION['toast']['type'] == 'warning' ? 'exclamation-triangle' : 'times-circle') ?> fa-sm"></i>
                    </div>
                    <div class="toast-body flex-grow-1 text-start ms-2" style="font-size: 13px; font-weight: 500; padding: 2px 0; line-height: 1.4;">
                        <?= $_SESSION['toast']['message'] ?>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-0" data-bs-dismiss="toast" aria-label="Close" style="font-size: 10px; padding: 4px;"></button>
                </div>
            </div>
        </div>
        <?php unset($_SESSION['toast']); ?>
    <?php endif; ?>

    <!-- Header -->
    <div class="dashboard-header">
        <div>
            <h4 class="dashboard-title">
                <i class="fas fa-chart-line text-primary me-1"></i> Monitoring Poin
            </h4>
            <small class="text-muted dashboard-subtitle">
                <?= htmlspecialchars($nama_siswa) ?> - Kelas <?= htmlspecialchars($kelas) ?>
            </small>
        </div>
        <div>
            <span class="badge bg-<?= $level['color'] ?>">
                <i class="fas <?= $level['icon'] ?> me-1"></i> <?= $level['level'] ?>
            </span>
            <span class="badge bg-danger ms-1">
                <i class="fas fa-star me-1"></i> <?= $total_poin ?> Poin
            </span>
        </div>
    </div>

    <!-- Ringkasan Poin -->
    <div class="row g-2 mb-3">
        <div class="col-4">
            <div class="stat-card bg-danger bg-opacity-10 text-danger">
                <div class="stat-card-body">
                    <div class="stat-number">+<?= $total_poin_pelanggaran ?></div>
                    <div class="stat-label">Pelanggaran</div>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="stat-card bg-success bg-opacity-10 text-success">
                <div class="stat-card-body">
                    <div class="stat-number">-<?= $total_poin_prestasi ?></div>
                    <div class="stat-label">Prestasi</div>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="stat-card bg-primary bg-opacity-10 text-primary">
                <div class="stat-card-body">
                    <div class="stat-number"><?= $total_poin ?></div>
                    <div class="stat-label">Total Poin</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Poin - Progress Bar -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="fas fa-info-circle me-2"></i> Status Poin
            <span class="info-badge">
                <?php 
                if ($total_poin >= $batas['do']) {
                    echo '⚠️ Batas DO (' . $batas['do'] . ' poin)';
                } elseif ($total_poin >= $batas['pemanggilan']) {
                    echo '📞 Batas panggil (' . $batas['pemanggilan'] . ' poin)';
                } elseif ($total_poin >= $batas['peringatan']) {
                    echo '📝 Batas teguran (' . $batas['peringatan'] . ' poin)';
                } else {
                    echo '✅ Poin aman';
                }
                ?>
            </span>
        </div>
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-3 col-sm-2 text-center">
                    <div class="poin-circle bg-<?= $level['color'] ?>">
                        <?= $total_poin ?>
                    </div>
                </div>
                <div class="col-9 col-sm-10">
                    <h5 class="status-level"><?= $level['level'] ?></h5>
                    <div class="progress" style="height:8px;">
                        <?php 
                        $max_poin = $batas['do'];
                        $persen = min(($total_poin / $max_poin) * 100, 100);
                        ?>
                        <div class="progress-bar bg-<?= $level['color'] ?>" 
                             role="progressbar" 
                             style="width: <?= $persen ?>%;">
                        </div>
                    </div>
                    <div class="d-flex justify-content-between small text-muted mt-1">
                        <span>0</span>
                        <span>DO: <?= $max_poin ?></span>
                    </div>
                    <div class="row g-1 mt-2">
                        <div class="col-4">
                            <div class="text-center p-1 rounded" style="background:#F3F4F6; font-size:11px;">
                                <span class="text-muted">Teguran</span>
                                <span class="fw-bold d-block"><?= $batas['peringatan'] ?></span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="text-center p-1 rounded" style="background:#FEF3C7; font-size:11px;">
                                <span class="text-muted">Panggilan</span>
                                <span class="fw-bold d-block"><?= $batas['pemanggilan'] ?></span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="text-center p-1 rounded" style="background:#FEE2E2; font-size:11px;">
                                <span class="text-muted">DO</span>
                                <span class="fw-bold d-block"><?= $batas['do'] ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistik Kategori Pelanggaran -->
    <div class="row g-2 mb-3">
        <div class="col-4">
            <div class="stat-card bg-success bg-opacity-10 text-success">
                <div class="stat-card-body">
                    <div class="stat-number"><?= $kategori_count['ringan'] ?></div>
                    <div class="stat-label">Ringan</div>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="stat-card bg-warning bg-opacity-10 text-warning">
                <div class="stat-card-body">
                    <div class="stat-number"><?= $kategori_count['sedang'] ?></div>
                    <div class="stat-label">Sedang</div>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="stat-card bg-danger bg-opacity-10 text-danger">
                <div class="stat-card-body">
                    <div class="stat-number"><?= $kategori_count['berat'] ?></div>
                    <div class="stat-label">Berat</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="fas fa-filter me-2"></i> Filter Data
        </div>
        <div class="card-body">
            <form method="GET" action="" class="row g-2">
                <div class="col-6 col-sm-4 col-md-3">
                    <label class="form-label">Tanggal</label>
                    <input type="date" class="form-control" name="tanggal" 
                           value="<?= $filter_tanggal ?>">
                </div>
                <div class="col-6 col-sm-4 col-md-3">
                    <label class="form-label">Kategori</label>
                    <select class="form-select" name="kategori">
                        <option value="">Semua</option>
                        <option value="ringan" <?= $filter_kategori == 'ringan' ? 'selected' : '' ?>>Ringan</option>
                        <option value="sedang" <?= $filter_kategori == 'sedang' ? 'selected' : '' ?>>Sedang</option>
                        <option value="berat" <?= $filter_kategori == 'berat' ? 'selected' : '' ?>>Berat</option>
                    </select>
                </div>
                <div class="col-12 col-sm-4 col-md-6 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-1"></i> Filter
                    </button>
                    <a href="<?= APP_URL ?>siswa/poin_siswa.php" class="btn btn-secondary">
                        <i class="fas fa-undo me-1"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Daftar Prestasi -->
    <div class="card mb-3">
        <div class="card-header d-flex align-items-center justify-content-between">
            <span><i class="fas fa-trophy text-warning me-2"></i> Daftar Prestasi</span>
            <span class="info-badge"><?= $total_prestasi ?> data</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="40" class="text-center">#</th>
                            <th>No. Prestasi</th>
                            <th>Prestasi</th>
                            <th class="text-center" width="80">Jenis</th>
                            <th class="text-center" width="80">Tingkat</th>
                            <th class="text-center" width="80">Tahun</th>
                            <th class="text-center" width="60">Poin</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($prestasi_list)): 
                            $no = 1;
                            foreach ($prestasi_list as $p): 
                                $jenis_badge = $p['jenis_prestasi'] == 'akademik' ? 'bg-success' : ($p['jenis_prestasi'] == 'non_akademik' ? 'bg-info' : 'bg-secondary');
                                $jenis_label = ucfirst(str_replace('_', ' ', $p['jenis_prestasi']));
                                $tingkat_badge = $p['tingkat'] == 'internasional' ? 'bg-danger' : ($p['tingkat'] == 'nasional' ? 'bg-warning' : ($p['tingkat'] == 'provinsi' ? 'bg-info' : 'bg-primary'));
                        ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td><span class="badge bg-primary"><?= $p['no_prestasi'] ?? 'PRS' ?></span></td>
                                <td><?= htmlspecialchars($p['nama_prestasi']) ?></td>
                                <td class="text-center">
                                    <span class="badge <?= $jenis_badge ?>">
                                        <?= $jenis_label ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge <?= $tingkat_badge ?>">
                                        <?= ucfirst($p['tingkat']) ?>
                                    </span>
                                </td>
                                <td class="text-center"><?= $p['tahun'] ?></td>
                                <td class="text-center">
                                    <span class="badge bg-success">-<?= $p['poin_prestasi'] ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="fas fa-trophy fa-2x mb-2 d-block opacity-25"></i>
                                    Belum ada catatan prestasi
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tabel Pelanggaran -->
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <span><i class="fas fa-list me-2"></i> Daftar Pelanggaran</span>
            <span class="info-badge"><?= count($pelanggaran_list) ?> data</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="40" class="text-center">#</th>
                            <th>Tanggal</th>
                            <th>Pelanggaran</th>
                            <th class="d-none d-sm-table-cell">Guru</th>
                            <th class="text-center" width="60">Poin</th>
                            <th class="text-center" width="80">Kategori</th>
                            <th class="text-center" width="90">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($pelanggaran_list)): 
                            $no = 1;
                            foreach ($pelanggaran_list as $p): 
                                $jp = getJenisPelanggaranById($p['jenis_pelanggaran_id']);
                                $kategori = $jp['kategori'] ?? 'ringan';
                                $badge_class = $kategori == 'berat' ? 'bg-danger' : ($kategori == 'sedang' ? 'bg-warning' : 'bg-success');
                                $status_class = $p['status'] == 'disetujui' ? 'bg-success' : ($p['status'] == 'ditolak' ? 'bg-danger' : 'bg-warning');
                        ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td><?= date('d-m-Y', strtotime($p['tanggal_pelanggaran'])) ?></td>
                                <td><?= htmlspecialchars($p['nama_pelanggaran'] ?? '-') ?></td>
                                <td class="d-none d-sm-table-cell"><?= htmlspecialchars($p['guru'] ?? '-') ?></td>
                                <td class="text-center">
                                    <span class="badge bg-danger">+<?= $p['poin'] ?? 0 ?></span>
                                </td>
                                <td class="text-center">
                                    <span class="badge <?= $badge_class ?>">
                                        <?= ucfirst($kategori) ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge <?= $status_class ?>">
                                        <?= ucfirst($p['status'] ?? 'pending') ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block opacity-25"></i>
                                    <?php if ($filter_tanggal || $filter_kategori): ?>
                                        Tidak ada pelanggaran dengan filter yang dipilih
                                    <?php else: ?>
                                        Belum ada catatan pelanggaran
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
/* ============================================
   STYLE MONITORING POIN - SEPERTI ADMIN
   ============================================ */
.dashboard-container {
    padding: 0 !important;
    max-width: 100%;
    overflow-x: hidden;
}

/* Header */
.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    margin-bottom: 12px;
    gap: 6px;
}

.dashboard-title {
    font-size: 0.95rem;
    margin-bottom: 0;
    font-weight: 600;
}

.dashboard-subtitle {
    font-size: 0.65rem;
    color: #6c757d;
    display: block;
}

.dashboard-header .badge {
    font-size: 0.6rem !important;
    padding: 4px 10px !important;
}

/* Card */
.card {
    border: none;
    border-radius: 8px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.05);
    margin-bottom: 10px;
    background: #fff;
    overflow: hidden;
}

.card-header {
    background: transparent;
    border-bottom: 1px solid #e9ecef;
    padding: 8px 14px;
    font-weight: 600;
    font-size: 0.75rem;
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 6px;
    color: #1A1A2E;
}

.card-body {
    padding: 10px 14px;
}

/* Stat Card */
.stat-card {
    padding: 8px 12px;
    border-radius: 6px;
    text-align: center;
}
.stat-card-body {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}
.stat-number {
    font-size: 1.3rem;
    font-weight: 700;
}
.stat-label {
    font-size: 0.65rem;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

/* Poin Circle */
.poin-circle {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    font-weight: 700;
    color: #fff;
    margin: 0 auto;
}
.poin-circle.bg-success { background: #10B981; }
.poin-circle.bg-info { background: #3B82F6; }
.poin-circle.bg-warning { background: #F59E0B; }
.poin-circle.bg-danger { background: #EF4444; }

.status-level {
    font-size: 0.9rem;
    font-weight: 600;
    margin-bottom: 4px;
}

.progress {
    height: 8px;
    background: #e9ecef;
    border-radius: 4px;
    overflow: hidden;
}

.progress-bar {
    border-radius: 4px;
}

/* Table */
.table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.table {
    font-size: 13px !important;
    margin-bottom: 0;
}

.table th,
.table td {
    padding: 8px 12px !important;
    vertical-align: middle;
    font-size: 13px !important;
}

.table thead th {
    background: #f8f9fa;
    font-weight: 600;
    font-size: 11px !important;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    color: #495057;
    border-bottom: 2px solid #dee2e6;
    white-space: nowrap;
    padding: 8px 12px !important;
}

.table tbody td {
    white-space: nowrap;
    padding: 8px 12px !important;
}

.table tbody tr:hover {
    background-color: rgba(108, 99, 255, 0.04);
}

/* Badge */
.badge {
    font-size: 10px !important;
    padding: 3px 8px !important;
    border-radius: 4px;
    font-weight: 600;
}

.info-badge {
    font-size: 0.6rem;
    color: #718096;
    margin-left: auto;
    font-weight: 400;
}

/* Form */
.form-control, .form-select {
    font-size: 13px !important;
    height: 38px !important;
    border-radius: 6px !important;
}

.form-label {
    font-size: 12px !important;
    margin-bottom: 4px !important;
    font-weight: 500;
}

/* Button */
.btn {
    font-size: 13px !important;
    padding: 6px 14px !important;
    border-radius: 6px !important;
}

.btn-sm {
    font-size: 11px !important;
    padding: 4px 10px !important;
}

/* Toast */
.toast-container .toast {
    border-radius: 8px !important;
    box-shadow: 0 4px 20px rgba(0,0,0,0.12) !important;
}
.toast-container .toast .toast-body {
    font-size: 13px !important;
    font-weight: 500;
    padding: 2px 0 !important;
}

/* ============================================
   RESPONSIVE
   ============================================ */
@media (max-width: 576px) {
    .dashboard-title {
        font-size: 0.8rem;
    }
    .dashboard-subtitle {
        font-size: 0.55rem;
    }
    .dashboard-header .badge {
        font-size: 0.5rem !important;
        padding: 2px 6px !important;
    }
    .table th,
    .table td {
        font-size: 11px !important;
        padding: 4px 6px !important;
    }
    .table thead th {
        font-size: 9px !important;
        padding: 4px 6px !important;
    }
    .badge {
        font-size: 8px !important;
        padding: 2px 5px !important;
    }
    .stat-number {
        font-size: 1rem;
    }
    .stat-label {
        font-size: 0.55rem;
    }
    .card-header {
        font-size: 0.65rem;
        padding: 6px 10px;
    }
    .card-body {
        padding: 6px 10px;
    }
    .poin-circle {
        width: 44px;
        height: 44px;
        font-size: 15px;
    }
    .status-level {
        font-size: 0.8rem;
    }
    .toast-container .toast {
        min-width: 200px !important;
        max-width: 280px !important;
    }
    .toast-container .toast .toast-body {
        font-size: 11px !important;
    }
    .form-control, .form-select {
        font-size: 12px !important;
        height: 34px !important;
    }
    .btn {
        font-size: 12px !important;
        padding: 4px 10px !important;
    }
}

@media (min-width: 576px) and (max-width: 768px) {
    .table th,
    .table td {
        font-size: 12px !important;
        padding: 6px 10px !important;
    }
    .table thead th {
        font-size: 10px !important;
    }
    .badge {
        font-size: 9px !important;
    }
}

@media (min-width: 768px) {
    .table th,
    .table td {
        font-size: 13px !important;
        padding: 8px 12px !important;
    }
    .table thead th {
        font-size: 11px !important;
    }
    .badge {
        font-size: 10px !important;
    }
    .stat-number {
        font-size: 1.3rem;
    }
}
</style>

<!-- Auto close toast -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    var toastElement = document.querySelector('.toast');
    if (toastElement) {
        setTimeout(function() {
            var toast = bootstrap.Toast.getInstance(toastElement);
            if (toast) {
                toast.hide();
            } else {
                toastElement.style.opacity = '0';
                setTimeout(function() {
                    toastElement.style.display = 'none';
                }, 300);
            }
        }, 3000);
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>