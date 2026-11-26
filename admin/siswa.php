<?php
// admin/siswa.php
include '../includes/config.php';
checkLogin();

$page_title = 'Kelola Siswa';
$conn = getDB();

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if ($action == 'tambah') {
        $data = [
            'nisn' => sanitize($_POST['nisn']),
            'nama_siswa' => sanitize($_POST['nama_siswa']),
            'kelas_id' => !empty($_POST['kelas_id']) ? (int)$_POST['kelas_id'] : null,
            'jenis_kelamin' => sanitize($_POST['jenis_kelamin']),
            'tempat_lahir' => sanitize($_POST['tempat_lahir']),
            'tanggal_lahir' => sanitize($_POST['tanggal_lahir']),
            'alamat' => sanitize($_POST['alamat']),
            'no_hp' => sanitize($_POST['no_hp'])
        ];
        
        // Handle foto upload
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
            $foto = uploadFile($_FILES['foto'], APP_PATH . 'uploads/siswa/');
            if ($foto) {
                $data['foto'] = $foto;
            }
        }
        
        if (tambahSiswa($data)) {
            $_SESSION['toast'] = [
                'message' => 'Siswa berhasil ditambahkan!',
                'type' => 'success'
            ];
        } else {
            $_SESSION['toast'] = [
                'message' => 'Gagal menambahkan siswa! NISN mungkin sudah terdaftar.',
                'type' => 'danger'
            ];
        }
        header("Location: " . APP_URL . "admin/siswa.php");
        exit();
    }
    
    if ($action == 'edit') {
        $id = (int)$_POST['id'];
        $data = [
            'nisn' => sanitize($_POST['nisn']),
            'nama_siswa' => sanitize($_POST['nama_siswa']),
            'kelas_id' => !empty($_POST['kelas_id']) ? (int)$_POST['kelas_id'] : null,
            'jenis_kelamin' => sanitize($_POST['jenis_kelamin']),
            'tempat_lahir' => sanitize($_POST['tempat_lahir']),
            'tanggal_lahir' => sanitize($_POST['tanggal_lahir']),
            'alamat' => sanitize($_POST['alamat']),
            'no_hp' => sanitize($_POST['no_hp'])
        ];
        
        // Handle foto upload
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
            $foto = uploadFile($_FILES['foto'], APP_PATH . 'uploads/siswa/');
            if ($foto) {
                // Delete old foto
                $old = getSiswaById($id);
                if ($old && !empty($old['foto'])) {
                    $old_file = APP_PATH . 'uploads/siswa/' . $old['foto'];
                    if (file_exists($old_file)) {
                        unlink($old_file);
                    }
                }
                $data['foto'] = $foto;
            }
        }
        
        if (updateSiswa($data, $id)) {
            $_SESSION['toast'] = [
                'message' => 'Siswa berhasil diupdate!',
                'type' => 'success'
            ];
        } else {
            $_SESSION['toast'] = [
                'message' => 'Gagal mengupdate siswa!',
                'type' => 'danger'
            ];
        }
        header("Location: " . APP_URL . "admin/siswa.php");
        exit();
    }
    
    if ($action == 'reset_password') {
        $id = (int)$_POST['id'];
        $siswa = getSiswaById($id);
        
        if ($siswa) {
            $nisn = $siswa['nisn'];
            $new_password = $nisn; // Password default = NISN
            
            // Update password siswa di tabel users
            $conn = getDB();
            $hashed = md5($new_password);
            $query = "UPDATE users SET password = '$hashed' WHERE username = '$nisn' AND role = 'siswa'";
            
            // Jika belum ada user, buat baru
            if ($conn->query($query) && $conn->affected_rows == 0) {
                $insert = "INSERT INTO users (username, password, nama_lengkap, role, nip) 
                           VALUES ('$nisn', '$hashed', '{$siswa['nama_siswa']}', 'siswa', '$nisn')";
                $conn->query($insert);
            }
            
            $_SESSION['toast'] = [
                'message' => 'Password siswa berhasil direset!<br>Username: <strong>' . $nisn . '</strong><br>Password: <strong>' . $new_password . '</strong>',
                'type' => 'success'
            ];
        } else {
            $_SESSION['toast'] = [
                'message' => 'Siswa tidak ditemukan!',
                'type' => 'danger'
            ];
        }
        header("Location: " . APP_URL . "admin/siswa.php");
        exit();
    }
    
    if ($action == 'import') {
        if (isset($_FILES['file_csv']) && $_FILES['file_csv']['error'] == 0) {
            $result = importSiswaCSV($_FILES['file_csv']);
            
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
        header("Location: " . APP_URL . "admin/siswa.php");
        exit();
    }
}

