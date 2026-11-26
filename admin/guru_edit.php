<?php
// admin/guru_edit.php
include '../includes/config.php';
checkLogin();

$page_title = 'Edit Guru';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$guru = getGuruById($id);

if (!$guru) {
    $_SESSION['toast'] = [
        'message' => 'Data guru tidak ditemukan!',
        'type' => 'danger'
    ];
    header("Location: " . APP_URL . "admin/guru.php");
    exit();
}

// Handle POST untuk update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit') {
    $id = (int)$_POST['id'];
    $old_role = $_POST['old_role'] ?? '';
    $new_role = sanitize($_POST['role']);
    
    $data = [
        'username' => sanitize($_POST['username']),
        'nama_lengkap' => sanitize($_POST['nama_lengkap']),
        'nip' => sanitize($_POST['nip']),
        'role' => $new_role,
        'jabatan' => sanitize($_POST['jabatan']),
        'no_hp' => sanitize($_POST['no_hp'])
    ];
    
    // Handle foto upload
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $foto = uploadFile($_FILES['foto'], APP_PATH . 'uploads/guru/');
        if ($foto) {
            $old = getGuruById($id);
            if ($old && !empty($old['foto'])) {
                $old_file = APP_PATH . 'uploads/guru/' . $old['foto'];
                if (file_exists($old_file)) {
                    unlink($old_file);
                }
            }
            $data['foto'] = $foto;
        }
    }
    
    // Update guru
    if (updateGuru($data, $id)) {
        // ============================================
        // SINKRONISASI ROLE DENGAN KELAS
        // ============================================
        
        // Jika role berubah menjadi walikelas (dari role lain)
        if ($new_role == 'walikelas' && $old_role != 'walikelas') {
            // Cari kelas yang belum punya wali kelas
            $kelas_kosong = getRow("SELECT id, nama_kelas FROM kelas WHERE wali_kelas_id IS NULL LIMIT 1");
            if ($kelas_kosong) {
                // Assign guru sebagai wali kelas
                update('kelas', ['wali_kelas_id' => $id], "id = " . $kelas_kosong['id']);
                $_SESSION['toast'] = [
                    'message' => 'Guru berhasil diupdate dan ditugaskan sebagai wali kelas untuk kelas ' . $kelas_kosong['nama_kelas'],
                    'type' => 'success'
                ];
            } else {
                $_SESSION['toast'] = [
                    'message' => 'Guru berhasil diupdate! (Semua kelas sudah memiliki wali kelas)',
                    'type' => 'success'
                ];
            }
        } 
        // Jika role berubah dari walikelas ke yang lain (guru/bk/admin)
        elseif ($old_role == 'walikelas' && $new_role != 'walikelas') {
            // Hapus dari wali kelas
            update('kelas', ['wali_kelas_id' => NULL], "wali_kelas_id = $id");
            $_SESSION['toast'] = [
                'message' => 'Guru berhasil diupdate dan dihapus dari wali kelas!',
                'type' => 'success'
            ];
        } else {
            $_SESSION['toast'] = [
                'message' => 'Guru berhasil diupdate!',
                'type' => 'success'
            ];
        }
    } else {
        $_SESSION['toast'] = [
            'message' => 'Gagal mengupdate guru!',
            'type' => 'danger'
        ];
    }
    header("Location: " . APP_URL . "admin/guru.php");
    exit();
}

include '../includes/header.php';
?>

