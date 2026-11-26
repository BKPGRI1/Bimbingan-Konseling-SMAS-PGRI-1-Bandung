<?php
// admin/kelas_detail.php
include '../includes/config.php';
checkLogin();

$page_title = 'Detail Kelas';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$kelas = getKelasById($id);
$siswa_list = getSiswaByKelas($id);

if (!$kelas) {
    $_SESSION['toast'] = [
        'message' => 'Data kelas tidak ditemukan!',
        'type' => 'danger'
    ];
    header("Location: " . APP_URL . "admin/kelas.php");
    exit();
}

include '../includes/header.php';
?>

<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
        <h4 class="mb-0">
            <i class="fas fa-building text-primary me-2"></i> Detail Kelas
        </h4>
        <div>
            <a href="<?= APP_URL ?>admin/kelas.php" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Kembali
            </a>

            </a>
        </div>
    </div>

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

    <!-- Informasi Kelas -->
    <div class="card mb-3">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-info-circle text-primary me-2"></i> Informasi Kelas
                <span class="badge bg-primary ms-2">ID: <?= $kelas['id'] ?></span>
            </h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3 col-6">
                    <label class="text-muted mb-0" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.3px;">Nama Kelas</label>
                    <p class="fw-bold mb-0" style="font-size: 16px;"><?= htmlspecialchars($kelas['nama_kelas']) ?></p>
                </div>
                <div class="col-md-3 col-6">
                    <label class="text-muted mb-0" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.3px;">Tingkat</label>
                    <p class="fw-bold mb-0" style="font-size: 16px;">
                        <span class="badge bg-secondary"><?= htmlspecialchars($kelas['tingkat'] ?? '-') ?></span>
                    </p>
                </div>
                <div class="col-md-3 col-6">
                    <label class="text-muted mb-0" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.3px;">Jurusan</label>
                    <p class="fw-bold mb-0" style="font-size: 16px;"><?= htmlspecialchars($kelas['jurusan'] ?? '-') ?></p>
                </div>
                <div class="col-md-3 col-6">
                    <label class="text-muted mb-0" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.3px;">Jumlah Siswa</label>
                    <p class="fw-bold mb-0" style="font-size: 16px; color: var(--primary);">
                        <i class="fas fa-users me-1"></i> <?= count($siswa_list) ?> Siswa
                    </p>
                </div>
                <div class="col-md-6">
                    <label class="text-muted mb-0" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.3px;">Wali Kelas</label>
                    <p class="fw-bold mb-0" style="font-size: 16px;">
                        <?php if (!empty($kelas['wali_kelas'])): ?>
                            <i class="fas fa-user-check text-success me-1"></i>
                            <?= htmlspecialchars($kelas['wali_kelas']) ?>
                        <?php else: ?>
                            <span class="text-muted">Belum ditentukan</span>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="col-md-6">
                    <label class="text-muted mb-0" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.3px;">Tahun Ajaran</label>
                    <p class="fw-bold mb-0" style="font-size: 16px;"><?= htmlspecialchars($kelas['tahun_ajaran'] ?? '-') ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistik Singkat -->
    <div class="row g-3 mb-3">
        <div class="col-md-3 col-6">
            <div class="card bg-primary text-white">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-0" style="font-size: 10px; text-transform: uppercase;">Total Siswa</h6>
                            <h3 class="mb-0"><?= count($siswa_list) ?></h3>
                        </div>
                        <div>
                            <i class="fas fa-users fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card bg-success text-white">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-0" style="font-size: 10px; text-transform: uppercase;">Laki-laki</h6>
                            <h3 class="mb-0">
                                <?php 
                                $l = 0;
                                foreach ($siswa_list as $s) {
                                    if ($s['jenis_kelamin'] == 'L') $l++;
                                }
                                echo $l;
                                ?>
                            </h3>
                        </div>
                        <div>
                            <i class="fas fa-male fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card bg-info text-white">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-0" style="font-size: 10px; text-transform: uppercase;">Perempuan</h6>
                            <h3 class="mb-0">
                                <?php 
                                $p = 0;
                                foreach ($siswa_list as $s) {
                                    if ($s['jenis_kelamin'] == 'P') $p++;
                                }
                                echo $p;
                                ?>
                            </h3>
                        </div>
                        <div>
                            <i class="fas fa-female fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card bg-warning text-white">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-0" style="font-size: 10px; text-transform: uppercase;">Total Poin</h6>
                            <h3 class="mb-0">
                                <?php 
                                $total_poin = 0;
                                foreach ($siswa_list as $s) {
                                    $total_poin += (int)$s['poin'];
                                }
                                echo number_format($total_poin);
                                ?>
                            </h3>
                        </div>
                        <div>
                            <i class="fas fa-star fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Daftar Siswa -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="fas fa-users text-primary me-2"></i> Daftar Siswa
                <span class="badge bg-secondary ms-2"><?= count($siswa_list) ?> Siswa</span>
            </h5>
            <?php if (!empty($siswa_list)): ?>
                <span class="text-muted" style="font-size: 12px;">
                    <i class="fas fa-print me-1"></i> Cetak: 
                    <a href="#" class="text-primary" onclick="window.print()">PDF</a> | 
                    <a href="#" class="text-success">Excel</a>
                </span>
            <?php endif; ?>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0" id="tableSiswa">
                    <thead class="table-light">
                        <tr>
                            <th width="45" class="text-center">No</th>
                            <th width="45" class="text-center">Foto</th>
                            <th>NISN</th>
                            <th>Nama Siswa</th>
                            <th class="text-center">JK</th>
                            <th class="text-center">Poin</th>
                            <th class="text-center" width="100">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($siswa_list)): ?>
                            <?php $no = 1; foreach ($siswa_list as $siswa): ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?></td>
                                    <td class="text-center">
                                        <?php if (!empty($siswa['foto']) && file_exists(APP_PATH . 'uploads/siswa/' . $siswa['foto'])): ?>
                                            <img src="<?= APP_URL ?>uploads/siswa/<?= $siswa['foto'] ?>" 
                                                 alt="Foto" width="30" height="30" 
                                                 style="object-fit: cover; border-radius: 50%;">
                                        <?php else: ?>
                                            <div class="avatar-placeholder" style="width:30px;height:30px;border-radius:50%;background:#e9ecef;display:inline-flex;align-items:center;justify-content:center;color:#6c757d;font-weight:600;font-size:11px;">
                                                <?= strtoupper(substr($siswa['nama_siswa'], 0, 1)) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge bg-light text-dark" style="font-size: 10px;"><?= htmlspecialchars($siswa['nisn'] ?? '-') ?></span></td>
                                    <td><strong><?= htmlspecialchars($siswa['nama_siswa']) ?></strong></td>
                                    <td class="text-center"><?= $siswa['jenis_kelamin'] == 'L' ? 'L' : 'P' ?></td>
                                    <td class="text-center">
                                        <?php 
                                        $poin = (int)$siswa['poin'];
                                        $badge = $poin >= 300 ? 'danger' : ($poin >= 200 ? 'warning' : ($poin >= 100 ? 'info' : 'success'));
                                        ?>
                                        <span class="badge bg-<?= $badge ?>" style="font-size: 10px;"><?= $poin ?></span>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?= APP_URL ?>admin/siswa_detail.php?id=<?= $siswa['id'] ?>" 
                                           class="btn btn-outline-info btn-sm" 
                                           title="Detail Siswa" 
                                           style="padding: 1px 5px; font-size: 10px;">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="fas fa-user-slash fa-2x mb-2 d-block opacity-25"></i>
                                    <p class="mb-0">Belum ada siswa di kelas ini</p>
                                    <small class="text-muted">Tambahkan siswa melalui menu Manajemen Siswa</small>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if (!empty($siswa_list)): ?>
            <div class="card-footer bg-transparent text-muted" style="font-size: 12px;">
                <i class="fas fa-info-circle me-1"></i>
                Menampilkan <?= count($siswa_list) ?> siswa
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Auto close toast setelah 3 detik
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

