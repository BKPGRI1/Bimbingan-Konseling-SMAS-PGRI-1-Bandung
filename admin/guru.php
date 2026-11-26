<?php
// admin/guru.php
include '../includes/config.php';
checkLogin();

$page_title = 'Kelola Guru';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if ($action == 'tambah') {
        // Cek username sudah ada
        $username = sanitize($_POST['username']);
        $cek = getRow("SELECT id FROM users WHERE username = '$username'");
        if ($cek) {
            $_SESSION['toast'] = [
                'message' => 'Username "' . htmlspecialchars($username) . '" sudah terdaftar!',
                'type' => 'danger'
            ];
            header("Location: " . APP_URL . "admin/guru.php");
            exit();
        }
        
        // Generate password jika kosong
        $password = !empty($_POST['password']) ? $_POST['password'] : generateRandomPassword(8);
        
        $data = [
            'username' => $username,
            'password' => $password, // Akan di-hash di fungsi tambahGuru
            'nama_lengkap' => sanitize($_POST['nama_lengkap']),
            'nip' => sanitize($_POST['nip']),
            'role' => sanitize($_POST['role']),
            'jabatan' => sanitize($_POST['jabatan']),
            'no_hp' => sanitize($_POST['no_hp'])
        ];
        
        // Handle foto upload
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] == UPLOAD_ERR_OK) {
            $upload_dir = APP_PATH . 'uploads/guru/';
            $foto = uploadFile($_FILES['foto'], $upload_dir, ['jpg', 'jpeg', 'png', 'gif']);
            if ($foto) {
                $data['foto'] = $foto;
            }
        }
        
        // Jika role walikelas, set is_walikelas = 1
        if ($data['role'] == 'walikelas') {
            $data['is_walikelas'] = 1;
        }
        
        // Hash password sebelum insert
        if (isset($data['password'])) {
            $data['password'] = md5($data['password']);
        }
        
        $result = insert('users', $data);
        
        if ($result) {
            // Jika role walikelas, assign ke kelas yang kosong
            if ($data['role'] == 'walikelas') {
                $kelas_kosong = getRow("SELECT id, nama_kelas FROM kelas WHERE wali_kelas_id IS NULL LIMIT 1");
                if ($kelas_kosong) {
                    update('kelas', ['wali_kelas_id' => $result], "id = " . $kelas_kosong['id']);
                    $_SESSION['toast'] = [
                        'message' => 'Guru berhasil ditambahkan!<br>Password: <strong>' . $password . '</strong><br>Ditugaskan sebagai wali kelas: ' . $kelas_kosong['nama_kelas'],
                        'type' => 'success'
                    ];
                } else {
                    $_SESSION['toast'] = [
                        'message' => 'Guru berhasil ditambahkan!<br>Password: <strong>' . $password . '</strong><br>(Semua kelas sudah memiliki wali kelas)',
                        'type' => 'success'
                    ];
                }
            } else {
                $_SESSION['toast'] = [
                    'message' => 'Guru berhasil ditambahkan!<br>Password: <strong>' . $password . '</strong>',
                    'type' => 'success'
                ];
            }
        } else {
            $_SESSION['toast'] = [
                'message' => 'Gagal menambahkan guru!',
                'type' => 'danger'
            ];
        }
        header("Location: " . APP_URL . "admin/guru.php");
        exit();
    }
    
    if ($action == 'edit') {
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
        
        // Handle password change
        if (!empty($_POST['password'])) {
            $data['password'] = md5($_POST['password']);
        }
        
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
            
            // Jika role berubah menjadi walikelas
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
            // Jika role berubah dari walikelas ke yang lain
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
    
    if ($action == 'sync_wali_kelas') {
        // SINKRONISASI SEMUA USER: Cek siapa yang menjadi wali kelas di tabel kelas
        $wali_kelas_list = getRows("SELECT DISTINCT wali_kelas_id FROM kelas WHERE wali_kelas_id IS NOT NULL");
        $wali_ids = array_column($wali_kelas_list, 'wali_kelas_id');
        
        // Update semua user yang menjadi wali kelas
        foreach ($wali_ids as $wali_id) {
            update('users', ['role' => 'walikelas', 'is_walikelas' => 1], "id = $wali_id");
        }
        
        // Update user yang tidak menjadi wali kelas (kecuali admin)
        // Hati-hati jangan ubah admin
        if (!empty($wali_ids)) {
            $non_wali = getRows("SELECT id FROM users WHERE role = 'walikelas' AND id NOT IN (" . implode(',', $wali_ids) . ") AND id != 1");
        } else {
            $non_wali = getRows("SELECT id FROM users WHERE role = 'walikelas' AND id != 1");
        }
        foreach ($non_wali as $user) {
            update('users', ['role' => 'guru', 'is_walikelas' => 0], "id = " . $user['id']);
        }
        
        $_SESSION['toast'] = [
            'message' => 'Sinkronisasi role wali kelas berhasil!',
            'type' => 'success'
        ];
        header("Location: " . APP_URL . "admin/guru.php");
        exit();
    }
    
    if ($action == 'reset_password') {
        $id = (int)$_POST['id'];
        $new_password = resetPasswordGuru($id);
        if ($new_password) {
            $_SESSION['toast'] = [
                'message' => 'Password berhasil direset! Password baru: <strong>' . $new_password . '</strong>',
                'type' => 'success'
            ];
        } else {
            $_SESSION['toast'] = [
                'message' => 'Gagal mereset password!',
                'type' => 'danger'
            ];
        }
        header("Location: " . APP_URL . "admin/guru.php");
        exit();
    }
    
    if ($action == 'import') {
        if (isset($_FILES['file_csv']) && $_FILES['file_csv']['error'] == 0) {
            $result = importGuruCSV($_FILES['file_csv']);
            $msg = "Import selesai! {$result['success']} berhasil, {$result['failed']} gagal.";
            if (!empty($result['errors'])) {
                $msg .= " " . implode('; ', array_slice($result['errors'], 0, 3));
                if (count($result['errors']) > 3) {
                    $msg .= " ... dan " . (count($result['errors']) - 3) . " error lainnya";
                }
            }
            $_SESSION['toast'] = [
                'message' => $msg,
                'type' => $result['failed'] > 0 ? 'warning' : 'success'
            ];
        } else {
            $_SESSION['toast'] = [
                'message' => 'Gagal upload file CSV!',
                'type' => 'danger'
            ];
        }
        header("Location: " . APP_URL . "admin/guru.php");
        exit();
    }
}

