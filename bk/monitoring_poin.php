<?php
// bk/monitoring_poin.php
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

$page_title = 'Monitoring Poin';
$conn = getDB();

// ============================================
// HANDLE FILTER
// ============================================
$kelas_id = isset($_GET['kelas_id']) ? (int)$_GET['kelas_id'] : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'poin_desc';

// ============================================
// BUILD QUERY - HITUNG POIN DENGAN BENAR (PELANGGARAN - PRESTASI)
// ============================================
$where = [];

if ($kelas_id > 0) {
    $where[] = "s.kelas_id = $kelas_id";
}

if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $where[] = "(s.nama_siswa LIKE '%$search%' OR s.nisn LIKE '%$search%')";
}

$where_sql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// ============================================
// GET DATA SISWA DENGAN POIN YANG BENAR
// ============================================
$query = "SELECT s.*, k.nama_kelas,
          COALESCE((SELECT SUM(poin_diperoleh) FROM pelanggaran WHERE siswa_id = s.id AND status = 'disetujui'), 0) as total_poin_pelanggaran,
          COALESCE((SELECT SUM(poin_prestasi) FROM prestasi_siswa WHERE siswa_id = s.id), 0) as total_poin_prestasi,
          COALESCE((SELECT COUNT(*) FROM pelanggaran WHERE siswa_id = s.id AND status = 'disetujui'), 0) as total_pelanggaran,
          COALESCE((SELECT COUNT(*) FROM prestasi_siswa WHERE siswa_id = s.id), 0) as total_prestasi,
          -- HITUNG POIN BERSIH: PELANGGARAN - PRESTASI
          (COALESCE((SELECT SUM(poin_diperoleh) FROM pelanggaran WHERE siswa_id = s.id AND status = 'disetujui'), 0) - 
           COALESCE((SELECT SUM(poin_prestasi) FROM prestasi_siswa WHERE siswa_id = s.id), 0)) as poin_bersih
          FROM siswa s
          LEFT JOIN kelas k ON s.kelas_id = k.id
          $where_sql";

// Sorting berdasarkan poin_bersih
switch ($sort_by) {
    case 'poin_asc':
        $query .= " ORDER BY poin_bersih ASC";
        break;
    case 'nama_asc':
        $query .= " ORDER BY s.nama_siswa ASC";
        break;
    case 'nama_desc':
        $query .= " ORDER BY s.nama_siswa DESC";
        break;
    default:
        $query .= " ORDER BY poin_bersih DESC";
}

$siswa_list = getRows($query);

// ============================================
// AMBIL DATA KELAS UNTUK FILTER
// ============================================
$kelas_options = getKelasOptions();

// ============================================
// BATAS POIN
// ============================================
$batas = getBatasPoin();

// ============================================
// HITUNG STATISTIK DENGAN BENAR
// ============================================
$total_siswa = count($siswa_list);
$total_poin = 0;
$total_poin_pelanggaran_all = 0;
$total_poin_prestasi_all = 0;
$siswa_bermasalah = 0;
$siswa_berprestasi = 0;

foreach ($siswa_list as $row) {
    // Gunakan poin_bersih dari query
    $poin = (int)($row['poin_bersih'] ?? 0);
    if ($poin < 0) $poin = 0;
    
    $total_poin += $poin;
    $total_poin_pelanggaran_all += (int)($row['total_poin_pelanggaran'] ?? 0);
    $total_poin_prestasi_all += (int)($row['total_poin_prestasi'] ?? 0);
    
    if ($poin >= $batas['peringatan']) {
        $siswa_bermasalah++;
    }
    if ((int)($row['total_prestasi'] ?? 0) > 0) {
        $siswa_berprestasi++;
    }
}

include __DIR__ . '/../includes/header_bk.php';
?>

