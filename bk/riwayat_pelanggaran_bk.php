<?php
// bk/riwayat_pelanggaran_bk.php
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

$page_title = 'Riwayat Pelanggaran';
$conn = getDB();

// ============================================
// HANDLE FILTER
// ============================================
$kelas_id = isset($_GET['kelas_id']) ? (int)$_GET['kelas_id'] : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// ============================================
// BUILD QUERY
// ============================================
$where = [
    "p.status = 'disetujui'"
];

if (!empty($start_date) && !empty($end_date)) {
    $where[] = "p.tanggal_pelanggaran BETWEEN '$start_date' AND '$end_date'";
}

if ($kelas_id > 0) {
    $where[] = "s.kelas_id = $kelas_id";
}

if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $where[] = "(s.nama_siswa LIKE '%$search%' OR s.nisn LIKE '%$search%')";
}

$where_sql = "WHERE " . implode(" AND ", $where);

// ============================================
// GET DATA PELANGGARAN
// ============================================
$query = "SELECT p.*, s.nama_siswa, s.nisn, k.nama_kelas, 
          jp.nama_pelanggaran, jp.poin as poin_jenis, jp.kategori,
          u.nama_lengkap as guru
          FROM pelanggaran p
          LEFT JOIN siswa s ON p.siswa_id = s.id
          LEFT JOIN kelas k ON s.kelas_id = k.id
          LEFT JOIN jenis_pelanggaran jp ON p.jenis_pelanggaran_id = jp.id
          LEFT JOIN users u ON p.guru_id = u.id
          $where_sql
          ORDER BY p.tanggal_pelanggaran DESC, p.created_at DESC";

$pelanggaran = getRows($query);

// ============================================
// GET TOTAL POIN
// ============================================
$total_poin = 0;
foreach ($pelanggaran as $row) {
    $total_poin += $row['poin_diperoleh'];
}

// ============================================
// GET KELAS OPTIONS
// ============================================
$kelas_options = getKelasOptions();

// ============================================
// FORMAT DATE UNTUK FILTER
// ============================================
$start_date_display = !empty($start_date) ? date('d/m/Y', strtotime($start_date)) : date('d/m/Y');
$end_date_display = !empty($end_date) ? date('d/m/Y', strtotime($end_date)) : date('d/m/Y');

include __DIR__ . '/../includes/header_bk.php';
?>

