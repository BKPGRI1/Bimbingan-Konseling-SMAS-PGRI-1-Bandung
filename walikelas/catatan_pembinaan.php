<?php
// walikelas/catatan_pembinaan.php
include '../includes/config.php';
checkLogin();

// Cek role
if (!hasRole('walikelas')) {
    $_SESSION['toast'] = [
        'message' => 'Anda tidak memiliki akses ke halaman ini!',
        'type' => 'danger'
    ];
    header("Location: " . APP_URL . "login.php");
    exit();
}

$page_title = 'Catatan Pembinaan';

// Get data wali kelas
$user = getCurrentUser();
$kelas_wali = getRow("SELECT * FROM kelas WHERE wali_kelas_id = " . $user['id']);
$kelas_id = $kelas_wali ? $kelas_wali['id'] : 0;
$nama_kelas = $kelas_wali ? $kelas_wali['nama_kelas'] : 'Belum ditentukan';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if ($action == 'tambah') {
        $data = [
            'siswa_id' => (int)$_POST['siswa_id'],
            'wali_kelas_id' => $user['id'],
            'tanggal' => sanitize($_POST['tanggal']),
            'catatan' => sanitize($_POST['catatan']),
            'tindak_lanjut' => sanitize($_POST['tindak_lanjut']),
            'status' => sanitize($_POST['status'])
        ];
        
        if (insert('catatan_pembinaan', $data)) {
            $_SESSION['toast'] = [
                'message' => 'Catatan pembinaan berhasil ditambahkan!',
                'type' => 'success'
            ];
        } else {
            $_SESSION['toast'] = [
                'message' => 'Gagal menambahkan catatan pembinaan!',
                'type' => 'danger'
            ];
        }
        header("Location: " . APP_URL . "walikelas/catatan_pembinaan.php");
        exit();
    }
    
    if ($action == 'edit') {
        $id = (int)$_POST['id'];
        $data = [
            'siswa_id' => (int)$_POST['siswa_id'],
            'tanggal' => sanitize($_POST['tanggal']),
            'catatan' => sanitize($_POST['catatan']),
            'tindak_lanjut' => sanitize($_POST['tindak_lanjut']),
            'status' => sanitize($_POST['status'])
        ];
        
        if (update('catatan_pembinaan', $data, "id = $id")) {
            $_SESSION['toast'] = [
                'message' => 'Catatan pembinaan berhasil diupdate!',
                'type' => 'success'
            ];
        } else {
            $_SESSION['toast'] = [
                'message' => 'Gagal mengupdate catatan pembinaan!',
                'type' => 'danger'
            ];
        }
        header("Location: " . APP_URL . "walikelas/catatan_pembinaan.php");
        exit();
    }
    
    if ($action == 'delete') {
        $id = (int)$_POST['id'];
        if (delete('catatan_pembinaan', "id = $id")) {
            $_SESSION['toast'] = [
                'message' => 'Catatan pembinaan berhasil dihapus!',
                'type' => 'success'
            ];
        } else {
            $_SESSION['toast'] = [
                'message' => 'Gagal menghapus catatan pembinaan!',
                'type' => 'danger'
            ];
        }
        header("Location: " . APP_URL . "walikelas/catatan_pembinaan.php");
        exit();
    }
}

// Get data siswa di kelas wali
$siswa_list = [];
if ($kelas_id > 0) {
    $siswa_list = getSiswaByKelas($kelas_id);
}

// Get catatan pembinaan
$catatan_list = [];
if ($kelas_id > 0) {
    $catatan_list = getRows("SELECT c.*, s.nama_siswa, s.nisn 
                             FROM catatan_pembinaan c
                             LEFT JOIN siswa s ON c.siswa_id = s.id
                             WHERE s.kelas_id = $kelas_id
                             ORDER BY c.tanggal DESC, c.created_at DESC");
}

// Filter search
$search = isset($_GET['search']) ? $_GET['search'] : '';
if (!empty($search)) {
    $search = strtolower($search);
    $catatan_list = array_filter($catatan_list, function($item) use ($search) {
        return strpos(strtolower($item['nama_siswa']), $search) !== false ||
               strpos(strtolower($item['nisn'] ?? ''), $search) !== false ||
               strpos(strtolower($item['catatan']), $search) !== false;
    });
}

include '../includes/header_walikelas.php';
?>

<div class="container-fluid px-2 px-md-3 px-lg-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
        <div>
            <h4 class="mb-0">
                <i class="fas fa-edit text-primary me-2"></i> Catatan Pembinaan
            </h4>
            <small class="text-muted">
                <i class="fas fa-school me-1"></i> Kelas: <strong><?= htmlspecialchars($nama_kelas) ?></strong>
                <?php if ($kelas_id > 0): ?>
                    <span class="badge bg-primary ms-1">ID: <?= $kelas_id ?></span>
                <?php endif; ?>
                <span class="badge bg-secondary ms-1">
                    <i class="fas fa-file-alt me-1"></i> <?= count($catatan_list) ?> Catatan
                </span>
            </small>
        </div>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCatatan">
            <i class="fas fa-plus me-1"></i> Tambah Catatan
        </button>
    </div>

    <?php if ($kelas_id == 0): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Anda belum ditugaskan sebagai wali kelas. Silahkan hubungi administrator.
        </div>
    <?php else: ?>

    <!-- Search -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="" class="row g-2">
                <div class="col-md-9">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" name="search" 
                               placeholder="Cari NISN, Nama Siswa atau Catatan..." 
                               value="<?= htmlspecialchars($search) ?>">
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

    <!-- Tabel Catatan -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="45" class="text-center">No</th>
                            <th>Tanggal</th>
                            <th>NISN</th>
                            <th>Nama Siswa</th>
                            <th>Catatan</th>
                            <th>Tindak Lanjut</th>
                            <th class="text-center">Status</th>
                            <th class="text-center" width="120">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($catatan_list)): ?>
                            <?php $no = 1; foreach ($catatan_list as $catatan): ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?></td>
                                    <td><?= formatTanggalIndo($catatan['tanggal']) ?></td>
                                    <td><span class="badge bg-light text-dark"><?= htmlspecialchars($catatan['nisn'] ?? '-') ?></span></td>
                                    <td><strong><?= htmlspecialchars($catatan['nama_siswa']) ?></strong></td>
                                    <td><?= htmlspecialchars(substr($catatan['catatan'], 0, 50)) ?><?= strlen($catatan['catatan']) > 50 ? '...' : '' ?></td>
                                    <td><?= htmlspecialchars(substr($catatan['tindak_lanjut'] ?? '-', 0, 50)) ?><?= strlen($catatan['tindak_lanjut'] ?? '') > 50 ? '...' : '' ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-<?= $catatan['status'] == 'selesai' ? 'success' : 'warning' ?>">
                                            <?= $catatan['status'] == 'selesai' ? 'Selesai' : 'Pending' ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button class="btn btn-outline-primary" onclick="editCatatan(<?= $catatan['id'] ?>)" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-outline-danger" onclick="hapusCatatan(<?= $catatan['id'] ?>)" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block opacity-25"></i>
                                    Belum ada catatan pembinaan
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if (!empty($catatan_list)): ?>
            <div class="card-footer bg-transparent text-muted">
                <i class="fas fa-info-circle me-1"></i>
                Menampilkan <?= count($catatan_list) ?> catatan
            </div>
        <?php endif; ?>
    </div>

    <?php endif; ?>
