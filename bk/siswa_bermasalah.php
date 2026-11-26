<?php
// bk/siswa_bermasalah.php
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

$page_title = 'Data Siswa Bermasalah';

// Filter
$kelas_filter = isset($_GET['kelas']) ? (int)$_GET['kelas'] : null;
$search = isset($_GET['search']) ? cleanString($_GET['search']) : '';

// Ambil data siswa bermasalah
$siswa_list = [];
try {
    $siswa_list = getSiswaBermasalah();
} catch (Exception $e) {
    $siswa_list = [];
}

// Ambil data prestasi untuk setiap siswa
foreach ($siswa_list as &$siswa) {
    $siswa['total_prestasi'] = getTotalPoinPrestasiBySiswa($siswa['id']);
    $siswa['total_pelanggaran'] = getTotalPoinSiswa($siswa['id']);
    // Poin bersih = poin pelanggaran - poin prestasi
    $siswa['poin_bersih'] = $siswa['total_pelanggaran'] - $siswa['total_prestasi'];
    if ($siswa['poin_bersih'] < 0) $siswa['poin_bersih'] = 0;
}

// Filter berdasarkan kelas
if ($kelas_filter) {
    $siswa_list = array_filter($siswa_list, function($item) use ($kelas_filter) {
        return $item['kelas_id'] == $kelas_filter;
    });
}

// Filter berdasarkan pencarian
if (!empty($search)) {
    $search_lower = strtolower($search);
    $siswa_list = array_filter($siswa_list, function($item) use ($search_lower) {
        return strpos(strtolower($item['nama_siswa']), $search_lower) !== false ||
               strpos(strtolower($item['nisn'] ?? ''), $search_lower) !== false;
    });
}

// Ambil daftar kelas untuk filter
$kelas_list = getKelasOptions();

// Include header BK
include __DIR__ . '/../includes/header_bk.php';
?>

