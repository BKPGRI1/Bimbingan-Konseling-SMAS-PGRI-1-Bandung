<?php
// walikelas/profil_walikelas.php
include '../includes/config.php';
checkLogin();

// Cek role
$user = getCurrentUser();
if (!$user || $user['role'] != 'walikelas') {
    $_SESSION['toast'] = [
        'message' => 'Anda tidak memiliki akses ke halaman ini!',
        'type' => 'danger'
    ];
    header("Location: " . APP_URL . "login.php");
    exit();
}

$page_title = 'Profil Wali Kelas';

// Get data wali kelas
$user = getCurrentUser();
$kelas_wali = getRow("SELECT * FROM kelas WHERE wali_kelas_id = " . $user['id']);
$kelas_id = $kelas_wali ? $kelas_wali['id'] : 0;
$nama_kelas = $kelas_wali ? $kelas_wali['nama_kelas'] : 'Belum ditentukan';

// Handle Update Profil
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    // Update Profil
    if ($action == 'update_profil') {
        $data = [
            'nama_lengkap' => sanitize($_POST['nama_lengkap']),
            'nip' => sanitize($_POST['nip']),
            'jabatan' => sanitize($_POST['jabatan']),
            'no_hp' => sanitize($_POST['no_hp'])
        ];
        
        // Update data user
        if (update('users', $data, "id = " . $user['id'])) {
            $_SESSION['toast'] = [
                'message' => 'Profil berhasil diupdate!',
                'type' => 'success'
            ];
            // Refresh data
            $user = getCurrentUser();
        } else {
            $_SESSION['toast'] = [
                'message' => 'Gagal mengupdate profil!',
                'type' => 'danger'
            ];
        }
        header("Location: " . APP_URL . "walikelas/profil_walikelas.php");
        exit();
    }
    
    // Update Password
    if ($action == 'update_password') {
        $old_password = $_POST['old_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
            $_SESSION['toast'] = [
                'message' => 'Semua field harus diisi!',
                'type' => 'warning'
            ];
        } elseif ($new_password !== $confirm_password) {
            $_SESSION['toast'] = [
                'message' => 'Password baru dan konfirmasi tidak sama!',
                'type' => 'danger'
            ];
        } elseif (strlen($new_password) < 6) {
            $_SESSION['toast'] = [
                'message' => 'Password minimal 6 karakter!',
                'type' => 'warning'
            ];
        } else {
            // Cek password lama
            $user_data = getRow("SELECT password FROM users WHERE id = " . $user['id']);
            
            // Cek dengan MD5
            $valid = false;
            if (md5($old_password) == $user_data['password']) {
                $valid = true;
            } elseif (password_verify($old_password, $user_data['password'])) {
                $valid = true;
            }
            
            if ($valid) {
                // Update password
                $new_hash = md5($new_password);
                if (update('users', ['password' => $new_hash], "id = " . $user['id'])) {
                    $_SESSION['toast'] = [
                        'message' => 'Password berhasil diubah!',
                        'type' => 'success'
                    ];
                } else {
                    $_SESSION['toast'] = [
                        'message' => 'Gagal mengubah password!',
                        'type' => 'danger'
                    ];
                }
            } else {
                $_SESSION['toast'] = [
                    'message' => 'Password lama salah!',
                    'type' => 'danger'
                ];
            }
        }
        header("Location: " . APP_URL . "walikelas/profil_walikelas.php");
        exit();
    }
}

include '../includes/header_walikelas.php';
?>

