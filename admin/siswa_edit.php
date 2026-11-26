<?php
// admin/siswa_edit.php
include '../includes/config.php';
checkLogin();

$page_title = 'Edit Siswa';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$siswa = getSiswaById($id);
$kelas_list = getKelasOptions();

if (!$siswa) {
    $_SESSION['toast'] = [
        'message' => 'Data siswa tidak ditemukan!',
        'type' => 'danger'
    ];
    header("Location: " . APP_URL . "admin/siswa.php");
    exit();
}

include '../includes/header.php';
?>

<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
        <h4 class="mb-0">
            <i class="fas fa-user-edit text-primary me-2"></i> Edit Siswa
        </h4>
        <a href="<?= APP_URL ?>admin/siswa.php" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Kembali
        </a>
    </div>

    <!-- Toast Notification -->
    <?php if (isset($_SESSION['toast'])): ?>
        <div id="toastNotification" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;">
            <div class="toast align-items-center text-white bg-<?= $_SESSION['toast']['type'] ?> border-0 show" role="alert" aria-live="assertive" aria-atomic="true" style="min-width: 300px; max-width: 450px; border-radius: 10px; box-shadow: 0 8px 30px rgba(0,0,0,0.15);">
                <div class="d-flex align-items-center p-2">
                    <div class="flex-shrink-0 ms-2">
                        <i class="fas fa-<?= $_SESSION['toast']['type'] == 'success' ? 'check-circle' : ($_SESSION['toast']['type'] == 'warning' ? 'exclamation-triangle' : 'times-circle') ?> fa-lg"></i>
                    </div>
                    <div class="toast-body flex-grow-1 text-start ms-2" style="font-size: 14px; font-weight: 500;">
                        <?= $_SESSION['toast']['message'] ?>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-1" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        </div>
        <?php unset($_SESSION['toast']); ?>
    <?php endif; ?>

    <!-- Form Edit Siswa -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-user me-2"></i> Data Siswa
                <span class="badge bg-primary ms-2">ID: <?= $siswa['id'] ?></span>
            </h5>
        </div>
        <div class="card-body">
            <form method="POST" action="siswa.php" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" value="<?= $siswa['id'] ?>">
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">NISN <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nisn" value="<?= htmlspecialchars($siswa['nisn']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nama Siswa <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nama_siswa" value="<?= htmlspecialchars($siswa['nama_siswa']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Kelas</label>
                        <select class="form-select" name="kelas_id">
                            <option value="">Pilih Kelas</option>
                            <?php foreach ($kelas_list as $kelas): ?>
                                <option value="<?= $kelas['id'] ?>" <?= $siswa['kelas_id'] == $kelas['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($kelas['nama_kelas']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Jenis Kelamin <span class="text-danger">*</span></label>
                        <select class="form-select" name="jenis_kelamin" required>
                            <option value="L" <?= $siswa['jenis_kelamin'] == 'L' ? 'selected' : '' ?>>Laki-laki</option>
                            <option value="P" <?= $siswa['jenis_kelamin'] == 'P' ? 'selected' : '' ?>>Perempuan</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tempat Lahir</label>
                        <input type="text" class="form-control" name="tempat_lahir" value="<?= htmlspecialchars($siswa['tempat_lahir'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tanggal Lahir</label>
                        <input type="date" class="form-control" name="tanggal_lahir" value="<?= $siswa['tanggal_lahir'] ?? '' ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Alamat</label>
                        <textarea class="form-control" name="alamat" rows="3" placeholder="Masukkan alamat lengkap"><?= htmlspecialchars($siswa['alamat'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">No. HP <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="no_hp" value="<?= htmlspecialchars($siswa['no_hp'] ?? '') ?>" placeholder="081234567890" required>
                        <small class="text-muted">Nomor HP untuk notifikasi WhatsApp</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Foto</label>
                        <input type="file" class="form-control" name="foto" accept="image/*">
                        <?php if (!empty($siswa['foto']) && file_exists(APP_PATH . 'uploads/siswa/' . $siswa['foto'])): ?>
                            <div class="mt-2">
                                <small class="text-muted">Foto saat ini:</small>
                                <div class="mt-1">
                                    <img src="<?= APP_URL ?>uploads/siswa/<?= $siswa['foto'] ?>" 
                                         width="80" height="80" 
                                         style="object-fit:cover;border-radius:50%;border:2px solid #e9ecef;">
                                    <span class="badge bg-light text-dark ms-2"><?= $siswa['foto'] ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="mt-4 pt-3 border-top">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Update Siswa
                    </button>
                    <a href="<?= APP_URL ?>admin/siswa.php" class="btn btn-secondary">
                        <i class="fas fa-times me-1"></i> Batal
                    </a>
                </div>
            </form>
        </div>
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
/* Toast Minimalis */
.toast-container .toast {
    border-radius: 10px !important;
    box-shadow: 0 8px 30px rgba(0,0,0,0.15) !important;
}

.toast-container .toast .toast-body {
    font-size: 14px;
    font-weight: 500;
}

/* Responsive */
@media (max-width: 768px) {
    .toast-container .toast {
        min-width: 250px !important;
        max-width: 350px !important;
    }
}

@media (max-width: 576px) {
    .toast-container .toast {
        min-width: 200px !important;
        max-width: 300px !important;
    }
}
</style>

<?php include '../includes/footer.php'; ?>