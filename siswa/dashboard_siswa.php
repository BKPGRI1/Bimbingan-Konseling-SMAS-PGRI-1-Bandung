<?php
// siswa/dashboard_siswa.php
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

$page_title = 'Dashboard Siswa';

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
// DATA KONSELING
// ============================================
$konseling_list = getCatatanKonseling($siswa_id);
$total_konseling = count($konseling_list);

// Level poin
$level = getPoinLevel($total_poin);
$batas = getBatasPoin();

// Ambil 5 pelanggaran terbaru
$pelanggaran_terbaru = array_slice($pelanggaran_list, 0, 5);

// Ambil 5 prestasi terbaru
$prestasi_terbaru = array_slice($prestasi_list, 0, 5);

// Ambil 3 catatan konseling terbaru
$konseling_terbaru = array_slice($konseling_list, 0, 3);
?>

<div class="dashboard-container">
    <!-- Toast Notification -->
    <?php if (isset($_SESSION['toast'])): ?>
        <div id="toastNotification" class="toast-container position-fixed top-0 end-0 p-2" style="z-index: 9999;">
            <div class="toast align-items-center text-white bg-<?= $_SESSION['toast']['type'] ?> border-0 show" role="alert" aria-live="assertive" aria-atomic="true" style="min-width: 200px; max-width: 320px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.12); font-size: 12px;">
                <div class="d-flex align-items-center p-2">
                    <div class="flex-shrink-0 ms-1">
                        <i class="fas fa-<?= $_SESSION['toast']['type'] == 'success' ? 'check-circle' : ($_SESSION['toast']['type'] == 'warning' ? 'exclamation-triangle' : 'times-circle') ?> fa-sm"></i>
                    </div>
                    <div class="toast-body flex-grow-1 text-start ms-2" style="font-size: 12px; font-weight: 500; padding: 2px 0; line-height: 1.3;">
                        <?= $_SESSION['toast']['message'] ?>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-0" data-bs-dismiss="toast" aria-label="Close" style="font-size: 9px; padding: 3px;"></button>
                </div>
            </div>
        </div>
        <?php unset($_SESSION['toast']); ?>
    <?php endif; ?>

    <!-- Header -->
    <div class="dashboard-header">
        <div>
            <h4 class="dashboard-title">
                <i class="fas fa-home text-primary me-1"></i> Dashboard
            </h4>
            <small class="text-muted dashboard-subtitle">
                Selamat datang, <?= htmlspecialchars($nama_siswa) ?>
            </small>
        </div>
        <div>
            <span class="badge bg-primary">
                <i class="fas fa-user me-1"></i> <?= htmlspecialchars($nama_siswa) ?>
            </span>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="quick-stats-grid">
        <div class="quick-stat-card">
            <div class="quick-stat-icon bg-info">
                <i class="fas fa-user-graduate"></i>
            </div>
            <div class="quick-stat-info">
                <span class="quick-stat-label">Nama</span>
                <span class="quick-stat-number"><?= htmlspecialchars($nama_siswa) ?></span>
            </div>
        </div>
        <div class="quick-stat-card">
            <div class="quick-stat-icon bg-primary">
                <i class="fas fa-school"></i>
            </div>
            <div class="quick-stat-info">
                <span class="quick-stat-label">Kelas</span>
                <span class="quick-stat-number"><?= htmlspecialchars($kelas) ?></span>
            </div>
        </div>
        <div class="quick-stat-card">
            <div class="quick-stat-icon bg-<?= $level['color'] ?>">
                <i class="fas <?= $level['icon'] ?>"></i>
            </div>
            <div class="quick-stat-info">
                <span class="quick-stat-label">Poin</span>
                <span class="quick-stat-number"><?= $total_poin ?></span>
                <small><?= $level['level'] ?></small>
            </div>
        </div>
        <div class="quick-stat-card">
            <div class="quick-stat-icon bg-danger">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="quick-stat-info">
                <span class="quick-stat-label">Pelanggaran</span>
                <span class="quick-stat-number"><?= $total_pelanggaran ?></span>
                <small>+<?= $total_poin_pelanggaran ?> poin</small>
            </div>
        </div>
        <div class="quick-stat-card">
            <div class="quick-stat-icon bg-success">
                <i class="fas fa-star"></i>
            </div>
            <div class="quick-stat-info">
                <span class="quick-stat-label">Prestasi</span>
                <span class="quick-stat-number"><?= $total_prestasi ?></span>
                <small>-<?= $total_poin_prestasi ?> poin</small>
            </div>
        </div>
        <div class="quick-stat-card">
            <div class="quick-stat-icon bg-warning">
                <i class="fas fa-notes-medical"></i>
            </div>
            <div class="quick-stat-info">
                <span class="quick-stat-label">Konseling</span>
                <span class="quick-stat-number"><?= $total_konseling ?></span>
            </div>
        </div>
    </div>

    <!-- Status Poin -->
    <div class="card">
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
            <div class="status-row">
                <div class="poin-circle bg-<?= $level['color'] ?>">
                    <?= $total_poin ?>
                </div>
                <div class="status-info">
                    <h5 class="status-level"><?= $level['level'] ?></h5>
                    <div class="progress">
                        <?php 
                        $max_poin = $batas['do'];
                        $persen = min(($total_poin / $max_poin) * 100, 100);
                        ?>
                        <div class="progress-bar bg-<?= $level['color'] ?>" 
                             role="progressbar" 
                             style="width: <?= $persen ?>%;">
                        </div>
                    </div>
                    <div class="progress-label">
                        <span>0</span>
                        <span>DO: <?= $max_poin ?></span>
                    </div>
                </div>
            </div>
            <!-- Detail Poin -->
            <div class="poin-detail mt-2">
                <div class="row g-1 text-center">
                    <div class="col-4">
                        <div class="p-1 bg-light rounded">
                            <small class="text-muted" style="font-size:0.5rem;">Pelanggaran</small>
                            <div class="fw-bold text-danger" style="font-size:0.7rem;">+<?= $total_poin_pelanggaran ?></div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-1 bg-light rounded">
                            <small class="text-muted" style="font-size:0.5rem;">Prestasi</small>
                            <div class="fw-bold text-success" style="font-size:0.7rem;">-<?= $total_poin_prestasi ?></div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-1 bg-light rounded">
                            <small class="text-muted" style="font-size:0.5rem;">Total</small>
                            <div class="fw-bold text-<?= $level['color'] ?>" style="font-size:0.7rem;"><?= $total_poin ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pelanggaran Terbaru -->
    <div class="card mt-3">
        <div class="card-header d-flex align-items-center justify-content-between">
            <span><i class="fas fa-list me-2"></i> Pelanggaran Terbaru</span>
            <span class="info-badge">5 terakhir</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="30" class="text-center">#</th>
                            <th>Tanggal</th>
                            <th>Pelanggaran</th>
                            <th class="text-center" width="45">Poin</th>
                            <th class="text-center" width="60">Kat</th>
                            <th class="text-center" width="60">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($pelanggaran_terbaru)): 
                            $no = 1;
                            foreach ($pelanggaran_terbaru as $p): 
                                // Ambil kategori dari database
                                $kategori = '';
                                if (isset($p['jenis_pelanggaran_id'])) {
                                    $jp = getJenisPelanggaranById($p['jenis_pelanggaran_id']);
                                    if ($jp) {
                                        $kategori = $jp['kategori'] ?? '';
                                    }
                                }
                                $badge_class = $kategori == 'berat' ? 'bg-danger' : ($kategori == 'sedang' ? 'bg-warning' : 'bg-success');
                                $kategori_label = ucfirst($kategori) ?: '-';
                                $status_badge = $p['status'] == 'disetujui' ? 'bg-success' : ($p['status'] == 'ditolak' ? 'bg-danger' : 'bg-warning');
                                $status_label = $p['status'] == 'disetujui' ? 'Disetujui' : ($p['status'] == 'ditolak' ? 'Ditolak' : 'Pending');
                        ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td><?= date('d-m-Y', strtotime($p['tanggal_pelanggaran'])) ?></td>
                                <td><?= htmlspecialchars($p['nama_pelanggaran'] ?? '-') ?></td>
                                <td class="text-center">
                                    <span class="badge bg-danger">+<?= $p['poin'] ?? 0 ?></span>
                                </td>
                                <td class="text-center">
                                    <?php if ($kategori): ?>
                                        <span class="badge <?= $badge_class ?>"><?= $kategori_label ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge <?= $status_badge ?>" style="font-size:0.45rem;">
                                        <?= $status_label ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-3">
                                    <i class="fas fa-check-circle text-success me-1"></i> Belum ada pelanggaran
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if ($total_pelanggaran > 5): ?>
            <div class="card-footer bg-transparent">
                <a href="<?= APP_URL ?>siswa/pelanggaran_siswa.php" class="btn btn-sm btn-primary w-100">
                    <i class="fas fa-eye me-1"></i> Lihat Semua
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Prestasi Terbaru -->
    <div class="card mt-3">
        <div class="card-header d-flex align-items-center justify-content-between">
            <span><i class="fas fa-trophy text-warning me-2"></i> Prestasi Terbaru</span>
            <span class="info-badge">5 terakhir</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="30" class="text-center">#</th>
                            <th>No. Prestasi</th>
                            <th>Prestasi</th>
                            <th class="text-center" width="60">Jenis</th>
                            <th class="text-center" width="45">Poin</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($prestasi_terbaru)): 
                            $no = 1;
                            foreach ($prestasi_terbaru as $p): 
                                $jenis_badge = $p['jenis_prestasi'] == 'akademik' ? 'bg-success' : ($p['jenis_prestasi'] == 'non_akademik' ? 'bg-info' : 'bg-secondary');
                                $jenis_label = ucfirst(str_replace('_', ' ', $p['jenis_prestasi']));
                        ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td><span class="badge bg-primary"><?= $p['no_prestasi'] ?? 'PRS' ?></span></td>
                                <td><?= htmlspecialchars($p['nama_prestasi']) ?></td>
                                <td class="text-center">
                                    <span class="badge <?= $jenis_badge ?>" style="font-size:0.45rem;">
                                        <?= $jenis_label ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success">-<?= $p['poin_prestasi'] ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">
                                    <i class="fas fa-trophy text-warning me-1"></i> Belum ada prestasi
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if ($total_prestasi > 5): ?>
            <div class="card-footer bg-transparent">
                <a href="<?= APP_URL ?>siswa/prestasi_siswa.php" class="btn btn-sm btn-primary w-100">
                    <i class="fas fa-eye me-1"></i> Lihat Semua
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Catatan Konseling Terbaru -->
    <div class="card mt-3">
        <div class="card-header d-flex align-items-center justify-content-between">
            <span><i class="fas fa-notes-medical me-2"></i> Catatan Konseling</span>
            <span class="info-badge">3 terakhir</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="30" class="text-center">#</th>
                            <th>Tanggal</th>
                            <th>Topik</th>
                            <th class="text-center" width="65">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($konseling_terbaru)): 
                            $no = 1;
                            foreach ($konseling_terbaru as $k): 
                                $status_badge = $k['status'] == 'selesai' ? 'bg-success' : ($k['status'] == 'proses' ? 'bg-warning' : 'bg-secondary');
                        ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td><?= date('d-m-Y', strtotime($k['tanggal'])) ?></td>
                                <td><?= htmlspecialchars($k['topik'] ?? '-') ?></td>
                                <td class="text-center">
                                    <span class="badge <?= $status_badge ?>">
                                        <?= ucfirst($k['status'] ?? 'Pending') ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">
                                    <i class="fas fa-info-circle me-1"></i> Belum ada catatan konseling
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if ($total_konseling > 3): ?>
            <div class="card-footer bg-transparent">
                <a href="<?= APP_URL ?>siswa/konseling_siswa.php" class="btn btn-sm btn-primary w-100">
                    <i class="fas fa-eye me-1"></i> Lihat Semua
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* ============================================
   DASHBOARD STYLE
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
    margin-bottom: 10px;
    gap: 4px;
}

