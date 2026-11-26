<?php
// bk/siswa_detail_bk.php
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

$page_title = 'Detail Siswa';

// Get ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id == 0) {
    $_SESSION['toast'] = [
        'message' => 'ID siswa tidak valid!',
        'type' => 'danger'
    ];
    header("Location: siswa_bermasalah.php");
    exit;
}

// Get data siswa
$siswa = getSiswaById($id);
if (!$siswa) {
    $_SESSION['toast'] = [
        'message' => 'Data siswa tidak ditemukan!',
        'type' => 'danger'
    ];
    header("Location: siswa_bermasalah.php");
    exit;
}

// Get riwayat pelanggaran
$pelanggaran = getPelanggaranBySiswa($id);

// Get catatan konseling
$konseling = getCatatanKonseling($id);

// Get prestasi siswa
$prestasi_siswa = getPrestasiBySiswa($id);

// Hitung statistik
$total_poin = (int)$siswa['poin'];
$total_pelanggaran = count($pelanggaran);
$total_prestasi = count($prestasi_siswa);
$total_poin_prestasi = getTotalPoinPrestasiBySiswa($id);
$total_poin_pelanggaran = getTotalPoinSiswa($id);

// Hitung poin bersih (pelanggaran - prestasi) - PASTIKAN BENAR
$poin_bersih = $total_poin_pelanggaran - $total_poin_prestasi;

// Jika poin bersih < 0, set ke 0
if ($poin_bersih < 0) {
    $poin_bersih = 0;
}

$level = getPoinLevel($total_poin);

// Include header BK
include __DIR__ . '/../includes/header_bk.php';
?>

