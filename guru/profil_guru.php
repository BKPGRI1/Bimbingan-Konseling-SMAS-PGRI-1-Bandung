<?php
// guru/profil_guru.php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Cek login
if (!isLoggedIn()) {
    header("Location: ../login.php");
    exit;
}

// Cek role
if (!hasRole(['admin', 'guru', 'bk', 'walikelas'])) {
    header("Location: ../dashboard.php");
    exit;
}

$page_title = 'Profil Guru';

// Ambil data user
$user = getCurrentUser();
if (!$user) {
    header("Location: ../logout.php");
    exit;
}

// Proses update profil
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        // Update Profil
        if ($_POST['action'] == 'update_profile') {
            $nama_lengkap = isset($_POST['nama_lengkap']) ? cleanString($_POST['nama_lengkap']) : '';
            $jabatan = isset($_POST['jabatan']) ? cleanString($_POST['jabatan']) : '';
            $no_hp = isset($_POST['no_hp']) ? cleanString($_POST['no_hp']) : '';
            $nip = isset($_POST['nip']) ? cleanString($_POST['nip']) : '';
            
            // Validasi
            if (empty($nama_lengkap)) {
                $_SESSION['toast'] = [
                    'message' => 'Nama lengkap tidak boleh kosong!',
                    'type' => 'danger'
                ];
            } else {
                $data = [
                    'nama_lengkap' => $nama_lengkap,
                    'jabatan' => $jabatan,
                    'no_hp' => $no_hp,
                    'nip' => $nip
                ];
                
                // Upload foto
                if (isset($_FILES['foto']) && $_FILES['foto']['error'] == UPLOAD_ERR_OK) {
                    $upload_dir = APP_PATH . 'uploads/guru/';
                    $foto_baru = uploadFile($_FILES['foto'], $upload_dir, ['jpg', 'jpeg', 'png', 'gif']);
                    if ($foto_baru) {
                        // Hapus foto lama
                        if (!empty($user['foto']) && file_exists($upload_dir . $user['foto'])) {
                            unlink($upload_dir . $user['foto']);
                        }
                        $data['foto'] = $foto_baru;
                    } else {
                        $_SESSION['toast'] = [
                            'message' => 'Gagal upload foto. Pastikan format JPG, PNG, atau GIF dan ukuran maksimal 2MB.',
                            'type' => 'danger'
                        ];
                    }
                }
                
                if (!isset($_SESSION['toast'])) {
                    $result = updateGuru($data, $user['id']);
                    if ($result) {
                        // Update session
                        $_SESSION['nama'] = $nama_lengkap;
                        $_SESSION['toast'] = [
                            'message' => 'Profil berhasil diperbarui!',
                            'type' => 'success'
                        ];
                        // Refresh user data
                        $user = getCurrentUser();
                    } else {
                        $_SESSION['toast'] = [
                            'message' => 'Gagal memperbarui profil!',
                            'type' => 'danger'
                        ];
                    }
                }
            }
            header("Location: " . APP_URL . "guru/profil_guru.php");
            exit();
        }
        
        // Ubah Password
        if ($_POST['action'] == 'change_password') {
            $password_lama = isset($_POST['password_lama']) ? $_POST['password_lama'] : '';
            $password_baru = isset($_POST['password_baru']) ? $_POST['password_baru'] : '';
            $password_konfirmasi = isset($_POST['password_konfirmasi']) ? $_POST['password_konfirmasi'] : '';
            
            // Validasi
            if (empty($password_lama) || empty($password_baru) || empty($password_konfirmasi)) {
                $_SESSION['toast'] = [
                    'message' => 'Semua field password harus diisi!',
                    'type' => 'danger'
                ];
            } elseif (strlen($password_baru) < 6) {
                $_SESSION['toast'] = [
                    'message' => 'Password baru minimal 6 karakter!',
                    'type' => 'danger'
                ];
            } elseif ($password_baru !== $password_konfirmasi) {
                $_SESSION['toast'] = [
                    'message' => 'Konfirmasi password tidak sesuai!',
                    'type' => 'danger'
                ];
            } else {
                $result = ubahPassword($user['id'], $password_lama, $password_baru);
                if ($result) {
                    $_SESSION['toast'] = [
                        'message' => 'Password berhasil diubah!',
                        'type' => 'success'
                    ];
                } else {
                    $_SESSION['toast'] = [
                        'message' => 'Password lama salah!',
                        'type' => 'danger'
                    ];
                }
            }
            header("Location: " . APP_URL . "guru/profil_guru.php");
            exit();
        }
    }
}

// Include header guru
include __DIR__ . '/../includes/header_guru.php';
?>

