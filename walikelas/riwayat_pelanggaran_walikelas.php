<?php
// walikelas/riwayat_pelanggaran_walikelas.php
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

$page_title = 'Riwayat Pelanggaran';

// Get data wali kelas
$user = getCurrentUser();
$kelas_wali = getRow("SELECT * FROM kelas WHERE wali_kelas_id = " . $user['id']);
$kelas_id = $kelas_wali ? $kelas_wali['id'] : 0;
$nama_kelas = $kelas_wali ? $kelas_wali['nama_kelas'] : 'Belum ditentukan';

// Filter
$search = isset($_GET['search']) ? $_GET['search'] : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Get data pelanggaran untuk kelas wali
$pelanggaran = [];
if ($kelas_id > 0) {
    $where = "s.kelas_id = $kelas_id";
    $where .= " AND p.tanggal_pelanggaran BETWEEN '$start_date' AND '$end_date'";
    if (!empty($status_filter)) {
        $where .= " AND p.status = '$status_filter'";
    }
    
    $query = "SELECT p.*, s.nama_siswa, s.nisn, jp.nama_pelanggaran, jp.poin, u.nama_lengkap as guru
              FROM pelanggaran p
              LEFT JOIN siswa s ON p.siswa_id = s.id
              LEFT JOIN jenis_pelanggaran jp ON p.jenis_pelanggaran_id = jp.id
              LEFT JOIN users u ON p.guru_id = u.id
              WHERE $where
              ORDER BY p.tanggal_pelanggaran DESC, p.created_at DESC";
    $pelanggaran = getRows($query);
}

// Filter by search
if (!empty($search)) {
    $search = strtolower($search);
    $pelanggaran = array_filter($pelanggaran, function($item) use ($search) {
        return strpos(strtolower($item['nama_siswa']), $search) !== false ||
               strpos(strtolower($item['nisn'] ?? ''), $search) !== false ||
               strpos(strtolower($item['nama_pelanggaran']), $search) !== false;
    });
}

// Total
$total_data = count($pelanggaran);
$total_poin = array_sum(array_column($pelanggaran, 'poin'));

include '../includes/header_walikelas.php';
?>

<div class="container-fluid px-2 px-md-3 px-lg-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
        <div>
            <h4 class="mb-0">
                <i class="fas fa-history text-primary me-2"></i> Riwayat Pelanggaran
            </h4>
            <small class="text-muted">
                <i class="fas fa-school me-1"></i> Kelas: <strong><?= htmlspecialchars($nama_kelas) ?></strong>
                <?php if ($kelas_id > 0): ?>
                    <span class="badge bg-primary ms-1">ID: <?= $kelas_id ?></span>
                <?php endif; ?>
                <span class="badge bg-secondary ms-1">
                    <i class="fas fa-file-alt me-1"></i> <?= $total_data ?> Data
                </span>
            </small>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="<?= APP_URL ?>walikelas/export_riwayat_pdf.php?start_date=<?= $start_date ?>&end_date=<?= $end_date ?><?= !empty($status_filter) ? '&status=' . $status_filter : '' ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
               class="btn btn-danger btn-sm" target="_blank">
                <i class="fas fa-file-pdf me-1"></i> PDF
            </a>
            <a href="<?= APP_URL ?>walikelas/export_riwayat_excel.php?start_date=<?= $start_date ?>&end_date=<?= $end_date ?><?= !empty($status_filter) ? '&status=' . $status_filter : '' ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
               class="btn btn-success btn-sm" target="_blank">
                <i class="fas fa-file-excel me-1"></i> Excel
            </a>
        </div>
    </div>

    <?php if ($kelas_id == 0): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Anda belum ditugaskan sebagai wali kelas. Silahkan hubungi administrator.
        </div>
    <?php else: ?>

    <!-- Filter -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="" class="row g-2">
                <div class="col-md-3">
                    <label class="form-label" style="font-size: 12px;">Tanggal Mulai</label>
                    <input type="date" class="form-control" name="start_date" value="<?= $start_date ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label" style="font-size: 12px;">Tanggal Selesai</label>
                    <input type="date" class="form-control" name="end_date" value="<?= $end_date ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label" style="font-size: 12px;">Status</label>
                    <select class="form-select" name="status">
                        <option value="">Semua Status</option>
                        <option value="pending" <?= $status_filter == 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="disetujui" <?= $status_filter == 'disetujui' ? 'selected' : '' ?>>Disetujui</option>
                        <option value="ditolak" <?= $status_filter == 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" style="font-size: 12px;">Cari</label>
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" placeholder="Nama/NISN..." value="<?= htmlspecialchars($search) ?>">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-12">
                    <a href="<?= APP_URL ?>walikelas/riwayat_pelanggaran_walikelas.php" class="btn btn-secondary btn-sm">
                        <i class="fas fa-undo me-1"></i> Reset
                    </a>
                    <span class="text-muted ms-3" style="font-size: 13px;">
                        <i class="fas fa-file-alt me-1"></i> Total: <?= $total_data ?> data
                    </span>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Riwayat -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="45" class="text-center">No</th>
                            <th>Tanggal</th>
                            <th>NISN</th>
                            <th>Nama Siswa</th>
                            <th>Pelanggaran</th>
                            <th class="text-center">Poin</th>
                            <th>Guru</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($pelanggaran)): ?>
                            <?php $no = 1; foreach ($pelanggaran as $p): ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?></td>
                                    <td><?= formatTanggalIndo($p['tanggal_pelanggaran']) ?></td>
                                    <td><span class="badge bg-light text-dark"><?= htmlspecialchars($p['nisn'] ?? '-') ?></span></td>
                                    <td><strong><?= htmlspecialchars($p['nama_siswa']) ?></strong></td>
                                    <td><?= htmlspecialchars($p['nama_pelanggaran']) ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-danger"><?= $p['poin'] ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($p['guru'] ?? '-') ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-<?= getStatusBadge($p['status']) ?>">
                                            <?= getStatusLabel($p['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block opacity-25"></i>
                                    Tidak ada data pelanggaran
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if (!empty($pelanggaran)): ?>
            <div class="card-footer bg-transparent text-muted">
                <i class="fas fa-info-circle me-1"></i>
                Menampilkan <?= count($pelanggaran) ?> data | Total Poin: <strong><?= $total_poin ?></strong>
            </div>
        <?php endif; ?>
    </div>

    <?php endif; ?>
</div>

<style>
/* Table Styles */
.table-bordered {
    border: 1px solid #dee2e6;
}

.table-bordered th,
.table-bordered td {
    border: 1px solid #dee2e6;
    padding: 8px 10px;
    vertical-align: middle;
}

.table thead th {
    background: #f8f9fa;
    font-weight: 600;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    color: #495057;
    border-bottom: 2px solid #dee2e6;
}

.table tbody tr:hover {
    background-color: rgba(108, 99, 255, 0.04);
}

.table tbody td {
    font-size: 13px;
}

/* Badge */
.badge {
    font-size: 10px !important;
    padding: 3px 8px !important;
}

/* Responsive */
@media (max-width: 768px) {
    .table thead th {
        font-size: 9px;
        padding: 4px 6px;
    }
    
    .table tbody td {
        font-size: 10px;
        padding: 4px 6px;
    }
    
    .badge {
        font-size: 8px !important;
        padding: 2px 5px !important;
    }
}
</style>

<?php include '../includes/footer.php'; ?>