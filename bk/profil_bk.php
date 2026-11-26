<?php
// bk/profil_bk.php
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

$page_title = 'Profil BK';

// Ambil data user
$user_id = $_SESSION['user_id'];
$user = getGuruById($user_id);

if (!$user) {
    header("Location: ../dashboard.php");
    exit;
}

// Proses update profil
$msg = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    // Update Profil
    if ($action == 'update_profil') {
        $data = [
            'nama_lengkap' => trim($_POST['nama_lengkap']),
            'nip' => trim($_POST['nip']),
            'jabatan' => trim($_POST['jabatan']),
            'no_hp' => trim($_POST['no_hp'])
        ];
        
        // Upload foto
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $foto_name = 'user_' . $user_id . '_' . time() . '.' . $ext;
                $foto_path = APP_PATH . 'uploads/guru/' . $foto_name;
                
                if (!is_dir(APP_PATH . 'uploads/guru/')) {
                    mkdir(APP_PATH . 'uploads/guru/', 0777, true);
                }
                
                // Hapus foto lama
                if (!empty($user['foto']) && file_exists(APP_PATH . 'uploads/guru/' . $user['foto'])) {
                    unlink(APP_PATH . 'uploads/guru/' . $user['foto']);
                }
                
                if (move_uploaded_file($_FILES['foto']['tmp_name'], $foto_path)) {
                    $data['foto'] = $foto_name;
                }
            } else {
                $msg = 'Format foto tidak didukung! Gunakan JPG, PNG, atau GIF.';
                $msg_type = 'danger';
            }
        }
        
        if (empty($msg)) {
            if (updateGuru($data, $user_id)) {
                $msg = 'Profil berhasil diperbarui!';
                $msg_type = 'success';
                // Refresh data user
                $user = getGuruById($user_id);
            } else {
                $msg = 'Gagal memperbarui profil!';
                $msg_type = 'danger';
            }
        }
    }
    
    // Ganti Password
    if ($action == 'ganti_password') {
        $old_password = $_POST['old_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
            $msg = 'Semua field harus diisi!';
            $msg_type = 'warning';
        } elseif ($new_password !== $confirm_password) {
            $msg = 'Password baru dan konfirmasi tidak sama!';
            $msg_type = 'danger';
        } elseif (strlen($new_password) < 6) {
            $msg = 'Password minimal 6 karakter!';
            $msg_type = 'warning';
        } else {
            if (ubahPassword($user_id, $old_password, $new_password)) {
                $msg = 'Password berhasil diubah!';
                $msg_type = 'success';
            } else {
                $msg = 'Password lama salah!';
                $msg_type = 'danger';
            }
        }
    }
}

include __DIR__ . '/../includes/header_bk.php';
?>

