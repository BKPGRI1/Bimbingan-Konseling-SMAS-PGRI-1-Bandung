<?php
// siswa/surat_siswa.php
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

$page_title = 'Surat Saya';

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

// Ambil semua surat siswa
$surat_list = getSuratMeneg();
$surat_siswa = array_filter($surat_list, function($s) use ($siswa_id) {
    return $s['siswa_id'] == $siswa_id;
});
$surat_siswa = array_values($surat_siswa);
$total_surat = count($surat_siswa);

// Filter
$filter_jenis = isset($_GET['jenis']) ? $_GET['jenis'] : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : '';

if (!empty($filter_jenis)) {
    $surat_siswa = array_filter($surat_siswa, function($s) use ($filter_jenis) {
        return $s['jenis_surat'] == $filter_jenis;
    });
}

if (!empty($filter_status)) {
    $surat_siswa = array_filter($surat_siswa, function($s) use ($filter_status) {
        return $s['status'] == $filter_status;
    });
}

if (!empty($filter_tanggal)) {
    $surat_siswa = array_filter($surat_siswa, function($s) use ($filter_tanggal) {
        return date('Y-m-d', strtotime($s['tanggal_surat'])) == $filter_tanggal;
    });
}

$surat_siswa = array_values($surat_siswa);
$total_filtered = count($surat_siswa);

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$total_pages = ceil($total_filtered / $limit);
$surat_paginated = array_slice($surat_siswa, $offset, $limit);