<div class="dashboard-container">
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

    <!-- Header -->
    <div class="dashboard-header">
        <div>
            <h4 class="dashboard-title">
                <i class="fas fa-user text-primary me-1"></i> Profil Wali Kelas
            </h4>
            <small class="text-muted dashboard-subtitle">
                <?= htmlspecialchars($user['nama_lengkap'] ?? 'Wali Kelas') ?> - Kelas <?= htmlspecialchars($nama_kelas) ?>
            </small>
        </div>
        <div>
            <span class="badge bg-primary">
                <i class="fas fa-chalkboard-teacher me-1"></i> Wali Kelas
            </span>
        </div>
    </div>

    <div class="row g-3">
        <!-- Kolom Kiri - Profil -->
        <div class="col-md-7">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-edit text-primary me-2"></i> Data Diri
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_profil">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" disabled readonly>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="nama_lengkap" 
                                       value="<?= htmlspecialchars($user['nama_lengkap']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">NIP</label>
                                <input type="text" class="form-control" name="nip" 
                                       value="<?= htmlspecialchars($user['nip'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Role</label>
                                <input type="text" class="form-control" value="Wali Kelas" disabled readonly>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Kelas Wali</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($nama_kelas) ?>" disabled readonly>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Jabatan</label>
                                <input type="text" class="form-control" name="jabatan" 
                                       value="<?= htmlspecialchars($user['jabatan'] ?? '') ?>"
                                       placeholder="Masukkan jabatan">
                            </div>
                            <div class="col-12">
                                <label class="form-label">No. HP</label>
                                <input type="text" class="form-control" name="no_hp" 
                                       value="<?= htmlspecialchars($user['no_hp'] ?? '') ?>"
                                       placeholder="081234567890">
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Simpan Perubahan
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan - Password & Info -->
        <div class="col-md-5">
            <!-- Ubah Password -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-key text-warning me-2"></i> Ubah Password
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_password">
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
                                    Password minimal 6 karakter. 
                                    Gunakan kombinasi huruf dan angka untuk keamanan.
                                </div>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-warning w-100">
                                    <i class="fas fa-key me-1"></i> Ubah Password
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Informasi Singkat -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle text-info me-2"></i> Informasi Akun
                    </h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="text-muted" style="font-size:12px;">Username</td>
                            <td style="font-size:13px; font-weight:500;"><?= htmlspecialchars($user['username']) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted" style="font-size:12px;">Nama Lengkap</td>
                            <td style="font-size:13px; font-weight:500;"><?= htmlspecialchars($user['nama_lengkap']) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted" style="font-size:12px;">Role</td>
                            <td style="font-size:13px; font-weight:500;">Wali Kelas</td>
                        </tr>
                        <tr>
                            <td class="text-muted" style="font-size:12px;">Kelas Wali</td>
                            <td style="font-size:13px; font-weight:500;"><?= htmlspecialchars($nama_kelas) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted" style="font-size:12px;">NIP</td>
                            <td style="font-size:13px; font-weight:500;"><?= htmlspecialchars($user['nip'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted" style="font-size:12px;">Jabatan</td>
                            <td style="font-size:13px; font-weight:500;"><?= htmlspecialchars($user['jabatan'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted" style="font-size:12px;">No. HP</td>
                            <td style="font-size:13px; font-weight:500;"><?= htmlspecialchars($user['no_hp'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted" style="font-size:12px;">Bergabung</td>
                            <td style="font-size:13px; font-weight:500;"><?= date('d-m-Y', strtotime($user['created_at'])) ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Logout Button -->
            <div class="card mt-3">
                <div class="card-body">
                    <a href="<?= APP_URL ?>logout.php" class="btn btn-danger w-100" onclick="return confirm('Yakin ingin logout?')">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* ============================================
   STYLE PROFIL WALI KELAS
   ============================================ */
.dashboard-container {
    padding: 0 !important;
    max-width: 100%;
    overflow-x: hidden;
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
    font-size: 0.95rem;
    margin-bottom: 0;
    font-weight: 600;
}

.dashboard-subtitle {
    font-size: 0.65rem;
    color: #6c757d;
    display: block;
}

.dashboard-header .badge {
    font-size: 0.6rem !important;
    padding: 4px 10px !important;
}

.card {
    border: none;
    border-radius: 8px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.05);
    margin-bottom: 10px;
    background: #fff;
    overflow: hidden;
}

.card-header {
    background: transparent;
    border-bottom: 1px solid #e9ecef;
    padding: 8px 14px;
    font-weight: 600;
    font-size: 0.75rem;
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 6px;
    color: #1A1A2E;
}

.card-body {
    padding: 10px 14px;
}

.form-control {
    font-size: 13px !important;
    height: 38px !important;
    border-radius: 6px !important;
}
.form-control:disabled,
.form-control[readonly] {
    background-color: #f8f9fa;
    opacity: 0.8;
}

.form-label {
    font-size: 12px !important;
    margin-bottom: 4px !important;
    font-weight: 500;
}

.btn {
    font-size: 13px !important;
    padding: 6px 14px !important;
    border-radius: 6px !important;
}

.btn-warning {
    color: #fff;
}
.btn-warning:hover {
    color: #fff;
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

.table-sm td {
    padding: 4px 0 !important;
}

/* Responsive */
@media (max-width: 576px) {
    .dashboard-title {
        font-size: 0.8rem;
    }
    .dashboard-subtitle {
        font-size: 0.55rem;
    }
    .dashboard-header .badge {
        font-size: 0.5rem !important;
        padding: 2px 6px !important;
    }
    .card-header {
        font-size: 0.65rem;
        padding: 6px 10px;
    }
    .card-body {
        padding: 6px 10px;
    }
    .form-control {
        font-size: 12px !important;
        height: 34px !important;
    }
    .btn {
        font-size: 12px !important;
        padding: 4px 10px !important;
    }
    .toast-container .toast {
        min-width: 200px !important;
        max-width: 280px !important;
    }
    .toast-container .toast .toast-body {
        font-size: 11px !important;
    }
}

@media (min-width: 768px) {
    .form-control {
        font-size: 13px !important;
        height: 38px !important;
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

<?php include '../includes/footer.php'; ?>