// Handle GET actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($action == 'delete' && $id > 0) {
        // Cek apakah guru adalah wali kelas
        $cek_wali = getRow("SELECT id FROM kelas WHERE wali_kelas_id = $id");
        if ($cek_wali) {
            $_SESSION['toast'] = [
                'message' => 'Guru tidak bisa dihapus karena masih menjadi wali kelas!',
                'type' => 'danger'
            ];
            header("Location: " . APP_URL . "admin/guru.php");
            exit();
        }
        
        // Delete foto
        $guru = getGuruById($id);
        if ($guru && !empty($guru['foto'])) {
            $foto_file = APP_PATH . 'uploads/guru/' . $guru['foto'];
            if (file_exists($foto_file)) {
                unlink($foto_file);
            }
        }
        
        if (hapusGuru($id)) {
            $_SESSION['toast'] = [
                'message' => 'Guru berhasil dihapus!',
                'type' => 'success'
            ];
        } else {
            $_SESSION['toast'] = [
                'message' => 'Gagal menghapus guru!',
                'type' => 'danger'
            ];
        }
        header("Location: " . APP_URL . "admin/guru.php");
        exit();
    }
}

// Get data
$guru_list = getGuru();

// Sinkronisasi otomatis saat halaman dimuat
// Cek apakah ada user dengan role walikelas tapi tidak menjadi wali kelas di tabel kelas
$sync_needed = false;
foreach ($guru_list as $g) {
    if ($g['role'] == 'walikelas') {
        $cek = getRow("SELECT id FROM kelas WHERE wali_kelas_id = " . $g['id']);
        if (!$cek) {
            $sync_needed = true;
            break;
        }
    }
}

include '../includes/header.php';
?>

