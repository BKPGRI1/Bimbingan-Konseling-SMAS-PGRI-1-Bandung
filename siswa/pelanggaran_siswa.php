<?php
// siswa/pelanggaran_siswa.php
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

$page_title = 'Riwayat Pelanggaran';

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

// Ambil semua pelanggaran siswa
$pelanggaran_list = getPelanggaranBySiswa($siswa_id);
$total_pelanggaran = count($pelanggaran_list);
$total_poin = getTotalPoinSiswa($siswa_id);

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

// Ambil data untuk statistik kategori
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
                <i class="fas fa-list text-primary me-1"></i> Riwayat Pelanggaran
            </h4>
            <small class="text-muted dashboard-subtitle">
                <?= htmlspecialchars($nama_siswa) ?> - Kelas <?= htmlspecialchars($kelas) ?>
            </small>
        </div>
        <div>
            <span class="badge bg-danger">
                <i class="fas fa-star me-1"></i> Total Poin: <?= $total_poin ?>
            </span>
            <span class="badge bg-secondary ms-1">
                <i class="fas fa-list me-1"></i> <?= $total_pelanggaran ?> Pelanggaran
            </span>
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
                    <a href="<?= APP_URL ?>siswa/pelanggaran_siswa.php" class="btn btn-secondary">
                        <i class="fas fa-undo me-1"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Statistik Kategori -->
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
                                    <span class="badge bg-danger"><?= $p['poin_diperoleh'] ?? 0 ?></span>
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
   STYLE PELANGGARAN - SEPERTI ADMIN
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

/* Table - Normal Size */
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

/* Badge - Normal */
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

/* Form - Normal */
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

/* Button - Normal */
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
/* Mobile */
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

/* Tablet */
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

/* Desktop */
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