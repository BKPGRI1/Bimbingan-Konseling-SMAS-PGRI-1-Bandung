<?php
// bk/catatan_konseling.php
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

$page_title = 'Catatan Konseling';

// Proses Tambah
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action == 'tambah') {
        $data = [
            'siswa_id' => (int)$_POST['siswa_id'],
            'bk_id' => $_SESSION['user_id'],
            'tanggal' => $_POST['tanggal'],
            'jenis_konseling' => $_POST['jenis_konseling'],
            'topik' => $_POST['topik'],
            'catatan' => $_POST['catatan'],
            'tindak_lanjut' => $_POST['tindak_lanjut'] ?? '',
            'status' => $_POST['status']
        ];
        
        if (tambahCatatanKonseling($data)) {
            $_SESSION['toast'] = ['message' => 'Catatan konseling berhasil ditambahkan!', 'type' => 'success'];
        } else {
            $_SESSION['toast'] = ['message' => 'Gagal menambahkan catatan konseling!', 'type' => 'danger'];
        }
        header("Location: " . APP_URL . "bk/catatan_konseling.php");
        exit();
    }
    
    if ($action == 'edit') {
        $id = (int)$_POST['id'];
        $data = [
            'siswa_id' => (int)$_POST['siswa_id'],
            'tanggal' => $_POST['tanggal'],
            'jenis_konseling' => $_POST['jenis_konseling'],
            'topik' => $_POST['topik'],
            'catatan' => $_POST['catatan'],
            'tindak_lanjut' => $_POST['tindak_lanjut'] ?? '',
            'status' => $_POST['status']
        ];
        
        if (updateCatatanKonseling($data, $id)) {
            $_SESSION['toast'] = ['message' => 'Catatan konseling berhasil diupdate!', 'type' => 'success'];
        } else {
            $_SESSION['toast'] = ['message' => 'Gagal mengupdate catatan konseling!', 'type' => 'danger'];
        }
        header("Location: " . APP_URL . "bk/catatan_konseling.php");
        exit();
    }
    
    if ($action == 'hapus') {
        $id = (int)$_POST['id'];
        if (hapusCatatanKonseling($id)) {
            $_SESSION['toast'] = ['message' => 'Catatan konseling berhasil dihapus!', 'type' => 'success'];
        } else {
            $_SESSION['toast'] = ['message' => 'Gagal menghapus catatan konseling!', 'type' => 'danger'];
        }
        header("Location: " . APP_URL . "bk/catatan_konseling.php");
        exit();
    }
}

// Filter
$siswa_id = isset($_GET['siswa_id']) ? (int)$_GET['siswa_id'] : 0;
$status = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Ambil data catatan konseling
$query = "SELECT c.*, s.nama_siswa, s.nisn, k.nama_kelas 
          FROM catatan_konseling c
          LEFT JOIN siswa s ON c.siswa_id = s.id
          LEFT JOIN kelas k ON s.kelas_id = k.id
          WHERE 1=1";

if ($siswa_id > 0) {
    $query .= " AND c.siswa_id = $siswa_id";
}

if (!empty($status)) {
    $query .= " AND c.status = '$status'";
}

if (!empty($search)) {
    $conn = getDB();
    $search = $conn->real_escape_string($search);
    $query .= " AND (s.nama_siswa LIKE '%$search%' OR s.nisn LIKE '%$search%' OR c.topik LIKE '%$search%')";
}

$query .= " ORDER BY c.tanggal DESC, c.created_at DESC";
$konseling_list = getRows($query);

// Ambil data siswa untuk dropdown
$siswa_options = getSiswa();

$page_title = 'Catatan Konseling';

include __DIR__ . '/../includes/header_bk.php';
?>

