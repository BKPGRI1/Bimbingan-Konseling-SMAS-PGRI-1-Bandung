<?php
// admin/pengaturan/profil.php
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

$page_title = 'Profil Sekolah';

// ============================================
// FUNGSI TAMBAHAN
// ============================================

// Buat tabel hari_libur jika belum ada - DIPERBAIKI
function createTableHariLibur() {
    $conn = getDB();
    
    // Cek apakah tabel sudah ada
    $check = $conn->query("SHOW TABLES LIKE 'hari_libur'");
    if ($check && $check->num_rows > 0) {
        // Cek apakah kolom 'hari' ada
        $check_col = $conn->query("SHOW COLUMNS FROM hari_libur LIKE 'hari'");
        if (!$check_col || $check_col->num_rows == 0) {
            // Tambahkan kolom 'hari'
            $conn->query("ALTER TABLE hari_libur ADD COLUMN `hari` varchar(20) DEFAULT NULL");
        }
        return true;
    }
    
    // Buat tabel baru
    $sql = "CREATE TABLE IF NOT EXISTS `hari_libur` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `tanggal` date DEFAULT NULL,
        `hari` varchar(20) DEFAULT NULL,
        `keterangan` varchar(255) DEFAULT NULL,
        `jenis` enum('otomatis_harian','otomatis_tanggal','custom') DEFAULT 'custom',
        `is_active` tinyint(1) DEFAULT 1,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;";
    $conn->query($sql);
    
    // Insert default hari libur minggu jika tabel kosong
    $check = $conn->query("SELECT COUNT(*) as total FROM hari_libur");
    if ($check) {
        $row = $check->fetch_assoc();
        if ($row['total'] == 0) {
            $conn->query("INSERT INTO hari_libur (hari, keterangan, jenis) VALUES ('Minggu', 'Libur Mingguan', 'otomatis_harian')");
        }
    }
    return true;
}

// Cek apakah tanggal libur
function isHariLibur($tanggal) {
    $conn = getDB();
    $hari = date('l', strtotime($tanggal));
    $hari_indonesia = [
        'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu',
        'Sunday' => 'Minggu'
    ];
    $hari_indo = $hari_indonesia[$hari] ?? $hari;
    
    // Cek hari libur otomatis (harian)
    $query = "SELECT * FROM hari_libur WHERE hari = '$hari_indo' AND is_active = 1 AND jenis = 'otomatis_harian'";
    $result = $conn->query($query);
    if ($result && $result->num_rows > 0) {
        return true;
    }
    
    // Cek hari libur otomatis (tanggal tertentu)
    $query = "SELECT * FROM hari_libur WHERE tanggal = '$tanggal' AND is_active = 1 AND jenis IN ('otomatis_tanggal', 'custom')";
    $result = $conn->query($query);
    if ($result && $result->num_rows > 0) {
        return true;
    }
    
    return false;
}

