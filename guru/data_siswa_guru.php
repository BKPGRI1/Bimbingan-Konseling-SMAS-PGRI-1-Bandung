<?php
// guru/data_siswa_guru.php
include '../includes/config.php';
checkLogin();

// Cek role
if (!hasRole(['guru', 'bk'])) {
    $_SESSION['toast'] = [
        'message' => 'Anda tidak memiliki akses ke halaman ini!',
        'type' => 'danger'
    ];
    header("Location: " . APP_URL . "login.php");
    exit();
}

$page_title = 'Data Siswa';
$conn = getDB();

// Filter
$search = isset($_GET['search']) ? $_GET['search'] : '';
$kelas_filter = isset($_GET['kelas']) ? (int)$_GET['kelas'] : null;

// ============================================
// AMBIL DATA SISWA DENGAN PERHITUNGAN POIN BENAR
// ============================================
$query = "SELECT s.*, k.nama_kelas
          FROM siswa s
          LEFT JOIN kelas k ON s.kelas_id = k.id";

$where = [];
if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $where[] = "(s.nama_siswa LIKE '%$search%' OR s.nisn LIKE '%$search%')";
}
if ($kelas_filter) {
    $where[] = "s.kelas_id = $kelas_filter";
}

if (!empty($where)) {
    $query .= " WHERE " . implode(" AND ", $where);
}

$query .= " ORDER BY s.nama_siswa ASC";
$siswa_list = getRows($query);

// ============================================
// HITUNG POIN AKHIR PER SISWA
// ============================================
foreach ($siswa_list as &$siswa) {
    // Total poin pelanggaran
    $q_pel = $conn->query("SELECT SUM(poin_diperoleh) as total FROM pelanggaran WHERE siswa_id = {$siswa['id']} AND status = 'disetujui'");
    $pel = $q_pel->fetch_assoc();
    $poin_pel = (int)$pel['total'];
    
    // Total poin prestasi
    $q_pres = $conn->query("SELECT SUM(poin_prestasi) as total FROM prestasi_siswa WHERE siswa_id = {$siswa['id']}");
    $pres = $q_pres->fetch_assoc();
    $poin_pres = (int)$pres['total'];
    
    // Poin akhir = pelanggaran - prestasi
    $poin_akhir = $poin_pel - $poin_pres;
    if ($poin_akhir < 0) $poin_akhir = 0;
    
    $siswa['poin_pelanggaran'] = $poin_pel;
    $siswa['poin_prestasi'] = $poin_pres;
    $siswa['poin_akhir'] = $poin_akhir;
    $siswa['total_pelanggaran'] = $conn->query("SELECT COUNT(*) as total FROM pelanggaran WHERE siswa_id = {$siswa['id']} AND status = 'disetujui'")->fetch_assoc()['total'] ?? 0;
    $siswa['total_prestasi'] = $conn->query("SELECT COUNT(*) as total FROM prestasi_siswa WHERE siswa_id = {$siswa['id']}")->fetch_assoc()['total'] ?? 0;
}

$kelas_list = getKelasOptions();

include '../includes/header_guru.php';
?>

