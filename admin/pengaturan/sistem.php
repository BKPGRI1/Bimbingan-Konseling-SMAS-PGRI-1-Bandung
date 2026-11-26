<?php
// admin/pengaturan/sistem.php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

// Cek login
if (!isLoggedIn()) {
    header("Location: " . APP_URL . "login.php");
    exit;
}

// Cek role (hanya admin)
if (!hasRole(['admin'])) {
    header("Location: " . APP_URL . "dashboard.php");
    exit;
}

$page_title = 'Pengaturan Sistem';

// Handle POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    // Ubah Password
    if ($action == 'ubah_password') {
        $user_id = $_SESSION['user_id'];
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
            $user_data = getRow("SELECT password FROM users WHERE id = " . $user_id);
            
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
                if (update('users', ['password' => $new_hash], "id = " . $user_id)) {
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
        header("Location: " . APP_URL . "admin/pengaturan/sistem.php");
        exit();
    }
    
    // Update Batas Poin
    if ($action == 'update_poin') {
        $batas_poin_peringatan = isset($_POST['batas_poin_peringatan']) ? (int)$_POST['batas_poin_peringatan'] : 100;
        $batas_poin_pemanggilan = isset($_POST['batas_poin_pemanggilan']) ? (int)$_POST['batas_poin_pemanggilan'] : 200;
        $batas_poin_do = isset($_POST['batas_poin_do']) ? (int)$_POST['batas_poin_do'] : 300;
        
        if ($batas_poin_peringatan >= $batas_poin_pemanggilan) {
            $_SESSION['toast'] = [
                'message' => 'Batas Poin Peringatan harus lebih kecil dari Batas Poin Pemanggilan!',
                'type' => 'danger'
            ];
        } elseif ($batas_poin_pemanggilan >= $batas_poin_do) {
            $_SESSION['toast'] = [
                'message' => 'Batas Poin Pemanggilan harus lebih kecil dari Batas Poin DO!',
                'type' => 'danger'
            ];
        } else {
            $result = updatePengaturan([
                'batas_poin_peringatan' => $batas_poin_peringatan,
                'batas_poin_pemanggilan' => $batas_poin_pemanggilan,
                'batas_poin_do' => $batas_poin_do
            ]);
            
            if ($result) {
                $_SESSION['toast'] = [
                    'message' => 'Pengaturan poin berhasil diperbarui!',
                    'type' => 'success'
                ];
            } else {
                $_SESSION['toast'] = [
                    'message' => 'Gagal memperbarui pengaturan poin!',
                    'type' => 'danger'
                ];
            }
        }
        header("Location: " . APP_URL . "admin/pengaturan/sistem.php");
        exit();
    }
    
    // Reset Seluruh Data
    if ($action == 'reset_data') {
        $confirm = isset($_POST['confirm']) ? $_POST['confirm'] : '';
        
        if ($confirm !== 'RESET') {
            $_SESSION['toast'] = [
                'message' => 'Konfirmasi tidak valid! Ketik "RESET" untuk mengkonfirmasi.',
                'type' => 'danger'
            ];
            header("Location: " . APP_URL . "admin/pengaturan/sistem.php");
            exit();
        }
        
        $conn = getDB();
        $tables = ['pelanggaran', 'surat_meneg', 'catatan_konseling', 'catatan_pembinaan'];
        $success = true;
        $deleted = [];
        
        foreach ($tables as $table) {
            $check = $conn->query("SHOW TABLES LIKE '$table'");
            if ($check && $check->num_rows > 0) {
                if ($conn->query("TRUNCATE TABLE $table")) {
                    $deleted[] = $table;
                } else {
                    $success = false;
                }
            }
        }
        
        // Reset poin siswa menjadi 0
        if ($conn->query("UPDATE siswa SET poin = 0")) {
            $deleted[] = 'poin siswa direset ke 0';
        } else {
            $success = false;
        }
        
        if ($success) {
            $_SESSION['toast'] = [
                'message' => 'Berhasil mereset semua data! Data yang direset: ' . implode(', ', $deleted),
                'type' => 'success'
            ];
        } else {
            $_SESSION['toast'] = [
                'message' => 'Gagal mereset beberapa data! Coba lagi.',
                'type' => 'danger'
            ];
        }
        header("Location: " . APP_URL . "admin/pengaturan/sistem.php");
        exit();
    }
    
    // Backup Database
    if ($action == 'backup') {
        $filename = backupDatabase();
        if ($filename && file_exists($filename)) {
            header('Content-Type: application/sql');
            header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
            header('Content-Length: ' . filesize($filename));
            readfile($filename);
            unlink($filename);
            exit();
        } else {
            $_SESSION['toast'] = [
                'message' => 'Gagal melakukan backup database!',
                'type' => 'danger'
            ];
            header("Location: " . APP_URL . "admin/pengaturan/sistem.php");
            exit();
        }
    }
}