// Get semua hari libur
function getHariLibur() {
    $conn = getDB();
    
    // Cek apakah tabel hari_libur ada
    $check = $conn->query("SHOW TABLES LIKE 'hari_libur'");
    if (!$check || $check->num_rows == 0) {
        createTableHariLibur();
    }
    
    // Cek apakah kolom 'hari' ada
    $check_col = $conn->query("SHOW COLUMNS FROM hari_libur LIKE 'hari'");
    if (!$check_col || $check_col->num_rows == 0) {
        $conn->query("ALTER TABLE hari_libur ADD COLUMN `hari` varchar(20) DEFAULT NULL");
    }
    
    $query = "SELECT * FROM hari_libur ORDER BY 
              FIELD(jenis, 'otomatis_harian', 'otomatis_tanggal', 'custom'), 
              FIELD(hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'),
              tanggal ASC";
    
    $result = $conn->query($query);
    if ($result && $result->num_rows > 0) {
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }
    return [];
}

// Get hari libur otomatis (harian)
function getHariLiburHarian() {
    $conn = getDB();
    
    $check = $conn->query("SHOW TABLES LIKE 'hari_libur'");
    if (!$check || $check->num_rows == 0) {
        createTableHariLibur();
        return [];
    }
    
    $query = "SELECT * FROM hari_libur WHERE jenis = 'otomatis_harian' ORDER BY FIELD(hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu')";
    $result = $conn->query($query);
    if ($result && $result->num_rows > 0) {
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }
    return [];
}

// Get hari libur otomatis (tanggal tertentu)
function getHariLiburTanggal() {
    $conn = getDB();
    
    $check = $conn->query("SHOW TABLES LIKE 'hari_libur'");
    if (!$check || $check->num_rows == 0) {
        createTableHariLibur();
        return [];
    }
    
    $query = "SELECT * FROM hari_libur WHERE jenis = 'otomatis_tanggal' ORDER BY tanggal ASC";
    $result = $conn->query($query);
    if ($result && $result->num_rows > 0) {
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }
    return [];
}

// ============================================
// CREATE TABLE HARI LIBUR
// ============================================
createTableHariLibur();

// ============================================
// HANDLE POST
// ============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $conn = getDB();
    
    // Update Profil Sekolah
    if ($action == 'update') {
        $data = [
            'nama_sekolah' => sanitize($_POST['nama_sekolah']),
            'alamat' => sanitize($_POST['alamat']),
            'no_telp' => sanitize($_POST['no_telp']),
            'email' => sanitize($_POST['email']),
            'tahun_ajaran' => sanitize($_POST['tahun_ajaran'])
        ];
        
        if (updatePengaturan($data)) {
            $_SESSION['toast'] = [
                'message' => 'Profil sekolah berhasil diupdate!',
                'type' => 'success'
            ];
        } else {
            $_SESSION['toast'] = [
                'message' => 'Gagal mengupdate profil sekolah!',
                'type' => 'danger'
            ];
        }
        header("Location: " . APP_URL . "admin/pengaturan/profil.php");
        exit();
    }
    
    // Upload Logo
    if ($action == 'upload_logo') {
        if (!isset($_FILES['logo']) || $_FILES['logo']['error'] != UPLOAD_ERR_OK) {
            $error_msg = isset($_FILES['logo']) ? 'Error upload file' : 'Pilih file terlebih dahulu';
            $_SESSION['toast'] = [
                'message' => 'Gagal upload logo: ' . $error_msg,
                'type' => 'danger'
            ];
            header("Location: " . APP_URL . "admin/pengaturan/profil.php");
            exit();
        }
        
        $upload_dir = APP_PATH . 'uploads/logo/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'];
        $file_ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_ext, $allowed_types)) {
            $_SESSION['toast'] = [
                'message' => 'Format file tidak didukung! Gunakan: JPG, PNG, GIF, SVG, WEBP',
                'type' => 'danger'
            ];
            header("Location: " . APP_URL . "admin/pengaturan/profil.php");
            exit();
        }
        
        $pengaturan = getPengaturan();
        if (!empty($pengaturan['logo'])) {
            $old_file = $upload_dir . $pengaturan['logo'];
            if (file_exists($old_file)) {
                unlink($old_file);
            }
        }
        
        $new_filename = 'logo_' . date('Ymd_His') . '_' . uniqid() . '.' . $file_ext;
        $target_path = $upload_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $target_path)) {
            if (updatePengaturan(['logo' => $new_filename])) {
                $_SESSION['toast'] = [
                    'message' => 'Logo berhasil diupload!',
                    'type' => 'success'
                ];
            } else {
                if (file_exists($target_path)) unlink($target_path);
                $_SESSION['toast'] = [
                    'message' => 'Gagal menyimpan logo ke database!',
                    'type' => 'danger'
                ];
            }
        } else {
            $_SESSION['toast'] = [
                'message' => 'Gagal upload logo! Pastikan folder uploads/logo writable.',
                'type' => 'danger'
            ];
        }
        header("Location: " . APP_URL . "admin/pengaturan/profil.php");
        exit();
    }
    
    // Hapus Logo
    if ($action == 'delete_logo') {
        $pengaturan = getPengaturan();
        if (!empty($pengaturan['logo'])) {
            $upload_dir = APP_PATH . 'uploads/logo/';
            $old_file = $upload_dir . $pengaturan['logo'];
            if (file_exists($old_file)) unlink($old_file);
            updatePengaturan(['logo' => '']);
            $_SESSION['toast'] = [
                'message' => 'Logo berhasil dihapus!',
                'type' => 'success'
            ];
        }
        header("Location: " . APP_URL . "admin/pengaturan/profil.php");
        exit();
    }
    
    // ============================================
    // TAMBAH HARI LIBUR OTOMATIS (HARIAN)
    // ============================================
    if ($action == 'tambah_libur_harian') {
        $hari = isset($_POST['hari_libur']) ? sanitize($_POST['hari_libur']) : '';
        $keterangan = isset($_POST['keterangan_libur']) ? sanitize($_POST['keterangan_libur']) : 'Libur';
        
        if (empty($hari)) {
            $_SESSION['toast'] = [
                'message' => 'Hari libur harus dipilih!',
                'type' => 'warning'
            ];
        } else {
            // Cek apakah kolom 'hari' ada, jika tidak tambahkan
            $check_col = $conn->query("SHOW COLUMNS FROM hari_libur LIKE 'hari'");
            if (!$check_col || $check_col->num_rows == 0) {
                $conn->query("ALTER TABLE hari_libur ADD COLUMN `hari` varchar(20) DEFAULT NULL");
            }
            
            $check = $conn->query("SELECT id FROM hari_libur WHERE hari = '$hari' AND jenis = 'otomatis_harian'");
            if ($check && $check->num_rows > 0) {
                $_SESSION['toast'] = [
                    'message' => 'Hari ' . $hari . ' sudah terdaftar sebagai hari libur!',
                    'type' => 'danger'
                ];
            } else {
                $insert = "INSERT INTO hari_libur (hari, keterangan, jenis) VALUES ('$hari', '$keterangan', 'otomatis_harian')";
                if ($conn->query($insert)) {
                    $_SESSION['toast'] = [
                        'message' => 'Hari libur ' . $hari . ' berhasil ditambahkan!',
                        'type' => 'success'
                    ];
                } else {
                    $_SESSION['toast'] = [
                        'message' => 'Gagal menambahkan hari libur! Error: ' . $conn->error,
                        'type' => 'danger'
                    ];
                }
            }
        }
        header("Location: " . APP_URL . "admin/pengaturan/profil.php");
        exit();
    }
    
    // ============================================
    // TAMBAH HARI LIBUR OTOMATIS (TANGGAL TERTENTU)
    // ============================================
    if ($action == 'tambah_libur_tanggal') {
        $tanggal = sanitize($_POST['tanggal_libur']);
        $keterangan = sanitize($_POST['keterangan_libur_tanggal']);
        
        if (empty($tanggal)) {
            $_SESSION['toast'] = [
                'message' => 'Tanggal libur harus diisi!',
                'type' => 'warning'
            ];
        } else {
            $check = $conn->query("SELECT id FROM hari_libur WHERE tanggal = '$tanggal'");
            if ($check && $check->num_rows > 0) {
                $_SESSION['toast'] = [
                    'message' => 'Tanggal ' . date('d-m-Y', strtotime($tanggal)) . ' sudah terdaftar sebagai hari libur!',
                    'type' => 'danger'
                ];
            } else {
                $insert = "INSERT INTO hari_libur (tanggal, keterangan, jenis) VALUES ('$tanggal', '$keterangan', 'otomatis_tanggal')";
                if ($conn->query($insert)) {
                    $_SESSION['toast'] = [
                        'message' => 'Hari libur tanggal ' . date('d-m-Y', strtotime($tanggal)) . ' berhasil ditambahkan!',
                        'type' => 'success'
                    ];
                } else {
                    $_SESSION['toast'] = [
                        'message' => 'Gagal menambahkan hari libur! Error: ' . $conn->error,
                        'type' => 'danger'
                    ];
                }
            }
        }
        header("Location: " . APP_URL . "admin/pengaturan/profil.php");
        exit();
    }
    
    // ============================================
    // TAMBAH HARI LIBUR CUSTOM
    // ============================================
    if ($action == 'tambah_libur_custom') {
        $tanggal = sanitize($_POST['tanggal_libur_custom']);
        $keterangan = sanitize($_POST['keterangan_libur_custom']);
        
        if (empty($tanggal)) {
            $_SESSION['toast'] = [
                'message' => 'Tanggal libur harus diisi!',
                'type' => 'warning'
            ];
        } else {
            $check = $conn->query("SELECT id FROM hari_libur WHERE tanggal = '$tanggal'");
            if ($check && $check->num_rows > 0) {
                $_SESSION['toast'] = [
                    'message' => 'Tanggal ' . date('d-m-Y', strtotime($tanggal)) . ' sudah terdaftar sebagai hari libur!',
                    'type' => 'danger'
                ];
            } else {
                $insert = "INSERT INTO hari_libur (tanggal, keterangan, jenis) VALUES ('$tanggal', '$keterangan', 'custom')";
                if ($conn->query($insert)) {
                    $_SESSION['toast'] = [
                        'message' => 'Hari libur custom tanggal ' . date('d-m-Y', strtotime($tanggal)) . ' berhasil ditambahkan!',
                        'type' => 'success'
                    ];
                } else {
                    $_SESSION['toast'] = [
                        'message' => 'Gagal menambahkan hari libur! Error: ' . $conn->error,
                        'type' => 'danger'
                    ];
                }
            }
        }
        header("Location: " . APP_URL . "admin/pengaturan/profil.php");
        exit();
    }
    
    // ============================================
    // GENERATE LIBUR OTOMATIS (TANGGAL NASIONAL)
    // ============================================
    if ($action == 'generate_libur') {
        $tahun = isset($_POST['tahun_libur']) ? (int)$_POST['tahun_libur'] : date('Y');
        
        $libur_nasional = [
            ['date' => $tahun . '-01-01', 'name' => 'Tahun Baru Masehi'],
            ['date' => $tahun . '-01-28', 'name' => 'Imlek 2567'],
            ['date' => $tahun . '-03-29', 'name' => 'Hari Raya Nyepi'],
            ['date' => $tahun . '-04-18', 'name' => 'Wafat Yesus Kristus'],
            ['date' => $tahun . '-05-01', 'name' => 'Hari Buruh Internasional'],
            ['date' => $tahun . '-05-09', 'name' => 'Kenaikan Yesus Kristus'],
            ['date' => $tahun . '-06-01', 'name' => 'Hari Lahir Pancasila'],
            ['date' => $tahun . '-06-17', 'name' => 'Hari Raya Idul Adha'],
            ['date' => $tahun . '-08-17', 'name' => 'Hari Kemerdekaan RI'],
            ['date' => $tahun . '-12-25', 'name' => 'Hari Natal'],
        ];
        
        $added = 0;
        foreach ($libur_nasional as $libur) {
            $check = $conn->query("SELECT id FROM hari_libur WHERE tanggal = '{$libur['date']}'");
            if (!$check || $check->num_rows == 0) {
                $insert = "INSERT INTO hari_libur (tanggal, keterangan, jenis) VALUES ('{$libur['date']}', '{$libur['name']}', 'otomatis_tanggal')";
                if ($conn->query($insert)) {
                    $added++;
                }
            }
        }
        
        $_SESSION['toast'] = [
            'message' => 'Berhasil menambahkan ' . $added . ' hari libur nasional untuk tahun ' . $tahun . '!',
            'type' => 'success'
        ];
        header("Location: " . APP_URL . "admin/pengaturan/profil.php");
        exit();
    }
    
    // ============================================
    // HAPUS HARI LIBUR
    // ============================================
    if ($action == 'hapus_libur') {
        $id = isset($_POST['id_libur']) ? (int)$_POST['id_libur'] : 0;
        if ($id > 0) {
            $conn->query("DELETE FROM hari_libur WHERE id = $id");
            $_SESSION['toast'] = [
                'message' => 'Hari libur berhasil dihapus!',
                'type' => 'success'
            ];
        }
        header("Location: " . APP_URL . "admin/pengaturan/profil.php");
        exit();
    }
    
    // ============================================
    // TOGGLE STATUS LIBUR
    // ============================================
    if ($action == 'toggle_libur') {
        $id = isset($_POST['id_libur']) ? (int)$_POST['id_libur'] : 0;
        if ($id > 0) {
            $check = $conn->query("SELECT is_active FROM hari_libur WHERE id = $id");
            if ($check && $check->num_rows > 0) {
                $row = $check->fetch_assoc();
                $new_status = $row['is_active'] ? 0 : 1;
                $conn->query("UPDATE hari_libur SET is_active = $new_status WHERE id = $id");
                $_SESSION['toast'] = [
                    'message' => 'Status hari libur berhasil diubah!',
                    'type' => 'success'
                ];
            }
        }
        header("Location: " . APP_URL . "admin/pengaturan/profil.php");
        exit();
    }
}