<div class="container-fluid dashboard-container">
    <!-- Header -->
    <div class="dashboard-header">
        <div>
            <h4 class="dashboard-title">
                <i class="fas fa-list text-primary me-2"></i> Riwayat Pelanggaran
            </h4>
            <small class="text-muted dashboard-subtitle">
                <i class="fas fa-calendar me-1"></i> Menampilkan semua pelanggaran yang telah disetujui
            </small>
        </div>
        <div>
            <span class="badge bg-primary p-2">
                <i class="fas fa-file-alt me-1"></i> <?= count($pelanggaran) ?> Data
            </span>
        </div>
    </div>

    <!-- Filter -->
    <div class="card filter-card mb-3">
        <div class="card-body">
            <form method="GET" action="" class="filter-form">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-3">
                        <label class="form-label">Kelas</label>
                        <select class="form-select" name="kelas_id">
                            <option value="0">-- Semua Kelas --</option>
                            <?php foreach ($kelas_options as $k): ?>
                                <option value="<?= $k['id'] ?>" <?= $kelas_id == $k['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($k['nama_kelas']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label">Tgl Awal</label>
                        <input type="date" class="form-control" name="start_date" value="<?= $start_date ?>">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label">Tgl Akhir</label>
                        <input type="date" class="form-control" name="end_date" value="<?= $end_date ?>">
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label">Cari Nama / NISN</label>
                        <input type="text" class="form-control" name="search" placeholder="Ketik nama atau NISN..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-12 col-md-2">
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="fas fa-search me-1"></i> Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- TABEL UNTUK DESKTOP -->
    <!-- ========================================== -->
    <div class="card table-card d-none d-md-block">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width:40px;">No</th>
                            <th style="width:90px;">Tanggal</th>
                            <th style="width:100px;">NISN</th>
                            <th>Nama Siswa</th>
                            <th style="width:70px;">Kelas</th>
                            <th>Pelanggaran</th>
                            <th style="width:80px;">Kategori</th>
                            <th class="text-center" style="width:50px;">Poin</th>
                            <th style="width:100px;">Guru</th>
                            <th style="width:120px;">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($pelanggaran) > 0): ?>
                            <?php $no = 1; ?>
                            <?php foreach ($pelanggaran as $row): ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?></td>
                                    <td><?= date('d-m-Y', strtotime($row['tanggal_pelanggaran'])) ?></td>
                                    <td><?= $row['nisn'] ?></td>
                                    <td><?= htmlspecialchars($row['nama_siswa']) ?></td>
                                    <td><?= $row['nama_kelas'] ?? '-' ?></td>
                                    <td><?= htmlspecialchars($row['nama_pelanggaran']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $row['kategori'] == 'berat' ? 'danger' : ($row['kategori'] == 'sedang' ? 'warning' : 'success') ?>">
                                            <?= ucfirst($row['kategori']) ?>
                                        </span>
                                    </td>
                                    <td class="text-center fw-bold"><?= $row['poin_diperoleh'] ?></td>
                                    <td><?= $row['guru'] ?? '-' ?></td>
                                    <td><?= htmlspecialchars($row['keterangan'] ?? '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="table-info fw-bold">
                                <td colspan="7" class="text-end">Total Poin</td>
                                <td class="text-center"><?= $total_poin ?></td>
                                <td colspan="2"></td>
                            </tr>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">
                                    <i class="fas fa-inbox fa-2x d-block mb-2"></i>
                                    Tidak ada data pelanggaran
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer text-muted" style="font-size:11px;">
            <i class="fas fa-info-circle me-1"></i> 
            Menampilkan <?= count($pelanggaran) ?> data pelanggaran | 
            Periode: <?= $start_date_display ?> s/d <?= $end_date_display ?>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- CARD VIEW UNTUK MOBILE -->
    <!-- ========================================== -->
    <div class="d-block d-md-none">
        <?php if (count($pelanggaran) > 0): ?>
            <?php $no = 1; ?>
            <?php foreach ($pelanggaran as $row): ?>
                <div class="card mb-2 mobile-card">
                    <div class="card-body p-2">
                        <div class="row g-1">
                            <div class="col-6">
                                <span class="text-muted" style="font-size:9px;">Tanggal</span>
                                <div class="fw-bold" style="font-size:11px;"><?= date('d-m-Y', strtotime($row['tanggal_pelanggaran'])) ?></div>
                            </div>
                            <div class="col-6 text-end">
                                <span class="badge bg-<?= $row['kategori'] == 'berat' ? 'danger' : ($row['kategori'] == 'sedang' ? 'warning' : 'success') ?>" style="font-size:9px;">
                                    <?= ucfirst($row['kategori']) ?>
                                </span>
                            </div>
                        </div>
                        <div class="row g-1 mt-1">
                            <div class="col-6">
                                <span class="text-muted" style="font-size:9px;">Nama Siswa</span>
                                <div class="fw-bold" style="font-size:11px;"><?= htmlspecialchars($row['nama_siswa']) ?></div>
                            </div>
                            <div class="col-6">
                                <span class="text-muted" style="font-size:9px;">NISN</span>
                                <div style="font-size:11px;"><?= $row['nisn'] ?></div>
                            </div>
                        </div>
                        <div class="row g-1 mt-1">
                            <div class="col-6">
                                <span class="text-muted" style="font-size:9px;">Kelas</span>
                                <div style="font-size:11px;"><?= $row['nama_kelas'] ?? '-' ?></div>
                            </div>
                            <div class="col-6">
                                <span class="text-muted" style="font-size:9px;">Poin</span>
                                <div class="fw-bold text-danger" style="font-size:13px;"><?= $row['poin_diperoleh'] ?></div>
                            </div>
                        </div>
                        <div class="row g-1 mt-1">
                            <div class="col-12">
                                <span class="text-muted" style="font-size:9px;">Pelanggaran</span>
                                <div style="font-size:11px;"><?= htmlspecialchars($row['nama_pelanggaran']) ?></div>
                            </div>
                        </div>
                        <div class="row g-1 mt-1">
                            <div class="col-6">
                                <span class="text-muted" style="font-size:9px;">Guru</span>
                                <div style="font-size:10px;"><?= $row['guru'] ?? '-' ?></div>
                            </div>
                            <div class="col-6">
                                <span class="text-muted" style="font-size:9px;">No</span>
                                <div style="font-size:10px;"><?= $no++ ?></div>
                            </div>
                        </div>
                        <?php if (!empty($row['keterangan'])): ?>
                            <div class="row g-1 mt-1">
                                <div class="col-12">
                                    <span class="text-muted" style="font-size:9px;">Keterangan</span>
                                    <div style="font-size:10px;"><?= htmlspecialchars($row['keterangan']) ?></div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <!-- Total Poin Mobile -->
            <div class="card mb-2" style="background:#e3f2fd;">
                <div class="card-body p-2 text-center">
                    <span class="text-muted" style="font-size:10px;">Total Poin Keseluruhan</span>
                    <div class="fw-bold text-danger" style="font-size:18px;"><?= $total_poin ?></div>
                </div>
            </div>
        <?php else: ?>
            <div class="card mb-2">
                <div class="card-body p-4 text-center text-muted">
                    <i class="fas fa-inbox fa-3x d-block mb-2"></i>
                    Tidak ada data pelanggaran
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Footer Mobile -->
        <div class="text-muted text-center" style="font-size:9px; padding:8px 0;">
            <i class="fas fa-info-circle me-1"></i> 
            Menampilkan <?= count($pelanggaran) ?> data | 
            <?= $start_date_display ?> s/d <?= $end_date_display ?>
        </div>
    </div>
</div>

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

.filter-card .card-body {
    padding: 12px;
}

.table-card .card-body {
    padding: 0;
}

.form-label {
    font-weight: 600;
    font-size: 11px;
    color: #4a5568;
    margin-bottom: 3px;
}

.form-control, .form-select {
    border-radius: 6px;
    border: 1px solid #e2e8f0;
    padding: 5px 10px;
    font-size: 12px;
    height: 34px;
}

.form-control:focus, .form-select:focus {
    border-color: #4F46E5;
    box-shadow: 0 0 0 3px rgba(79,70,229,0.15);
}

.btn-sm {
    padding: 4px 10px !important;
    font-size: 11px !important;
    border-radius: 6px !important;
}

.table {
    font-size: 12px;
    margin-bottom: 0;
}

.table-bordered {
    border: 1px solid #dee2e6;
}

.table-bordered th,
.table-bordered td {
    border: 1px solid #dee2e6;
    padding: 6px 8px;
}

.table th {
    font-weight: 600;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    white-space: nowrap;
    background-color: #f8f9fa;
}

.table td {
    vertical-align: middle;
    font-size: 12px;
}

.table-striped tbody tr:nth-of-type(odd) {
    background-color: rgba(0,0,0,0.02);
}

.table-hover tbody tr:hover {
    background-color: rgba(79,70,229,0.05);
}

.badge {
    font-size: 9px !important;
    padding: 3px 8px !important;
    border-radius: 4px;
}

.card-footer {
    background: transparent;
    border-top: 1px solid #e9ecef;
    padding: 6px 12px;
    font-size: 10px;
}

/* Mobile Card */
.mobile-card {
    border: 1px solid #e9ecef !important;
    border-radius: 8px !important;
}

.mobile-card .card-body {
    padding: 10px !important;
}

/* ============================================
   RESPONSIVE
   ============================================ */
@media (max-width: 768px) {
    .dashboard-container {
        padding: 4px !important;
    }
    
    .dashboard-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 4px;
        margin-bottom: 8px;
    }
    
    .dashboard-title {
        font-size: 0.9rem;
    }
    
    .filter-card .card-body {
        padding: 8px;
    }
    
    .form-label {
        font-size: 10px;
    }
    
    .form-control, .form-select {
        font-size: 11px;
        padding: 4px 8px;
        height: 30px;
    }
    
    .btn-sm {
        font-size: 10px !important;
        padding: 3px 8px !important;
    }
    
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    .table {
        font-size: 10px;
        min-width: 700px;
    }
    
    .table th {
        font-size: 9px;
        padding: 4px 5px;
    }
    
    .table td {
        font-size: 10px;
        padding: 4px 5px;
    }
}

@media (max-width: 576px) {
    .dashboard-container {
        padding: 2px !important;
    }
    
    .dashboard-title {
        font-size: 0.8rem;
    }
    
    .dashboard-subtitle {
        font-size: 0.55rem;
    }
    
    .filter-card .card-body {
        padding: 6px;
    }
    
    .form-label {
        font-size: 9px;
    }
    
    .form-control, .form-select {
        font-size: 10px;
        padding: 3px 6px;
        height: 28px;
    }
    
    .btn-sm {
        font-size: 9px !important;
        padding: 2px 6px !important;
    }
    
    .mobile-card .card-body {
        padding: 6px !important;
    }
    
    .mobile-card .fw-bold {
        font-size: 10px !important;
    }
    
    .mobile-card .text-muted {
        font-size: 8px !important;
    }
}
</style>

<?php include '../includes/footer.php'; ?>