// Get data
$user = getCurrentUser();
$guru_list = getGuru();
$system_info = getSystemInfo();
$pengaturan = getPengaturan();

include '../../includes/header.php';
?>

<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
        <h4 class="mb-0">
            <i class="fas fa-cogs text-primary me-2"></i> Pengaturan Sistem
        </h4>
        <span class="text-muted" style="font-size: 13px;">
            <i class="fas fa-clock me-1"></i> <?= date('d-m-Y H:i') ?>
        </span>
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

    <div class="row g-3">
        <!-- Ubah Password -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-key text-warning me-2"></i> Ubah Password
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="ubah_password">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Password Lama <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" name="old_password" placeholder="Masukkan password lama" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Password Baru <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" name="new_password" placeholder="Minimal 6 karakter" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Konfirmasi Password Baru <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" name="confirm_password" placeholder="Konfirmasi password baru" required>
                            </div>
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Password minimal 6 karakter. Gunakan kombinasi huruf dan angka untuk keamanan.
                                </div>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-save me-1"></i> Ubah Password
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Pengaturan Batas Poin -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-balance-scale text-primary me-2"></i> Pengaturan Batas Poin
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_poin">
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Batas Poin:</strong>
                                    <ul class="mb-0 mt-1">
                                        <li><strong>Surat Teguran:</strong> Poin mencapai batas ini akan otomatis dibuatkan Surat Teguran</li>
                                        <li><strong>Surat Pemanggilan:</strong> Poin mencapai batas ini akan otomatis dibuatkan Surat Pemanggilan</li>
                                        <li><strong>Surat DO:</strong> Poin mencapai batas ini akan otomatis dibuatkan Surat Peringatan DO</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-4">
                                <label class="form-label">Surat Teguran</label>
                                <input type="number" class="form-control" name="batas_poin_peringatan" 
                                       value="<?= $pengaturan['batas_poin_peringatan'] ?? 100 ?>" min="1" required>
                                <small class="text-muted">Minimal 1</small>
                            </div>
                            <div class="col-4">
                                <label class="form-label">Surat Pemanggilan</label>
                                <input type="number" class="form-control" name="batas_poin_pemanggilan" 
                                       value="<?= $pengaturan['batas_poin_pemanggilan'] ?? 200 ?>" min="1" required>
                                <small class="text-muted">Harus > Teguran</small>
                            </div>
                            <div class="col-4">
                                <label class="form-label">Surat DO</label>
                                <input type="number" class="form-control" name="batas_poin_do" 
                                       value="<?= $pengaturan['batas_poin_do'] ?? 300 ?>" min="1" required>
                                <small class="text-muted">Harus > Pemanggilan</small>
                            </div>
                            <div class="col-12">
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Urutan Batas Poin:</strong>
                                    Teguran (<?= $pengaturan['batas_poin_peringatan'] ?? 100 ?>) &lt; 
                                    Pemanggilan (<?= $pengaturan['batas_poin_pemanggilan'] ?? 200 ?>) &lt; 
                                    DO (<?= $pengaturan['batas_poin_do'] ?? 300 ?>)
                                </div>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Simpan Pengaturan Poin
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Reset Seluruh Data -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-trash-alt text-danger me-2"></i> Reset Seluruh Data
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="" onsubmit="return confirmReset()">
                        <input type="hidden" name="action" value="reset_data">
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>PERINGATAN!</strong><br>
                                    Tindakan ini akan menghapus semua data berikut:
                                    <ul class="mb-0 mt-1">
                                        <li><i class="fas fa-times-circle text-danger me-1"></i> Semua data pelanggaran</li>
                                        <li><i class="fas fa-times-circle text-danger me-1"></i> Semua surat teguran, pemanggilan, dan DO</li>
                                        <li><i class="fas fa-times-circle text-danger me-1"></i> Semua catatan konseling</li>
                                        <li><i class="fas fa-times-circle text-danger me-1"></i> Semua catatan pembinaan</li>
                                        <li><i class="fas fa-times-circle text-danger me-1"></i> Reset poin semua siswa menjadi 0</li>
                                    </ul>
                                    <strong class="mt-2 d-block">Data yang TIDAK dihapus:</strong>
                                    <ul class="mb-0 mt-1">
                                        <li><i class="fas fa-check-circle text-success me-1"></i> Data siswa</li>
                                        <li><i class="fas fa-check-circle text-success me-1"></i> Data guru</li>
                                        <li><i class="fas fa-check-circle text-success me-1"></i> Data kelas</li>
                                        <li><i class="fas fa-check-circle text-success me-1"></i> Jenis pelanggaran</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Ketik <strong>"RESET"</strong> untuk konfirmasi <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="confirm" placeholder="Ketik RESET" required>
                                <small class="text-muted">Ketik RESET (huruf kapital) untuk mengkonfirmasi</small>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-trash-alt me-1"></i> Reset Semua Data
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Backup Database -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-database text-success me-2"></i> Backup Database
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="backup">
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Backup Database</strong><br>
                                    Backup akan mendownload seluruh data database dalam format SQL.
                                    File backup akan otomatis terhapus setelah diunduh.
                                </div>
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-check-circle text-success me-2"></i> Backup semua tabel</li>
                                    <li><i class="fas fa-check-circle text-success me-2"></i> Format SQL</li>
                                    <li><i class="fas fa-check-circle text-success me-2"></i> Auto download</li>
                                    <li><i class="fas fa-check-circle text-success me-2"></i> File aman (auto delete)</li>
                                </ul>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-download me-1"></i> Download Backup
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Informasi Sistem -->
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-server text-primary me-2"></i> Informasi Sistem
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <td class="text-muted" style="font-size:12px; width:45%;">Versi Aplikasi</td>
                                    <td class="fw-bold" style="font-size:12px;"><?= APP_VERSION ?? '1.0.0' ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted" style="font-size:12px;">Nama Aplikasi</td>
                                    <td class="fw-bold" style="font-size:12px;"><?= APP_NAME ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted" style="font-size:12px;">PHP Version</td>
                                    <td class="fw-bold" style="font-size:12px;"><?= $system_info['php_version'] ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted" style="font-size:12px;">Database</td>
                                    <td class="fw-bold" style="font-size:12px;">MySQL <?= $system_info['mysql_version'] ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted" style="font-size:12px;">Server</td>
                                    <td class="fw-bold" style="font-size:12px;"><?= $system_info['server_software'] ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <td class="text-muted" style="font-size:12px; width:45%;">Memory Limit</td>
                                    <td class="fw-bold" style="font-size:12px;"><?= $system_info['memory_limit'] ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted" style="font-size:12px;">Max Execution Time</td>
                                    <td class="fw-bold" style="font-size:12px;"><?= $system_info['max_execution_time'] ?>s</td>
                                </tr>
                                <tr>
                                    <td class="text-muted" style="font-size:12px;">Upload Max Filesize</td>
                                    <td class="fw-bold" style="font-size:12px;"><?= $system_info['upload_max_filesize'] ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted" style="font-size:12px;">Post Max Size</td>
                                    <td class="fw-bold" style="font-size:12px;"><?= $system_info['post_max_size'] ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted" style="font-size:12px;">Total Users</td>
                                    <td class="fw-bold" style="font-size:12px;"><?= count($guru_list) + 1 ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="text-center p-2 bg-light rounded">
                                <h6 class="text-muted mb-0">Total Siswa</h6>
                                <span class="fw-bold fs-5"><?= getTotalSiswa() ?></span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center p-2 bg-light rounded">
                                <h6 class="text-muted mb-0">Total Guru</h6>
                                <span class="fw-bold fs-5"><?= getTotalGuru() ?></span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center p-2 bg-light rounded">
                                <h6 class="text-muted mb-0">Total Kelas</h6>
                                <span class="fw-bold fs-5"><?= getTotalKelas() ?></span>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="text-center p-2 bg-warning bg-opacity-10 rounded">
                                <h6 class="text-muted mb-0">Batas Poin Teguran</h6>
                                <span class="fw-bold fs-5 text-warning"><?= $pengaturan['batas_poin_peringatan'] ?? 100 ?></span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center p-2 bg-info bg-opacity-10 rounded">
                                <h6 class="text-muted mb-0">Batas Poin Pemanggilan</h6>
                                <span class="fw-bold fs-5 text-info"><?= $pengaturan['batas_poin_pemanggilan'] ?? 200 ?></span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center p-2 bg-danger bg-opacity-10 rounded">
                                <h6 class="text-muted mb-0">Batas Poin DO</h6>
                                <span class="fw-bold fs-5 text-danger"><?= $pengaturan['batas_poin_do'] ?? 300 ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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

// Konfirmasi Reset Data
function confirmReset() {
    var confirmText = document.querySelector('input[name="confirm"]').value;
    if (confirmText !== 'RESET') {
        alert('Ketik "RESET" (huruf kapital) untuk mengkonfirmasi reset data!');
        return false;
    }
    return confirm('PERINGATAN! Anda akan menghapus semua data pelanggaran, surat, dan konseling. Lanjutkan?');
}
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

/* Card */
.card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
    height: 100%;
}

.card-header {
    background: transparent;
    border-bottom: 1px solid #e9ecef;
    padding: 12px 16px;
}

.card-body {
    padding: 16px;
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
    
    .table td {
        font-size: 11px !important;
    }
    
    .card-body {
        padding: 12px;
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

<?php include '../../includes/footer.php'; ?>