// Handle GET actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($action == 'delete' && $id > 0) {
        // Delete foto
        $siswa = getSiswaById($id);
        if ($siswa && !empty($siswa['foto'])) {
            $foto_file = APP_PATH . 'uploads/siswa/' . $siswa['foto'];
            if (file_exists($foto_file)) {
                unlink($foto_file);
            }
        }
        
        // Delete user siswa
        $conn = getDB();
        $nisn = $siswa['nisn'] ?? '';
        if (!empty($nisn)) {
            $conn->query("DELETE FROM users WHERE username = '$nisn' AND role = 'siswa'");
        }
        
        if (hapusSiswa($id)) {
            $_SESSION['toast'] = [
                'message' => 'Siswa berhasil dihapus!',
                'type' => 'success'
            ];
        } else {
            $_SESSION['toast'] = [
                'message' => 'Siswa tidak bisa dihapus karena memiliki riwayat pelanggaran!',
                'type' => 'danger'
            ];
        }
        header("Location: " . APP_URL . "admin/siswa.php");
        exit();
    }
}

// ============================================
// AMBIL DATA SISWA DENGAN POIN BENAR
// ============================================
$siswa_list = getSiswa();

// Hitung poin akhir per siswa
foreach ($siswa_list as &$siswa) {
    // Total poin pelanggaran
    $q_pel = $conn->query("SELECT SUM(poin_diperoleh) as total FROM pelanggaran WHERE siswa_id = {$siswa['id']} AND status = 'disetujui'");
    $pel = $q_pel->fetch_assoc();
    $poin_pel = (int)$pel['total'];
    
    // Total poin prestasi
    $q_pres = $conn->query("SELECT SUM(poin_prestasi) as total FROM prestasi_siswa WHERE siswa_id = {$siswa['id']}");
    $pres = $q_pres->fetch_assoc();
    $poin_pres = (int)$pres['total'];
    
    // Poin akhir = pelanggaran - prestasi
    $poin_akhir = $poin_pel - $poin_pres;
    if ($poin_akhir < 0) $poin_akhir = 0;
    
    $siswa['poin_akhir'] = $poin_akhir;
    $siswa['poin_pelanggaran'] = $poin_pel;
    $siswa['poin_prestasi'] = $poin_pres;
    $siswa['total_pelanggaran'] = $conn->query("SELECT COUNT(*) as total FROM pelanggaran WHERE siswa_id = {$siswa['id']} AND status = 'disetujui'")->fetch_assoc()['total'] ?? 0;
    $siswa['total_prestasi'] = $conn->query("SELECT COUNT(*) as total FROM prestasi_siswa WHERE siswa_id = {$siswa['id']}")->fetch_assoc()['total'] ?? 0;
}

$kelas_list = getKelasOptions();

include '../includes/header.php';
?>