<div class="container-fluid dashboard-container">
    <!-- Header -->
    <div class="dashboard-header">
        <div>
            <h4 class="dashboard-title">
                <i class="fas fa-user-graduate text-primary me-2"></i> Detail Siswa
            </h4>
            <small class="text-muted dashboard-subtitle">
                <i class="fas fa-info-circle me-1"></i> Informasi lengkap siswa
            </small>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="<?= APP_URL ?>bk/siswa_bermasalah.php" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Kembali
            </a>
            <a href="<?= APP_URL ?>bk/catatan_konseling.php?siswa=<?= $id ?>" class="btn btn-success btn-sm">
                <i class="fas fa-notes-medical me-1"></i> Tambah Konseling
            </a>
            <a href="<?= APP_URL ?>bk/prestasi_siswa.php?siswa_id=<?= $id ?>" class="btn btn-warning btn-sm">
                <i class="fas fa-trophy me-1"></i> Tambah Prestasi
            </a>
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

    <!-- Data Siswa -->
    <div class="row g-3">
        <!-- Foto & Info Singkat -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <?php if (!empty($siswa['foto']) && file_exists(APP_PATH . 'uploads/siswa/' . $siswa['foto'])): ?>
                        <img src="<?= APP_URL ?>uploads/siswa/<?= $siswa['foto'] ?>" 
                             alt="Foto" width="120" height="120" 
                             style="object-fit: cover; border-radius: 50%; border: 4px solid #e9ecef;">
                    <?php else: ?>
                        <div class="avatar-placeholder" style="width:120px;height:120px;border-radius:50%;background:#e9ecef;display:inline-flex;align-items:center;justify-content:center;color:#6c757d;font-weight:600;font-size:48px;margin:0 auto;">
                            <?= strtoupper(substr($siswa['nama_siswa'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    
                    <h5 class="mt-3 mb-1"><?= htmlspecialchars($siswa['nama_siswa']) ?></h5>
                    <p class="text-muted mb-0">NISN: <?= htmlspecialchars($siswa['nisn']) ?></p>
                    <p class="text-muted mb-0">Kelas: <?= htmlspecialchars($siswa['nama_kelas'] ?? 'Belum ditentukan') ?></p>
                    
                    <div class="mt-3">
                        <span class="badge bg-<?= $level['color'] ?> px-3 py-2" style="font-size: 14px;">
                            <i class="fas <?= $level['icon'] ?> me-1"></i> <?= $level['level'] ?> (<?= $total_poin ?> Poin)
                        </span>
                    </div>
                    
                    <div class="mt-3 d-flex justify-content-center gap-3 flex-wrap">
                        <div class="text-center px-2">
                            <h6 class="mb-0 text-danger"><?= $total_pelanggaran ?></h6>
                            <small class="text-muted">Pelanggaran</small>
                        </div>
                        <div class="text-center px-2 border-start">
                            <h6 class="mb-0 text-success"><?= $total_prestasi ?></h6>
                            <small class="text-muted">Prestasi</small>
                        </div>
                        <div class="text-center px-2 border-start">
                            <h6 class="mb-0 text-warning"><?= count($konseling) ?></h6>
                            <small class="text-muted">Konseling</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Informasi Lengkap -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-info-circle text-primary me-2"></i> Informasi Siswa
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="text-muted mb-0" style="font-size: 12px;">NISN</label>
                            <p class="fw-bold mb-0"><?= htmlspecialchars($siswa['nisn']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted mb-0" style="font-size: 12px;">Nama Lengkap</label>
                            <p class="fw-bold mb-0"><?= htmlspecialchars($siswa['nama_siswa']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted mb-0" style="font-size: 12px;">Kelas</label>
                            <p class="fw-bold mb-0"><?= htmlspecialchars($siswa['nama_kelas'] ?? 'Belum ditentukan') ?></p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted mb-0" style="font-size: 12px;">Jenis Kelamin</label>
                            <p class="fw-bold mb-0"><?= getJenisKelaminLabel($siswa['jenis_kelamin']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted mb-0" style="font-size: 12px;">Tempat Lahir</label>
                            <p class="fw-bold mb-0"><?= htmlspecialchars($siswa['tempat_lahir'] ?? '-') ?></p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted mb-0" style="font-size: 12px;">Tanggal Lahir</label>
                            <p class="fw-bold mb-0"><?= formatTanggalIndo($siswa['tanggal_lahir']) ?></p>
                        </div>
                        <div class="col-md-12">
                            <label class="text-muted mb-0" style="font-size: 12px;">Alamat</label>
                            <p class="fw-bold mb-0"><?= htmlspecialchars($siswa['alamat'] ?? '-') ?></p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted mb-0" style="font-size: 12px;">No. HP</label>
                            <p class="fw-bold mb-0"><?= htmlspecialchars($siswa['no_hp'] ?? '-') ?></p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted mb-0" style="font-size: 12px;">Total Poin</label>
                            <p class="fw-bold mb-0 text-danger"><?= $total_poin ?> Poin</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistik Poin -->
    <div class="row g-2 mt-2">
        <div class="col-12 col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-1">Total Poin Pelanggaran</h6>
                    <h3 class="text-danger mb-0"><?= $total_poin_pelanggaran ?></h3>
                    <small class="text-muted">Dari <?= $total_pelanggaran ?> pelanggaran</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-1">Total Poin Prestasi</h6>
                    <h3 class="text-success mb-0">- <?= $total_poin_prestasi ?></h3>
                    <small class="text-muted">Dari <?= $total_prestasi ?> prestasi</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card <?= $poin_bersih >= 100 ? 'border-danger' : 'border-success' ?>">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-1">Poin Bersih (Akhir)</h6>
                    <h3 class="<?= $poin_bersih >= 100 ? 'text-danger' : 'text-success' ?> mb-0"><?= $poin_bersih ?></h3>
                    <small class="text-muted">
                        <?= $total_poin_pelanggaran ?> - <?= $total_poin_prestasi ?> = <strong><?= $poin_bersih ?></strong>
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Riwayat Pelanggaran -->
    <div class="card mt-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="fas fa-history text-warning me-2"></i> Riwayat Pelanggaran
                <span class="badge bg-secondary ms-2"><?= count($pelanggaran) ?> Pelanggaran</span>
                <span class="badge bg-danger ms-2">Total Poin: <?= $total_poin_pelanggaran ?></span>
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="50">No</th>
                            <th>Tanggal</th>
                            <th>Pelanggaran</th>
                            <th>Poin</th>
                            <th>Guru</th>
                            <th>Status</th>
                            <th class="text-center">Bukti</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($pelanggaran)): ?>
                            <?php $no = 1; foreach ($pelanggaran as $p): ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?></td>
                                    <td><?= formatTanggalIndo($p['tanggal_pelanggaran']) ?></td>
                                    <td><?= htmlspecialchars($p['nama_pelanggaran']) ?></td>
                                    <td><span class="badge bg-danger"><?= $p['poin'] ?></span></td>
                                    <td><?= htmlspecialchars($p['guru'] ?? '-') ?></td>
                                    <td>
                                        <span class="badge bg-<?= getStatusBadge($p['status']) ?>">
                                            <?= getStatusLabel($p['status']) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php if (!empty($p['bukti']) && file_exists(APP_PATH . 'uploads/bukti_pelanggaran/' . $p['bukti'])): ?>
                                            <a href="<?= APP_URL ?>uploads/bukti_pelanggaran/<?= $p['bukti'] ?>" 
                                               target="_blank" class="btn btn-outline-success btn-sm" title="Lihat Bukti">
                                                <i class="fas fa-camera"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="table-danger fw-bold">
                                <td colspan="3" class="text-end">Total</td>
                                <td><?= $total_poin_pelanggaran ?></td>
                                <td colspan="3"></td>
                            </tr>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="fas fa-check-circle fa-2x mb-2 d-block text-success opacity-50"></i>
                                    Siswa ini belum memiliki riwayat pelanggaran
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Riwayat Prestasi -->
    <div class="card mt-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="fas fa-trophy text-success me-2"></i> Riwayat Prestasi
                <span class="badge bg-secondary ms-2"><?= count($prestasi_siswa) ?> Prestasi</span>
                <span class="badge bg-success ms-2">Total Poin: -<?= $total_poin_prestasi ?></span>
            </h5>
            <a href="<?= APP_URL ?>bk/prestasi_siswa.php?siswa_id=<?= $id ?>" class="btn btn-warning btn-sm">
                <i class="fas fa-plus me-1"></i> Tambah
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="50">No</th>
                            <th>No. Prestasi</th>
                            <th>Prestasi</th>
                            <th>Juara</th>
                            <th>Tingkat</th>
                            <th>Tahun</th>
                            <th>Poin</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($prestasi_siswa)): ?>
                            <?php $no = 1; foreach ($prestasi_siswa as $p): ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?></td>
                                    <td><span class="badge bg-primary"><?= $p['no_prestasi'] ?? 'PRS' ?></span></td>
                                    <td><?= htmlspecialchars($p['nama_prestasi']) ?></td>
                                    <td><?= htmlspecialchars($p['juara'] ?? '-') ?></td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $p['tingkat'] == 'internasional' ? 'danger' : 
                                            ($p['tingkat'] == 'nasional' ? 'warning' : 
                                            ($p['tingkat'] == 'provinsi' ? 'info' : 
                                            ($p['tingkat'] == 'kota' ? 'success' : 
                                            ($p['tingkat'] == 'kecamatan' ? 'primary' : 'secondary')))) ?>">
                                            <?= ucfirst($p['tingkat']) ?>
                                        </span>
                                    </td>
                                    <td><?= $p['tahun'] ?></td>
                                    <td><span class="badge bg-success">-<?= $p['poin_prestasi'] ?></span></td>
                                    <td class="text-center">
                                        <a href="<?= APP_URL ?>bk/prestasi_siswa.php?edit=<?= $p['id'] ?>" 
                                           class="btn btn-outline-primary btn-sm" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?= APP_URL ?>bk/prestasi_siswa.php?delete=<?= $p['id'] ?>" 
                                           class="btn btn-outline-danger btn-sm" 
                                           onclick="return confirm('Yakin hapus prestasi ini?')" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="table-success fw-bold">
                                <td colspan="6" class="text-end">Total Pengurangan Poin</td>
                                <td>-<?= $total_poin_prestasi ?></td>
                                <td></td>
                            </tr>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    <i class="fas fa-trophy fa-2x mb-2 d-block text-warning opacity-50"></i>
                                    Siswa ini belum memiliki prestasi
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Catatan Konseling -->
    <div class="card mt-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="fas fa-notes-medical text-success me-2"></i> Catatan Konseling
                <span class="badge bg-secondary ms-2"><?= count($konseling) ?> Catatan</span>
            </h5>
            <a href="<?= APP_URL ?>bk/catatan_konseling.php?siswa=<?= $id ?>" class="btn btn-success btn-sm">
                <i class="fas fa-plus me-1"></i> Tambah
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="50">No</th>
                            <th>Tanggal</th>
                            <th>Topik</th>
                            <th>Catatan</th>
                            <th>Status</th>
                            <th class="text-center" width="100">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($konseling)): ?>
                            <?php $no = 1; foreach ($konseling as $k): ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?></td>
                                    <td><?= formatTanggalIndo($k['tanggal']) ?></td>
                                    <td><?= htmlspecialchars($k['topik'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars(substr($k['catatan'] ?? '', 0, 50)) ?>...</td>
                                    <td>
                                        <?php 
                                        $badge = $k['status'] == 'selesai' ? 'success' : ($k['status'] == 'proses' ? 'warning' : 'secondary');
                                        ?>
                                        <span class="badge bg-<?= $badge ?>">
                                            <?= ucfirst($k['status'] ?? 'pending') ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?= APP_URL ?>bk/catatan_konseling_detail.php?id=<?= $k['id'] ?>" 
                                           class="btn btn-outline-info btn-sm" title="Detail">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="<?= APP_URL ?>bk/catatan_konseling_edit.php?id=<?= $k['id'] ?>" 
                                           class="btn btn-outline-warning btn-sm" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="fas fa-notes-medical fa-2x mb-2 d-block text-success opacity-50"></i>
                                    Belum ada catatan konseling untuk siswa ini
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
   STYLE DETAIL SISWA BK
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
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    color: #495057;
    border-bottom: 2px solid #dee2e6;
}
.table tbody tr:hover {
    background-color: rgba(108, 99, 255, 0.04);
}
.table tbody td {
    font-size: 12px;
}

/* Badge */
.badge {
    font-size: 9px !important;
    padding: 2px 6px !important;
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

/* Buttons */
.btn-sm {
    padding: 4px 10px !important;
    font-size: 11px !important;
    border-radius: 6px !important;
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
    .card-header {
        padding: 8px 10px;
        font-size: 11px;
    }
    .card-body {
        padding: 8px 10px;
    }
    .table thead th {
        font-size: 8px;
        padding: 3px 4px;
    }
    .table tbody td {
        font-size: 10px;
        padding: 3px 4px;
    }
    .badge {
        font-size: 7px !important;
        padding: 1px 4px !important;
    }
    .btn-sm {
        padding: 2px 6px !important;
        font-size: 9px !important;
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
    .card-body {
        padding: 4px 6px;
    }
    .card-header {
        padding: 4px 6px;
        font-size: 9px;
    }
    .table thead th {
        font-size: 7px;
        padding: 2px 3px;
    }
    .table tbody td {
        font-size: 9px;
        padding: 2px 3px;
    }
    .badge {
        font-size: 6px !important;
        padding: 1px 3px !important;
    }
    .btn-sm {
        padding: 1px 4px !important;
        font-size: 8px !important;
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

<?php include '../includes/footer.php'; ?>