<div class="container-fluid dashboard-container">
    <!-- Header -->
    <div class="dashboard-header">
        <div>
            <h4 class="dashboard-title">
                <i class="fas fa-notes-medical text-primary me-2"></i> Catatan Konseling
            </h4>
            <small class="text-muted dashboard-subtitle">
                <i class="fas fa-info-circle me-1"></i> Total <?= count($konseling_list) ?> catatan
            </small>
        </div>
        <div>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
                <i class="fas fa-plus me-1"></i> Tambah
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
                        <?= $_SESSION['toast']['message'] ?>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-0" data-bs-dismiss="toast" aria-label="Close" style="font-size: 10px; padding: 4px;"></button>
                </div>
            </div>
        </div>
        <?php unset($_SESSION['toast']); ?>
    <?php endif; ?>

    <!-- Filter -->
    <div class="card filter-card mb-3">
        <div class="card-body">
            <form method="GET" action="" class="filter-form">
                <div class="row g-2">
                    <div class="col-12 col-md-3">
                        <label class="form-label">Siswa</label>
                        <select class="form-select" name="siswa_id">
                            <option value="0">-- Semua Siswa --</option>
                            <?php foreach ($siswa_options as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= $siswa_id == $s['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($s['nama_siswa']) ?> (<?= $s['nisn'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="">-- Semua --</option>
                            <option value="pending" <?= $status == 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="proses" <?= $status == 'proses' ? 'selected' : '' ?>>Proses</option>
                            <option value="selesai" <?= $status == 'selesai' ? 'selected' : '' ?>>Selesai</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">Cari (Nama/NISN/Topik)</label>
                        <input type="text" class="form-control" name="search" placeholder="Ketik kata kunci..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label d-none d-md-block">&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="fas fa-search me-1"></i> Filter
                        </button>
                    </div>
                    <div class="col-6 col-md-1">
                        <label class="form-label d-none d-md-block">&nbsp;</label>
                        <a href="?siswa_id=0" class="btn btn-secondary btn-sm w-100">
                            <i class="fas fa-undo"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- TABEL UNTUK DESKTOP -->
    <!-- ========================================== -->
    <div class="card table-card d-none d-md-block">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width:40px;">No</th>
                            <th style="width:90px;">Tanggal</th>
                            <th style="width:100px;">NISN</th>
                            <th>Nama Siswa</th>
                            <th style="width:80px;">Kelas</th>
                            <th>Topik</th>
                            <th style="width:80px;">Jenis</th>
                            <th style="width:100px;">Status</th>
                            <th class="text-center" style="width:120px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($konseling_list) > 0): ?>
                            <?php $no = 1; ?>
                            <?php foreach ($konseling_list as $row): ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?></td>
                                    <td><?= date('d-m-Y', strtotime($row['tanggal'])) ?></td>
                                    <td><?= $row['nisn'] ?? '-' ?></td>
                                    <td><?= htmlspecialchars($row['nama_siswa'] ?? '-') ?></td>
                                    <td><?= $row['nama_kelas'] ?? '-' ?></td>
                                    <td><?= htmlspecialchars($row['topik'] ?? '-') ?></td>
                                    <td><?= ucfirst($row['jenis_konseling'] ?? '-') ?></td>
                                    <td>
                                        <span class="badge bg-<?= $row['status'] == 'selesai' ? 'success' : ($row['status'] == 'proses' ? 'warning' : 'secondary') ?>">
                                            <?= ucfirst($row['status']) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-primary btn-sm btn-edit" 
                                                data-id="<?= $row['id'] ?>"
                                                data-siswa_id="<?= $row['siswa_id'] ?>"
                                                data-tanggal="<?= $row['tanggal'] ?>"
                                                data-jenis="<?= $row['jenis_konseling'] ?>"
                                                data-topik="<?= htmlspecialchars($row['topik']) ?>"
                                                data-catatan="<?= htmlspecialchars($row['catatan']) ?>"
                                                data-tindak_lanjut="<?= htmlspecialchars($row['tindak_lanjut']) ?>"
                                                data-status="<?= $row['status'] ?>"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalEdit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-danger btn-sm btn-hapus" 
                                                data-id="<?= $row['id'] ?>"
                                                data-nama="<?= htmlspecialchars($row['nama_siswa']) ?>"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalHapus">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">
                                    <i class="fas fa-inbox fa-2x d-block mb-2"></i>
                                    Tidak ada catatan konseling
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer text-muted" style="font-size:11px;">
            <i class="fas fa-info-circle me-1"></i> 
            Menampilkan <?= count($konseling_list) ?> catatan konseling
        </div>
    </div>

    <!-- ========================================== -->
    <!-- CARD VIEW UNTUK MOBILE -->
    <!-- ========================================== -->
    <div class="d-block d-md-none">
        <?php if (count($konseling_list) > 0): ?>
            <?php $no = 1; ?>
            <?php foreach ($konseling_list as $row): ?>
                <div class="card mb-2 mobile-card">
                    <div class="card-body p-2">
                        <div class="row g-1">
                            <div class="col-8">
                                <span class="text-muted" style="font-size:9px;">Tanggal</span>
                                <div style="font-size:11px;"><?= date('d-m-Y', strtotime($row['tanggal'])) ?></div>
                            </div>
                            <div class="col-4 text-end">
                                <span class="badge bg-<?= $row['status'] == 'selesai' ? 'success' : ($row['status'] == 'proses' ? 'warning' : 'secondary') ?>" style="font-size:9px;">
                                    <?= ucfirst($row['status']) ?>
                                </span>
                            </div>
                        </div>
                        <div class="row g-1 mt-1">
                            <div class="col-12">
                                <span class="text-muted" style="font-size:9px;">Siswa</span>
                                <div class="fw-bold" style="font-size:12px;"><?= htmlspecialchars($row['nama_siswa'] ?? '-') ?></div>
                            </div>
                        </div>
                        <div class="row g-1 mt-1">
                            <div class="col-6">
                                <span class="text-muted" style="font-size:9px;">NISN</span>
                                <div style="font-size:11px;"><?= $row['nisn'] ?? '-' ?></div>
                            </div>
                            <div class="col-6">
                                <span class="text-muted" style="font-size:9px;">Kelas</span>
                                <div style="font-size:11px;"><?= $row['nama_kelas'] ?? '-' ?></div>
                            </div>
                        </div>
                        <div class="row g-1 mt-1">
                            <div class="col-6">
                                <span class="text-muted" style="font-size:9px;">Jenis</span>
                                <div style="font-size:11px;"><?= ucfirst($row['jenis_konseling'] ?? '-') ?></div>
                            </div>
                            <div class="col-6">
                                <span class="text-muted" style="font-size:9px;">Topik</span>
                                <div style="font-size:11px;"><?= htmlspecialchars($row['topik'] ?? '-') ?></div>
                            </div>
                        </div>
                        <div class="row g-1 mt-1">
                            <div class="col-12">
                                <span class="text-muted" style="font-size:9px;">Catatan</span>
                                <div style="font-size:10px;"><?= htmlspecialchars(substr($row['catatan'] ?? '', 0, 50)) ?>...</div>
                            </div>
                        </div>
                        <div class="row g-1 mt-1">
                            <div class="col-12 text-end">
                                <button class="btn btn-primary btn-sm btn-edit" 
                                        data-id="<?= $row['id'] ?>"
                                        data-siswa_id="<?= $row['siswa_id'] ?>"
                                        data-tanggal="<?= $row['tanggal'] ?>"
                                        data-jenis="<?= $row['jenis_konseling'] ?>"
                                        data-topik="<?= htmlspecialchars($row['topik']) ?>"
                                        data-catatan="<?= htmlspecialchars($row['catatan']) ?>"
                                        data-tindak_lanjut="<?= htmlspecialchars($row['tindak_lanjut']) ?>"
                                        data-status="<?= $row['status'] ?>"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#modalEdit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-danger btn-sm btn-hapus" 
                                        data-id="<?= $row['id'] ?>"
                                        data-nama="<?= htmlspecialchars($row['nama_siswa']) ?>"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#modalHapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="card mb-2">
                <div class="card-body p-4 text-center text-muted">
                    <i class="fas fa-inbox fa-3x d-block mb-2"></i>
                    Tidak ada catatan konseling
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Footer Mobile -->
        <div class="text-muted text-center" style="font-size:9px; padding:8px 0;">
            <i class="fas fa-info-circle me-1"></i> <?= count($konseling_list) ?> catatan konseling
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- MODAL TAMBAH -->
<!-- ============================================ -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="action" value="tambah">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus text-primary me-2"></i> Tambah Catatan Konseling</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label class="form-label">Siswa <span class="text-danger">*</span></label>
                            <select class="form-select" name="siswa_id" required>
                                <option value="">-- Pilih Siswa --</option>
                                <?php foreach ($siswa_options as $s): ?>
                                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nama_siswa']) ?> (<?= $s['nisn'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="tanggal" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label">Jenis Konseling <span class="text-danger">*</span></label>
                            <select class="form-select" name="jenis_konseling" required>
                                <option value="individu">Individu</option>
                                <option value="kelompok">Kelompok</option>
                                <option value="kelas">Kelas</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" name="status" required>
                                <option value="pending">Pending</option>
                                <option value="proses">Proses</option>
                                <option value="selesai">Selesai</option>
                            </select>
                        </div>
                        <div class="col-12 mb-2">
                            <label class="form-label">Topik <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="topik" placeholder="Masukkan topik konseling" required>
                        </div>
                        <div class="col-12 mb-2">
                            <label class="form-label">Catatan <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="catatan" rows="4" placeholder="Tulis catatan konseling" required></textarea>
                        </div>
                        <div class="col-12 mb-2">
                            <label class="form-label">Tindak Lanjut</label>
                            <textarea class="form-control" name="tindak_lanjut" rows="3" placeholder="Tulis tindak lanjut (opsional)"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- MODAL EDIT -->
<!-- ============================================ -->
<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit text-primary me-2"></i> Edit Catatan Konseling</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label class="form-label">Siswa <span class="text-danger">*</span></label>
                            <select class="form-select" name="siswa_id" id="edit_siswa_id" required>
                                <option value="">-- Pilih Siswa --</option>
                                <?php foreach ($siswa_options as $s): ?>
                                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nama_siswa']) ?> (<?= $s['nisn'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="tanggal" id="edit_tanggal" required>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label">Jenis Konseling <span class="text-danger">*</span></label>
                            <select class="form-select" name="jenis_konseling" id="edit_jenis" required>
                                <option value="individu">Individu</option>
                                <option value="kelompok">Kelompok</option>
                                <option value="kelas">Kelas</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" name="status" id="edit_status" required>
                                <option value="pending">Pending</option>
                                <option value="proses">Proses</option>
                                <option value="selesai">Selesai</option>
                            </select>
                        </div>
                        <div class="col-12 mb-2">
                            <label class="form-label">Topik <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="topik" id="edit_topik" required>
                        </div>
                        <div class="col-12 mb-2">
                            <label class="form-label">Catatan <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="catatan" id="edit_catatan" rows="4" required></textarea>
                        </div>
                        <div class="col-12 mb-2">
                            <label class="form-label">Tindak Lanjut</label>
                            <textarea class="form-control" name="tindak_lanjut" id="edit_tindak_lanjut" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- MODAL HAPUS -->
<!-- ============================================ -->
<div class="modal fade" id="modalHapus" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="action" value="hapus">
                <input type="hidden" name="id" id="hapus_id">
                <div class="modal-header">
                    <h5 class="modal-title text-danger"><i class="fas fa-trash me-2"></i> Hapus Catatan Konseling</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus catatan konseling untuk siswa <strong id="hapus_nama"></strong>?</p>
                    <p class="text-danger"><small>Tindakan ini tidak dapat dibatalkan!</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-trash me-1"></i> Hapus</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.dashboard-container {
    padding: 8px !important;
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
    font-size: 1rem;
    margin-bottom: 0;
}

.dashboard-subtitle {
    font-size: 0.65rem;
}

.card {
    border: none;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    margin-bottom: 12px;
    background: #fff;
}

.filter-card .card-body {
    padding: 12px;
}

.table-card .card-body {
    padding: 0;
}

.form-label {
    font-weight: 600;
    font-size: 11px;
    color: #4a5568;
    margin-bottom: 3px;
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

.table {
    font-size: 12px;
    margin-bottom: 0;
}

.table-bordered {
    border: 1px solid #dee2e6;
}

.table-bordered th,
.table-bordered td {
    border: 1px solid #dee2e6;
    padding: 6px 8px;
}

.table th {
    font-weight: 600;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    white-space: nowrap;
    background-color: #f8f9fa;
}

.table td {
    vertical-align: middle;
    font-size: 12px;
}

.table-striped tbody tr:nth-of-type(odd) {
    background-color: rgba(0,0,0,0.02);
}

.badge {
    font-size: 9px !important;
    padding: 3px 8px !important;
    border-radius: 4px;
}

.card-footer {
    background: transparent;
    border-top: 1px solid #e9ecef;
    padding: 6px 12px;
    font-size: 10px;
}

.mobile-card {
    border: 1px solid #e9ecef !important;
    border-radius: 8px !important;
}

.mobile-card .card-body {
    padding: 10px !important;
}

/* Modal */
.modal-content {
    border-radius: 12px;
    border: none;
}

.modal-header {
    border-bottom: 1px solid #e9ecef;
    padding: 12px 16px;
}

.modal-body {
    padding: 16px;
}

.modal-footer {
    border-top: 1px solid #e9ecef;
    padding: 12px 16px;
}

/* Responsive */
@media (max-width: 768px) {
    .dashboard-container {
        padding: 4px !important;
    }
    
    .dashboard-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 4px;
        margin-bottom: 8px;
    }
    
    .dashboard-title {
        font-size: 0.9rem;
    }
    
    .filter-card .card-body {
        padding: 8px;
    }
    
    .form-label {
        font-size: 10px;
    }
    
    .form-control, .form-select {
        font-size: 11px;
        padding: 4px 8px;
        height: 30px;
    }
    
    .btn-sm {
        font-size: 10px !important;
        padding: 3px 8px !important;
    }
    
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    .table {
        font-size: 10px;
        min-width: 700px;
    }
    
    .table th {
        font-size: 9px;
        padding: 4px 5px;
    }
    
    .table td {
        font-size: 10px;
        padding: 4px 5px;
    }
}

@media (max-width: 576px) {
    .dashboard-container {
        padding: 2px !important;
    }
    
    .dashboard-title {
        font-size: 0.8rem;
    }
    
    .dashboard-subtitle {
        font-size: 0.55rem;
    }
    
    .filter-card .card-body {
        padding: 6px;
    }
    
    .form-label {
        font-size: 9px;
    }
    
    .form-control, .form-select {
        font-size: 10px;
        padding: 3px 6px;
        height: 28px;
    }
    
    .btn-sm {
        font-size: 9px !important;
        padding: 2px 6px !important;
    }
    
    .mobile-card .card-body {
        padding: 6px !important;
    }
    
    .mobile-card .fw-bold {
        font-size: 10px !important;
    }
    
    .mobile-card .text-muted {
        font-size: 8px !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto close toast
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
    
    // Edit button - isi data ke modal
    document.querySelectorAll('.btn-edit').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.getElementById('edit_id').value = this.dataset.id;
            document.getElementById('edit_siswa_id').value = this.dataset.siswa_id;
            document.getElementById('edit_tanggal').value = this.dataset.tanggal;
            document.getElementById('edit_jenis').value = this.dataset.jenis;
            document.getElementById('edit_status').value = this.dataset.status;
            document.getElementById('edit_topik').value = this.dataset.topik;
            document.getElementById('edit_catatan').value = this.dataset.catatan;
            document.getElementById('edit_tindak_lanjut').value = this.dataset.tindak_lanjut || '';
        });
    });
    
    // Hapus button - set id dan nama
    document.querySelectorAll('.btn-hapus').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.getElementById('hapus_id').value = this.dataset.id;
            document.getElementById('hapus_nama').textContent = this.dataset.nama || 'tidak diketahui';
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>