<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
        <h4 class="mb-0">
            <i class="fas fa-user-graduate text-primary me-2"></i> Manajemen Siswa
        </h4>
        <div class="d-flex gap-2 flex-wrap">
            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalImport">
                <i class="fas fa-file-import me-1"></i> Import CSV
            </button>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalSiswa">
                <i class="fas fa-plus me-1"></i> Tambah Siswa
            </button>
        </div>
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
                        <?= nl2br($_SESSION['toast']['message']) ?>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-0" data-bs-dismiss="toast" aria-label="Close" style="font-size: 10px; padding: 4px;"></button>
                </div>
            </div>
        </div>
        <?php unset($_SESSION['toast']); ?>
    <?php endif; ?>

    <!-- Search & Filter -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="" class="row g-2">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" name="search" 
                               placeholder="Cari NISN atau Nama Siswa..." 
                               value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <select class="form-select" name="kelas">
                        <option value="">Semua Kelas</option>
                        <?php foreach ($kelas_list as $kelas): ?>
                            <option value="<?= $kelas['id'] ?>" <?= (isset($_GET['kelas']) && $_GET['kelas'] == $kelas['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($kelas['nama_kelas']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-1"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Siswa -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="35" class="text-center">No</th>
                            <th width="40" class="text-center">Foto</th>
                            <th>NISN</th>
                            <th>Nama Siswa</th>
                            <th>Kelas</th>
                            <th class="text-center">JK</th>
                            <th class="text-center">Pelanggaran</th>
                            <th class="text-center">Prestasi</th>
                            <th class="text-center">Poin</th>
                            <th>No. HP</th>
                            <th class="text-center" width="230">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($siswa_list)): ?>
                            <?php 
                            $no = 1; 
                            $search = isset($_GET['search']) ? strtolower($_GET['search']) : '';
                            $filter_kelas = isset($_GET['kelas']) ? $_GET['kelas'] : '';
                            $filtered = 0;
                            
                            foreach ($siswa_list as $siswa): 
                                $match = true;
                                if (!empty($search)) {
                                    $nisn = strtolower($siswa['nisn'] ?? '');
                                    $nama = strtolower($siswa['nama_siswa']);
                                    if (!strpos($nisn, $search) && !strpos($nama, $search)) {
                                        $match = false;
                                    }
                                }
                                if (!empty($filter_kelas) && $siswa['kelas_id'] != $filter_kelas) {
                                    $match = false;
                                }
                                if (!$match) continue;
                                $filtered++;
                                
                                $poin = $siswa['poin_akhir'];
                                $level = getPoinLevel($poin);
                            ?>
                                <tr>
                                    <td class="text-center"><?= $filtered ?></td>
                                    <td class="text-center">
                                        <?php if (!empty($siswa['foto']) && file_exists(APP_PATH . 'uploads/siswa/' . $siswa['foto'])): ?>
                                            <img src="<?= APP_URL ?>uploads/siswa/<?= $siswa['foto'] ?>" 
                                                 alt="Foto" width="32" height="32" 
                                                 style="object-fit: cover; border-radius: 50%;">
                                        <?php else: ?>
                                            <div class="avatar-placeholder" style="width:32px;height:32px;border-radius:50%;background:#e9ecef;display:inline-flex;align-items:center;justify-content:center;color:#6c757d;font-weight:600;font-size:12px;">
                                                <?= strtoupper(substr($siswa['nama_siswa'], 0, 1)) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge bg-light text-dark" style="font-size: 9px;"><?= htmlspecialchars($siswa['nisn'] ?? '-') ?></span></td>
                                    <td><strong><?= htmlspecialchars($siswa['nama_siswa']) ?></strong></td>
                                    <td><?= htmlspecialchars($siswa['nama_kelas'] ?? 'Belum ditentukan') ?></td>
                                    <td class="text-center"><?= $siswa['jenis_kelamin'] == 'L' ? 'L' : 'P' ?></td>
                                    <td class="text-center">
                                        <?php if ($siswa['total_pelanggaran'] > 0): ?>
                                            <span class="badge bg-danger"><?= $siswa['total_pelanggaran'] ?></span>
                                            <br><small style="font-size:7px;">(<?= $siswa['poin_pelanggaran'] ?>)</small>
                                        <?php else: ?>
                                            <span class="text-muted">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($siswa['total_prestasi'] > 0): ?>
                                            <span class="badge bg-success"><?= $siswa['total_prestasi'] ?></span>
                                            <br><small style="font-size:7px;">(<?= $siswa['poin_prestasi'] ?>)</small>
                                        <?php else: ?>
                                            <span class="text-muted">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-<?= $level['color'] ?>" style="font-size: 10px;">
                                            <?= $poin ?>
                                        </span>
                                        <?php if ($siswa['total_pelanggaran'] > 0 && $siswa['total_prestasi'] > 0): ?>
                                            <br><small style="font-size:7px;"><?= $siswa['poin_pelanggaran'] ?>-<?= $siswa['poin_prestasi'] ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($siswa['no_hp'])): ?>
                                            <span style="font-size: 10px;">
                                                <i class="fas fa-phone text-success me-1"></i>
                                                <?= htmlspecialchars($siswa['no_hp']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted" style="font-size: 9px;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="<?= APP_URL ?>admin/siswa_detail.php?id=<?= $siswa['id'] ?>" class="btn btn-outline-info" title="Detail" style="padding: 2px 6px; font-size: 10px;">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?= APP_URL ?>admin/siswa_edit.php?id=<?= $siswa['id'] ?>" class="btn btn-outline-primary" title="Edit" style="padding: 2px 6px; font-size: 10px;">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button class="btn btn-outline-warning btn-reset-password" 
                                                    data-id="<?= $siswa['id'] ?>" 
                                                    data-nama="<?= htmlspecialchars($siswa['nama_siswa']) ?>"
                                                    data-nisn="<?= $siswa['nisn'] ?>"
                                                    title="Reset Password Login"
                                                    style="padding: 2px 6px; font-size: 10px;">
                                                <i class="fas fa-key"></i>
                                            </button>
                                            <?php if (!empty($siswa['no_hp'])): ?>
                                                <button class="btn btn-outline-success" 
                                                        onclick="sendWA('<?= $siswa['no_hp'] ?>', '<?= htmlspecialchars($siswa['nama_siswa']) ?>')"
                                                        title="Kirim WhatsApp"
                                                        style="padding: 2px 6px; font-size: 10px;">
                                                    <i class="fab fa-whatsapp"></i>
                                                </button>
                                            <?php endif; ?>
                                            <a href="<?= APP_URL ?>admin/siswa.php?action=delete&id=<?= $siswa['id'] ?>" 
                                               class="btn btn-outline-danger" 
                                               onclick="return confirm('Apakah Anda yakin ingin menghapus siswa ini?\\n\\nSiswa tidak bisa dihapus jika memiliki riwayat pelanggaran!')" 
                                               title="Hapus" style="padding: 2px 6px; font-size: 10px;">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if ($filtered == 0): ?>
                                <tr>
                                    <td colspan="11" class="text-center text-muted py-4">
                                        <i class="fas fa-search fa-2x mb-2 d-block opacity-25"></i>
                                        Tidak ada data yang sesuai dengan pencarian
                                    </td>
                                </tr>
                            <?php else: ?>
                                <tr class="table-info fw-bold">
                                    <td colspan="5" class="text-end">Total</td>
                                    <td></td>
                                    <td class="text-center">
                                        <?php 
                                        $total_pel = array_sum(array_column($siswa_list, 'total_pelanggaran'));
                                        echo $total_pel;
                                        ?>
                                    </td>
                                    <td class="text-center">
                                        <?php 
                                        $total_pres = array_sum(array_column($siswa_list, 'total_prestasi'));
                                        echo $total_pres;
                                        ?>
                                    </td>
                                    <td class="text-center">
                                        <?php 
                                        $total_poin = array_sum(array_column($siswa_list, 'poin_akhir'));
                                        echo $total_poin;
                                        ?>
                                    </td>
                                    <td colspan="2"></td>
                                </tr>
                            <?php endif; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="11" class="text-center text-muted py-4">
                                    <i class="fas fa-user-graduate fa-2x mb-2 d-block opacity-25"></i>
                                    Belum ada data siswa
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if (!empty($siswa_list)): ?>
            <div class="card-footer bg-transparent text-muted">
                <i class="fas fa-info-circle me-1"></i>
                Menampilkan <?= $filtered ?? count($siswa_list) ?> siswa | 
                <span class="text-danger">Total Pelanggaran: <?= array_sum(array_column($siswa_list, 'total_pelanggaran')) ?></span> | 
                <span class="text-success">Total Prestasi: <?= array_sum(array_column($siswa_list, 'total_prestasi')) ?></span> | 
                <span class="text-warning">Total Poin: <?= array_sum(array_column($siswa_list, 'poin_akhir')) ?></span>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Tambah Siswa -->
<div class="modal fade" id="modalSiswa" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus text-primary me-2"></i> Tambah Siswa
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="action" value="tambah">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">NISN <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nisn" placeholder="Masukkan NISN" required>
                            <small class="text-muted">NISN akan digunakan sebagai username login siswa</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nama Siswa <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nama_siswa" placeholder="Masukkan nama lengkap" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kelas</label>
                            <select class="form-select" name="kelas_id">
                                <option value="">Pilih Kelas</option>
                                <?php foreach ($kelas_list as $kelas): ?>
                                    <option value="<?= $kelas['id'] ?>">
                                        <?= htmlspecialchars($kelas['nama_kelas']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Jenis Kelamin <span class="text-danger">*</span></label>
                            <select class="form-select" name="jenis_kelamin" required>
                                <option value="">Pilih</option>
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tempat Lahir</label>
                            <input type="text" class="form-control" name="tempat_lahir" placeholder="Tempat lahir">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tanggal Lahir</label>
                            <input type="date" class="form-control" name="tanggal_lahir">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Alamat</label>
                            <textarea class="form-control" name="alamat" rows="2" placeholder="Alamat lengkap"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">No. HP <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="no_hp" placeholder="081234567890" required>
                            <small class="text-muted">Nomor HP untuk notifikasi WhatsApp</small>
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
                    <i class="fas fa-file-import text-success me-2"></i> Import Siswa CSV
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="action" value="import">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Format CSV (pemisah ;):</strong><br>
                        NISN;Nama Siswa;Kelas ID;Jenis Kelamin;Tempat Lahir;Tanggal Lahir;Alamat;No HP
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
                            <li>Kelas ID harus sesuai dengan ID kelas di database</li>
                            <li>Jenis Kelamin: L atau P</li>
                            <li>Tanggal Lahir format: YYYY-MM-DD</li>
                            <li>NISN akan otomatis menjadi username login siswa</li>
                            <li>No HP untuk notifikasi WhatsApp</li>
                        </ul>
                    </div>
                    <div class="mt-3">
                        <a href="<?= APP_URL ?>assets/sample/download_siswa_sample.php" class="btn btn-sm btn-outline-primary" target="_blank">
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
                <h5 class="modal-title">
                    <i class="fas fa-key text-warning me-2"></i> Reset Password Siswa
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="id" id="reset_siswa_id">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Reset Password Login Siswa</strong><br>
                        Password akan direset ke <strong>NISN</strong> siswa.
                    </div>
                    <p>Anda akan mereset password untuk siswa:</p>
                    <div class="bg-light p-3 rounded">
                        <p class="mb-1"><strong>Nama:</strong> <span id="reset_nama"></span></p>
                        <p class="mb-0"><strong>NISN:</strong> <span id="reset_nisn"></span></p>
                    </div>
                    <div class="alert alert-warning mt-3">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Perhatian!</strong> Setelah direset, siswa dapat login menggunakan:
                        <ul class="mb-0 mt-1">
                            <li><strong>Username:</strong> NISN</li>
                            <li><strong>Password:</strong> NISN</li>
                        </ul>
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
    
    // Reset Password button
    document.querySelectorAll('.btn-reset-password').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.dataset.id;
            var nama = this.dataset.nama;
            var nisn = this.dataset.nisn;
            
            document.getElementById('reset_siswa_id').value = id;
            document.getElementById('reset_nama').textContent = nama;
            document.getElementById('reset_nisn').textContent = nisn;
            
            var modal = new bootstrap.Modal(document.getElementById('modalResetPassword'));
            modal.show();
        });
    });
});