// Get data
$pengaturan = getPengaturan();
$hari_libur = getHariLibur();
$hari_libur_harian = getHariLiburHarian();
$hari_libur_tanggal = getHariLiburTanggal();

$daftar_hari = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

include __DIR__ . '/../../includes/header.php';
?>

<!-- HTML sama seperti sebelumnya (tidak berubah) -->
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
        <h4 class="mb-0">
            <i class="fas fa-school text-primary me-2"></i> Profil Sekolah
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
        <!-- Form Profil Sekolah -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-edit text-primary me-2"></i> Data Sekolah
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Nama Sekolah <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="nama_sekolah" 
                                       value="<?= htmlspecialchars($pengaturan['nama_sekolah'] ?? '') ?>" 
                                       required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Alamat <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="alamat" rows="2" required><?= htmlspecialchars($pengaturan['alamat'] ?? '') ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">No. Telepon <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="no_telp" 
                                       value="<?= htmlspecialchars($pengaturan['no_telp'] ?? '') ?>" 
                                       required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" name="email" 
                                       value="<?= htmlspecialchars($pengaturan['email'] ?? '') ?>" 
                                       required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tahun Ajaran <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="tahun_ajaran" 
                                       value="<?= htmlspecialchars($pengaturan['tahun_ajaran'] ?? date('Y') . '/' . (date('Y') + 1)) ?>" 
                                       placeholder="2024/2025" required>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Simpan Perubahan
                                </button>
                                <a href="<?= APP_URL ?>admin/dashboard.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-1"></i> Kembali
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Preview Logo -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-image text-primary me-2"></i> Upload Logo
                    </h5>
                </div>
                <div class="card-body text-center">
                    <div class="logo-preview mb-3">
                        <?php if (!empty($pengaturan['logo']) && file_exists(APP_PATH . 'uploads/logo/' . $pengaturan['logo'])): ?>
                            <img src="<?= APP_URL ?>uploads/logo/<?= $pengaturan['logo'] ?>" 
                                 alt="Logo Sekolah" 
                                 class="img-fluid rounded" 
                                 style="max-height: 150px; border: 1px solid #e9ecef; padding: 10px; background:#fff;">
                            <div class="mt-2">
                                <span class="badge bg-success"><i class="fas fa-check me-1"></i> Logo aktif</span>
                                <br>
                                <small class="text-muted"><?= $pengaturan['logo'] ?></small>
                            </div>
                            <form method="POST" action="" class="mt-2" onsubmit="return confirm('Yakin ingin menghapus logo?')">
                                <input type="hidden" name="action" value="delete_logo">
                                <button type="submit" class="btn btn-danger btn-sm">
                                    <i class="fas fa-trash me-1"></i> Hapus Logo
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="bg-light rounded p-4">
                                <i class="fas fa-school fa-4x text-secondary opacity-25"></i>
                                <p class="text-muted mt-2">Belum ada logo</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <form method="POST" action="" enctype="multipart/form-data" id="formUploadLogo">
                        <input type="hidden" name="action" value="upload_logo">
                        <div class="mb-3">
                            <label class="form-label">Pilih File Logo</label>
                            <input type="file" class="form-control" name="logo" id="logoInput" accept="image/*" required>
                            <small class="text-muted">Format: JPG, PNG, GIF, SVG, WEBP (Max 2MB)</small>
                        </div>
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fas fa-upload me-1"></i> Upload Logo
                        </button>
                    </form>
                </div>
            </div>

            <!-- Informasi Singkat -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle text-info me-2"></i> Informasi
                    </h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="text-muted" style="font-size:12px;">Nama Sekolah</td>
                            <td style="font-size:13px; font-weight:500;"><?= htmlspecialchars($pengaturan['nama_sekolah'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted" style="font-size:12px;">Tahun Ajaran</td>
                            <td style="font-size:13px; font-weight:500;"><?= htmlspecialchars($pengaturan['tahun_ajaran'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted" style="font-size:12px;">Total Siswa</td>
                            <td style="font-size:13px; font-weight:500;"><?= getTotalSiswa() ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted" style="font-size:12px;">Total Guru</td>
                            <td style="font-size:13px; font-weight:500;"><?= getTotalGuru() ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted" style="font-size:12px;">Total Kelas</td>
                            <td style="font-size:13px; font-weight:500;"><?= getTotalKelas() ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- PENGATURAN HARI LIBUR -->
    <!-- ============================================ -->
    <div class="card mt-3">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-calendar-day text-warning me-2"></i> Pengaturan Hari Libur
            </h5>
        </div>
        <div class="card-body">
            <!-- Informasi -->
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Jenis Hari Libur:</strong>
                <ul class="mb-0 mt-1">
                    <li><span class="badge bg-primary">Otomatis Harian</span> - Hari tertentu dalam seminggu (contoh: Minggu)</li>
                    <li><span class="badge bg-success">Otomatis Tanggal</span> - Tanggal tertentu setiap tahun (contoh: 17 Agustus)</li>
                    <li><span class="badge bg-warning text-dark">Custom</span> - Tanggal khusus yang ditentukan manual</li>
                </ul>
            </div>

            <!-- ========================================== -->
            <!-- OTOMATIS HARIAN (Minggu, Senin, dll) -->
            <!-- ========================================== -->
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-header bg-primary text-white">
                            <i class="fas fa-calendar-week me-2"></i> Otomatis Harian
                            <small class="d-block text-white-50" style="font-size:10px;">Libur setiap hari tertentu</small>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="tambah_libur_harian">
                                <div class="mb-2">
                                    <label class="form-label">Pilih Hari</label>
                                    <select class="form-select" name="hari_libur" required>
                                        <option value="">-- Pilih Hari --</option>
                                        <?php 
                                        $existing_hari = [];
                                        foreach ($hari_libur_harian as $l) {
                                            $existing_hari[] = $l['hari'];
                                        }
                                        foreach ($daftar_hari as $h): 
                                            $disabled = in_array($h, $existing_hari) ? 'disabled' : '';
                                        ?>
                                            <option value="<?= $h ?>" <?= $disabled ?>>
                                                <?= $h ?> <?= $disabled ? '(Sudah ada)' : '' ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Keterangan</label>
                                    <input type="text" class="form-control" name="keterangan_libur" placeholder="Libur Mingguan" value="Libur" required>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-plus me-1"></i> Tambah
                                </button>
                            </form>
                            <hr>
                            <div class="mt-2">
                                <small class="text-muted">Hari libur aktif:</small>
                                <div class="d-flex flex-wrap gap-1 mt-1">
                                    <?php if (count($hari_libur_harian) > 0): ?>
                                        <?php foreach ($hari_libur_harian as $libur): ?>
                                            <span class="badge bg-<?= $libur['is_active'] ? 'primary' : 'secondary' ?>">
                                                <?= $libur['hari'] ?>
                                                <?php if (!$libur['is_active']): ?> (nonaktif)<?php endif; ?>
                                            </span>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="text-muted">Belum ada</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- OTOMATIS TANGGAL (17 Agustus, dll) -->
                <!-- ========================================== -->
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-header bg-success text-white">
                            <i class="fas fa-calendar-alt me-2"></i> Otomatis Tanggal
                            <small class="d-block text-white-50" style="font-size:10px;">Libur tanggal tertentu</small>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="tambah_libur_tanggal">
                                <div class="mb-2">
                                    <label class="form-label">Tanggal Libur</label>
                                    <input type="date" class="form-control" name="tanggal_libur" required>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Keterangan</label>
                                    <input type="text" class="form-control" name="keterangan_libur_tanggal" placeholder="Libur Nasional..." required>
                                </div>
                                <button type="submit" class="btn btn-success w-100">
                                    <i class="fas fa-plus me-1"></i> Tambah
                                </button>
                            </form>
                            <hr>
                            <div class="mt-2">
                                <form method="POST" action="" class="row g-1">
                                    <input type="hidden" name="action" value="generate_libur">
                                    <div class="col-7">
                                        <select class="form-select form-select-sm" name="tahun_libur">
                                            <?php for ($i = date('Y') - 1; $i <= date('Y') + 1; $i++): ?>
                                                <option value="<?= $i ?>" <?= $i == date('Y') ? 'selected' : '' ?>><?= $i ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                    <div class="col-5">
                                        <button type="submit" class="btn btn-sm btn-info w-100">
                                            <i class="fas fa-magic me-1"></i> Generate
                                        </button>
                                    </div>
                                    <div class="col-12">
                                        <small class="text-muted">Generate libur nasional (17 Agustus, Natal, dll)</small>
                                    </div>
                                </form>
                            </div>
                            <div class="mt-2">
                                <small class="text-muted">Libur tanggal aktif:</small>
                                <div class="d-flex flex-wrap gap-1 mt-1">
                                    <?php if (count($hari_libur_tanggal) > 0): ?>
                                        <?php foreach ($hari_libur_tanggal as $libur): ?>
                                            <span class="badge bg-<?= $libur['is_active'] ? 'success' : 'secondary' ?>" style="font-size:8px;">
                                                <?= date('d-m-Y', strtotime($libur['tanggal'])) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="text-muted">Belum ada</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- CUSTOM (Tanggal Khusus) -->
                <!-- ========================================== -->
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-header bg-warning text-dark">
                            <i class="fas fa-pen me-2"></i> Custom
                            <small class="d-block text-dark-50" style="font-size:10px;">Libur tambahan manual</small>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="tambah_libur_custom">
                                <div class="mb-2">
                                    <label class="form-label">Tanggal Libur</label>
                                    <input type="date" class="form-control" name="tanggal_libur_custom" required>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Keterangan</label>
                                    <input type="text" class="form-control" name="keterangan_libur_custom" placeholder="Libur Khusus..." required>
                                </div>
                                <button type="submit" class="btn btn-warning w-100">
                                    <i class="fas fa-plus me-1"></i> Tambah
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabel Semua Hari Libur -->
            <div class="table-responsive mt-3">
                <h6 class="mb-2"><i class="fas fa-list me-2"></i> Daftar Semua Hari Libur</h6>
                <table class="table table-bordered table-hover table-striped" style="font-size:13px;">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Jenis</th>
                            <th>Hari / Tanggal</th>
                            <th>Keterangan</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($hari_libur) > 0): ?>
                            <?php $no = 1; ?>
                            <?php foreach ($hari_libur as $libur): 
                                $label = '';
                                $badge = '';
                                if ($libur['jenis'] == 'otomatis_harian') {
                                    $label = 'Harian';
                                    $badge = 'bg-primary';
                                    $display = $libur['hari'];
                                } elseif ($libur['jenis'] == 'otomatis_tanggal') {
                                    $label = 'Otomatis Tanggal';
                                    $badge = 'bg-success';
                                    $display = date('d-m-Y', strtotime($libur['tanggal']));
                                } else {
                                    $label = 'Custom';
                                    $badge = 'bg-warning text-dark';
                                    $display = date('d-m-Y', strtotime($libur['tanggal']));
                                }
                            ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><span class="badge <?= $badge ?>"><?= $label ?></span></td>
                                    <td><strong><?= $display ?></strong></td>
                                    <td><?= htmlspecialchars($libur['keterangan']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $libur['is_active'] ? 'success' : 'secondary' ?>">
                                            <?= $libur['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <form method="POST" action="" style="display:inline;">
                                                <input type="hidden" name="action" value="toggle_libur">
                                                <input type="hidden" name="id_libur" value="<?= $libur['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-<?= $libur['is_active'] ? 'warning' : 'success' ?>" title="Toggle Status">
                                                    <i class="fas fa-<?= $libur['is_active'] ? 'pause' : 'play' ?>"></i>
                                                </button>
                                            </form>
                                            <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Yakin ingin menghapus hari libur ini?')">
                                                <input type="hidden" name="action" value="hapus_libur">
                                                <input type="hidden" name="id_libur" value="<?= $libur['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-3">
                                    <i class="fas fa-calendar-day me-1"></i> Belum ada hari libur
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer text-muted" style="font-size:11px;">
            <i class="fas fa-info-circle me-1"></i> 
            <span class="badge bg-primary">Harian</span> = Libur setiap hari tertentu (Minggu, Senin, dll) | 
            <span class="badge bg-success">Otomatis Tanggal</span> = Libur tanggal tertentu (17 Agustus, dll) | 
            <span class="badge bg-warning text-dark">Custom</span> = Libur tambahan manual
        </div>
    </div>
</div>

<script>
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

// Preview logo sebelum upload
document.getElementById('logoInput')?.addEventListener('change', function(e) {
    var file = this.files[0];
    if (file) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var preview = document.querySelector('.logo-preview img');
            var container = document.querySelector('.logo-preview');
            if (preview) {
                preview.src = e.target.result;
            } else {
                container.innerHTML = '<img src="' + e.target.result + '" class="img-fluid rounded" style="max-height:150px; border:1px solid #e9ecef; padding:10px; background:#fff;">';
            }
        };
        reader.readAsDataURL(file);
    }
});
</script>

<style>
/* Toast */
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
.card-footer {
    background: transparent;
    border-top: 1px solid #e9ecef;
    padding: 8px 16px;
}

/* Logo Preview */
.logo-preview {
    min-height: 120px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

/* Table */
.table th {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}
.table td {
    vertical-align: middle;
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

<?php include __DIR__ . '/../../includes/footer.php'; ?>