// Jika ada parameter detail, tampilkan detail surat
$detail_surat = null;
if (isset($_GET['detail'])) {
    $detail_id = (int)$_GET['detail'];
    foreach ($surat_siswa as $s) {
        if ($s['id'] == $detail_id) {
            $detail_surat = $s;
            break;
        }
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
                <i class="fas fa-envelope text-primary me-1"></i> Surat Saya
            </h4>
            <small class="text-muted dashboard-subtitle">
                <?= htmlspecialchars($nama_siswa) ?> - Kelas <?= htmlspecialchars($kelas) ?>
            </small>
        </div>
        <div>
            <span class="badge bg-primary">
                <i class="fas fa-envelope me-1"></i> <?= $total_surat ?>
            </span>
        </div>
    </div>

    <?php if ($detail_surat): ?>
        <!-- Detail Surat -->
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">
                    <i class="fas fa-file-alt text-primary me-2"></i> Detail Surat
                </h5>
                <a href="<?= APP_URL ?>siswa/surat_siswa.php" class="btn btn-sm btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Kembali
                </a>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-12">
                        <table class="table table-bordered">
                            <tr>
                                <td width="150" class="bg-light fw-bold">Nomor Surat</td>
                                <td><strong><?= htmlspecialchars($detail_surat['nomor_surat']) ?></strong></td>
                            </tr>
                            <tr>
                                <td class="bg-light fw-bold">Jenis Surat</td>
                                <td>
                                    <?php 
                                    $jenis_label = [
                                        'teguran' => 'Teguran',
                                        'pemanggilan' => 'Pemanggilan',
                                        'peringatan' => 'Peringatan',
                                        'do' => 'DO'
                                    ];
                                    $jenis_badge = [
                                        'teguran' => 'bg-info',
                                        'pemanggilan' => 'bg-warning',
                                        'peringatan' => 'bg-danger',
                                        'do' => 'bg-dark'
                                    ];
                                    ?>
                                    <span class="badge <?= $jenis_badge[$detail_surat['jenis_surat']] ?? 'bg-secondary' ?>">
                                        <?= $jenis_label[$detail_surat['jenis_surat']] ?? $detail_surat['jenis_surat'] ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="bg-light fw-bold">Tanggal Surat</td>
                                <td><strong><?= date('d-m-Y', strtotime($detail_surat['tanggal_surat'])) ?></strong></td>
                            </tr>
                            <tr>
                                <td class="bg-light fw-bold">Status</td>
                                <td>
                                    <?php 
                                    $status_badge = [
                                        'draft' => 'bg-secondary',
                                        'terkirim' => 'bg-primary',
                                        'selesai' => 'bg-success'
                                    ];
                                    ?>
                                    <span class="badge <?= $status_badge[$detail_surat['status']] ?? 'bg-secondary' ?>">
                                        <?= ucfirst($detail_surat['status'] ?? 'Draft') ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="bg-light fw-bold">Siswa</td>
                                <td><strong><?= htmlspecialchars($nama_siswa) ?></strong></td>
                            </tr>
                            <tr>
                                <td class="bg-light fw-bold">NISN</td>
                                <td><strong><?= htmlspecialchars($siswa['nisn']) ?></strong></td>
                            </tr>
                            <tr>
                                <td class="bg-light fw-bold">Kelas</td>
                                <td><strong><?= htmlspecialchars($kelas) ?></strong></td>
                            </tr>
                            <tr>
                                <td colspan="2" class="bg-light fw-bold">Isi Surat</td>
                            </tr>
                            <tr>
                                <td colspan="2" style="white-space: pre-wrap; font-size: 14px; line-height: 1.8; padding: 15px;">
                                    <?= nl2br(htmlspecialchars($detail_surat['isi_surat'])) ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="card-footer text-end">
                <button class="btn btn-primary btn-sm" onclick="window.print()">
                    <i class="fas fa-print me-1"></i> Cetak
                </button>
                <a href="<?= APP_URL ?>siswa/surat_siswa.php" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Kembali
                </a>
            </div>
        </div>

    <?php else: ?>

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
                    <label class="form-label">Jenis Surat</label>
                    <select class="form-select" name="jenis">
                        <option value="">Semua</option>
                        <option value="teguran" <?= $filter_jenis == 'teguran' ? 'selected' : '' ?>>Teguran</option>
                        <option value="pemanggilan" <?= $filter_jenis == 'pemanggilan' ? 'selected' : '' ?>>Pemanggilan</option>
                        <option value="peringatan" <?= $filter_jenis == 'peringatan' ? 'selected' : '' ?>>Peringatan</option>
                        <option value="do" <?= $filter_jenis == 'do' ? 'selected' : '' ?>>DO</option>
                    </select>
                </div>
                <div class="col-6 col-sm-4 col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="">Semua</option>
                        <option value="draft" <?= $filter_status == 'draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="terkirim" <?= $filter_status == 'terkirim' ? 'selected' : '' ?>>Terkirim</option>
                        <option value="selesai" <?= $filter_status == 'selesai' ? 'selected' : '' ?>>Selesai</option>
                    </select>
                </div>
                <div class="col-6 col-sm-12 col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="fas fa-search me-1"></i> Filter
                    </button>
                    <a href="<?= APP_URL ?>siswa/surat_siswa.php" class="btn btn-secondary btn-sm w-100">
                        <i class="fas fa-undo me-1"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Surat -->
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <span><i class="fas fa-list me-2"></i> Daftar Surat</span>
            <span class="info-badge"><?= $total_filtered ?> data</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="35" class="text-center">#</th>
                            <th>Nomor Surat</th>
                            <th>Tanggal</th>
                            <th>Jenis</th>
                            <th class="text-center" width="80">Status</th>
                            <th class="text-center" width="70">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($surat_paginated)): 
                            $no = $offset + 1;
                            foreach ($surat_paginated as $s): 
                                $jenis_label = [
                                    'teguran' => 'Teguran',
                                    'pemanggilan' => 'Pemanggilan',
                                    'peringatan' => 'Peringatan',
                                    'do' => 'DO'
                                ];
                                $jenis_badge = [
                                    'teguran' => 'bg-info',
                                    'pemanggilan' => 'bg-warning',
                                    'peringatan' => 'bg-danger',
                                    'do' => 'bg-dark'
                                ];
                                $status_badge = [
                                    'draft' => 'bg-secondary',
                                    'terkirim' => 'bg-primary',
                                    'selesai' => 'bg-success'
                                ];
                        ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td><?= htmlspecialchars($s['nomor_surat']) ?></td>
                                <td><?= date('d-m-Y', strtotime($s['tanggal_surat'])) ?></td>
                                <td>
                                    <span class="badge <?= $jenis_badge[$s['jenis_surat']] ?? 'bg-secondary' ?>">
                                        <?= $jenis_label[$s['jenis_surat']] ?? $s['jenis_surat'] ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge <?= $status_badge[$s['status']] ?? 'bg-secondary' ?>">
                                        <?= ucfirst($s['status'] ?? 'Draft') ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="<?= APP_URL ?>siswa/surat_siswa.php?detail=<?= $s['id'] ?>" 
                                       class="btn btn-sm btn-outline-primary" title="Lihat Surat">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block opacity-25"></i>
                                    <?php if ($filter_jenis || $filter_status || $filter_tanggal): ?>
                                        Tidak ada surat dengan filter yang dipilih
                                    <?php else: ?>
                                        Belum ada surat
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="card-footer">
            <nav aria-label="Page navigation">
                <ul class="pagination pagination-sm justify-content-center mb-0">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page - 1 ?><?= $filter_jenis ? '&jenis='.$filter_jenis : '' ?><?= $filter_status ? '&status='.$filter_status : '' ?><?= $filter_tanggal ? '&tanggal='.$filter_tanggal : '' ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?><?= $filter_jenis ? '&jenis='.$filter_jenis : '' ?><?= $filter_status ? '&status='.$filter_status : '' ?><?= $filter_tanggal ? '&tanggal='.$filter_tanggal : '' ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page + 1 ?><?= $filter_jenis ? '&jenis='.$filter_jenis : '' ?><?= $filter_status ? '&status='.$filter_status : '' ?><?= $filter_tanggal ? '&tanggal='.$filter_tanggal : '' ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>

    <?php endif; ?>
</div>

<style>
/* ============================================
   STYLE SURAT SISWA
   ============================================ */
.dashboard-container {
    padding: 0 !important;
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

.card-footer {
    background: transparent;
    border-top: 1px solid #e9ecef;
    padding: 8px 14px;
}

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

.btn {
    font-size: 13px !important;
    padding: 6px 14px !important;
    border-radius: 6px !important;
}

.btn-sm {
    font-size: 11px !important;
    padding: 4px 10px !important;
}

/* Pagination */
.pagination {
    gap: 2px;
}
.pagination .page-link {
    padding: 4px 10px;
    font-size: 12px;
    border-radius: 4px;
    color: #1A1A2E;
}
.pagination .page-item.active .page-link {
    background: #6C63FF;
    border-color: #6C63FF;
    color: #fff;
}
.pagination .page-item.disabled .page-link {
    color: #adb5bd;
}
.pagination .page-link:hover {
    background: rgba(108, 99, 255, 0.1);
}

.toast-container .toast {
    border-radius: 8px !important;
    box-shadow: 0 4px 20px rgba(0,0,0,0.12) !important;
}
.toast-container .toast .toast-body {
    font-size: 13px !important;
    font-weight: 500;
    padding: 2px 0 !important;
}

@media (max-width: 576px) {
    .dashboard-title { font-size: 0.8rem; }
    .dashboard-subtitle { font-size: 0.55rem; }
    .dashboard-header .badge { font-size: 0.5rem !important; padding: 2px 6px !important; }
    .table th, .table td { font-size: 11px !important; padding: 4px 6px !important; }
    .table thead th { font-size: 9px !important; padding: 4px 6px !important; }
    .badge { font-size: 8px !important; padding: 2px 5px !important; }
    .card-header { font-size: 0.65rem; padding: 6px 10px; }
    .card-body { padding: 6px 10px; }
    .toast-container .toast { min-width: 200px !important; max-width: 280px !important; }
    .toast-container .toast .toast-body { font-size: 11px !important; }
    .form-control, .form-select { font-size: 12px !important; height: 34px !important; }
    .btn { font-size: 12px !important; padding: 4px 10px !important; }
    .pagination .page-link { padding: 2px 6px; font-size: 10px; }
}

@media (min-width: 576px) and (max-width: 768px) {
    .table th, .table td { font-size: 12px !important; padding: 6px 10px !important; }
    .table thead th { font-size: 10px !important; }
    .badge { font-size: 9px !important; }
}

@media (min-width: 768px) {
    .table th, .table td { font-size: 13px !important; padding: 8px 12px !important; }
    .table thead th { font-size: 11px !important; }
    .badge { font-size: 10px !important; }
}
</style>

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