<div class="container-fluid dashboard-container">
    <!-- Header -->
    <div class="dashboard-header">
        <div>
            <h4 class="dashboard-title">
                <i class="fas fa-user-circle text-primary me-2"></i> Profil Guru
            </h4>
            <small class="text-muted dashboard-subtitle">
                <i class="fas fa-edit me-1"></i> Kelola informasi profil Anda
            </small>
        </div>
        <div>
            <span class="badge bg-primary p-2">
                <i class="fas fa-tag me-1"></i> <?= ucfirst($user['role'] ?? '') ?>
            </span>
        </div>
    </div>

    <!-- Toast Notification - Seperti admin (pojok kanan atas) -->
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

    <div class="row">
        <!-- Profil -->
        <div class="col-md-5 mb-3">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-id-card me-2"></i> Informasi Profil
                </div>
                <div class="card-body text-center">
                    <!-- Foto Profil -->
                    <div class="profile-photo-container mb-3">
                        <?php if (!empty($user['foto']) && file_exists(APP_PATH . 'uploads/guru/' . $user['foto'])): ?>
                            <img src="<?= APP_URL ?>uploads/guru/<?= $user['foto'] ?>" 
                                 alt="Foto Profil" 
                                 class="profile-photo">
                        <?php else: ?>
                            <div class="profile-photo-placeholder">
                                <?= strtoupper(substr($user['nama_lengkap'] ?? 'U', 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <h5 class="mb-1"><?= htmlspecialchars($user['nama_lengkap'] ?? '-') ?></h5>
                    <p class="text-muted small">
                        <i class="fas fa-user-tag me-1"></i> <?= ucfirst($user['role'] ?? '-') ?>
                        <?php if (!empty($user['jabatan'])): ?>
                        <br><i class="fas fa-briefcase me-1"></i> <?= htmlspecialchars($user['jabatan']) ?>
                        <?php endif; ?>
                    </p>
                    
                    <hr>
                    
                    <div class="text-start">
                        <div class="info-item">
                            <span class="info-label"><i class="fas fa-user me-2"></i>Username</span>
                            <span class="info-value"><?= htmlspecialchars($user['username'] ?? '-') ?></span>
                        </div>
                        <?php if (!empty($user['nip'])): ?>
                        <div class="info-item">
                            <span class="info-label"><i class="fas fa-id-badge me-2"></i>NIP</span>
                            <span class="info-value"><?= htmlspecialchars($user['nip']) ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($user['no_hp'])): ?>
                        <div class="info-item">
                            <span class="info-label"><i class="fas fa-phone me-2"></i>No HP</span>
                            <span class="info-value"><?= htmlspecialchars($user['no_hp']) ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="info-item">
                            <span class="info-label"><i class="fas fa-calendar-alt me-2"></i>Bergabung</span>
                            <span class="info-value"><?= formatTanggalIndo($user['created_at'] ?? date('Y-m-d')) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Profil & Ubah Password -->
        <div class="col-md-7">
            <!-- Edit Profil -->
            <div class="card mb-3">
                <div class="card-header">
                    <i class="fas fa-edit me-2"></i> Edit Profil
                </div>
                <div class="card-body">
                    <form method="POST" action="" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label class="form-label">Nama Lengkap <span class="required">*</span></label>
                                <input type="text" class="form-control" name="nama_lengkap" 
                                       value="<?= htmlspecialchars($user['nama_lengkap'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label">NIP</label>
                                <input type="text" class="form-control" name="nip" 
                                       value="<?= htmlspecialchars($user['nip'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label">Jabatan</label>
                                <input type="text" class="form-control" name="jabatan" 
                                       value="<?= htmlspecialchars($user['jabatan'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label">No HP</label>
                                <input type="text" class="form-control" name="no_hp" 
                                       value="<?= htmlspecialchars($user['no_hp'] ?? '') ?>">
                            </div>
                            <div class="col-md-12 mb-2">
                                <label class="form-label">Foto Profil</label>
                                <input type="file" class="form-control" name="foto" accept=".jpg,.jpeg,.png,.gif">
                                <small class="text-muted">Format: JPG, PNG, GIF (Max 2MB)</small>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end mt-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Update Profil
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Ubah Password -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-lock me-2"></i> Ubah Password
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="row">
                            <div class="col-md-12 mb-2">
                                <label class="form-label">Password Lama <span class="required">*</span></label>
                                <input type="password" class="form-control" name="password_lama" required 
                                       placeholder="Masukkan password lama">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label">Password Baru <span class="required">*</span></label>
                                <input type="password" class="form-control" name="password_baru" required 
                                       placeholder="Minimal 6 karakter">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label">Konfirmasi Password <span class="required">*</span></label>
                                <input type="password" class="form-control" name="password_konfirmasi" required 
                                       placeholder="Ulangi password baru">
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end mt-2">
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-key me-1"></i> Ubah Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* ============================================
   STYLE PROFIL GURU
   ============================================ */

.dashboard-container {
    padding: 12px !important;
    max-width: 100%;
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
    font-size: 1.25rem;
    margin-bottom: 0;
}

.dashboard-subtitle {
    font-size: 0.75rem;
}

/* Toast Kecil - Seperti Admin */
.toast-container .toast {
    border-radius: 8px !important;
    box-shadow: 0 4px 20px rgba(0,0,0,0.12) !important;
}

.toast-container .toast .toast-body {
    font-size: 13px !important;
    font-weight: 500;
    padding: 2px 0 !important;
}

/* Card */
.card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 2px 15px rgba(0,0,0,0.08);
    margin-bottom: 20px;
    background: #fff;
}
.card-header {
    background: transparent;
    border-bottom: 1px solid #e9ecef;
    padding: 14px 18px;
    font-weight: 600;
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
}
.card-body {
    padding: 18px;
}

