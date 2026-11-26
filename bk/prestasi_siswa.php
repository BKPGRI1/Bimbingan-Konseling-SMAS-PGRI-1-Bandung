<?php
// bk/prestasi_siswa.php
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

$page_title = 'Prestasi Siswa';
$conn = getDB();
$message = '';
$error = '';

// ============================================
// HANDLE CRUD OPERATIONS
// ============================================

// Generate nomor prestasi otomatis
function generateNoPrestasi($conn) {
    $year = date('Y');
    $query = "SELECT COUNT(*) as total FROM prestasi_siswa WHERE YEAR(created_at) = $year";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    $no = str_pad($row['total'] + 1, 4, '0', STR_PAD_LEFT);
    return "PRS/" . $year . "/" . $no;
}

// ============================================
// FUNGSI UPDATE POIN SISWA - PASTIKAN TIDAK NEGATIF
// ============================================
function updatePoinSiswa($conn, $siswa_id, $perubahan, $keterangan, $jenis, $prestasi_id = null) {
    // Ambil poin saat ini
    $cek = $conn->query("SELECT poin FROM siswa WHERE id = $siswa_id");
    $poin_sekarang = $cek->fetch_assoc()['poin'];
    
    // Hitung poin baru
    $poin_baru = $poin_sekarang + $perubahan;
    
    // PASTIKAN POIN TIDAK PERNAH NEGATIF (MINIMAL 0)
    if ($poin_baru < 0) {
        $poin_baru = 0;
    }
    
    // Update poin
    $query = "UPDATE siswa SET poin = $poin_baru WHERE id = $siswa_id";
    $conn->query($query);
    
    // Catat log
    $log_query = "INSERT INTO log_poin (siswa_id, perubahan, keterangan, jenis, prestasi_id) 
                  VALUES ($siswa_id, $perubahan, '$keterangan', '$jenis', " . ($prestasi_id ? $prestasi_id : 'NULL') . ")";
    $conn->query($log_query);
    
    return [
        'poin_sebelum' => $poin_sekarang,
        'poin_sesudah' => $poin_baru,
        'perubahan' => $perubahan
    ];
}

// ============================================
// TAMBAH PRESTASI - SEMUA PRESTASI MENGURANGI POIN
// ============================================
if (isset($_POST['action']) && $_POST['action'] == 'add') {
    $siswa_id = (int)$_POST['siswa_id'];
    $jenis_prestasi = sanitize($_POST['jenis_prestasi']);
    $nama_prestasi = sanitize($_POST['nama_prestasi']);
    $tingkat = sanitize($_POST['tingkat']);
    $juara = sanitize($_POST['juara']);
    $tahun = (int)$_POST['tahun'];
    $keterangan = sanitize($_POST['keterangan']);
    $poin_prestasi = (int)$_POST['poin_prestasi'];
    $no_prestasi = generateNoPrestasi($conn);
    
    // Validasi
    if (empty($siswa_id) || empty($nama_prestasi) || empty($tahun)) {
        $error = 'Semua field wajib diisi!';
    } elseif ($poin_prestasi < 1 || $poin_prestasi > 50) {
        $error = 'Poin prestasi harus antara 1-50!';
    } else {
        // Insert prestasi
        $query = "INSERT INTO prestasi_siswa (no_prestasi, siswa_id, jenis_prestasi, nama_prestasi, tingkat, juara, tahun, poin_prestasi, keterangan) 
                  VALUES ('$no_prestasi', $siswa_id, '$jenis_prestasi', '$nama_prestasi', '$tingkat', '$juara', $tahun, $poin_prestasi, '$keterangan')";
        
        if ($conn->query($query)) {
            $prestasi_id = $conn->insert_id;
            
            // SEMUA PRESTASI MENGURANGI POIN (perubahan NEGATIF)
            $perubahan = -$poin_prestasi;
            $keterangan_log = "Pengurangan poin karena prestasi: $nama_prestasi";
            $result = updatePoinSiswa($conn, $siswa_id, $perubahan, $keterangan_log, 'prestasi', $prestasi_id);
            
            $message = "Data prestasi berhasil ditambahkan! Poin siswa berkurang " . ($result['poin_sebelum'] - $result['poin_sesudah']) . " poin. (Sebelum: {$result['poin_sebelum']} → Sesudah: {$result['poin_sesudah']})";
        } else {
            $error = 'Gagal menambahkan data: ' . $conn->error;
        }
    }
}