</div>

<!-- Modal Tambah/Edit Catatan -->
<div class="modal fade" id="modalCatatan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalCatatanTitle">
                    <i class="fas fa-plus-circle text-primary me-2"></i> Tambah Catatan Pembinaan
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="" id="formCatatan">
                <input type="hidden" name="action" id="formAction" value="tambah">
                <input type="hidden" name="id" id="catatanId" value="">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Siswa <span class="text-danger">*</span></label>
                            <select class="form-select" name="siswa_id" id="siswa_id" required>
                                <option value="">Pilih Siswa</option>
                                <?php foreach ($siswa_list as $siswa): ?>
                                    <option value="<?= $siswa['id'] ?>">
                                        <?= htmlspecialchars($siswa['nama_siswa']) ?> (<?= htmlspecialchars($siswa['nisn']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="tanggal" id="tanggal" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Catatan <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="catatan" id="catatan" rows="4" placeholder="Masukkan catatan pembinaan..." required></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Tindak Lanjut</label>
                            <textarea class="form-control" name="tindak_lanjut" id="tindak_lanjut" rows="3" placeholder="Masukkan tindak lanjut..."></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" name="status" id="status" required>
                                <option value="pending">Pending</option>
                                <option value="selesai">Selesai</option>
                            </select>
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

<!-- Modal Delete -->
<div class="modal fade" id="modalDelete" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger">
                    <i class="fas fa-exclamation-triangle text-danger me-2"></i> Konfirmasi Hapus
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="deleteId" value="">
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus catatan pembinaan ini?</p>
                    <p class="text-muted" style="font-size: 14px;">Data yang dihapus tidak dapat dikembalikan!</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i> Hapus
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Edit Catatan
function editCatatan(id) {
    fetch('<?= APP_URL ?>walikelas/ajax/get_catatan.php?id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data) {
                document.getElementById('modalCatatanTitle').innerHTML = 
                    '<i class="fas fa-edit text-primary me-2"></i> Edit Catatan Pembinaan';
                document.getElementById('formAction').value = 'edit';
                document.getElementById('catatanId').value = data.id;
                document.getElementById('siswa_id').value = data.siswa_id;
                document.getElementById('tanggal').value = data.tanggal;
                document.getElementById('catatan').value = data.catatan;
                document.getElementById('tindak_lanjut').value = data.tindak_lanjut || '';
                document.getElementById('status').value = data.status;
                
                var modal = new bootstrap.Modal(document.getElementById('modalCatatan'));
                modal.show();
            }
        })
        .catch(() => {
            alert('Gagal mengambil data catatan!');
        });
}

// Hapus Catatan
function hapusCatatan(id) {
    document.getElementById('deleteId').value = id;
    var modal = new bootstrap.Modal(document.getElementById('modalDelete'));
    modal.show();
}

// Reset form untuk tambah
document.getElementById('modalCatatan').addEventListener('hidden.bs.modal', function() {
    document.getElementById('modalCatatanTitle').innerHTML = 
        '<i class="fas fa-plus-circle text-primary me-2"></i> Tambah Catatan Pembinaan';
    document.getElementById('formAction').value = 'tambah';
    document.getElementById('catatanId').value = '';
    document.getElementById('formCatatan').reset();
    document.getElementById('tanggal').value = '<?= date('Y-m-d') ?>';
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
    font-size: 12px;
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

/* Badge */
.badge {
    font-size: 10px !important;
    padding: 3px 8px !important;
}

/* Button */
.btn-group-sm .btn {
    padding: 2px 6px !important;
    font-size: 12px !important;
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
    
    .badge {
        font-size: 8px !important;
        padding: 2px 5px !important;
    }
    
    .btn-group-sm .btn {
        padding: 1px 4px !important;
        font-size: 9px !important;
    }
}
</style>

<?php include '../includes/footer.php'; ?>