<div class="container-fluid dashboard-container">
    <!-- Header -->
    <div class="dashboard-header">
        <div>
            <h4 class="dashboard-title">
                <i class="fas fa-chart-line text-primary me-2"></i> Monitoring Poin
            </h4>
            <small class="text-muted dashboard-subtitle">
                <i class="fas fa-info-circle me-1"></i> Total <?= $total_siswa ?> siswa | 
                Batas: Teguran <?= $batas['peringatan'] ?> | Pemanggilan <?= $batas['pemanggilan'] ?> | DO <?= $batas['do'] ?>
            </small>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <span class="badge bg-primary p-2">
                <i class="fas fa-users me-1"></i> <?= $total_siswa ?> Siswa
            </span>
            <span class="badge bg-danger p-2">
                <i class="fas fa-exclamation-triangle me-1"></i> <?= $siswa_bermasalah ?> Bermasalah
            </span>
            <span class="badge bg-success p-2">
                <i class="fas fa-star me-1"></i> <?= $siswa_berprestasi ?> Berprestasi
            </span>
        </div>
    </div>

    <!-- Statistik Ringkas -->
    <div class="quick-stats-grid mb-3">
        <div class="quick-stat-card">
            <div class="quick-stat-icon bg-primary">
                <i class="fas fa-users"></i>
            </div>
            <div class="quick-stat-info">
                <h6 class="quick-stat-label">Total Siswa</h6>
                <h4 class="quick-stat-number"><?= $total_siswa ?></h4>
            </div>
        </div>
        <div class="quick-stat-card">
            <div class="quick-stat-icon bg-danger">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="quick-stat-info">
                <h6 class="quick-stat-label">Bermasalah</h6>
                <h4 class="quick-stat-number"><?= $siswa_bermasalah ?></h4>
                <small class="text-muted">Poin ≥ <?= $batas['peringatan'] ?></small>
            </div>
        </div>
        <div class="quick-stat-card">
            <div class="quick-stat-icon bg-success">
                <i class="fas fa-star"></i>
            </div>
            <div class="quick-stat-info">
                <h6 class="quick-stat-label">Berprestasi</h6>
                <h4 class="quick-stat-number"><?= $siswa_berprestasi ?></h4>
                <small class="text-muted">Memiliki prestasi</small>
            </div>
        </div>
        <div class="quick-stat-card">
            <div class="quick-stat-icon bg-warning">
                <i class="fas fa-calculator"></i>
            </div>
            <div class="quick-stat-info">
                <h6 class="quick-stat-label">Total Poin</h6>
                <h4 class="quick-stat-number"><?= $total_poin ?></h4>
                <small class="text-muted">(Pelanggaran - Prestasi)</small>
            </div>
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
                    <div class="col-12 col-md-3">
                        <label class="form-label">Cari Nama / NISN</label>
                        <input type="text" class="form-control" name="search" placeholder="Ketik nama atau NISN..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label">Urutkan</label>
                        <select class="form-select" name="sort_by">
                            <option value="poin_desc" <?= $sort_by == 'poin_desc' ? 'selected' : '' ?>>Poin Tertinggi</option>
                            <option value="poin_asc" <?= $sort_by == 'poin_asc' ? 'selected' : '' ?>>Poin Terendah</option>
                            <option value="nama_asc" <?= $sort_by == 'nama_asc' ? 'selected' : '' ?>>Nama A-Z</option>
                            <option value="nama_desc" <?= $sort_by == 'nama_desc' ? 'selected' : '' ?>>Nama Z-A</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="fas fa-search me-1"></i> Filter
                        </button>
                    </div>
                    <div class="col-12 col-md-2">
                        <a href="?kelas_id=0&sort_by=poin_desc" class="btn btn-secondary btn-sm w-100">
                            <i class="fas fa-undo me-1"></i> Reset
                        </a>
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
                            <th style="width:100px;">NISN</th>
                            <th>Nama Siswa</th>
                            <th style="width:70px;">Kelas</th>
                            <th class="text-center" style="width:40px;">JK</th>
                            <th class="text-center" style="width:80px;">Pelanggaran</th>
                            <th class="text-center" style="width:80px;">Prestasi</th>
                            <th class="text-center" style="width:80px;">Poin Akhir</th>
                            <th style="width:140px;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($siswa_list) > 0): ?>
                            <?php $no = 1; ?>
                            <?php foreach ($siswa_list as $row): 
                                // HITUNG POIN BERSIH
                                $total_poin_pelanggaran = (int)($row['total_poin_pelanggaran'] ?? 0);
                                $total_poin_prestasi = (int)($row['total_poin_prestasi'] ?? 0);
                                $poin = $total_poin_pelanggaran - $total_poin_prestasi;
                                if ($poin < 0) $poin = 0;
                                
                                $total_pelanggaran = (int)($row['total_pelanggaran'] ?? 0);
                                $total_prestasi = (int)($row['total_prestasi'] ?? 0);
                                $level = getPoinLevel($poin);
                            ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?></td>
                                    <td><?= $row['nisn'] ?? '-' ?></td>
                                    <td><?= htmlspecialchars($row['nama_siswa']) ?></td>
                                    <td><?= $row['nama_kelas'] ?? '-' ?></td>
                                    <td class="text-center"><?= $row['jenis_kelamin'] ?? '-' ?></td>
                                    <td class="text-center">
                                        <?php if ($total_pelanggaran > 0): ?>
                                            <span class="badge bg-danger"><?= $total_pelanggaran ?></span>
                                            <br><small class="text-muted" style="font-size:8px;">(<?= $total_poin_pelanggaran ?> poin)</small>
                                        <?php else: ?>
                                            <span class="text-muted">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($total_prestasi > 0): ?>
                                            <span class="badge bg-success"><?= $total_prestasi ?></span>
                                            <br><small class="text-muted" style="font-size:8px;">(-<?= $total_poin_prestasi ?> poin)</small>
                                        <?php else: ?>
                                            <span class="text-muted">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center fw-bold text-<?= $level['color'] ?>">
                                        <?= $poin ?>
                                        <?php if ($total_pelanggaran > 0 && $total_prestasi > 0): ?>
                                            <br><small class="text-muted" style="font-size:7px;"><?= $total_poin_pelanggaran ?>-<?= $total_poin_prestasi ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $level['color'] ?>">
                                            <i class="fas <?= $level['icon'] ?> me-1"></i>
                                            <?= $level['level'] ?>
                                        </span>
                                        <?php if ($total_prestasi > 0): ?>
                                            <span class="badge bg-success ms-1" style="font-size:7px;">
                                                <i class="fas fa-star"></i> Prestasi
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($total_pelanggaran == 0 && $total_prestasi == 0): ?>
                                            <span class="badge bg-secondary ms-1" style="font-size:7px;">Bersih</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="table-info fw-bold">
                                <td colspan="5" class="text-end">Total</td>
                                <td class="text-center"><?= $total_poin_pelanggaran_all ?></td>
                                <td class="text-center"><?= $total_poin_prestasi_all ?></td>
                                <td class="text-center"><?= $total_poin ?></td>
                                <td></td>
                            </tr>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">
                                    <i class="fas fa-inbox fa-2x d-block mb-2"></i>
                                    Tidak ada data siswa
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer text-muted" style="font-size:11px;">
            <i class="fas fa-info-circle me-1"></i> 
            Menampilkan <?= $total_siswa ?> siswa | 
            <span class="text-danger">Total Pelanggaran: <?= $total_poin_pelanggaran_all ?> poin</span> | 
            <span class="text-success">Total Prestasi: <?= $total_poin_prestasi_all ?> poin</span> | 
            <span class="text-warning">Total Poin Akhir: <?= $total_poin ?></span> |
            <span class="text-success"><i class="fas fa-star me-1"></i> Prestasi mengurangi poin</span>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- CARD VIEW UNTUK MOBILE -->
    <!-- ========================================== -->
    <div class="d-block d-md-none">
        <?php if (count($siswa_list) > 0): ?>
            <?php $no = 1; ?>
            <?php foreach ($siswa_list as $row): 
                $total_poin_pelanggaran = (int)($row['total_poin_pelanggaran'] ?? 0);
                $total_poin_prestasi = (int)($row['total_poin_prestasi'] ?? 0);
                $poin = $total_poin_pelanggaran - $total_poin_prestasi;
                if ($poin < 0) $poin = 0;
                
                $total_pelanggaran = (int)($row['total_pelanggaran'] ?? 0);
                $total_prestasi = (int)($row['total_prestasi'] ?? 0);
                $level = getPoinLevel($poin);
            ?>
                <div class="card mb-2 mobile-card">
                    <div class="card-body p-2">
                        <div class="row g-1">
                            <div class="col-7">
                                <span class="text-muted" style="font-size:9px;">Nama</span>
                                <div class="fw-bold" style="font-size:12px;"><?= htmlspecialchars($row['nama_siswa']) ?></div>
                            </div>
                            <div class="col-5 text-end">
                                <span class="badge bg-<?= $level['color'] ?>" style="font-size:9px;">
                                    <?= $level['level'] ?>
                                </span>
                                <?php if ($total_prestasi > 0): ?>
                                    <span class="badge bg-success ms-1" style="font-size:7px;">
                                        <i class="fas fa-star"></i>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="row g-1 mt-1">
                            <div class="col-6">
                                <span class="text-muted" style="font-size:9px;">NISN</span>
                                <div style="font-size:11px;"><?= $row['nisn'] ?? '-' ?></div>
                            </div>
                            <div class="col-6">
                                <span class="text-muted" style="font-size:9px;">Kelas</span>
                                <div style="font-size:11px;"><?= $row['nama_kelas'] ?? '-' ?></div>
                            </div>
                        </div>
                        <div class="row g-1 mt-1">
                            <div class="col-4">
                                <span class="text-muted" style="font-size:9px;">JK</span>
                                <div style="font-size:11px;"><?= $row['jenis_kelamin'] ?? '-' ?></div>
                            </div>
                            <div class="col-4">
                                <span class="text-muted" style="font-size:9px;">Pelanggaran</span>
                                <div style="font-size:11px;">
                                    <?php if ($total_pelanggaran > 0): ?>
                                        <span class="badge bg-danger"><?= $total_pelanggaran ?></span>
                                        <small class="text-muted" style="font-size:8px;">(<?= $total_poin_pelanggaran ?>)</small>
                                    <?php else: ?>
                                        <span>0</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-4">
                                <span class="text-muted" style="font-size:9px;">Prestasi</span>
                                <div style="font-size:11px;">
                                    <?php if ($total_prestasi > 0): ?>
                                        <span class="badge bg-success"><?= $total_prestasi ?></span>
                                        <small class="text-muted" style="font-size:8px;">(-<?= $total_poin_prestasi ?>)</small>
                                    <?php else: ?>
                                        <span>0</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="row g-1 mt-1">
                            <div class="col-12 text-center">
                                <span class="text-muted" style="font-size:9px;">Total Poin Akhir</span>
                                <div class="fw-bold text-<?= $level['color'] ?>" style="font-size:18px;"><?= $poin ?></div>
                                <?php if ($total_pelanggaran > 0 || $total_prestasi > 0): ?>
                                    <small class="text-muted" style="font-size:8px;">
                                        <?= $total_poin_pelanggaran ?> - <?= $total_poin_prestasi ?> = <?= $poin ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <!-- Total Mobile -->
            <div class="card mb-2" style="background:#e3f2fd;">
                <div class="card-body p-2 text-center">
                    <span class="text-muted" style="font-size:10px;">Total Poin Keseluruhan</span>
                    <div class="fw-bold text-danger" style="font-size:18px;"><?= $total_poin ?></div>
                    <small class="text-muted" style="font-size:9px;">
                        Pelanggaran: <?= $total_poin_pelanggaran_all ?> - Prestasi: <?= $total_poin_prestasi_all ?> = <?= $total_poin ?>
                    </small>
                </div>
            </div>
        <?php else: ?>
            <div class="card mb-2">
                <div class="card-body p-4 text-center text-muted">
                    <i class="fas fa-inbox fa-3x d-block mb-2"></i>
                    Tidak ada data siswa
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Footer Mobile -->
        <div class="text-muted text-center" style="font-size:9px; padding:8px 0;">
            <i class="fas fa-info-circle me-1"></i> 
            <?= $total_siswa ?> siswa | 
            Batas Teguran: <?= $batas['peringatan'] ?> | Pemanggilan: <?= $batas['pemanggilan'] ?> | DO: <?= $batas['do'] ?>
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