<div class="container-fluid dashboard-container">
    <!-- Header -->
    <div class="dashboard-header">
        <div>
            <h4 class="dashboard-title">
                <i class="fas fa-user-graduate text-danger me-2"></i> Data Siswa Bermasalah
            </h4>
            <small class="text-muted dashboard-subtitle">
                <i class="fas fa-exclamation-triangle me-1"></i> Siswa dengan poin mencapai batas peringatan
            </small>
        </div>
        <div>
            <span class="badge bg-danger p-2">
                <i class="fas fa-users me-1"></i> <?= count($siswa_list) ?> Siswa
            </span>
        </div>
    </div>

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

    <!-- Filter -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small mb-1">Cari Siswa</label>
                    <input type="text" class="form-control form-control-sm" name="search" 
                           placeholder="Nama atau NISN..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label small mb-1">Kelas</label>
                    <select class="form-select form-select-sm" name="kelas">
                        <option value="">Semua Kelas</option>
                        <?php foreach ($kelas_list as $kelas): ?>
                            <option value="<?= $kelas['id'] ?>" <?= $kelas_filter == $kelas['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($kelas['nama_kelas']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="fas fa-search me-1"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Statistik -->
    <div class="quick-stats-grid mb-3">
        <div class="quick-stat-card h-100">
            <div class="quick-stat-icon bg-danger">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="quick-stat-info">
                <h6 class="quick-stat-label">Total Bermasalah</h6>
                <h4 class="quick-stat-number"><?= count($siswa_list) ?></h4>
                <small class="text-muted">Siswa</small>
            </div>
        </div>
        <div class="quick-stat-card h-100">
            <div class="quick-stat-icon bg-warning">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="quick-stat-info">
                <h6 class="quick-stat-label">Rata-rata Poin</h6>
                <h4 class="quick-stat-number">
                    <?php 
                    $avg = 0;
                    if (count($siswa_list) > 0) {
                        $total = array_sum(array_column($siswa_list, 'poin_bersih'));
                        $avg = round($total / count($siswa_list));
                    }
                    echo $avg;
                    ?>
                </h4>
                <small class="text-muted">Poin</small>
            </div>
        </div>
        <div class="quick-stat-card h-100">
            <div class="quick-stat-icon bg-info">
                <i class="fas fa-tag"></i>
            </div>
            <div class="quick-stat-info">
                <h6 class="quick-stat-label">Poin Tertinggi</h6>
                <h4 class="quick-stat-number">
                    <?php 
                    $max = 0;
                    if (count($siswa_list) > 0) {
                        $max = max(array_column($siswa_list, 'poin_bersih'));
                    }
                    echo $max;
                    ?>
                </h4>
                <small class="text-muted">Poin</small>
            </div>
        </div>
        <div class="quick-stat-card h-100">
            <div class="quick-stat-icon bg-success">
                <i class="fas fa-file-alt"></i>
            </div>
            <div class="quick-stat-info">
                <h6 class="quick-stat-label">Perlu Surat</h6>
                <h4 class="quick-stat-number">
                    <?php 
                    $batas = getBatasPoin();
                    $perlu_surat = 0;
                    foreach ($siswa_list as $s) {
                        if ($s['poin_bersih'] >= $batas['peringatan']) {
                            $perlu_surat++;
                        }
                    }
                    echo $perlu_surat;
                    ?>
                </h4>
                <small class="text-muted">Siswa</small>
            </div>
        </div>
    </div>

    <!-- Tabel Siswa Bermasalah -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <i class="fas fa-list me-2"></i> Daftar Siswa Bermasalah
                <span class="info-badge">
                    <i class="fas fa-info-circle"></i> <?= count($siswa_list) ?> data
                </span>
            </div>
            <?php if (!empty($siswa_list)): ?>
                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn btn-sm btn-success" onclick="cetakSuratSemua()">
                        <i class="fas fa-print me-1"></i> Cetak Surat Semua
                    </button>
                </div>
            <?php endif; ?>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0" id="tableSiswa">
                    <thead class="table-light">
                        <tr>
                            <th width="35" class="text-center">
                                <input type="checkbox" id="selectAll" onchange="toggleAll(this)">
                            </th>
                            <th width="35" class="text-center">No</th>
                            <th>NISN</th>
                            <th>Nama Siswa</th>
                            <th class="d-none d-sm-table-cell">Kelas</th>
                            <th class="text-center">Poin Pelanggaran</th>
                            <th class="text-center">Poin Prestasi</th>
                            <th class="text-center">Poin Bersih</th>
                            <th class="text-center d-none d-md-table-cell">Level</th>
                            <th class="text-center" width="200">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($siswa_list)): 
                            $no = 1; 
                            $batas = getBatasPoin();
                            foreach ($siswa_list as $siswa): 
                                $level = getPoinLevel($siswa['poin_bersih']);
                                $jenis_surat = '';
                                if ($siswa['poin_bersih'] >= $batas['do']) {
                                    $jenis_surat = 'DO';
                                } elseif ($siswa['poin_bersih'] >= $batas['pemanggilan']) {
                                    $jenis_surat = 'Pemanggilan';
                                } elseif ($siswa['poin_bersih'] >= $batas['peringatan']) {
                                    $jenis_surat = 'Teguran';
                                }
                        ?>
                            <tr>
                                <td class="text-center">
                                    <input type="checkbox" class="siswa-checkbox" value="<?= $siswa['id'] ?>" 
                                           data-nama="<?= htmlspecialchars($siswa['nama_siswa']) ?>"
                                           data-kelas="<?= htmlspecialchars($siswa['nama_kelas'] ?? '-') ?>"
                                           data-poin="<?= $siswa['poin_bersih'] ?>"
                                           data-nisn="<?= htmlspecialchars($siswa['nisn'] ?? '-') ?>">
                                </td>
                                <td class="text-center"><?= $no++ ?></td>
                                <td><span class="badge bg-light text-dark" style="font-size: 9px;"><?= htmlspecialchars($siswa['nisn'] ?? '-') ?></span></td>
                                <td><strong><?= htmlspecialchars($siswa['nama_siswa']) ?></strong></td>
                                <td class="d-none d-sm-table-cell"><?= htmlspecialchars($siswa['nama_kelas'] ?? '-') ?></td>
                                <td class="text-center">
                                    <span class="badge bg-danger" style="font-size: 10px;"><?= $siswa['total_pelanggaran'] ?></span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success" style="font-size: 10px;">-<?= $siswa['total_prestasi'] ?></span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-<?= $level['color'] ?>" style="font-size: 10px;">
                                        <?= $siswa['poin_bersih'] ?>
                                    </span>
                                </td>
                                <td class="text-center d-none d-md-table-cell">
                                    <span class="badge bg-<?= $level['color'] ?>" style="font-size: 9px;">
                                        <i class="fas <?= $level['icon'] ?> me-1"></i> <?= $level['level'] ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="<?= APP_URL ?>bk/siswa_detail_bk.php?id=<?= $siswa['id'] ?>" 
                                           class="btn btn-outline-info btn-sm" title="Detail">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($jenis_surat): ?>
                                            <button class="btn btn-outline-warning btn-sm" onclick="cetakSurat(<?= $siswa['id'] ?>, '<?= $jenis_surat ?>', '<?= htmlspecialchars($siswa['nama_siswa']) ?>')" title="Cetak Surat">
                                                <i class="fas fa-print"></i>
                                            </button>
                                        <?php endif; ?>
                                        <a href="<?= APP_URL ?>bk/catatan_konseling.php?siswa=<?= $siswa['id'] ?>" 
                                           class="btn btn-outline-success btn-sm" title="Konseling">
                                            <i class="fas fa-notes-medical"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">
                                    <i class="fas fa-check-circle fa-2x mb-2 d-block text-success opacity-50"></i>
                                    Tidak ada siswa bermasalah
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if (!empty($siswa_list)): ?>
            <div class="card-footer bg-transparent text-muted d-flex justify-content-between align-items-center flex-wrap">
                <span>
                    <i class="fas fa-info-circle me-1"></i>
                    Menampilkan <?= count($siswa_list) ?> siswa bermasalah
                </span>
                <span>
                    <i class="fas fa-print me-1"></i>
                    <a href="#" onclick="cetakSuratSemua()" class="text-primary">Cetak Surat Semua</a>
                </span>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Cetak Surat -->
