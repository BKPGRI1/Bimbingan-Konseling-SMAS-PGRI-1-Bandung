<?php
// admin/jenis_pelanggaran.php
include '../includes/config.php';
checkLogin();

$page_title = 'Jenis Pelanggaran';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if ($action == 'tambah') {
        $data = [
            'nama_pelanggaran' => sanitize($_POST['nama_pelanggaran']),
            'poin' => (int)$_POST['poin'],
            'kategori' => sanitize($_POST['kategori']),
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];
        
        if (tambahJenisPelanggaran($data)) {
            $_SESSION['toast'] = [
                'message' => 'Jenis pelanggaran berhasil ditambahkan!',
                'type' => 'success'
            ];
        } else {
            $_SESSION['toast'] = [
                'message' => 'Gagal menambahkan jenis pelanggaran!',
                'type' => 'danger'
            ];
        }
        header("Location: " . APP_URL . "admin/jenis_pelanggaran.php");
        exit();
    }
    
    if ($action == 'edit') {
        $id = (int)$_POST['id'];
        $data = [
            'nama_pelanggaran' => sanitize($_POST['nama_pelanggaran']),
            'poin' => (int)$_POST['poin'],
            'kategori' => sanitize($_POST['kategori']),
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];
        
        if (updateJenisPelanggaran($data, $id)) {
            $_SESSION['toast'] = [
                'message' => 'Jenis pelanggaran berhasil diupdate!',
                'type' => 'success'
            ];
        } else {
            $_SESSION['toast'] = [
                'message' => 'Gagal mengupdate jenis pelanggaran!',
                'type' => 'danger'
            ];
        }
        header("Location: " . APP_URL . "admin/jenis_pelanggaran.php");
        exit();
    }
    
    if ($action == 'import') {
        if (isset($_FILES['file_csv']) && $_FILES['file_csv']['error'] == 0) {
            $result = importJenisPelanggaranCSV($_FILES['file_csv']);
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
        header("Location: " . APP_URL . "admin/jenis_pelanggaran.php");
        exit();
    }
}

// Handle GET actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($action == 'delete' && $id > 0) {
        if (hapusJenisPelanggaran($id)) {
            $_SESSION['toast'] = [
                'message' => 'Jenis pelanggaran berhasil dihapus!',
                'type' => 'success'
            ];
        } else {
            $_SESSION['toast'] = [
                'message' => 'Jenis pelanggaran tidak bisa dihapus karena sudah digunakan!',
                'type' => 'danger'
            ];
        }
        header("Location: " . APP_URL . "admin/jenis_pelanggaran.php");
        exit();
    }
    
    if ($action == 'toggle' && $id > 0) {
        if (toggleJenisPelanggaran($id)) {
            $_SESSION['toast'] = [
                'message' => 'Status pelanggaran berhasil diubah!',
                'type' => 'success'
            ];
        } else {
            $_SESSION['toast'] = [
                'message' => 'Gagal mengubah status!',
                'type' => 'danger'
            ];
        }
        header("Location: " . APP_URL . "admin/jenis_pelanggaran.php");
        exit();
    }
}

// Get data
$pelanggaran_list = getJenisPelanggaranAll();

include '../includes/header.php';
?>