// ============================================
// EDIT PRESTASI
// ============================================
if (isset($_POST['action']) && $_POST['action'] == 'edit') {
    $id = (int)$_POST['id'];
    $siswa_id = (int)$_POST['siswa_id'];
    $jenis_prestasi = sanitize($_POST['jenis_prestasi']);
    $nama_prestasi = sanitize($_POST['nama_prestasi']);
    $tingkat = sanitize($_POST['tingkat']);
    $juara = sanitize($_POST['juara']);
    $tahun = (int)$_POST['tahun'];
    $keterangan = sanitize($_POST['keterangan']);
    $poin_prestasi = (int)$_POST['poin_prestasi'];
    
    // Get data lama
    $old_query = "SELECT poin_prestasi, siswa_id, nama_prestasi FROM prestasi_siswa WHERE id = $id";
    $old_result = $conn->query($old_query);
    $old_data = $old_result->fetch_assoc();
    $old_poin = $old_data['poin_prestasi'];
    $old_siswa_id = $old_data['siswa_id'];
    $old_nama = $old_data['nama_prestasi'];
    
    // Update prestasi
    $query = "UPDATE prestasi_siswa SET 
              siswa_id = $siswa_id,
              jenis_prestasi = '$jenis_prestasi',
              nama_prestasi = '$nama_prestasi',
              tingkat = '$tingkat',
              juara = '$juara',
              tahun = $tahun,
              keterangan = '$keterangan',
              poin_prestasi = $poin_prestasi
              WHERE id = $id";
    
    if ($conn->query($query)) {
        // Jika siswa berubah
        if ($old_siswa_id != $siswa_id) {
            // Kembalikan poin ke siswa lama
            updatePoinSiswa($conn, $old_siswa_id, $old_poin, "Poin dikembalikan karena prestasi dipindahkan: $old_nama", 'prestasi_edit', $id);
            // Kurangi poin siswa baru
            updatePoinSiswa($conn, $siswa_id, -$poin_prestasi, "Pengurangan poin karena prestasi dipindahkan: $nama_prestasi", 'prestasi_edit', $id);
        } else {
            // Siswa sama, update selisih poin
            $selisih = $old_poin - $poin_prestasi;
            if ($selisih != 0) {
                if ($selisih > 0) {
                    updatePoinSiswa($conn, $siswa_id, $selisih, "Poin ditambahkan karena perubahan poin prestasi: $nama_prestasi", 'prestasi_edit', $id);
                } else {
                    $selisih_abs = abs($selisih);
                    updatePoinSiswa($conn, $siswa_id, -$selisih_abs, "Pengurangan poin karena perubahan poin prestasi: $nama_prestasi", 'prestasi_edit', $id);
                }
            }
        }
        
        $message = 'Data prestasi berhasil diperbarui!';
    } else {
        $error = 'Gagal memperbarui data: ' . $conn->error;
    }
}

// ============================================
// HAPUS PRESTASI
// ============================================
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    $query = "SELECT siswa_id, poin_prestasi, nama_prestasi FROM prestasi_siswa WHERE id = $id";
    $result = $conn->query($query);
    $data = $result->fetch_assoc();
    
    if ($data) {
        // Kembalikan poin ke siswa
        updatePoinSiswa($conn, $data['siswa_id'], $data['poin_prestasi'], "Poin dikembalikan karena prestasi dihapus: {$data['nama_prestasi']}", 'prestasi_hapus', $id);
        $conn->query("DELETE FROM prestasi_siswa WHERE id = $id");
        $message = 'Data prestasi berhasil dihapus! Poin siswa dikembalikan ' . $data['poin_prestasi'] . ' poin.';
    } else {
        $error = 'Data tidak ditemukan!';
    }
}

// ============================================
// GET FILTERS
// ============================================
$filter_siswa = isset($_GET['siswa_id']) ? (int)$_GET['siswa_id'] : 0;
$filter_jenis = isset($_GET['jenis_prestasi']) ? sanitize($_GET['jenis_prestasi']) : '';
$filter_tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : 0;
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// ============================================
// BUILD QUERY
// ============================================
$where = [];
if ($filter_siswa > 0) {
    $where[] = "p.siswa_id = $filter_siswa";
}
if (!empty($filter_jenis)) {
    $where[] = "p.jenis_prestasi = '$filter_jenis'";
}
if ($filter_tahun > 0) {
    $where[] = "p.tahun = $filter_tahun";
}
if (!empty($search)) {
    $where[] = "(s.nama_siswa LIKE '%$search%' OR s.nisn LIKE '%$search%' OR p.nama_prestasi LIKE '%$search%')";
}
$where_sql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Get prestasi data
$query = "SELECT p.*, s.nama_siswa, s.nisn, k.nama_kelas, s.poin as poin_siswa
          FROM prestasi_siswa p
          JOIN siswa s ON p.siswa_id = s.id
          LEFT JOIN kelas k ON s.kelas_id = k.id
          $where_sql
          ORDER BY p.tahun DESC, p.created_at DESC";