<div class="container-fluid dashboard-container">
    <!-- Header -->
    <div class="dashboard-header">
        <div>
            <h4 class="dashboard-title">
                <i class="fas fa-users text-primary me-2"></i> Data Siswa
            </h4>
            <small class="text-muted dashboard-subtitle">
                <i class="fas fa-search me-1"></i> Cari dan lihat data siswa
            </small>
        </div>
        <div class="header-right">
            <span class="text-muted user-name">
                <i class="fas fa-user me-1"></i> <?= htmlspecialchars($_SESSION['nama'] ?? 'Guru') ?>
            </span>
        </div>
    </div>

    <!-- Search & Filter -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="" class="row g-2">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" name="search" 
                               placeholder="Cari NISN atau Nama Siswa..." 
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <select class="form-select" name="kelas">
                        <option value="">Semua Kelas</option>
                        <?php foreach ($kelas_list as $kelas): ?>
                            <option value="<?= $kelas['id'] ?>" <?= $kelas_filter == $kelas['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($kelas['nama_kelas']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-1"></i> Cari
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Stats -->
    <div class="quick-stats-grid mb-3">
        <div class="quick-stat-card">
            <div class="quick-stat-icon bg-primary">
                <i class="fas fa-users"></i>
            </div>
            <div class="quick-stat-info">
                <h6 class="quick-stat-label">Total Siswa</h6>
                <h4 class="quick-stat-number"><?= count($siswa_list) ?></h4>
                <small class="text-muted">Ditemukan</small>
            </div>
        </div>
        <div class="quick-stat-card">
            <div class="quick-stat-icon bg-success">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="quick-stat-info">
                <h6 class="quick-stat-label">Total Kelas</h6>
                <h4 class="quick-stat-number"><?= count($kelas_list) ?></h4>
                <small class="text-muted">Tersedia</small>
            </div>
        </div>
        <div class="quick-stat-card">
            <div class="quick-stat-icon bg-danger">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="quick-stat-info">
                <h6 class="quick-stat-label">Bermasalah</h6>
                <h4 class="quick-stat-number">
                    <?php 
                    $batas = getBatasPoin();
                    $count = 0;
                    foreach ($siswa_list as $s) {
                        if ($s['poin_akhir'] >= $batas['peringatan']) $count++;
                    }
                    echo $count;
                    ?>
                </h4>
                <small class="text-muted">Poin ≥ <?= $batas['peringatan'] ?></small>
            </div>
        </div>
        <div class="quick-stat-card">
            <div class="quick-stat-icon bg-warning">
                <i class="fas fa-calculator"></i>
            </div>
            <div class="quick-stat-info">
                <h6 class="quick-stat-label">Total Poin</h6>
                <h4 class="quick-stat-number">
                    <?php 
                    $total = 0;
                    foreach ($siswa_list as $s) {
                        $total += $s['poin_akhir'];
                    }
                    echo $total;
                    ?>
                </h4>
                <small class="text-muted">Keseluruhan</small>
            </div>
        </div>
    </div>

    <!-- Tabel Siswa -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="35" class="text-center">No</th>
                            <th width="40" class="text-center d-none d-sm-table-cell">Foto</th>
                            <th>NISN</th>
                            <th>Nama Siswa</th>
                            <th class="d-none d-md-table-cell">Kelas</th>
                            <th class="text-center d-none d-sm-table-cell">JK</th>
                            <th class="text-center">Pelanggaran</th>
                            <th class="text-center">Prestasi</th>
                            <th class="text-center">Poin</th>
                            <th class="text-center" width="100">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($siswa_list)): ?>
                            <?php $no = 1; foreach ($siswa_list as $siswa): 
                                $poin = $siswa['poin_akhir'];
                                $level = getPoinLevel($poin);
                            ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?></td>
                                    <td class="text-center d-none d-sm-table-cell">
                                        <?php if (!empty($siswa['foto']) && file_exists(APP_PATH . 'uploads/siswa/' . $siswa['foto'])): ?>
                                            <img src="<?= APP_URL ?>uploads/siswa/<?= $siswa['foto'] ?>" 
                                                 alt="Foto" width="32" height="32" 
                                                 style="object-fit: cover; border-radius: 50%;">
                                        <?php else: ?>
                                            <div class="avatar-placeholder" style="width:32px;height:32px;border-radius:50%;background:#e9ecef;display:inline-flex;align-items:center;justify-content:center;color:#6c757d;font-weight:600;font-size:12px;">
                                                <?= strtoupper(substr($siswa['nama_siswa'], 0, 1)) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge bg-light text-dark" style="font-size:9px;"><?= htmlspecialchars($siswa['nisn'] ?? '-') ?></span></td>
                                    <td><strong><?= htmlspecialchars($siswa['nama_siswa']) ?></strong></td>
                                    <td class="d-none d-md-table-cell"><?= htmlspecialchars($siswa['nama_kelas'] ?? 'Belum ditentukan') ?></td>
                                    <td class="text-center d-none d-sm-table-cell"><?= $siswa['jenis_kelamin'] == 'L' ? 'L' : 'P' ?></td>
                                    <td class="text-center">
                                        <?php if ($siswa['total_pelanggaran'] > 0): ?>
                                            <span class="badge bg-danger"><?= $siswa['total_pelanggaran'] ?></span>
                                            <br><small style="font-size:7px;">(<?= $siswa['poin_pelanggaran'] ?>)</small>
                                        <?php else: ?>
                                            <span class="text-muted">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($siswa['total_prestasi'] > 0): ?>
                                            <span class="badge bg-success"><?= $siswa['total_prestasi'] ?></span>
                                            <br><small style="font-size:7px;">(-<?= $siswa['poin_prestasi'] ?>)</small>
                                        <?php else: ?>
                                            <span class="text-muted">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-<?= $level['color'] ?>" style="font-size:10px;">
                                            <?= $poin ?>
                                        </span>
                                        <?php if ($siswa['total_pelanggaran'] > 0 && $siswa['total_prestasi'] > 0): ?>
                                            <br><small style="font-size:7px;"><?= $siswa['poin_pelanggaran'] ?>-<?= $siswa['poin_prestasi'] ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="<?= APP_URL ?>guru/siswa_detail_guru.php?id=<?= $siswa['id'] ?>" 
                                               class="btn btn-outline-info btn-sm" title="Detail">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?= APP_URL ?>guru/input_pelanggaran.php?siswa=<?= $siswa['id'] ?>" 
                                               class="btn btn-outline-danger btn-sm" title="Input Pelanggaran">
                                                <i class="fas fa-plus-circle"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="table-info fw-bold">
                                <td colspan="6" class="text-end">Total</td>
                                <td class="text-center">
                                    <?php 
                                    $total_pel = array_sum(array_column($siswa_list, 'total_pelanggaran'));
                                    echo $total_pel;
                                    ?>
                                </td>
                                <td class="text-center">
                                    <?php 
                                    $total_pres = array_sum(array_column($siswa_list, 'total_prestasi'));
                                    echo $total_pres;
                                    ?>
                                </td>
                                <td class="text-center">
                                    <?php 
                                    $total_poin = array_sum(array_column($siswa_list, 'poin_akhir'));
                                    echo $total_poin;
                                    ?>
                                </td>
                                <td></td>
                            </tr>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">
                                    <i class="fas fa-user-slash fa-2x mb-2 d-block opacity-25"></i>
                                    <?php if (!empty($search) || $kelas_filter): ?>
                                        Tidak ada data yang sesuai dengan pencarian
                                    <?php else: ?>
                                        Belum ada data siswa
                                    <?php endif; ?>
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
                Menampilkan <?= count($siswa_list) ?> siswa | 
                <span class="text-danger">Total Pelanggaran: <?= array_sum(array_column($siswa_list, 'total_pelanggaran')) ?></span> | 
                <span class="text-success">Total Prestasi: <?= array_sum(array_column($siswa_list, 'total_prestasi')) ?></span> | 
                <span class="text-warning">Total Poin: <?= array_sum(array_column($siswa_list, 'poin_akhir')) ?></span>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Table Styles */
