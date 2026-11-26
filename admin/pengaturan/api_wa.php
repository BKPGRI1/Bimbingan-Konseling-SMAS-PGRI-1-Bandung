<?php
// admin/pengaturan/api_wa.php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

// Cek login manual
if (!isset($_SESSION['user_id'])) {
    header("Location: " . APP_URL . "login.php");
    exit;
}

// Cek role admin
$user = getCurrentUser();
if (!$user || $user['role'] != 'admin') {
    header("Location: " . APP_URL . "dashboard.php");
    exit;
}

$page_title = 'API WhatsApp';

// Handle POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if ($action == 'update') {
        $url = sanitize($_POST['api_wa_url']);
        $token = sanitize($_POST['api_wa_token']);
        
        if (updateApiWa($url, $token)) {
            $_SESSION['toast'] = [
                'message' => 'Konfigurasi API WhatsApp berhasil diupdate!',
                'type' => 'success'
            ];
        } else {
            $_SESSION['toast'] = [
                'message' => 'Gagal mengupdate konfigurasi API WhatsApp!',
                'type' => 'danger'
            ];
        }
        header("Location: " . APP_URL . "admin/pengaturan/api_wa.php");
        exit();
    }
    
    if ($action == 'test') {
        $no_hp = sanitize($_POST['test_no_hp']);
        $message = sanitize($_POST['test_message']);
        
        if (empty($no_hp) || empty($message)) {
            $_SESSION['toast'] = [
                'message' => 'Nomor HP dan pesan harus diisi!',
                'type' => 'warning'
            ];
            header("Location: " . APP_URL . "admin/pengaturan/api_wa.php");
            exit();
        }
        
        $result = sendWhatsApp($no_hp, $message);
        
        if ($result) {
            $_SESSION['toast'] = [
                'message' => 'Pesan WhatsApp berhasil dikirim ke ' . $no_hp . '!',
                'type' => 'success'
            ];
        } else {
            $_SESSION['toast'] = [
                'message' => 'Gagal mengirim pesan! Periksa konfigurasi API atau nomor tujuan.',
                'type' => 'danger'
            ];
        }
        header("Location: " . APP_URL . "admin/pengaturan/api_wa.php");
        exit();
    }
}

// Get data
$pengaturan = getPengaturan();
$api_config = getApiWaConfig();

include __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
        <h4 class="mb-0">
            <i class="fab fa-whatsapp text-success me-2"></i> API WhatsApp
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
        <!-- Konfigurasi API -->
        <div class="col-md-7">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-cog text-primary me-2"></i> Konfigurasi API WhatsApp
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">API URL <span class="text-danger">*</span></label>
                                <input type="url" class="form-control" name="api_wa_url" 
                                       value="<?= htmlspecialchars($api_config['url'] ?? '') ?>" 
                                       placeholder="https://api.whatsapp.com/send" required>
                                <small class="text-muted">URL endpoint API WhatsApp yang digunakan</small>
                            </div>
                            <div class="col-12">
                                <label class="form-label">API Token <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" class="form-control" name="api_wa_token" 
                                           id="api_token" value="<?= htmlspecialchars($api_config['token'] ?? '') ?>" 
                                           placeholder="Masukkan token API" required>
                                    <button class="btn btn-outline-secondary" type="button" onclick="toggleToken()">
                                        <i class="fas fa-eye" id="toggleIcon"></i>
                                    </button>
                                </div>
                                <small class="text-muted">Token autentikasi untuk API WhatsApp</small>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Simpan Konfigurasi
                                </button>
                                <a href="<?= APP_URL ?>admin/dashboard.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-1"></i> Kembali
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Panduan -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle text-info me-2"></i> Panduan Konfigurasi
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-lightbulb me-2"></i>
                        <strong>Untuk menggunakan API WhatsApp, Anda perlu:</strong>
                        <ol class="mb-0 mt-2">
                            <li>Mendaftar di layanan API WhatsApp (misal: <a href="https://api.whatsapp.com/" target="_blank">WhatsApp Business API</a>)</li>
                            <li>Dapatkan URL endpoint dan Token dari penyedia layanan</li>
                            <li>Masukkan URL dan Token pada form di atas</li>
                            <li>Gunakan fitur Test Kirim untuk memastikan konfigurasi berjalan</li>
                        </ol>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Catatan:</strong>
                        <ul class="mb-0 mt-1">
                            <li>Pastikan URL API yang dimasukkan valid</li>
                            <li>Token harus sesuai dengan yang diberikan oleh penyedia layanan</li>
                            <li>Untuk testing, gunakan nomor HP yang terdaftar</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Test Kirim Pesan -->
        <div class="col-md-5">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-paper-plane text-primary me-2"></i> Test Kirim Pesan
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="test">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Nomor HP Tujuan <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="test_no_hp" 
                                       placeholder="081234567890" required>
                                <small class="text-muted">Masukkan nomor HP dengan kode area (08xx)</small>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Pesan <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="test_message" rows="4" 
                                          placeholder="Masukkan pesan test..." required>Test pesan dari Sistem Tatib Sekolah</textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-success w-100">
                                    <i class="fab fa-whatsapp me-1"></i> Kirim Test WhatsApp
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Status Konfigurasi -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-check-circle text-success me-2"></i> Status Konfigurasi
                    </h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="text-muted" style="font-size:12px;">Status API</td>
                            <td>
                                <?php if (!empty($api_config['url']) && !empty($api_config['token'])): ?>
                                    <span class="badge bg-success">Terkonfigurasi</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Belum Dikonfigurasi</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted" style="font-size:12px;">API URL</td>
                            <td style="font-size:12px;">
                                <?php if (!empty($api_config['url'])): ?>
                                    <span class="text-success">✓ <?= htmlspecialchars(substr($api_config['url'], 0, 30)) ?>...</span>
                                <?php else: ?>
                                    <span class="text-danger">✗ Belum diisi</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted" style="font-size:12px;">API Token</td>
                            <td style="font-size:12px;">
                                <?php if (!empty($api_config['token'])): ?>
                                    <span class="text-success">✓ Terisi</span>
                                <?php else: ?>
                                    <span class="text-danger">✗ Belum diisi</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted" style="font-size:12px;">Terakhir Update</td>
                            <td style="font-size:12px;"><?= date('d-m-Y H:i') ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle token visibility
function toggleToken() {
    var tokenInput = document.getElementById('api_token');
    var icon = document.getElementById('toggleIcon');
    
    if (tokenInput.type === 'password') {
        tokenInput.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        tokenInput.type = 'password';
        icon.className = 'fas fa-eye';
    }
}

// Auto close toast
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

// Validasi nomor HP
document.querySelector('input[name="test_no_hp"]')?.addEventListener('input', function(e) {
    this.value = this.value.replace(/[^0-9]/g, '');
});
</script>

<style>
.toast-container .toast {
    border-radius: 8px !important;
    box-shadow: 0 4px 20px rgba(0,0,0,0.12) !important;
}
.toast-container .toast .toast-body {
    font-size: 13px !important;
    font-weight: 500;
    padding: 2px 0 !important;
}
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

<?php include __DIR__ . '/../../includes/footer.php'; ?>