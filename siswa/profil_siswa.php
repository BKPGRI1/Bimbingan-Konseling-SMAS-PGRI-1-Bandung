<?php
// siswa/profil_siswa.php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Cek login
if (!isLoggedIn()) {
    header("Location: ../login.php");
    exit;
}

// Cek role siswa
if (!hasRole(['siswa'])) {
    header("Location: " . APP_URL . "dashboard.php");
    exit;
}

$page_title = 'Profil Saya';

// Include header siswa
include __DIR__ . '/../includes/header_siswa.php';

// Ambil data user
$user = getCurrentUser();

// Ambil data siswa berdasarkan username (NISN)
$nisn = $_SESSION['username'];
$siswa = getRow("SELECT s.*, k.nama_kelas 
                 FROM siswa s 
                 LEFT JOIN kelas k ON s.kelas_id = k.id 
                 WHERE s.nisn = '$nisn'");

if (!$siswa) {
    echo '<div class="alert alert-danger">Data siswa tidak ditemukan!</div>';
    include __DIR__ . '/../includes/footer.php';
    exit;
}

$siswa_id = $siswa['id'];
$nama_siswa = $siswa['nama_siswa'];
$kelas = $siswa['nama_kelas'] ?? 'Belum ditentukan';

// ============================================
// AMBIL DATA PELANGGARAN
// ============================================
$pelanggaran_list = getPelanggaranBySiswa($siswa_id);
$total_pelanggaran = count($pelanggaran_list);

// Hitung total poin pelanggaran (yang disetujui)
$total_poin_pelanggaran = 0;
foreach ($pelanggaran_list as $p) {
    if ($p['status'] == 'disetujui') {
        $total_poin_pelanggaran += $p['poin'];
    }
}

// ============================================
// AMBIL DATA PRESTASI
// ============================================
$conn = getDB();
$query_prestasi = "SELECT * FROM prestasi_siswa WHERE siswa_id = $siswa_id ORDER BY tahun DESC, created_at DESC";
$prestasi_list = getRows($query_prestasi);
$total_prestasi = count($prestasi_list);

// Hitung total poin prestasi
$total_poin_prestasi = 0;
foreach ($prestasi_list as $p) {
    $total_poin_prestasi += $p['poin_prestasi'];
}

// ============================================
// HITUNG TOTAL POIN AKHIR (PELANGGARAN - PRESTASI)
// ============================================
$total_poin = $total_poin_pelanggaran - $total_poin_prestasi;
if ($total_poin < 0) $total_poin = 0;

// ============================================
// LEVEL POIN
// ============================================
$level = getPoinLevel($total_poin);

// Total konseling
$total_konseling = count(getCatatanKonseling($siswa_id));

// Handle Update Profil
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    // Update Profil
    if ($action == 'update_profil') {
        $data = [
            'nama_siswa' => sanitize($_POST['nama_siswa']),
            'tempat_lahir' => sanitize($_POST['tempat_lahir']),
            'tanggal_lahir' => sanitize($_POST['tanggal_lahir']),
            'alamat' => sanitize($_POST['alamat']),
            'no_hp' => sanitize($_POST['no_hp'])
        ];
        
        // Update data siswa
        if (updateSiswa($data, $siswa_id)) {
            // Update juga nama di tabel users
            update('users', ['nama_lengkap' => $data['nama_siswa']], "id = " . $user['id']);
            
            $_SESSION['toast'] = [
                'message' => 'Profil berhasil diupdate!',
                'type' => 'success'
            ];
            
            // Refresh data
            $siswa = getSiswaById($siswa_id);
            $nama_siswa = $siswa['nama_siswa'];
        } else {
            $_SESSION['toast'] = [
                'message' => 'Gagal mengupdate profil!',
                'type' => 'danger'
            ];
        }
        header("Location: " . APP_URL . "siswa/profil_siswa.php");
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
        header("Location: " . APP_URL . "siswa/profil_siswa.php");
        exit();
    }
}
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
                <i class="fas fa-user text-primary me-1"></i> Profil Saya
            </h4>
            <small class="text-muted dashboard-subtitle">
                Kelola data diri dan password Anda
            </small>
        </div>
        <div>
            <span class="badge bg-<?= $level['color'] ?>">
                <i class="fas <?= $level['icon'] ?> me-1"></i> <?= $level['level'] ?>
            </span>
            <span class="badge bg-primary ms-1">
                <i class="fas fa-star me-1"></i> <?= $total_poin ?> Poin
            </span>
        </div>
    </div>

    <!-- Ringkasan Poin -->
    <div class="row g-2 mb-3">
        <div class="col-4">
            <div class="stat-card bg-danger bg-opacity-10 text-danger">
                <div class="stat-card-body">
                    <div class="stat-number">+<?= $total_poin_pelanggaran ?></div>
                    <div class="stat-label">Pelanggaran</div>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="stat-card bg-success bg-opacity-10 text-success">
                <div class="stat-card-body">
                    <div class="stat-number">-<?= $total_poin_prestasi ?></div>
                    <div class="stat-label">Prestasi</div>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="stat-card bg-primary bg-opacity-10 text-primary">
                <div class="stat-card-body">
                    <div class="stat-number"><?= $total_poin ?></div>
                    <div class="stat-label">Total Poin</div>
                </div>
            </div>
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
                                <label class="form-label">NISN</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($siswa['nisn']) ?>" disabled readonly>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="nama_siswa" 
                                       value="<?= htmlspecialchars($siswa['nama_siswa']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kelas</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($kelas) ?>" disabled readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Jenis Kelamin</label>
                                <input type="text" class="form-control" value="<?= $siswa['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan' ?>" disabled readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tempat Lahir</label>
                                <input type="text" class="form-control" name="tempat_lahir" 
                                       value="<?= htmlspecialchars($siswa['tempat_lahir'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tanggal Lahir</label>
                                <input type="date" class="form-control" name="tanggal_lahir" 
                                       value="<?= $siswa['tanggal_lahir'] && $siswa['tanggal_lahir'] != '0000-00-00' ? $siswa['tanggal_lahir'] : '' ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Alamat</label>
                                <textarea class="form-control" name="alamat" rows="2"><?= htmlspecialchars($siswa['alamat'] ?? '') ?></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">No. HP</label>
                                <input type="text" class="form-control" name="no_hp" 
                                       value="<?= htmlspecialchars($siswa['no_hp'] ?? '') ?>" 
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
                            <td class="text-muted" style="font-size:12px;">NISN</td>
                            <td style="font-size:13px; font-weight:500;"><?= htmlspecialchars($siswa['nisn']) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted" style="font-size:12px;">Nama</td>
                            <td style="font-size:13px; font-weight:500;"><?= htmlspecialchars($siswa['nama_siswa']) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted" style="font-size:12px;">Kelas</td>
                            <td style="font-size:13px; font-weight:500;"><?= htmlspecialchars($kelas) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted" style="font-size:12px;">Total Poin</td>
                            <td style="font-size:13px; font-weight:500;">
                                <span class="badge bg-<?= $level['color'] ?>">
                                    <?= $total_poin ?> (<?= $level['level'] ?>)
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted" style="font-size:12px;">Poin Pelanggaran</td>
                            <td style="font-size:13px; font-weight:500; color:#dc3545;">+<?= $total_poin_pelanggaran ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted" style="font-size:12px;">Poin Prestasi</td>
                            <td style="font-size:13px; font-weight:500; color:#198754;">-<?= $total_poin_prestasi ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted" style="font-size:12px;">Total Pelanggaran</td>
                            <td style="font-size:13px; font-weight:500;"><?= $total_pelanggaran ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted" style="font-size:12px;">Total Prestasi</td>
                            <td style="font-size:13px; font-weight:500;"><?= $total_prestasi ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted" style="font-size:12px;">Total Konseling</td>
                            <td style="font-size:13px; font-weight:500;"><?= $total_konseling ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted" style="font-size:12px;">Bergabung</td>
                            <td style="font-size:13px; font-weight:500;"><?= date('d-m-Y', strtotime($siswa['created_at'])) ?></td>
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
   STYLE PROFIL SISWA
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

/* Stat Card */
.stat-card {
    padding: 8px 12px;
    border-radius: 6px;
    text-align: center;
}
.stat-card-body {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}
.stat-number {
    font-size: 1.3rem;
    font-weight: 700;
}
.stat-label {
    font-size: 0.65rem;
    text-transform: uppercase;
    letter-spacing: 0.3px;
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
    .stat-number {
        font-size: 1rem;
    }
    .stat-label {
        font-size: 0.55rem;
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

<?php include __DIR__ . '/../includes/footer.php'; ?>