<div class="container-fluid dashboard-container">
    <!-- Header -->
    <div class="dashboard-header">
        <div>
            <h4 class="dashboard-title">
                <i class="fas fa-user-circle text-primary me-2"></i> Profil BK
            </h4>
            <small class="text-muted dashboard-subtitle">
                <i class="fas fa-id-card me-1"></i> Kelola profil dan password
            </small>
        </div>
        <div>
            <span class="badge bg-primary p-2">
                <i class="fas fa-user me-1"></i> <?= htmlspecialchars($user['nama_lengkap']) ?>
            </span>
        </div>
    </div>

    <!-- Toast Notification -->
    <?php if ($msg): ?>
        <div id="toastNotification" class="toast-container position-fixed top-0 end-0 p-2" style="z-index: 9999;">
            <div class="toast align-items-center text-white bg-<?= $msg_type ?> border-0 show" role="alert" aria-live="assertive" aria-atomic="true" style="min-width: 280px; max-width: 380px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.12); font-size: 13px;">
                <div class="d-flex align-items-center p-2">
                    <div class="flex-shrink-0 ms-1">
                        <i class="fas fa-<?= $msg_type == 'success' ? 'check-circle' : ($msg_type == 'warning' ? 'exclamation-triangle' : 'times-circle') ?> fa-sm"></i>
                    </div>
                    <div class="toast-body flex-grow-1 text-start ms-2" style="font-size: 13px; font-weight: 500; padding: 2px 0; line-height: 1.4;">
                        <?= $msg ?>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-0" data-bs-dismiss="toast" aria-label="Close" style="font-size: 10px; padding: 4px;"></button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="row g-3">
        <!-- Profil -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-id-card text-primary me-2"></i> Data Profil
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update_profil">
                        
                        <div class="text-center mb-3">
                            <?php 
                            $foto_path = APP_PATH . 'uploads/guru/' . ($user['foto'] ?? '');
                            if (!empty($user['foto']) && file_exists($foto_path)): 
                            ?>
                                <img src="<?= APP_URL ?>uploads/guru/<?= $user['foto'] ?>" 
                                     alt="Foto Profil" 
                                     class="profile-img"
                                     style="width: 120px; height: 120px; object-fit: cover; border-radius: 50%; border: 3px solid #4F46E5; padding: 3px;">
                            <?php else: ?>
                                <div class="profile-placeholder" style="width: 120px; height: 120px; border-radius: 50%; background: #4F46E5; color: #fff; display: flex; align-items: center; justify-content: center; margin: 0 auto; font-size: 48px; font-weight: 700; border: 3px solid #4F46E5;">
                                    <?= strtoupper(substr($user['nama_lengkap'] ?? 'U', 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                            <div class="mt-2">
                                <input type="file" class="form-control form-control-sm" name="foto" accept="image/*" style="max-width: 200px; margin: 0 auto;">
                                <small class="text-muted">Format: JPG, PNG, GIF (Max 2MB)</small>
                            </div>
                        </div>

                        <div class="row g-2">
                            <div class="col-12">
                                <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="nama_lengkap" 
                                       value="<?= htmlspecialchars($user['nama_lengkap'] ?? '') ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($user['username'] ?? '') ?>" disabled>
                                <small class="text-muted">Username tidak dapat diubah</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">NIP</label>
                                <input type="text" class="form-control" name="nip" 
                                       value="<?= htmlspecialchars($user['nip'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Jabatan</label>
                                <input type="text" class="form-control" name="jabatan" 
                                       value="<?= htmlspecialchars($user['jabatan'] ?? '') ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label">No. HP</label>
                                <input type="text" class="form-control" name="no_hp" 
                                       value="<?= htmlspecialchars($user['no_hp'] ?? '') ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Role</label>
                                <input type="text" class="form-control" value="<?= ucfirst($user['role'] ?? '') ?>" disabled>
                            </div>
                            <div class="col-12 mt-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Update Profil
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Ganti Password -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-key text-warning me-2"></i> Ganti Password
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="ganti_password">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Password Lama <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" name="old_password" 
                                       placeholder="Masukkan password lama" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Password Baru <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" name="new_password" 
                                       placeholder="Minimal 6 karakter" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Konfirmasi Password Baru <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" name="confirm_password" 
                                       placeholder="Konfirmasi password baru" required>
                            </div>
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Tips Password:</strong>
                                    <ul class="mb-0 mt-1">
                                        <li>Minimal 6 karakter</li>
                                        <li>Gunakan kombinasi huruf, angka, dan simbol</li>
                                        <li>Jangan gunakan password yang sama dengan sebelumnya</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-save me-1"></i> Ganti Password
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Informasi Akun -->
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle text-info me-2"></i> Informasi Akun
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 text-center">
                            <div class="p-3 bg-light rounded">
                                <h6 class="text-muted mb-1">Bergabung Sejak</h6>
                                <span class="fw-bold"><?= date('d F Y', strtotime($user['created_at'] ?? 'now')) ?></span>
                            </div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="p-3 bg-light rounded">
                                <h6 class="text-muted mb-1">Role</h6>
                                <span class="fw-bold text-primary"><?= ucfirst($user['role'] ?? '-') ?></span>
                            </div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="p-3 bg-light rounded">
                                <h6 class="text-muted mb-1">Status</h6>
                                <span class="fw-bold text-success"><i class="fas fa-circle me-1" style="font-size: 8px;"></i> Aktif</span>
                            </div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="p-3 bg-light rounded">
                                <h6 class="text-muted mb-1">ID User</h6>
                                <span class="fw-bold">#<?= $user['id'] ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
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
    padding: 12px 16px;
    font-weight: 600;
    font-size: 13px;
}

.card-body {
    padding: 16px;
}

.form-label {
    font-weight: 600;
    font-size: 12px;
    color: #4a5568;
    margin-bottom: 4px;
}

.form-control {
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    padding: 8px 12px;
    font-size: 13px;
    transition: all 0.3s;
}

.form-control:focus {
    border-color: #4F46E5;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
}

.form-control:disabled {
    background: #f8f9fa;
    cursor: not-allowed;
}

.btn-primary {
    background: linear-gradient(135deg, #4F46E5 0%, #4338CA 100%);
    border: none;
    padding: 8px 24px;
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
    padding: 8px 24px;
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

.profile-img {
    width: 120px;
    height: 120px;
    object-fit: cover;
    border-radius: 50%;
    border: 3px solid #4F46E5;
    padding: 3px;
}

.profile-placeholder {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    background: #4F46E5;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    font-size: 48px;
    font-weight: 700;
    border: 3px solid #4F46E5;
}

.alert {
    border-radius: 8px;
    font-size: 13px;
    padding: 12px 16px;
}

.alert ul {
    font-size: 12px;
}

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
    .card-body {
        padding: 12px;
    }
    .btn-primary, .btn-warning {
        padding: 6px 16px;
        font-size: 12px;
        width: 100%;
    }
    .profile-img, .profile-placeholder {
        width: 80px;
        height: 80px;
        font-size: 32px;
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
        padding: 4px !important;
    }
    .card-body {
        padding: 8px;
    }
    .form-control {
        font-size: 12px;
        padding: 6px 10px;
    }
    .toast-container .toast {
        min-width: 160px !important;
        max-width: 240px !important;
    }
    .toast-container .toast .toast-body {
        font-size: 10px !important;
    }
}
</style>

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

<?php include '../includes/footer.php'; ?>