<div class="modal fade" id="modalCetakSurat" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-print text-primary me-2"></i> Cetak Surat
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Surat akan dicetak berdasarkan tingkat poin siswa:</strong>
                    <ul class="mb-0 mt-2">
                        <li><span class="badge bg-warning">Poin 100-199</span> → Surat Teguran</li>
                        <li><span class="badge bg-info">Poin 200-299</span> → Surat Pemanggilan</li>
                        <li><span class="badge bg-danger">Poin ≥ 300</span> → Surat Peringatan DO</li>
                    </ul>
                </div>
                <div id="suratPreview">
                    <div class="text-center text-muted py-3">
                        <i class="fas fa-spinner fa-spin me-2"></i> Memuat preview surat...
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <a href="#" id="btnCetakPDF" target="_blank" class="btn btn-primary">
                    <i class="fas fa-file-pdf me-1"></i> Cetak PDF
                </a>
            </div>
        </div>
    </div>
</div>

<style>
/* ============================================
   STYLE SISWA BERMASALAH
   ============================================ */

.dashboard-container {
    padding: 12px !important;
    max-width: 100%;
    overflow-x: hidden;
}

.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    margin-bottom: 16px;
    gap: 8px;
}

.dashboard-title {
    font-size: 1.1rem;
    margin-bottom: 0;
}