// Send WhatsApp
function sendWA(no_hp, nama) {
    if (confirm('Kirim pesan WhatsApp ke ' + nama + ' (' + no_hp + ')?')) {
        var url = 'https://wa.me/62' + no_hp.replace(/^0+/, '');
        window.open(url, '_blank');
    }
}
</script>

<style>
/* Table Styles */
.table-bordered {
    border: 1px solid #dee2e6;
}

.table-bordered th,
.table-bordered td {
    border: 1px solid #dee2e6;
    padding: 6px 8px;
    vertical-align: middle;
}

.table thead th {
    background: #f8f9fa;
    font-weight: 600;
    font-size: 9px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    color: #495057;
    border-bottom: 2px solid #dee2e6;
    white-space: nowrap;
}

.table tbody tr:hover {
    background-color: rgba(108, 99, 255, 0.04);
}

.table tbody td {
    font-size: 11px;
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

/* Badge */
.badge {
    font-size: 8px !important;
    padding: 2px 6px !important;
}

/* Button */
.btn-group-sm .btn {
    padding: 2px 6px !important;
    font-size: 10px !important;
}

/* Responsive */
@media (max-width: 768px) {
    .table thead th {
        font-size: 7px;
        padding: 3px 4px;
    }
    
    .table tbody td {
        font-size: 9px;
        padding: 3px 4px;
    }
    
    .btn-group-sm .btn {
        padding: 1px 4px !important;
        font-size: 8px !important;
    }
    
    .badge {
        font-size: 7px !important;
        padding: 1px 4px !important;
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
        font-size: 6px;
        padding: 2px 3px;
    }
    
    .table tbody td {
        font-size: 8px;
        padding: 2px 3px;
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