/* Toast Kecil */
.toast-container .toast {
    border-radius: 8px !important;
    box-shadow: 0 4px 20px rgba(0,0,0,0.12) !important;
}

.toast-container .toast .toast-body {
    font-size: 13px !important;
    font-weight: 500;
    padding: 2px 0 !important;
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
    font-size: 10px !important;
    padding: 3px 8px !important;
}

/* Button */
.btn-group-sm .btn,
.btn-sm {
    padding: 2px 6px !important;
    font-size: 11px !important;
}

/* Card Stats */
.card .card-body .h6 {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
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
    
    .btn-sm {
        padding: 1px 4px !important;
        font-size: 9px !important;
    }
    
    .badge {
        font-size: 8px !important;
        padding: 2px 5px !important;
    }
    
    .toast-container .toast {
        min-width: 200px !important;
        max-width: 280px !important;
    }
    
    .toast-container .toast .toast-body {
        font-size: 11px !important;
    }
    
    .card .card-body .h3 {
        font-size: 20px;
    }
}

@media (max-width: 576px) {
    .table thead th {
        font-size: 8px;
        padding: 3px 4px;
    }
    
    .table tbody td {
        font-size: 9px;
        padding: 3px 4px;
    }
    
    .toast-container .toast {
        min-width: 160px !important;
        max-width: 240px !important;
    }
    
    .toast-container .toast .toast-body {
        font-size: 10px !important;
    }
    
    .card .card-body .h3 {
        font-size: 16px;
    }
}

/* Print Styles */
@media print {
    .btn,
    .btn-group,
    .toast-container {
        display: none !important;
    }
    
    .card {
        border: 1px solid #dee2e6 !important;
        box-shadow: none !important;
    }
    
    .table-bordered {
        border: 1px solid #dee2e6 !important;
    }
    
    .table-bordered th,
    .table-bordered td {
        border: 1px solid #dee2e6 !important;
    }
}
</style>

<?php include '../includes/footer.php'; ?>