/* Quick Stats */
.quick-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 8px;
    margin-bottom: 12px;
}

.quick-stat-card {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 10px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    height: 100%;
    min-height: 60px;
}

.quick-stat-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 13px;
    flex-shrink: 0;
}
.quick-stat-icon.bg-primary { background: #3B82F6; }
.quick-stat-icon.bg-danger { background: #EF4444; }
.quick-stat-icon.bg-success { background: #10B981; }
.quick-stat-icon.bg-warning { background: #F59E0B; }

.quick-stat-info {
    flex: 1;
    min-width: 0;
}

.quick-stat-label {
    font-size: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    color: #6B7280;
    margin-bottom: 0;
}

.quick-stat-number {
    font-size: 1rem;
    font-weight: 700;
    margin-bottom: 0;
    line-height: 1.2;
}

.quick-stat-info small {
    font-size: 0.45rem;
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
    width: 100% !important;
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
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    white-space: nowrap;
    background-color: #f8f9fa;
}

.table td {
    vertical-align: middle;
    font-size: 11px;
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
    
    .quick-stats-grid {
        grid-template-columns: 1fr 1fr;
        gap: 4px;
    }
    
    .quick-stat-card {
        padding: 4px 6px;
        gap: 4px;
        min-height: 50px;
    }
    
    .quick-stat-icon {
        width: 24px;
        height: 24px;
        font-size: 10px;
    }
    
    .quick-stat-number {
        font-size: 0.85rem;
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
    
    .quick-stats-grid {
        grid-template-columns: 1fr 1fr;
        gap: 3px;
    }
    
    .quick-stat-card {
        padding: 3px 4px;
        min-height: 40px;
    }
    
    .quick-stat-icon {
        width: 20px;
        height: 20px;
        font-size: 8px;
    }
    
    .quick-stat-number {
        font-size: 0.75rem;
    }
    
    .quick-stat-label {
        font-size: 0.35rem;
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