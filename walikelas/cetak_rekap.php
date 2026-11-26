<?php
// walikelas/cetak_rekap.php
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

$page_title = 'Cetak Rekap Siswa';

// Get data wali kelas
$user = getCurrentUser();
$kelas_wali = getRow("SELECT * FROM kelas WHERE wali_kelas_id = " . $user['id']);
$kelas_id = $kelas_wali ? $kelas_wali['id'] : 0;
$nama_kelas = $kelas_wali ? $kelas_wali['nama_kelas'] : 'Belum ditentukan';

// Filter
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'nama';
$order = isset($_GET['order']) ? $_GET['order'] : 'asc';

// Get siswa dengan rekap
$siswa_list = [];
if ($kelas_id > 0) {
    $siswa_list = getRows("SELECT s.*, 
                          (SELECT COUNT(*) FROM pelanggaran WHERE siswa_id = s.id) as total_pelanggaran,
                          (SELECT SUM(poin_diperoleh) FROM pelanggaran WHERE siswa_id = s.id AND status = 'disetujui') as total_poin,
                          (SELECT COUNT(*) FROM pelanggaran WHERE siswa_id = s.id AND status = 'pending') as pending,
                          (SELECT COUNT(*) FROM pelanggaran WHERE siswa_id = s.id AND status = 'disetujui') as disetujui,
                          (SELECT COUNT(*) FROM pelanggaran WHERE siswa_id = s.id AND status = 'ditolak') as ditolak
                          FROM siswa s
                          WHERE s.kelas_id = $kelas_id
                          ORDER BY 
                          CASE 
                              WHEN '$sort' = 'nama' THEN s.nama_siswa 
                              WHEN '$sort' = 'nisn' THEN s.nisn
                              WHEN '$sort' = 'poin' THEN total_poin
                              WHEN '$sort' = 'pelanggaran' THEN total_pelanggaran
                              ELSE s.nama_siswa
                          END $order");
}

// Filter by search
if (!empty($search)) {
    $search = strtolower($search);
    $siswa_list = array_filter($siswa_list, function($item) use ($search) {
        return strpos(strtolower($item['nama_siswa']), $search) !== false ||
               strpos(strtolower($item['nisn'] ?? ''), $search) !== false;
    });
}

// Total statistik
$total_siswa = count($siswa_list);
$total_poin = array_sum(array_column($siswa_list, 'total_poin'));
$total_pelanggaran = array_sum(array_column($siswa_list, 'total_pelanggaran'));

include '../includes/header_walikelas.php';
?>

<div class="container-fluid px-2 px-md-3 px-lg-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
        <div>
            <h4 class="mb-0">
                <i class="fas fa-file-alt text-primary me-2"></i> Cetak Rekap Siswa
            </h4>
            <small class="text-muted">
                <i class="fas fa-school me-1"></i> Kelas: <strong><?= htmlspecialchars($nama_kelas) ?></strong>
                <?php if ($kelas_id > 0): ?>
                    <span class="badge bg-primary ms-1">ID: <?= $kelas_id ?></span>
                <?php endif; ?>
                <span class="badge bg-secondary ms-1">
                    <i class="fas fa-user-graduate me-1"></i> <?= $total_siswa ?> Siswa
                </span>
            </small>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="<?= APP_URL ?>walikelas/export_rekap_pdf.php?kelas_id=<?= $kelas_id ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
               class="btn btn-danger btn-sm" target="_blank">
                <i class="fas fa-file-pdf me-1"></i> PDF
            </a>
            <a href="<?= APP_URL ?>walikelas/export_rekap_excel.php?kelas_id=<?= $kelas_id ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
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

    <!-- Summary Cards -->
    <div class="row g-2 g-md-3 mb-3">
        <div class="col-4 col-md-3">
            <div class="card stat-card bg-primary text-white h-100">
                <div class="card-body p-2 p-md-3 text-center">
                    <h6 class="text-white-50 mb-0 text-uppercase" style="font-size: 0.55rem; letter-spacing: 0.5px;">Total Siswa</h6>
                    <h2 class="mb-0" style="font-size: 1.3rem; font-weight: 700;"><?= $total_siswa ?></h2>
                </div>
            </div>
        </div>
        <div class="col-4 col-md-3">
            <div class="card stat-card bg-danger text-white h-100">
                <div class="card-body p-2 p-md-3 text-center">
                    <h6 class="text-white-50 mb-0 text-uppercase" style="font-size: 0.55rem; letter-spacing: 0.5px;">Total Pelanggaran</h6>
                    <h2 class="mb-0" style="font-size: 1.3rem; font-weight: 700;"><?= $total_pelanggaran ?></h2>
                </div>
            </div>
        </div>
        <div class="col-4 col-md-3">
            <div class="card stat-card bg-warning text-white h-100">
                <div class="card-body p-2 p-md-3 text-center">
                    <h6 class="text-white-50 mb-0 text-uppercase" style="font-size: 0.55rem; letter-spacing: 0.5px;">Total Poin</h6>
                    <h2 class="mb-0" style="font-size: 1.3rem; font-weight: 700;"><?= number_format($total_poin) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-4 col-md-3">
            <div class="card stat-card bg-success text-white h-100">
                <div class="card-body p-2 p-md-3 text-center">
                    <h6 class="text-white-50 mb-0 text-uppercase" style="font-size: 0.55rem; letter-spacing: 0.5px;">Rata-rata Poin</h6>
                    <h2 class="mb-0" style="font-size: 1.3rem; font-weight: 700;">
                        <?= $total_siswa > 0 ? round($total_poin / $total_siswa, 1) : 0 ?>
                    </h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="" class="row g-2">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" name="search" 
                               placeholder="Cari NISN atau Nama Siswa..." 
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="sort">
                        <option value="nama" <?= $sort == 'nama' ? 'selected' : '' ?>>Urutkan Nama</option>
                        <option value="nisn" <?= $sort == 'nisn' ? 'selected' : '' ?>>Urutkan NISN</option>
                        <option value="poin" <?= $sort == 'poin' ? 'selected' : '' ?>>Urutkan Poin</option>
                        <option value="pelanggaran" <?= $sort == 'pelanggaran' ? 'selected' : '' ?>>Urutkan Pelanggaran</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-1"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Rekap -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0" id="tableRekap">
                    <thead class="table-light">
                        <tr>
                            <th width="45" class="text-center">No</th>
                            <th>NISN</th>
                            <th>Nama Siswa</th>
                            <th class="text-center">JK</th>
                            <th class="text-center">Total Pelanggaran</th>
                            <th class="text-center">Total Poin</th>
                            <th class="text-center">Level</th>
                            <th class="text-center" width="100">Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($siswa_list)): ?>
                            <?php $no = 1; foreach ($siswa_list as $siswa): ?>
                                <?php 
                                $poin = (int)($siswa['total_poin'] ?? 0);
                                $level = getPoinLevel($poin);
                                $total_pel = (int)($siswa['total_pelanggaran'] ?? 0);
                                ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?></td>
                                    <td><span class="badge bg-light text-dark"><?= htmlspecialchars($siswa['nisn'] ?? '-') ?></span></td>
                                    <td><strong><?= htmlspecialchars($siswa['nama_siswa']) ?></strong></td>
                                    <td class="text-center"><?= $siswa['jenis_kelamin'] == 'L' ? 'L' : 'P' ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-info"><?= $total_pel ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-danger"><?= number_format($poin) ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-<?= $level['color'] ?>">
                                            <i class="fas <?= $level['icon'] ?> me-1"></i> <?= $level['level'] ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?= APP_URL ?>walikelas/siswa_detail_walikelas.php?id=<?= $siswa['id'] ?>" 
                                           class="btn btn-outline-info btn-sm" title="Detail">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    <i class="fas fa-user-slash fa-2x mb-2 d-block opacity-25"></i>
                                    Tidak ada data siswa
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if (!empty($siswa_list)): ?>
            <div class="card-footer bg-transparent text-muted">
                <i class="fas fa-info-circle me-1"></i>
                Menampilkan <?= count($siswa_list) ?> siswa
            </div>
        <?php endif; ?>
    </div>

    <?php endif; ?>
</div>

<style>
/* Stat Cards */
.stat-card {
    border-radius: 10px;
    border: none;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}

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
    font-size: 11px;
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

/* Button */
.btn-group-sm .btn {
    padding: 2px 6px !important;
    font-size: 12px !important;
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
    
    .btn-group-sm .btn {
        padding: 1px 4px !important;
        font-size: 9px !important;
    }
}

@media (max-width: 576px) {
    .stat-card h2 {
        font-size: 1rem !important;
    }
    .stat-card h6 {
        font-size: 0.45rem !important;
    }
}
</style>

<?php include '../includes/footer.php'; ?>