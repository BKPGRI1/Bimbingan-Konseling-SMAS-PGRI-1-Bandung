<?php
// walikelas/siswa_detail_walikelas.php
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

$page_title = 'Detail Siswa';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$siswa = getSiswaById($id);

if (!$siswa) {
    $_SESSION['toast'] = [
        'message' => 'Data siswa tidak ditemukan!',
        'type' => 'danger'
    ];
    header("Location: " . APP_URL . "walikelas/data_siswa_kelas.php");
    exit();
}

// Cek apakah siswa berada di kelas wali
$user = getCurrentUser();
$kelas_wali = getRow("SELECT id FROM kelas WHERE wali_kelas_id = " . $user['id']);
if ($kelas_wali && $siswa['kelas_id'] != $kelas_wali['id']) {
    $_SESSION['toast'] = [
        'message' => 'Anda tidak memiliki akses ke data siswa ini!',
        'type' => 'danger'
    ];
    header("Location: " . APP_URL . "walikelas/data_siswa_kelas.php");
    exit();
}

$pelanggaran = getPelanggaranBySiswa($id);

include '../includes/header.php';
include '../includes/sidebar_walikelas.php';
?>

<div class="container-fluid px-1 px-sm-2 px-md-3">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-start align-items-sm-center mb-1 mb-sm-2">
        <div>
            <h5 class="mb-0" style="font-size: clamp(0.85rem, 2vw, 1.2rem);">
                <i class="fas fa-user-graduate text-primary me-1"></i> Detail Siswa
            </h5>
            <small class="text-muted" style="font-size: clamp(0.55rem, 1.2vw, 0.75rem);">
                <i class="fas fa-id-card me-1"></i> NISN: <strong><?= htmlspecialchars($siswa['nisn']) ?></strong>
            </small>
        </div>
        <div>
            <a href="<?= APP_URL ?>walikelas/data_siswa_kelas.php" class="btn btn-secondary btn-sm" style="font-size: clamp(0.5rem, 1vw, 0.75rem); padding: 2px 8px;">
                <i class="fas fa-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    <!-- Data Siswa -->
    <div class="row g-1 g-sm-2">
        <!-- Profile Card -->
        <div class="col-12 col-md-4">
            <div class="card">
                <div class="card-body text-center p-2 p-sm-3">
                    <?php if (!empty($siswa['foto']) && file_exists(APP_PATH . 'uploads/siswa/' . $siswa['foto'])): ?>
                        <img src="<?= APP_URL ?>uploads/siswa/<?= $siswa['foto'] ?>" 
                             alt="Foto" width="100" height="100" 
                             style="object-fit: cover; border-radius: 50%; border: 3px solid #e9ecef;">
                    <?php else: ?>
                        <div class="avatar-placeholder" style="width:100px;height:100px;border-radius:50%;background:#e9ecef;display:inline-flex;align-items:center;justify-content:center;color:#6c757d;font-weight:600;font-size:40px;">
                            <?= strtoupper(substr($siswa['nama_siswa'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    
                    <h5 class="mt-2 mb-0" style="font-size: clamp(0.8rem, 1.5vw, 1rem);"><?= htmlspecialchars($siswa['nama_siswa']) ?></h5>
                    <p class="text-muted mb-0" style="font-size: clamp(0.5rem, 0.8vw, 0.65rem);"><?= htmlspecialchars($siswa['nisn']) ?></p>
                    
                    <?php 
                    $poin = (int)$siswa['poin'];
                    $level = getPoinLevel($poin);
                    ?>
                    <div class="mt-2">
                        <span class="badge bg-<?= $level['color'] ?> px-2 py-1" style="font-size: clamp(0.5rem, 0.8vw, 0.7rem);">
                            <i class="fas <?= $level['icon'] ?> me-1"></i> <?= $level['level'] ?> (<?= $poin ?> Poin)
                        </span>
                    </div>
                    <div class="mt-2">
                        <small class="text-muted" style="font-size: clamp(0.4rem, 0.6vw, 0.55rem);">
                            <i class="fas fa-school me-1"></i> Kelas: <?= htmlspecialchars($siswa['nama_kelas'] ?? 'Belum ditentukan') ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Informasi Lengkap -->
        <div class="col-12 col-md-8">
            <div class="card">
                <div class="card-header p-1 p-sm-2">
                    <h6 class="card-title mb-0" style="font-size: clamp(0.6rem, 1.2vw, 0.8rem);">
                        <i class="fas fa-info-circle text-primary me-1"></i> Informasi Siswa
                    </h6>
                </div>
                <div class="card-body p-2 p-sm-3">
                    <div class="row g-1 g-sm-2">
                        <div class="col-6">
                            <label class="text-muted mb-0" style="font-size: clamp(0.4rem, 0.7vw, 0.55rem);">NISN</label>
                            <p class="fw-bold mb-0" style="font-size: clamp(0.5rem, 0.9vw, 0.7rem);"><?= htmlspecialchars($siswa['nisn']) ?></p>
                        </div>
                        <div class="col-6">
                            <label class="text-muted mb-0" style="font-size: clamp(0.4rem, 0.7vw, 0.55rem);">Nama Lengkap</label>
                            <p class="fw-bold mb-0" style="font-size: clamp(0.5rem, 0.9vw, 0.7rem);"><?= htmlspecialchars($siswa['nama_siswa']) ?></p>
                        </div>
                        <div class="col-6">
                            <label class="text-muted mb-0" style="font-size: clamp(0.4rem, 0.7vw, 0.55rem);">Kelas</label>
                            <p class="fw-bold mb-0" style="font-size: clamp(0.5rem, 0.9vw, 0.7rem);"><?= htmlspecialchars($siswa['nama_kelas'] ?? 'Belum ditentukan') ?></p>
                        </div>
                        <div class="col-6">
                            <label class="text-muted mb-0" style="font-size: clamp(0.4rem, 0.7vw, 0.55rem);">Jenis Kelamin</label>
                            <p class="fw-bold mb-0" style="font-size: clamp(0.5rem, 0.9vw, 0.7rem);"><?= getJenisKelaminLabel($siswa['jenis_kelamin']) ?></p>
                        </div>
                        <div class="col-6">
                            <label class="text-muted mb-0" style="font-size: clamp(0.4rem, 0.7vw, 0.55rem);">Tempat Lahir</label>
                            <p class="fw-bold mb-0" style="font-size: clamp(0.5rem, 0.9vw, 0.7rem);"><?= htmlspecialchars($siswa['tempat_lahir'] ?? '-') ?></p>
                        </div>
                        <div class="col-6">
                            <label class="text-muted mb-0" style="font-size: clamp(0.4rem, 0.7vw, 0.55rem);">Tanggal Lahir</label>
                            <p class="fw-bold mb-0" style="font-size: clamp(0.5rem, 0.9vw, 0.7rem);"><?= formatTanggalIndo($siswa['tanggal_lahir']) ?></p>
                        </div>
                        <div class="col-12">
                            <label class="text-muted mb-0" style="font-size: clamp(0.4rem, 0.7vw, 0.55rem);">Alamat</label>
                            <p class="fw-bold mb-0" style="font-size: clamp(0.5rem, 0.9vw, 0.7rem);"><?= htmlspecialchars($siswa['alamat'] ?? '-') ?></p>
                        </div>
                        <div class="col-6">
                            <label class="text-muted mb-0" style="font-size: clamp(0.4rem, 0.7vw, 0.55rem);">No. HP</label>
                            <p class="fw-bold mb-0" style="font-size: clamp(0.5rem, 0.9vw, 0.7rem);"><?= htmlspecialchars($siswa['no_hp'] ?? '-') ?></p>
                        </div>
                        <div class="col-6">
                            <label class="text-muted mb-0" style="font-size: clamp(0.4rem, 0.7vw, 0.55rem);">Total Poin</label>
                            <p class="fw-bold mb-0 text-danger" style="font-size: clamp(0.5rem, 0.9vw, 0.7rem);"><?= $poin ?> Poin</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Riwayat Pelanggaran -->
    <div class="card mt-1 mt-sm-2">
        <div class="card-header d-flex justify-content-between align-items-center p-1 p-sm-2">
            <h6 class="card-title mb-0" style="font-size: clamp(0.6rem, 1.2vw, 0.8rem);">
                <i class="fas fa-history text-warning me-1"></i> Riwayat Pelanggaran
                <span class="badge bg-secondary ms-1" style="font-size: clamp(0.4rem, 0.7vw, 0.55rem);"><?= count($pelanggaran) ?></span>
            </h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0" style="font-size: clamp(0.45rem, 0.9vw, 0.7rem);">
                    <thead class="table-light">
                        <tr>
                            <th style="font-size: clamp(0.35rem, 0.7vw, 0.55rem); padding: 4px 6px; text-align: center; width: 35px;">No</th>
                            <th style="font-size: clamp(0.35rem, 0.7vw, 0.55rem); padding: 4px 6px;">Tanggal</th>
                            <th style="font-size: clamp(0.35rem, 0.7vw, 0.55rem); padding: 4px 6px; display: none;">Pelanggaran</th>
                            <th style="font-size: clamp(0.35rem, 0.7vw, 0.55rem); padding: 4px 6px; text-align: center;">Poin</th>
                            <th style="font-size: clamp(0.35rem, 0.7vw, 0.55rem); padding: 4px 6px; display: none;">Guru</th>
                            <th style="font-size: clamp(0.35rem, 0.7vw, 0.55rem); padding: 4px 6px; text-align: center;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($pelanggaran)): ?>
                            <?php $no = 1; foreach ($pelanggaran as $p): ?>
                                <tr>
                                    <td style="padding: 4px 6px; text-align: center; font-size: clamp(0.4rem, 0.8vw, 0.6rem);"><?= $no++ ?></td>
                                    <td style="padding: 4px 6px; font-size: clamp(0.4rem, 0.8vw, 0.6rem);"><?= formatTanggalIndo($p['tanggal_pelanggaran']) ?></td>
                                    <td style="padding: 4px 6px; display: none; font-size: clamp(0.4rem, 0.8vw, 0.6rem);"><?= htmlspecialchars($p['nama_pelanggaran']) ?></td>
                                    <td style="padding: 4px 6px; text-align: center;">
                                        <span class="badge bg-danger" style="font-size: clamp(0.35rem, 0.6vw, 0.5rem);"><?= $p['poin'] ?></span>
                                    </td>
                                    <td style="padding: 4px 6px; display: none; font-size: clamp(0.4rem, 0.8vw, 0.6rem);"><?= htmlspecialchars($p['guru'] ?? '-') ?></td>
                                    <td style="padding: 4px 6px; text-align: center;">
                                        <span class="badge bg-<?= getStatusBadge($p['status']) ?>" style="font-size: clamp(0.35rem, 0.6vw, 0.5rem);">
                                            <?= getStatusLabel($p['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-2" style="font-size: clamp(0.5rem, 1vw, 0.7rem);">
                                    <i class="fas fa-check-circle text-success opacity-50 me-1"></i>
                                    Siswa ini belum memiliki riwayat pelanggaran
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
/* Avatar */
.avatar-placeholder {
    background: #e9ecef;
    color: #6c757d;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

/* Mobile */
@media (max-width: 576px) {
    .table th,
    .table td {
        padding: 2px 4px !important;
    }
    
    .table th:nth-child(3),
    .table td:nth-child(3),
    .table th:nth-child(5),
    .table td:nth-child(5) {
        display: none !important;
    }
    
    .card-body {
        padding: 4px 6px !important;
    }
    
    .container-fluid {
        padding-left: 4px !important;
        padding-right: 4px !important;
    }
    
    .row {
        margin-left: -2px !important;
        margin-right: -2px !important;
    }
}

@media (max-width: 400px) {
    .table th,
    .table td {
        padding: 1px 2px !important;
    }
}
</style>

<?php include '../includes/footer.php'; ?>