<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
        <h4 class="mb-0">
            <i class="fas fa-chalkboard-teacher text-primary me-2"></i> Manajemen Guru
        </h4>
        <div class="d-flex gap-2 flex-wrap">
            <button class="btn btn-info btn-sm" onclick="syncWaliKelas()">
                <i class="fas fa-sync me-1"></i> Sinkron Role
            </button>
            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalImport">
                <i class="fas fa-file-import me-1"></i> Import CSV
            </button>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalGuru">
                <i class="fas fa-plus me-1"></i> Tambah Guru
            </button>
        </div>
    </div>

    <?php if ($sync_needed): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Terdapat ketidaksesuaian role wali kelas. Klik tombol <strong>Sinkron Role</strong> untuk memperbaiki.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Toast Notification -->
    <?php if (isset($_SESSION['toast'])): ?>
        <div id="toastNotification" class="toast-container position-fixed top-0 end-0 p-2" style="z-index: 9999;">
            <div class="toast align-items-center text-white bg-<?= $_SESSION['toast']['type'] ?> border-0 show" role="alert" aria-live="assertive" aria-atomic="true" style="min-width: 280px; max-width: 380px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.12); font-size: 13px;">
                <div class="d-flex align-items-center p-2">
                    <div class="flex-shrink-0 ms-1">
                        <i class="fas fa-<?= $_SESSION['toast']['type'] == 'success' ? 'check-circle' : ($_SESSION['toast']['type'] == 'warning' ? 'exclamation-triangle' : 'times-circle') ?> fa-sm"></i>
                    </div>
                    <div class="toast-body flex-grow-1 text-start ms-2" style="font-size: 13px; font-weight: 500; padding: 2px 0; line-height: 1.4;">
                        <?= nl2br($_SESSION['toast']['message']) ?>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-0" data-bs-dismiss="toast" aria-label="Close" style="font-size: 10px; padding: 4px;"></button>
                </div>
            </div>
        </div>
        <?php unset($_SESSION['toast']); ?>
    <?php endif; ?>

    <!-- Search -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="" class="row g-2">
                <div class="col-md-9">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" name="search" 
                               placeholder="Cari Nama, NIP atau Username..." 
                               value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-1"></i> Cari
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Guru -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="45" class="text-center">No</th>
                            <th width="50" class="text-center">Foto</th>
                            <th>NIP</th>
                            <th>Nama Lengkap</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Jabatan</th>
                            <th class="text-center" width="180">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($guru_list)): ?>
                            <?php 
                            $no = 1; 
                            $search = isset($_GET['search']) ? strtolower($_GET['search']) : '';
                            $filtered = 0;
                            
                            foreach ($guru_list as $guru): 
                                // Filter
                                $match = true;
                                if (!empty($search)) {
                                    $nama = strtolower($guru['nama_lengkap']);
                                    $nip = strtolower($guru['nip'] ?? '');
                                    $username = strtolower($guru['username']);
                                    if (!strpos($nama, $search) && !strpos($nip, $search) && !strpos($username, $search)) {
                                        $match = false;
                                    }
                                }
                                if (!$match) continue;
                                $filtered++;
                                
                                // Cek apakah guru menjadi wali kelas di tabel kelas
                                $is_wali = getRow("SELECT id, nama_kelas FROM kelas WHERE wali_kelas_id = " . $guru['id']);
                                
                                // Tentukan role yang ditampilkan
                                $display_role = $guru['role'];
                                $role_label = [
                                    'admin' => 'Admin',
                                    'guru' => 'Guru',
                                    'bk' => 'BK',
                                    'walikelas' => 'Wali Kelas'
                                ];
                                
                                $role_badge = [
                                    'admin' => 'danger',
                                    'guru' => 'info',
                                    'bk' => 'warning',
                                    'walikelas' => 'success'
                                ];
                                
                                // Jika role di tabel users adalah guru tapi menjadi wali kelas di tabel kelas
                                // Tampilkan sebagai Wali Kelas
                                if ($guru['role'] != 'walikelas' && $is_wali) {
                                    $display_role = 'walikelas';
                                }
                            ?>
                                <tr>
                                    <td class="text-center"><?= $filtered ?></td>
                                    <td class="text-center">
                                        <?php if (!empty($guru['foto']) && file_exists(APP_PATH . 'uploads/guru/' . $guru['foto'])): ?>
                                            <img src="<?= APP_URL ?>uploads/guru/<?= $guru['foto'] ?>" 
                                                 alt="Foto" width="32" height="32" 
                                                 style="object-fit: cover; border-radius: 50%;">
                                        <?php else: ?>
                                            <div class="avatar-placeholder" style="width:32px;height:32px;border-radius:50%;background:#e9ecef;display:inline-flex;align-items:center;justify-content:center;color:#6c757d;font-weight:600;font-size:12px;">
                                                <?= strtoupper(substr($guru['nama_lengkap'], 0, 1)) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($guru['nip'] ?? '-') ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($guru['nama_lengkap']) ?></strong>
                                        <?php if ($is_wali): ?>
                                            <span class="badge bg-success ms-1" style="font-size: 9px;">
                                                <i class="fas fa-user-check me-1"></i> Wali Kelas <?= htmlspecialchars($is_wali['nama_kelas']) ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($guru['role'] != $display_role): ?>
                                            <span class="badge bg-warning text-dark ms-1" style="font-size: 9px;">
                                                <i class="fas fa-sync me-1"></i> Sinkronisasi diperlukan
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge bg-light text-dark" style="font-size: 10px;"><?= htmlspecialchars($guru['username']) ?></span></td>
                                    <td>
                                        <span class="badge bg-<?= $role_badge[$display_role] ?? 'secondary' ?>" style="font-size: 10px;">
                                            <?= $role_label[$display_role] ?? $display_role ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($guru['jabatan'] ?? '-') ?></td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="<?= APP_URL ?>admin/guru_edit.php?id=<?= $guru['id'] ?>" class="btn btn-outline-primary" title="Edit" style="padding: 2px 6px; font-size: 11px;">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button class="btn btn-outline-warning" onclick="resetPassword(<?= $guru['id'] ?>)" title="Reset Password" style="padding: 2px 6px; font-size: 11px;">
                                                <i class="fas fa-key"></i>
                                            </button>
                                            <a href="<?= APP_URL ?>admin/guru.php?action=delete&id=<?= $guru['id'] ?>" 
                                               class="btn btn-outline-danger" 
                                               onclick="return confirm('Apakah Anda yakin ingin menghapus guru ini?\\n\\nGuru yang menjadi wali kelas tidak bisa dihapus!')" 
                                               title="Hapus" style="padding: 2px 6px; font-size: 11px;">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if ($filtered == 0): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="fas fa-search fa-2x mb-2 d-block opacity-25"></i>
                                        Tidak ada data yang sesuai dengan pencarian
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    <i class="fas fa-chalkboard-teacher fa-2x mb-2 d-block opacity-25"></i>
                                    Belum ada data guru
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Guru -->
<div class="modal fade" id="modalGuru" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus text-primary me-2"></i> Tambah Guru
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="action" value="tambah">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="username" placeholder="Masukkan username" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password</label>
                            <input type="text" class="form-control" name="password" placeholder="Kosongkan untuk auto generate">
                            <small class="text-muted">Biarkan kosong untuk generate otomatis</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nama_lengkap" placeholder="Masukkan nama lengkap" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">NIP</label>
                            <input type="text" class="form-control" name="nip" placeholder="Masukkan NIP">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Role <span class="text-danger">*</span></label>
                            <select class="form-select" name="role" required>
                                <option value="">Pilih Role</option>
                                <option value="guru">Guru</option>
                                <option value="bk">BK</option>
                                <option value="walikelas">Wali Kelas</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Jabatan</label>
                            <input type="text" class="form-control" name="jabatan" placeholder="Masukkan jabatan">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">No. HP</label>
                            <input type="text" class="form-control" name="no_hp" placeholder="No. HP">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Foto</label>
                            <input type="file" class="form-control" name="foto" accept="image/*">
                            <small class="text-muted">Format: JPG, PNG, GIF (Max 2MB)</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Import CSV -->