.dashboard-subtitle {
    font-size: 0.7rem;
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
.quick-stat-icon.bg-danger { background: #EF4444; }
.quick-stat-icon.bg-warning { background: #F59E0B; }
.quick-stat-icon.bg-info { background: #3B82F6; }
.quick-stat-icon.bg-success { background: #10B981; }

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

/* Card */
.card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
    margin-bottom: 16px;
    background: #fff;
}
.card-header {
    background: transparent;
    border-bottom: 1px solid #e9ecef;
    padding: 10px 14px;
    font-weight: 600;
    font-size: 12px;
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 4px;
}
.card-body {
    padding: 12px 14px;
}
.card-footer {
    background: transparent;
    border-top: 1px solid #e9ecef;
    padding: 8px 14px;
    font-size: 11px;
}

/* Table */
.table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
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
}
.table tbody tr:hover {
    background-color: rgba(108, 99, 255, 0.04);
}
.table tbody td {
    font-size: 11px;
}

/* Badge */
.badge {
    font-size: 8px !important;
    padding: 2px 6px !important;
}

.info-badge {
    font-size: 10px;
    color: #718096;
    margin-left: 4px;
}

/* Buttons */
.btn-group-sm .btn,
.btn-sm {
    padding: 1px 4px !important;
    font-size: 9px !important;
    border-radius: 4px !important;
}

/* Checkbox */
.siswa-checkbox {
    width: 16px;
    height: 16px;
    cursor: pointer;
}

/* Toast */
.toast-container .toast {
    border-radius: 8px !important;
    box-shadow: 0 4px 20px rgba(0,0,0,0.12) !important;
}
.toast-container .toast .toast-body {
    font-size: 11px !important;
    font-weight: 500;
    padding: 2px 0 !important;
}

/* Modal */
.modal-content {
    border-radius: 12px;
    border: none;
}
.modal-header {
    border-bottom: 1px solid #e9ecef;
    padding: 14px 18px;
}
.modal-body {
    padding: 18px;
    max-height: 500px;
    overflow-y: auto;
}
.modal-footer {
    border-top: 1px solid #e9ecef;
    padding: 12px 18px;
}
#suratPreview {
    font-family: 'Times New Roman', Times, serif;
    font-size: 13px;
    line-height: 1.8;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 8px;
    min-height: 100px;
}

/* Responsive */
@media (max-width: 768px) {
    .dashboard-container {
        padding: 8px !important;
    }
    .dashboard-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 4px;
        margin-bottom: 10px;
    }
    .dashboard-title {
        font-size: 1rem;
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
    .card-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 4px;
    }
    .card-body {
        padding: 8px 10px;
    }
    .table thead th {
        font-size: 7px;
        padding: 2px 4px;
    }
    .table tbody td {
        font-size: 9px;
        padding: 2px 4px;
    }
    .badge {
        font-size: 7px !important;
        padding: 1px 4px !important;
    }
    .btn-sm {
        padding: 1px 3px !important;
        font-size: 8px !important;
    }
    .toast-container .toast {
        min-width: 150px !important;
        max-width: 220px !important;
    }
    .toast-container .toast .toast-body {
        font-size: 10px !important;
    }
}

@media (max-width: 576px) {
    .dashboard-container {
        padding: 4px !important;
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
    .card-body {
        padding: 4px 6px;
    }
    .table thead th {
        font-size: 6px;
        padding: 1px 3px;
    }
    .table tbody td {
        font-size: 8px;
        padding: 1px 3px;
    }
    .badge {
        font-size: 6px !important;
        padding: 1px 3px !important;
    }
    .btn-sm {
        padding: 0px 2px !important;
        font-size: 7px !important;
    }
    .toast-container .toast {
        min-width: 130px !important;
        max-width: 180px !important;
    }
    .toast-container .toast .toast-body {
        font-size: 9px !important;
    }
}
</style>

<script>
// ============================================
// FUNGSI CETAK SURAT
// ============================================

function toggleAll(source) {
    var checkboxes = document.getElementsByClassName('siswa-checkbox');
    for (var i = 0; i < checkboxes.length; i++) {
        checkboxes[i].checked = source.checked;
    }
}

function getSelectedSiswa() {
    var checkboxes = document.getElementsByClassName('siswa-checkbox');
    var selected = [];
    for (var i = 0; i < checkboxes.length; i++) {
        if (checkboxes[i].checked) {
            selected.push({
                id: checkboxes[i].value,
                nama: checkboxes[i].dataset.nama,
                kelas: checkboxes[i].dataset.kelas,
                poin: parseInt(checkboxes[i].dataset.poin),
                nisn: checkboxes[i].dataset.nisn
            });
        }
    }
    return selected;
}

function cetakSurat(id, jenis, nama) {
    var modal = new bootstrap.Modal(document.getElementById('modalCetakSurat'));
    modal.show();
    
    document.getElementById('suratPreview').innerHTML = 
        '<div class="text-center text-muted py-3"><i class="fas fa-spinner fa-spin me-2"></i> Memuat preview surat untuk ' + nama + '...</div>';
    document.getElementById('btnCetakPDF').href = '#';
    
    fetch('<?= APP_URL ?>bk/ajax/get_surat_preview.php?id=' + id + '&jenis=' + jenis)
        .then(response => response.text())
        .then(data => {
            document.getElementById('suratPreview').innerHTML = data;
            document.getElementById('btnCetakPDF').href = '<?= APP_URL ?>bk/cetak_surat_siswa.php?id=' + id + '&jenis=' + jenis;
        })
        .catch(() => {
            document.getElementById('suratPreview').innerHTML = 
                '<div class="alert alert-danger">Gagal memuat preview surat!</div>';
        });
}

function cetakSuratSemua() {
    var selected = getSelectedSiswa();
    if (selected.length === 0) {
        alert('Pilih minimal 1 siswa untuk mencetak surat!');
        return;
    }
    
    var modal = new bootstrap.Modal(document.getElementById('modalCetakSurat'));
    modal.show();
    
    document.getElementById('suratPreview').innerHTML = 
        '<div class="text-center text-muted py-3"><i class="fas fa-spinner fa-spin me-2"></i> Memuat preview surat untuk ' + selected.length + ' siswa...</div>';
    document.getElementById('btnCetakPDF').href = '#';
    
    var ids = selected.map(function(s) { return s.id; }).join(',');
    fetch('<?= APP_URL ?>bk/ajax/get_surat_preview_batch.php?ids=' + ids)
        .then(response => response.text())
        .then(data => {
            document.getElementById('suratPreview').innerHTML = data;
            document.getElementById('btnCetakPDF').href = '<?= APP_URL ?>bk/cetak_surat_batch.php?ids=' + ids;
        })
        .catch(() => {
            document.getElementById('suratPreview').innerHTML = 
                '<div class="alert alert-danger">Gagal memuat preview surat!</div>';
        });
}
</script>

<?php include '../includes/footer.php'; ?>