<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
        <h4 class="mb-0">
            <i class="fas fa-gavel text-primary me-2"></i> Jenis Pelanggaran
        </h4>
        <div class="d-flex gap-2 flex-wrap">
            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalImport">
                <i class="fas fa-file-import me-1"></i> Import CSV
            </button>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalPelanggaran">
                <i class="fas fa-plus me-1"></i> Tambah Pelanggaran
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

    <!-- Search -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="" class="row g-2">
                <div class="col-md-9">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" name="search" 
                               placeholder="Cari nama pelanggaran..." 
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

    <!-- Tabel Jenis Pelanggaran -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="45" class="text-center">No</th>
                            <th>Nama Pelanggaran</th>
                            <th class="text-center">Poin</th>
                            <th class="text-center">Kategori</th>
                            <th class="text-center">Status</th>
                            <th class="text-center" width="180">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($pelanggaran_list)): ?>
                            <?php 
                            $no = 1; 
                            $search = isset($_GET['search']) ? strtolower($_GET['search']) : '';
                            $filtered = 0;
                            
                            foreach ($pelanggaran_list as $pelanggaran): 
                                if (!empty($search) && !strpos(strtolower($pelanggaran['nama_pelanggaran']), $search)) {
                                    continue;
                                }
                                $filtered++;
                            ?>
                                <tr>
                                    <td class="text-center"><?= $filtered ?></td>
                                    <td><strong><?= htmlspecialchars($pelanggaran['nama_pelanggaran']) ?></strong></td>
                                    <td class="text-center">
                                        <span class="badge bg-danger" style="font-size: 12px;"><?= $pelanggaran['poin'] ?> Poin</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-<?= getKategoriBadge($pelanggaran['kategori']) ?>">
                                            <?= getKategoriLabel($pelanggaran['kategori']) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($pelanggaran['is_active']): ?>
                                            <span class="badge bg-success">Aktif</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Nonaktif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button class="btn btn-outline-primary" onclick="editPelanggaran(<?= $pelanggaran['id'] ?>)" title="Edit" style="padding: 2px 6px; font-size: 11px;">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="<?= APP_URL ?>admin/jenis_pelanggaran.php?action=toggle&id=<?= $pelanggaran['id'] ?>" 
                                               class="btn btn-outline-<?= $pelanggaran['is_active'] ? 'warning' : 'success' ?>" 
                                               title="<?= $pelanggaran['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>" 
                                               style="padding: 2px 6px; font-size: 11px;">
                                                <i class="fas fa-<?= $pelanggaran['is_active'] ? 'pause' : 'play' ?>"></i>
                                            </a>
                                            <a href="<?= APP_URL ?>admin/jenis_pelanggaran.php?action=delete&id=<?= $pelanggaran['id'] ?>" 
                                               class="btn btn-outline-danger" 
                                               onclick="return confirm('Apakah Anda yakin ingin menghapus jenis pelanggaran ini?\\n\\nData tidak bisa dihapus jika sudah digunakan!')" 
                                               title="Hapus" style="padding: 2px 6px; font-size: 11px;">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if ($filtered == 0): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="fas fa-search fa-2x mb-2 d-block opacity-25"></i>
                                        Tidak ada data yang sesuai dengan pencarian
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="fas fa-gavel fa-2x mb-2 d-block opacity-25"></i>
                                    Belum ada data jenis pelanggaran
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah/Edit Pelanggaran -->
<div class="modal fade" id="modalPelanggaran" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalPelanggaranTitle">
                    <i class="fas fa-plus-circle text-primary me-2"></i> Tambah Jenis Pelanggaran
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="" id="formPelanggaran">
                <input type="hidden" name="action" id="formAction" value="tambah">
                <input type="hidden" name="id" id="pelanggaranId" value="">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Nama Pelanggaran <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nama_pelanggaran" id="nama_pelanggaran" 
                                   placeholder="Masukkan nama pelanggaran" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Poin <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="poin" id="poin" 
                                   placeholder="Masukkan jumlah poin" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kategori <span class="text-danger">*</span></label>
                            <select class="form-select" name="kategori" id="kategori" required>
                                <option value="">Pilih Kategori</option>
                                <option value="ringan">Ringan</option>
                                <option value="sedang">Sedang</option>
                                <option value="berat">Berat</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="is_active" id="is_active" value="1" checked>
                                <label class="form-check-label" for="is_active">Aktif</label>
                            </div>
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
                    <i class="fas fa-file-import text-success me-2"></i> Import Jenis Pelanggaran CSV
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="action" value="import">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Format CSV (pemisah ;):</strong><br>
                        Nama Pelanggaran;Poin;Kategori;Status
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
                            <li>Kategori: ringan, sedang, berat</li>
                            <li>Status: 1 (Aktif) atau 0 (Nonaktif)</li>
                        </ul>
                    </div>
                    <div class="mt-3">
                        <a href="<?= APP_URL ?>assets/sample/download_pelanggaran_sample.php" class="btn btn-sm btn-outline-primary" target="_blank">
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

<script>
// Edit Pelanggaran via AJAX
function editPelanggaran(id) {
    fetch('<?= APP_URL ?>admin/jenis_pelanggaran_ajax.php?id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data) {
                document.getElementById('modalPelanggaranTitle').innerHTML = 
                    '<i class="fas fa-edit text-primary me-2"></i> Edit Jenis Pelanggaran';
                document.getElementById('formAction').value = 'edit';
                document.getElementById('pelanggaranId').value = data.id;
                document.getElementById('nama_pelanggaran').value = data.nama_pelanggaran;
                document.getElementById('poin').value = data.poin;
                document.getElementById('kategori').value = data.kategori;
                document.getElementById('is_active').checked = data.is_active == 1;
                
                var modal = new bootstrap.Modal(document.getElementById('modalPelanggaran'));
                modal.show();
            }
        })
        .catch(() => {
            alert('Gagal mengambil data pelanggaran!');
        });
}

// Reset form untuk tambah
document.getElementById('modalPelanggaran').addEventListener('hidden.bs.modal', function() {
    document.getElementById('modalPelanggaranTitle').innerHTML = 
        '<i class="fas fa-plus-circle text-primary me-2"></i> Tambah Jenis Pelanggaran';
    document.getElementById('formAction').value = 'tambah';
    document.getElementById('pelanggaranId').value = '';
    document.getElementById('formPelanggaran').reset();
    document.getElementById('is_active').checked = true;
});

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

/* Badge */
.badge {
    font-size: 10px !important;
    padding: 3px 8px !important;
}

/* Button */
.btn-group-sm .btn {
    padding: 2px 6px !important;
    font-size: 11px !important;
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