.dashboard-title {
    font-size: 0.9rem;
    margin-bottom: 0;
    font-weight: 600;
}

.dashboard-subtitle {
    font-size: 0.6rem;
    color: #6c757d;
    display: block;
}

.dashboard-header .badge {
    font-size: 0.55rem !important;
    padding: 2px 8px !important;
}

/* Quick Stats */
.quick-stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 4px;
    margin-bottom: 8px;
}

.quick-stat-card {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 5px 8px;
    background: #fff;
    border-radius: 6px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    min-height: 36px;
}

.quick-stat-icon {
    width: 24px;
    height: 24px;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 9px;
    flex-shrink: 0;
}
.quick-stat-icon.bg-info { background: #3B82F6; }
.quick-stat-icon.bg-primary { background: #4F46E5; }
.quick-stat-icon.bg-success { background: #10B981; }
.quick-stat-icon.bg-danger { background: #EF4444; }
.quick-stat-icon.bg-warning { background: #F59E0B; }

.quick-stat-info {
    flex: 1;
    min-width: 0;
    overflow: hidden;
}

.quick-stat-label {
    font-size: 0.4rem;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    color: #6B7280;
    display: block;
    line-height: 1.1;
}

.quick-stat-number {
    font-size: 0.7rem;
    font-weight: 700;
    display: block;
    line-height: 1.2;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.quick-stat-info small {
    font-size: 0.4rem;
    color: #6B7280;
    display: block;
}

/* Card */
.card {
    border: none;
    border-radius: 8px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.05);
    margin-bottom: 6px;
    background: #fff;
    overflow: hidden;
}

.card-header {
    background: transparent;
    border-bottom: 1px solid #e9ecef;
    padding: 4px 8px;
    font-weight: 600;
    font-size: 0.65rem;
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 4px;
    color: #1A1A2E;
}

.card-body {
    padding: 6px 8px;
}

.card-footer {
    background: transparent;
    border-top: 1px solid #e9ecef;
    padding: 3px 8px;
}

/* Status Poin */
.status-row {
    display: flex;
    align-items: center;
    gap: 10px;
}

.poin-circle {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    font-weight: 700;
    color: #fff;
    flex-shrink: 0;
}
.poin-circle.bg-success { background: #10B981; }
.poin-circle.bg-info { background: #3B82F6; }
.poin-circle.bg-warning { background: #F59E0B; }
.poin-circle.bg-danger { background: #EF4444; }

.status-info {
    flex: 1;
    min-width: 0;
}

.status-level {
    font-size: 0.8rem;
    font-weight: 600;
    margin-bottom: 2px;
}

.progress {
    height: 4px;
    background: #e9ecef;
    border-radius: 4px;
    overflow: hidden;
}

.progress-bar {
    border-radius: 4px;
}

.progress-label {
    display: flex;
    justify-content: space-between;
    font-size: 0.5rem;
    color: #6c757d;
    margin-top: 2px;
}

/* Poin Detail */
.poin-detail {
    border-top: 1px solid #e9ecef;
    padding-top: 6px;
}

/* Table */
.table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.table-sm th,
.table-sm td {
    padding: 2px 3px;
    font-size: 0.55rem;
    vertical-align: middle;
}

.table-bordered {
    border: 1px solid #dee2e6;
}
.table-bordered th,
.table-bordered td {
    border: 1px solid #dee2e6;
}

.table thead th {
    background: #f8f9fa;
    font-weight: 600;
    font-size: 0.45rem;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    color: #495057;
    border-bottom: 2px solid #dee2e6;
    white-space: nowrap;
}

.table tbody td {
    white-space: nowrap;
}

/* Badge */
.badge {
    font-size: 0.5rem !important;
    padding: 1px 5px !important;
    border-radius: 3px;
    font-weight: 600;
}

.info-badge {
    font-size: 0.5rem;
    color: #718096;
    margin-left: auto;
    font-weight: 400;
}

/* Button */
.btn-sm {
    font-size: 0.6rem !important;
    padding: 2px 6px !important;
}

/* ============================================
   RESPONSIVE
   ============================================ */
@media (min-width: 480px) {
    .quick-stat-card {
        min-height: 40px;
        padding: 6px 10px;
    }
    .quick-stat-icon {
        width: 26px;
        height: 26px;
        font-size: 10px;
    }
    .quick-stat-number {
        font-size: 0.75rem;
    }
    .table-sm th,
    .table-sm td {
        font-size: 0.6rem;
        padding: 3px 4px;
    }
    .badge {
        font-size: 0.5rem !important;
    }
    .poin-circle {
        width: 48px;
        height: 48px;
        font-size: 16px;
    }
}

@media (min-width: 576px) {
    .quick-stats-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 6px;
    }
    .quick-stat-card {
        min-height: 44px;
        padding: 8px 12px;
    }
    .quick-stat-icon {
        width: 28px;
        height: 28px;
        font-size: 11px;
    }
    .quick-stat-number {
        font-size: 0.8rem;
    }
    .table-sm th,
    .table-sm td {
        font-size: 0.65rem;
        padding: 4px 6px;
    }
}

@media (min-width: 768px) {
    .quick-stats-grid {
        grid-template-columns: repeat(6, 1fr);
        gap: 8px;
    }
    .quick-stat-card {
        min-height: 50px;
        padding: 10px 14px;
        gap: 8px;
    }
    .quick-stat-icon {
        width: 32px;
        height: 32px;
        font-size: 13px;
    }
    .quick-stat-number {
        font-size: 0.9rem;
    }
    .quick-stat-label {
        font-size: 0.5rem;
    }
    .quick-stat-info small {
        font-size: 0.45rem;
        display: inline-block;
    }
    .table-sm th,
    .table-sm td {
        font-size: 0.7rem;
        padding: 5px 8px;
    }
    .badge {
        font-size: 0.55rem !important;
        padding: 2px 6px !important;
    }
    .poin-circle {
        width: 54px;
        height: 54px;
        font-size: 18px;
    }
    .card-header {
        padding: 6px 12px;
        font-size: 0.7rem;
    }
    .card-body {
        padding: 8px 12px;
    }
}

@media (min-width: 992px) {
    .quick-stats-grid {
        gap: 10px;
    }
    .quick-stat-card {
        min-height: 56px;
        padding: 12px 16px;
    }
    .quick-stat-icon {
        width: 36px;
        height: 36px;
        font-size: 14px;
    }
    .quick-stat-number {
        font-size: 1rem;
    }
    .table-sm th,
    .table-sm td {
        font-size: 0.75rem;
        padding: 6px 10px;
    }
    .poin-circle {
        width: 60px;
        height: 60px;
        font-size: 20px;
    }
}

@media (max-width: 480px) {
    .quick-stats-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 3px;
    }
    .quick-stat-card {
        padding: 4px 6px;
        min-height: 32px;
    }
    .quick-stat-icon {
        width: 20px;
        height: 20px;
        font-size: 8px;
    }
    .quick-stat-number {
        font-size: 0.6rem;
    }
    .quick-stat-label {
        font-size: 0.35rem;
    }
    .quick-stat-info small {
        font-size: 0.35rem;
    }
    .table-sm th,
    .table-sm td {
        font-size: 0.45rem;
        padding: 1px 2px;
    }
    .badge {
        font-size: 0.4rem !important;
        padding: 1px 3px !important;
    }
    .poin-circle {
        width: 36px;
        height: 36px;
        font-size: 12px;
    }
    .status-level {
        font-size: 0.65rem;
    }
    .card-header {
        font-size: 0.55rem;
        padding: 3px 6px;
    }
    .card-body {
        padding: 4px 6px;
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