<div class="modal fade" id="modalImport" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-file-import text-success me-2"></i> Import Guru CSV
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="action" value="import">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Format CSV (pemisah ;):</strong><br>
                        Username;Password;Nama Lengkap;NIP;Role;Jabatan;No HP
                    </div>
                    <div class="mb-3">
                        <label class="form-label">File CSV <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" name="file_csv" accept=".csv" required>
                    </div>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Catatan:</strong>
                        <ul class="mb-0 mt-1">
                            <li>File harus berekstensi .csv</li>
                            <li>Gunakan pemisah <strong>;</strong> (titik koma)</li>
                            <li>Role: guru, bk, walikelas, admin</li>
                            <li>Password akan di-hash MD5</li>
                        </ul>
                    </div>
                    <div class="mt-3">
                        <a href="<?= APP_URL ?>assets/sample/download_guru_sample.php" class="btn btn-sm btn-outline-primary" target="_blank">
                            <i class="fas fa-download me-1"></i> Download Sample CSV
                        </a>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-upload me-1"></i> Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Reset Password -->
<div class="modal fade" id="modalResetPassword" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-warning">
                    <i class="fas fa-key text-warning me-2"></i> Reset Password
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="id" id="resetGuruId" value="">
                <div class="modal-body">
                    <div class="text-center py-3">
                        <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3 d-block"></i>
                        <h5>Reset Password Guru</h5>
                        <p class="text-muted">
                            Password akan direset menjadi password acak baru.
                            Password baru akan ditampilkan setelah proses selesai.
                        </p>
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle me-2"></i>
                            Pastikan Anda mencatat password baru yang akan diberikan.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-sync-alt me-1"></i> Reset Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Reset Password
function resetPassword(id) {
    document.getElementById('resetGuruId').value = id;
    var modal = new bootstrap.Modal(document.getElementById('modalResetPassword'));
    modal.show();
}

// Sinkron Role Wali Kelas
function syncWaliKelas() {
    if (confirm('Sinkronisasi akan menyamakan role "Wali Kelas" dengan data di tabel kelas. Lanjutkan?')) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '';
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'action';
        input.value = 'sync_wali_kelas';
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
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
        }, 3500);
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
    
    .btn-group-sm .btn {
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
}
</style>

<?php include '../includes/footer.php'; ?>