<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
        <h4 class="mb-0">
            <i class="fas fa-user-edit text-primary me-2"></i> Edit Guru
        </h4>
        <a href="<?= APP_URL ?>admin/guru.php" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Kembali
        </a>
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

    <!-- Form Edit Guru -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-user me-2"></i> Data Guru
                <span class="badge bg-primary ms-2">ID: <?= $guru['id'] ?></span>
            </h5>
        </div>
        <div class="card-body">
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" value="<?= $guru['id'] ?>">
                <input type="hidden" name="old_role" value="<?= $guru['role'] ?>">
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="username" value="<?= htmlspecialchars($guru['username']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nama_lengkap" value="<?= htmlspecialchars($guru['nama_lengkap']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">NIP</label>
                        <input type="text" class="form-control" name="nip" value="<?= htmlspecialchars($guru['nip'] ?? '') ?>" placeholder="Masukkan NIP">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Role <span class="text-danger">*</span></label>
                        <select class="form-select" name="role" id="roleSelect" required>
                            <option value="guru" <?= $guru['role'] == 'guru' ? 'selected' : '' ?>>Guru</option>
                            <option value="bk" <?= $guru['role'] == 'bk' ? 'selected' : '' ?>>BK</option>
                            <option value="walikelas" <?= $guru['role'] == 'walikelas' ? 'selected' : '' ?>>Wali Kelas</option>
                            <option value="admin" <?= $guru['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                        </select>
                        <small class="text-muted" id="roleInfo">
                            <?php 
                            $is_wali = getRow("SELECT id, nama_kelas FROM kelas WHERE wali_kelas_id = " . $guru['id']);
                            if ($is_wali): 
                            ?>
                                <i class="fas fa-check-circle text-success me-1"></i> 
                                Saat ini menjadi wali kelas untuk: <strong><?= $is_wali['nama_kelas'] ?></strong>
                            <?php else: ?>
                                <i class="fas fa-info-circle text-muted me-1"></i> 
                                Tidak menjadi wali kelas saat ini
                            <?php endif; ?>
                        </small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Jabatan</label>
                        <input type="text" class="form-control" name="jabatan" value="<?= htmlspecialchars($guru['jabatan'] ?? '') ?>" placeholder="Masukkan jabatan">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">No. HP</label>
                        <input type="text" class="form-control" name="no_hp" value="<?= htmlspecialchars($guru['no_hp'] ?? '') ?>" placeholder="No. HP">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Foto</label>
                        <input type="file" class="form-control" name="foto" accept="image/*">
                        <?php if (!empty($guru['foto']) && file_exists(APP_PATH . 'uploads/guru/' . $guru['foto'])): ?>
                            <div class="mt-2">
                                <small class="text-muted">Foto saat ini:</small>
                                <div class="mt-1">
                                    <img src="<?= APP_URL ?>uploads/guru/<?= $guru['foto'] ?>" 
                                         width="80" height="80" 
                                         style="object-fit:cover;border-radius:50%;border:2px solid #e9ecef;">
                                    <span class="badge bg-light text-dark ms-2"><?= $guru['foto'] ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="mt-4 pt-3 border-top">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Update Guru
                    </button>
                    <a href="<?= APP_URL ?>admin/guru.php" class="btn btn-secondary">
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

// Update role info when role changes
document.getElementById('roleSelect').addEventListener('change', function() {
    var roleInfo = document.getElementById('roleInfo');
    if (this.value == 'walikelas') {
        roleInfo.innerHTML = '<i class="fas fa-info-circle text-warning me-1"></i> Akan ditugaskan sebagai wali kelas (otomatis mencari kelas kosong)';
    } else {
        <?php 
        $is_wali = getRow("SELECT id, nama_kelas FROM kelas WHERE wali_kelas_id = " . $guru['id']);
        if ($is_wali): 
        ?>
            if (this.value != 'walikelas') {
                roleInfo.innerHTML = '<i class="fas fa-exclamation-triangle text-danger me-1"></i> <strong>Perhatian!</strong> Role akan diubah, otomatis dihapus dari wali kelas';
            }
        <?php else: ?>
            roleInfo.innerHTML = '<i class="fas fa-info-circle text-muted me-1"></i> Tidak menjadi wali kelas saat ini';
        <?php endif; ?>
    }
});
</script>

<style>
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

/* Responsive */
@media (max-width: 768px) {
    .toast-container .toast {
        min-width: 200px !important;
        max-width: 280px !important;
    }
    
    .toast-container .toast .toast-body {
        font-size: 11px !important;
    }
}

@media (max-width: 576px) {
    .toast-container .toast {
        min-width: 160px !important;
        max-width: 240px !important;
    }
    
    .toast-container .toast .toast-body {
        font-size: 10px !important;
    }
}
</style>

<?php include '../includes/footer.php'; ?>