/* Profile Photo */
.profile-photo-container {
    text-align: center;
}

.profile-photo {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid #e2e8f0;
    padding: 3px;
    background: #fff;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.profile-photo-placeholder {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    background: linear-gradient(135deg, #4F46E5 0%, #4338CA 100%);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 48px;
    font-weight: 700;
    margin: 0 auto;
    border: 4px solid #e2e8f0;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

/* Info Items */
.info-item {
    display: flex;
    justify-content: space-between;
    padding: 6px 0;
    border-bottom: 1px solid #f1f3f5;
}
.info-item:last-child {
    border-bottom: none;
}

.info-label {
    color: #6b7280;
    font-size: 13px;
}

.info-value {
    font-weight: 600;
    color: #1a202c;
    font-size: 13px;
}

/* Form */
.form-control {
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    padding: 8px 12px;
    transition: all 0.3s;
    font-size: 13px;
}
.form-control:focus {
    border-color: #4F46E5;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
}

.form-label {
    font-weight: 600;
    color: #4a5568;
    font-size: 12px;
    margin-bottom: 4px;
}

.required {
    color: #e53e3e;
    margin-left: 3px;
}

/* Buttons */
.btn-primary {
    background: linear-gradient(135deg, #4F46E5 0%, #4338CA 100%);
    border: none;
    padding: 8px 20px;
    border-radius: 8px;
    font-weight: 600;
    color: #fff;
    transition: all 0.3s;
    font-size: 13px;
}
.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(79, 70, 229, 0.4);
    color: #fff;
}

.btn-warning {
    background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);
    border: none;
    padding: 8px 20px;
    border-radius: 8px;
    font-weight: 600;
    color: #fff;
    transition: all 0.3s;
    font-size: 13px;
}
.btn-warning:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(245, 158, 11, 0.4);
    color: #fff;
}

/* Badge */
.badge {
    font-size: 11px !important;
    padding: 5px 12px !important;
}

/* Responsive */
@media (max-width: 768px) {
    .dashboard-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 4px;
        margin-bottom: 10px;
    }
    .dashboard-title {
        font-size: 1rem;
    }
    .card-body {
        padding: 15px;
    }
    .card-header {
        padding: 12px 16px;
        font-size: 13px;
    }
    .profile-photo,
    .profile-photo-placeholder {
        width: 90px;
        height: 90px;
        font-size: 36px;
    }
    .btn-primary, .btn-warning {
        padding: 6px 16px;
        font-size: 12px;
        width: 100%;
    }
    .d-flex.justify-content-end {
        flex-direction: column;
        gap: 6px;
    }
    .d-flex.justify-content-end .btn {
        width: 100%;
    }
    .info-item {
        flex-direction: column;
        padding: 4px 0;
    }
    .info-label {
        font-size: 12px;
    }
    .info-value {
        font-size: 12px;
    }
    .toast-container .toast {
        min-width: 200px !important;
        max-width: 280px !important;
    }
    .toast-container .toast .toast-body {
        font-size: 11px !important;
    }
}

@media (max-width: 576px) {
    .dashboard-container {
        padding: 6px !important;
    }
    .card-body {
        padding: 12px;
    }
    .card-header {
        padding: 10px 14px;
        font-size: 12px;
    }
    .profile-photo,
    .profile-photo-placeholder {
        width: 70px;
        height: 70px;
        font-size: 28px;
    }
    .form-control {
        font-size: 12px;
        padding: 6px 10px;
    }
    .form-label {
        font-size: 11px;
    }
    .toast-container .toast {
        min-width: 160px !important;
        max-width: 240px !important;
    }
    .toast-container .toast .toast-body {
        font-size: 10px !important;
    }
}

/* Auto close toast */
.toast {
    animation: slideInRight 0.3s ease;
}

@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}
</style>

<script>
// Auto close toast setelah 3 detik (seperti admin)
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

<?php include '../includes/footer.php'; ?>