.table-bordered {
    border: 1px solid #dee2e6;
}

.table-bordered th,
.table-bordered td {
    border: 1px solid #dee2e6;
    padding: 6px 8px;
    vertical-align: middle;
}

.table thead th {
    background: #f8f9fa;
    font-weight: 600;
    font-size: 9px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    color: #495057;
    border-bottom: 2px solid #dee2e6;
    white-space: nowrap;
}

.table tbody tr:hover {
    background-color: rgba(108, 99, 255, 0.04);
}

.table tbody td {
    font-size: 11px;
}

/* Avatar */
.avatar-placeholder {
    background: #e9ecef;
    color: #6c757d;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

/* Badge */
.badge {
    font-size: 8px !important;
    padding: 2px 6px !important;
}

/* Button */
.btn-group-sm .btn,
.btn-sm {
    padding: 1px 4px !important;
    font-size: 9px !important;
    border-radius: 4px !important;
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

.quick-stat-icon.bg-primary { background: #4F46E5; }
.quick-stat-icon.bg-success { background: #10B981; }
.quick-stat-icon.bg-danger { background: #EF4444; }
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

/* Dashboard Container */
.dashboard-container {
    padding: 8px !important;
    max-width: 100%;
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

.header-right {
    text-align: right;
}

.user-name {
    font-size: 0.8rem;
}

/* Responsive */
@media (max-width: 768px) {
    .dashboard-container {
        padding: 6px !important;
    }
    .dashboard-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 4px;
        margin-bottom: 10px;
    }
    .header-right {
        text-align: left;
        width: 100%;
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
    .table thead th {
        font-size: 7px;
        padding: 3px 4px;
    }
    .table tbody td {
        font-size: 9px;
        padding: 3px 4px;
    }
    .badge {
        font-size: 7px !important;
        padding: 1px 4px !important;
    }
    .btn-sm {
        padding: 1px 3px !important;
        font-size: 8px !important;
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
    .table thead th {
        font-size: 6px;
        padding: 2px 3px;
    }
    .table tbody td {
        font-size: 8px;
        padding: 2px 3px;
    }
    .badge {
        font-size: 6px !important;
        padding: 1px 3px !important;
    }
    .btn-sm {
        padding: 0px 2px !important;
        font-size: 7px !important;
    }
}
</style>

<?php include '../includes/footer.php'; ?>