$prestasi = getRows($query);

// Get siswa list
$siswa_list = getRows("SELECT id, nama_siswa, nisn, poin FROM siswa ORDER BY nama_siswa");

// Get tahun list
$tahun_list = getRows("SELECT DISTINCT tahun FROM prestasi_siswa ORDER BY tahun DESC");

// Total poin prestasi
$total_poin = 0;
foreach ($prestasi as $p) {
    $total_poin += $p['poin_prestasi'];
}

include __DIR__ . '/../includes/header_bk.php';
?>

<div class="container-fluid dashboard-container">
    <!-- Toast Notification -->
    <?php if ($message): ?>
        <div id="toastNotification" class="toast-container position-fixed top-0 end-0 p-2" style="z-index: 9999;">
            <div class="toast align-items-center text-white bg-success border-0 show" role="alert" aria-live="assertive" aria-atomic="true" style="min-width: 200px; max-width: 320px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.12); font-size: 12px;">
                <div class="d-flex align-items-center p-2">
                    <div class="flex-shrink-0 ms-1">
                        <i class="fas fa-check-circle fa-sm"></i>
                    </div>
                    <div class="toast-body flex-grow-1 text-start ms-2" style="font-size: 12px; font-weight: 500; padding: 2px 0; line-height: 1.3;">
                        <?= $message ?>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-0" data-bs-dismiss="toast" aria-label="Close" style="font-size: 9px; padding: 3px;"></button>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div id="toastNotification" class="toast-container position-fixed top-0 end-0 p-2" style="z-index: 9999;">
            <div class="toast align-items-center text-white bg-danger border-0 show" role="alert" aria-live="assertive" aria-atomic="true" style="min-width: 200px; max-width: 320px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.12); font-size: 12px;">
                <div class="d-flex align-items-center p-2">
                    <div class="flex-shrink-0 ms-1">
                        <i class="fas fa-exclamation-circle fa-sm"></i>
                    </div>
                    <div class="toast-body flex-grow-1 text-start ms-2" style="font-size: 12px; font-weight: 500; padding: 2px 0; line-height: 1.3;">
                        <?= $error ?>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-0" data-bs-dismiss="toast" aria-label="Close" style="font-size: 9px; padding: 3px;"></button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Header -->
    <div class="dashboard-header">
        <div>
            <h4 class="dashboard-title">
                <i class="fas fa-trophy text-warning me-2"></i> Prestasi Siswa
            </h4>
            <small class="text-muted dashboard-subtitle">
                <i class="fas fa-star me-1"></i> Kelola prestasi siswa dan pengurangan poin otomatis
            </small>
        </div>
        <div class="d-flex gap-2">
            <span class="badge bg-success p-2">
                <i class="fas fa-star me-1"></i> Total Prestasi: <?= count($prestasi) ?>
            </span>
            <span class="badge bg-info p-2">
                <i class="fas fa-minus-circle me-1"></i> Total Pengurangan: <?= $total_poin ?> Poin
            </span>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambahPrestasi">
                <i class="fas fa-plus me-1"></i> Tambah
            </button>
        </div>
    </div>

    <!-- Statistik -->
    <div class="quick-stats-grid mb-3">
        <div class="quick-stat-card">
            <div class="quick-stat-icon bg-warning">
                <i class="fas fa-trophy"></i>
            </div>
            <div class="quick-stat-info">
                <h6 class="quick-stat-label">Total Prestasi</h6>
                <h4 class="quick-stat-number"><?= count($prestasi) ?></h4>
                <small class="text-muted">Siswa Berprestasi</small>
            </div>
        </div>
        <div class="quick-stat-card">
            <div class="quick-stat-icon bg-success">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <div class="quick-stat-info">
                <h6 class="quick-stat-label">Akademik</h6>
                <h4 class="quick-stat-number">
                    <?php 
                    $akademik = array_filter($prestasi, function($p) { return $p['jenis_prestasi'] == 'akademik'; });
                    echo count($akademik);
                    ?>
                </h4>
                <small class="text-muted">Prestasi</small>
            </div>
        </div>
        <div class="quick-stat-card">
            <div class="quick-stat-icon bg-info">
                <i class="fas fa-medal"></i>
            </div>
            <div class="quick-stat-info">
                <h6 class="quick-stat-label">Non-Akademik</h6>
                <h4 class="quick-stat-number">
                    <?php 
                    $non_akademik = array_filter($prestasi, function($p) { return $p['jenis_prestasi'] == 'non_akademik'; });
                    echo count($non_akademik);
                    ?>
                </h4>
                <small class="text-muted">Prestasi</small>
            </div>
        </div>
        <div class="quick-stat-card">
            <div class="quick-stat-icon bg-secondary">
                <i class="fas fa-ellipsis-h"></i>
            </div>
            <div class="quick-stat-info">
                <h6 class="quick-stat-label">Lainnya</h6>
                <h4 class="quick-stat-number">
                    <?php 
                    $lainnya = array_filter($prestasi, function($p) { return $p['jenis_prestasi'] == 'lainnya'; });
                    echo count($lainnya);
                    ?>
                </h4>
                <small class="text-muted">Prestasi</small>
            </div>
        </div>
    </div>

    <!-- Filter -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small mb-1">Cari</label>
                    <input type="text" class="form-control form-control-sm" name="search" 
                           placeholder="Nama / NISN / Prestasi..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Siswa</label>
                    <select class="form-select form-select-sm" name="siswa_id">
                        <option value="">Semua Siswa</option>
                        <?php foreach ($siswa_list as $siswa): ?>
                            <option value="<?= $siswa['id'] ?>" <?= $filter_siswa == $siswa['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($siswa['nama_siswa']) ?> (<?= $siswa['nisn'] ?>) - Poin: <?= $siswa['poin'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Jenis</label>
                    <select class="form-select form-select-sm" name="jenis_prestasi">
                        <option value="">Semua</option>
                        <option value="akademik" <?= $filter_jenis == 'akademik' ? 'selected' : '' ?>>Akademik</option>
                        <option value="non_akademik" <?= $filter_jenis == 'non_akademik' ? 'selected' : '' ?>>Non-Akademik</option>
                        <option value="lainnya" <?= $filter_jenis == 'lainnya' ? 'selected' : '' ?>>Lainnya</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Tahun</label>
                    <select class="form-select form-select-sm" name="tahun">
                        <option value="">Semua Tahun</option>
                        <?php foreach ($tahun_list as $t): ?>
                            <option value="<?= $t['tahun'] ?>" <?= $filter_tahun == $t['tahun'] ? 'selected' : '' ?>>
                                <?= $t['tahun'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="fas fa-search me-1"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Prestasi -->
    <div class="card d-none d-md-block">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" width="35">No</th>
                            <th>No. Prestasi</th>
                            <th>Nama Siswa</th>
                            <th class="d-none d-lg-table-cell">NISN</th>
                            <th class="d-none d-lg-table-cell">Kelas</th>
                            <th>Prestasi</th>
                            <th class="text-center">Jenis</th>
                            <th class="text-center">Tingkat</th>
                            <th class="text-center">Poin</th>
                            <th>Tahun</th>
                            <th class="text-center" width="100">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($prestasi) > 0): ?>
                            <?php $no = 1; ?>
                            <?php foreach ($prestasi as $row): ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?></td>
                                    <td><span class="badge bg-primary"><?= $row['no_prestasi'] ?? 'PRS' ?></span></td>
                                    <td>
                                        <strong><?= htmlspecialchars($row['nama_siswa']) ?></strong>
                                        <br>
                                        <span class="badge bg-<?= 
                                            $row['jenis_prestasi'] == 'akademik' ? 'success' : 
                                            ($row['jenis_prestasi'] == 'non_akademik' ? 'info' : 'secondary') 
                                        ?>" style="font-size:7px;">
                                            <?= ucfirst(str_replace('_', ' ', $row['jenis_prestasi'])) ?>
                                        </span>
                                    </td>
                                    <td class="d-none d-lg-table-cell"><?= $row['nisn'] ?></td>
                                    <td class="d-none d-lg-table-cell"><?= $row['nama_kelas'] ?? '-' ?></td>
                                    <td>
                                        <div><?= htmlspecialchars($row['nama_prestasi']) ?></div>
                                        <?php if ($row['juara']): ?>
                                            <small class="text-muted">Juara: <?= htmlspecialchars($row['juara']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-<?= 
                                            $row['jenis_prestasi'] == 'akademik' ? 'success' : 
                                            ($row['jenis_prestasi'] == 'non_akademik' ? 'info' : 'secondary') 
                                        ?>">
                                            <?= ucfirst(str_replace('_', ' ', $row['jenis_prestasi'])) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-<?= 
                                            $row['tingkat'] == 'internasional' ? 'danger' : 
                                            ($row['tingkat'] == 'nasional' ? 'warning' : 
                                            ($row['tingkat'] == 'provinsi' ? 'info' : 
                                            ($row['tingkat'] == 'kota' ? 'success' : 
                                            ($row['tingkat'] == 'kecamatan' ? 'primary' : 'secondary')))) ?>">
                                            <?= ucfirst($row['tingkat']) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success"><?= $row['poin_prestasi'] ?></span>
                                    </td>
                                    <td><?= $row['tahun'] ?></td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary btn-sm" onclick="editPrestasi(<?= $row['id'] ?>)" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="?delete=<?= $row['id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Yakin hapus prestasi ini?')" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="table-info fw-bold">
                                <td colspan="8" class="text-end">Total Pengurangan Poin</td>
                                <td class="text-center"><?= $total_poin ?></td>
                                <td colspan="2"></td>
                            </tr>
                        <?php else: ?>
                            <tr>
                                <td colspan="11" class="text-center py-4 text-muted">
                                    <i class="fas fa-trophy fa-2x d-block mb-2 opacity-25"></i>
                                    Belum ada data prestasi
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer text-muted" style="font-size:11px;">
            <i class="fas fa-info-circle me-1"></i> 
            Menampilkan <?= count($prestasi) ?> data prestasi | 
            Total pengurangan poin: <?= $total_poin ?> poin
            <?php if ($filter_siswa > 0): ?>
                <?php 
                $siswa = getRow("SELECT poin FROM siswa WHERE id = $filter_siswa");
                if ($siswa): ?>
                    | Poin siswa saat ini: <span class="fw-bold"><?= $siswa['poin'] ?></span>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Mobile View -->
    <div class="d-block d-md-none">
        <?php if (count($prestasi) > 0): ?>
            <?php $no = 1; ?>
            <?php foreach ($prestasi as $row): ?>
                <div class="card mb-2 mobile-card">
                    <div class="card-body p-2">
                        <div class="row g-1">
                            <div class="col-8">
                                <span class="badge bg-primary" style="font-size:8px;"><?= $row['no_prestasi'] ?? 'PRS' ?></span>
                                <span class="badge bg-<?= 
                                    $row['jenis_prestasi'] == 'akademik' ? 'success' : 
                                    ($row['jenis_prestasi'] == 'non_akademik' ? 'info' : 'secondary') 
                                ?>" style="font-size:7px;">
                                    <?= ucfirst(str_replace('_', ' ', $row['jenis_prestasi'])) ?>
                                </span>
                            </div>
                            <div class="col-4 text-end">
                                <span class="badge bg-success"><?= $row['poin_prestasi'] ?></span>
                            </div>
                        </div>
                        <div class="row g-1 mt-1">
                            <div class="col-12">
                                <div class="fw-bold" style="font-size:12px;"><?= htmlspecialchars($row['nama_siswa']) ?></div>
                                <div style="font-size:10px; color:#6c757d;"><?= $row['nisn'] ?> | <?= $row['nama_kelas'] ?? '-' ?></div>
                            </div>
                        </div>
                        <div class="row g-1 mt-1">
                            <div class="col-12">
                                <span class="text-muted" style="font-size:9px;">Prestasi</span>
                                <div style="font-size:11px; font-weight:500;"><?= htmlspecialchars($row['nama_prestasi']) ?></div>
                                <?php if ($row['juara']): ?>
                                    <div style="font-size:10px; color:#6c757d;">Juara: <?= htmlspecialchars($row['juara']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="row g-1 mt-1">
                            <div class="col-6">
                                <span class="text-muted" style="font-size:9px;">Tingkat</span>
                                <div>
                                    <span class="badge bg-<?= 
                                        $row['tingkat'] == 'internasional' ? 'danger' : 
                                        ($row['tingkat'] == 'nasional' ? 'warning' : 
                                        ($row['tingkat'] == 'provinsi' ? 'info' : 
                                        ($row['tingkat'] == 'kota' ? 'success' : 
                                        ($row['tingkat'] == 'kecamatan' ? 'primary' : 'secondary')))) ?>" style="font-size:8px;">
                                        <?= ucfirst($row['tingkat']) ?>
                                    </span>
                                </div>
                            </div>
                            <div class="col-6">
                                <span class="text-muted" style="font-size:9px;">Tahun</span>
                                <div style="font-size:11px;"><?= $row['tahun'] ?></div>
                            </div>
                        </div>
                        <div class="row g-1 mt-1">
                            <div class="col-12 text-end">
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary btn-sm" onclick="editPrestasi(<?= $row['id'] ?>)" title="Edit" style="font-size:8px; padding:1px 6px;">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="?delete=<?= $row['id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Yakin hapus prestasi ini?')" title="Hapus" style="font-size:8px; padding:1px 6px;">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <!-- Total Mobile -->
            <div class="card mb-2" style="background:#e8f5e9;">
                <div class="card-body p-2 text-center">
                    <span class="text-muted" style="font-size:10px;">Total Pengurangan Poin</span>
                    <div class="fw-bold text-success" style="font-size:16px;"><?= $total_poin ?></div>
                </div>
            </div>
        <?php else: ?>
            <div class="card mb-2">
                <div class="card-body p-4 text-center text-muted">
                    <i class="fas fa-trophy fa-3x d-block mb-2 opacity-25"></i>
                    Belum ada data prestasi
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL TAMBAH PRESTASI -->
<!-- ========================================== -->
<div class="modal fade" id="modalTambahPrestasi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle text-primary me-2"></i> Tambah Prestasi
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Poin prestasi akan otomatis mengurangi poin pelanggaran siswa.</strong>
                        <ul class="mb-0 mt-1" style="font-size:12px;">
                            <li>Poin akan dikurangi dari total poin pelanggaran siswa</li>
                            <li>Minimal 1 poin, maksimal 50 poin per prestasi</li>
                            <li><strong class="text-success">Poin tidak akan menjadi negatif (minimal 0)</strong></li>
                        </ul>
                    </div>
                    
                    <div class="mb-2">
                        <label class="form-label">Siswa <span class="text-danger">*</span></label>
                        <select class="form-select" name="siswa_id" required>
                            <option value="">-- Pilih Siswa --</option>
                            <?php foreach ($siswa_list as $siswa): ?>
                                <option value="<?= $siswa['id'] ?>">
                                    <?= htmlspecialchars($siswa['nama_siswa']) ?> (<?= $siswa['nisn'] ?>) - Poin: <?= $siswa['poin'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-2">
                        <label class="form-label">Jenis Prestasi <span class="text-danger">*</span></label>
                        <select class="form-select" name="jenis_prestasi" required>
                            <option value="akademik">Akademik</option>
                            <option value="non_akademik">Non-Akademik</option>
                            <option value="lainnya">Lainnya</option>
                        </select>
                    </div>
                    
                    <div class="mb-2">
                        <label class="form-label">Nama Prestasi <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nama_prestasi" placeholder="Contoh: Juara 1 Olimpiade Matematika" required>
                    </div>
                    
                    <div class="row g-2">
                        <div class="col-md-6">
                            <div class="mb-2">
                                <label class="form-label">Tingkat <span class="text-danger">*</span></label>
                                <select class="form-select" name="tingkat" required>
                                    <option value="sekolah">Sekolah</option>
                                    <option value="kecamatan">Kecamatan</option>
                                    <option value="kota">Kota</option>
                                    <option value="provinsi">Provinsi</option>
                                    <option value="nasional">Nasional</option>
                                    <option value="internasional">Internasional</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-2">
                                <label class="form-label">Juara</label>
                                <input type="text" class="form-control" name="juara" placeholder="Juara 1, 2, 3, dll">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row g-2">
                        <div class="col-md-6">
                            <div class="mb-2">
                                <label class="form-label">Tahun <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="tahun" value="<?= date('Y') ?>" min="2000" max="<?= date('Y') ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-2">
                                <label class="form-label">Poin Prestasi <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="poin_prestasi" value="10" min="1" max="50" required>
                                <small class="text-muted">(1-50 poin, akan mengurangi poin pelanggaran)</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-2">
                        <label class="form-label">Keterangan</label>
                        <textarea class="form-control" name="keterangan" rows="2" placeholder="Keterangan tambahan..."></textarea>
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

<!-- ========================================== -->
<!-- MODAL EDIT PRESTASI -->
<!-- ========================================== -->
<div class="modal fade" id="modalEditPrestasi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit text-warning me-2"></i> Edit Prestasi
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="" id="formEditPrestasi">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Perubahan poin akan otomatis menyesuaikan poin siswa.</strong>
                    </div>
                    
                    <div class="mb-2">
                        <label class="form-label">Siswa <span class="text-danger">*</span></label>
                        <select class="form-select" name="siswa_id" id="edit_siswa_id" required>
                            <option value="">-- Pilih Siswa --</option>
                            <?php foreach ($siswa_list as $siswa): ?>
                                <option value="<?= $siswa['id'] ?>">
                                    <?= htmlspecialchars($siswa['nama_siswa']) ?> (<?= $siswa['nisn'] ?>) - Poin: <?= $siswa['poin'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-2">
                        <label class="form-label">Jenis Prestasi <span class="text-danger">*</span></label>
                        <select class="form-select" name="jenis_prestasi" id="edit_jenis_prestasi" required>
                            <option value="akademik">Akademik</option>
                            <option value="non_akademik">Non-Akademik</option>
                            <option value="lainnya">Lainnya</option>
                        </select>
                    </div>
                    
                    <div class="mb-2">
                        <label class="form-label">Nama Prestasi <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nama_prestasi" id="edit_nama_prestasi" required>
                    </div>
                    
                    <div class="row g-2">
                        <div class="col-md-6">
                            <div class="mb-2">
                                <label class="form-label">Tingkat <span class="text-danger">*</span></label>
                                <select class="form-select" name="tingkat" id="edit_tingkat" required>
                                    <option value="sekolah">Sekolah</option>
                                    <option value="kecamatan">Kecamatan</option>
                                    <option value="kota">Kota</option>
                                    <option value="provinsi">Provinsi</option>
                                    <option value="nasional">Nasional</option>
                                    <option value="internasional">Internasional</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-2">
                                <label class="form-label">Juara</label>
                                <input type="text" class="form-control" name="juara" id="edit_juara" placeholder="Juara 1, 2, 3, dll">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row g-2">
                        <div class="col-md-6">
                            <div class="mb-2">
                                <label class="form-label">Tahun <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="tahun" id="edit_tahun" min="2000" max="<?= date('Y') ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-2">
                                <label class="form-label">Poin Prestasi <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="poin_prestasi" id="edit_poin_prestasi" min="1" max="50" required>
                                <small class="text-muted">(1-50 poin, akan mengurangi poin pelanggaran)</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-2">
                        <label class="form-label">Keterangan</label>
                        <textarea class="form-control" name="keterangan" id="edit_keterangan" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save me-1"></i> Update
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* ============================================
   STYLE PRESTASI SISWA
   ============================================ */

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

/* Quick Stats */
.quick-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 8px;
    margin-bottom: 12px;
}

.quick-stat-card {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 10px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
}

.quick-stat-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 13px;
    flex-shrink: 0;
}
.quick-stat-icon.bg-warning { background: #F59E0B; }
.quick-stat-icon.bg-success { background: #10B981; }
.quick-stat-icon.bg-info { background: #3B82F6; }
.quick-stat-icon.bg-secondary { background: #6c757d; }

.quick-stat-info {
    flex: 1;
    min-width: 0;
}

.quick-stat-label {
    font-size: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    color: #6B7280;
    margin-bottom: 0;
}

.quick-stat-number {
    font-size: 1rem;
    font-weight: 700;
    margin-bottom: 0;
    line-height: 1.2;
}

.quick-stat-info small {
    font-size: 0.45rem;
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
    padding: 10px 14px;
    font-weight: 600;
    font-size: 12px;
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 4px;
}
.card-body {
    padding: 12px 14px;
}
.card-footer {
    background: transparent;
    border-top: 1px solid #e9ecef;
    padding: 8px 14px;
    font-size: 11px;
}

.table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
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
}
.table tbody tr:hover {
    background-color: rgba(108, 99, 255, 0.04);
}
.table tbody td {
    font-size: 11px;
}

.badge {
    font-size: 8px !important;
    padding: 2px 6px !important;
}

.form-label {
    font-weight: 600;
    font-size: 11px;
    color: #4a5568;
    margin-bottom: 2px;
}
.form-control, .form-select {
    border-radius: 6px;
    border: 1px solid #e2e8f0;
    padding: 5px 10px;
    font-size: 12px;
    height: 34px;
}
.form-control:focus, .form-select:focus {
    border-color: #4F46E5;
    box-shadow: 0 0 0 3px rgba(79,70,229,0.15);
}
.btn-sm {
    padding: 4px 10px !important;
    font-size: 11px !important;
    border-radius: 6px !important;
}

.modal-content {
    border-radius: 12px;
    border: none;
}
.modal-header {
    border-bottom: 1px solid #e9ecef;
    padding: 14px 18px;
}
.modal-body {
    padding: 18px;
    max-height: 500px;
    overflow-y: auto;
}
.modal-footer {
    border-top: 1px solid #e9ecef;
    padding: 12px 18px;
}

.mobile-card {
    border: 1px solid #e9ecef !important;
    border-radius: 8px !important;
}
.mobile-card .card-body {
    padding: 10px !important;
}

.toast-container .toast {
    border-radius: 8px !important;
    box-shadow: 0 4px 20px rgba(0,0,0,0.12) !important;
}
.toast-container .toast .toast-body {
    font-size: 11px !important;
    font-weight: 500;
    padding: 2px 0 !important;
}

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
    .quick-stats-grid {
        grid-template-columns: 1fr 1fr;
        gap: 4px;
    }
    .quick-stat-card {
        padding: 4px 6px;
        gap: 4px;
    }
    .quick-stat-icon {
        width: 24px;
        height: 24px;
        font-size: 10px;
    }
    .quick-stat-number {
        font-size: 0.85rem;
    }
    .card-body {
        padding: 8px 10px;
    }
    .table thead th {
        font-size: 7px;
        padding: 2px 4px;
    }
    .table tbody td {
        font-size: 9px;
        padding: 2px 4px;
    }
    .badge {
        font-size: 7px !important;
        padding: 1px 4px !important;
    }
    .btn-sm {
        padding: 2px 6px !important;
        font-size: 9px !important;
    }
    .form-control, .form-select {
        font-size: 11px;
        height: 30px;
        padding: 4px 8px;
    }
    .modal-body {
        padding: 12px;
    }
}

@media (max-width: 576px) {
    .dashboard-container {
        padding: 4px !important;
    }
    .quick-stats-grid {
        grid-template-columns: 1fr 1fr;
        gap: 3px;
    }
    .quick-stat-card {
        padding: 3px 4px;
        min-height: 34px;
    }
    .quick-stat-icon {
        width: 20px;
        height: 20px;
        font-size: 8px;
    }
    .quick-stat-number {
        font-size: 0.75rem;
    }
    .quick-stat-label {
        font-size: 0.35rem;
    }
    .card-body {
        padding: 4px 6px;
    }
    .table thead th {
        font-size: 6px;
        padding: 1px 3px;
    }
    .table tbody td {
        font-size: 8px;
        padding: 1px 3px;
    }
    .badge {
        font-size: 6px !important;
        padding: 1px 3px !important;
    }
    .btn-sm {
        padding: 1px 4px !important;
        font-size: 8px !important;
    }
    .mobile-card .card-body {
        padding: 6px !important;
    }
}
</style>

<script>
// ============================================
// FUNGSI EDIT PRESTASI
// ============================================
function editPrestasi(id) {
    fetch('ajax/get_prestasi.php?id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('edit_id').value = data.id;
                document.getElementById('edit_siswa_id').value = data.siswa_id;
                document.getElementById('edit_jenis_prestasi').value = data.jenis_prestasi;
                document.getElementById('edit_nama_prestasi').value = data.nama_prestasi;
                document.getElementById('edit_tingkat').value = data.tingkat;
                document.getElementById('edit_juara').value = data.juara || '';
                document.getElementById('edit_tahun').value = data.tahun;
                document.getElementById('edit_poin_prestasi').value = data.poin_prestasi;
                document.getElementById('edit_keterangan').value = data.keterangan || '';
                
                var modal = new bootstrap.Modal(document.getElementById('modalEditPrestasi'));
                modal.show();
            } else {
                alert('Gagal mengambil data prestasi!');
            }
        })
        .catch(() => {
            alert('Terjadi kesalahan!');
        });
}

// Auto close toast
document.addEventListener('DOMContentLoaded', function() {
    var toastEl = document.getElementById('toastNotification');
    if (toastEl) {
        setTimeout(function() {
            var toast = bootstrap.Toast.getInstance(toastEl);
            if (toast) toast.hide();
        }, 5000);
    }
});
</script>

<?php include '../includes/footer.php'; ?>