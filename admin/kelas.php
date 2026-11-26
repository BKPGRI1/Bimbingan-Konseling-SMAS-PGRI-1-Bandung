<?php
// admin/kelas.php
include '../includes/config.php';
checkLogin();

$page_title = 'Kelola Kelas';

// Handle AJAX requests
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    
    if ($_GET['ajax'] == 'get_siswa') {
        $kelas_id = isset($_GET['kelas_id']) ? (int)$_GET['kelas_id'] : 0;
        $siswa = getSiswaByKelas($kelas_id);
        echo json_encode($siswa);
        exit;
    }
    
    if ($_GET['ajax'] == 'get_kelas') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $kelas = getKelasById($id);
        echo json_encode($kelas);
        exit;
    }
    
    if ($_GET['ajax'] == 'get_kelas_tujuan') {
        $kelas_id = isset($_GET['kelas_id']) ? (int)$_GET['kelas_id'] : 0;
        $kelas = getKelasById($kelas_id);
        $kelas_list = getKelasOptions();
        
        $result = [];
        foreach ($kelas_list as $k) {
            if ($k['id'] != $kelas_id) {
                $result[] = $k;
            }
        }
        echo json_encode($result);
        exit;
    }
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if ($action == 'tambah') {
        $data = [
            'nama_kelas' => sanitize($_POST['nama_kelas']),
            'tingkat' => sanitize($_POST['tingkat']),
            'jurusan' => sanitize($_POST['jurusan']),
            'wali_kelas_id' => !empty($_POST['wali_kelas_id']) ? (int)$_POST['wali_kelas_id'] : null,
            'tahun_ajaran' => sanitize($_POST['tahun_ajaran'])
        ];
        
        if (insert('kelas', $data)) {
            $_SESSION['toast'] = [
                'message' => 'Kelas berhasil ditambahkan!',
                'type' => 'success'
            ];
        } else {
            $_SESSION['toast'] = [
                'message' => 'Gagal menambahkan kelas!',
                'type' => 'danger'
            ];
        }
        header("Location: " . APP_URL . "admin/kelas.php");
        exit();
    }
    
    if ($action == 'edit') {
        $id = (int)$_POST['id'];
        $data = [
            'nama_kelas' => sanitize($_POST['nama_kelas']),
            'tingkat' => sanitize($_POST['tingkat']),
            'jurusan' => sanitize($_POST['jurusan']),
            'wali_kelas_id' => !empty($_POST['wali_kelas_id']) ? (int)$_POST['wali_kelas_id'] : null,
            'tahun_ajaran' => sanitize($_POST['tahun_ajaran'])
        ];
        
        if (update('kelas', $data, "id = $id")) {
            $_SESSION['toast'] = [
                'message' => 'Kelas berhasil diupdate!',
                'type' => 'success'
            ];
        } else {
            $_SESSION['toast'] = [
                'message' => 'Gagal mengupdate kelas!',
                'type' => 'danger'
            ];
        }
        header("Location: " . APP_URL . "admin/kelas.php");
        exit();
    }
    
    if ($action == 'naikkan') {
        $kelas_asal_id = (int)$_POST['kelas_asal_id'];
        $kelas_tujuan_id = (int)$_POST['kelas_tujuan_id'];
        $siswa_ids = isset($_POST['siswa_ids']) ? $_POST['siswa_ids'] : [];
        
        if (empty($siswa_ids)) {
            $_SESSION['toast'] = [
                'message' => 'Pilih minimal 1 siswa untuk dinaikkan!',
                'type' => 'warning'
            ];
            header("Location: " . APP_URL . "admin/kelas.php");
            exit();
        }
        
        if (empty($kelas_tujuan_id)) {
            $_SESSION['toast'] = [
                'message' => 'Pilih kelas tujuan!',
                'type' => 'warning'
            ];
            header("Location: " . APP_URL . "admin/kelas.php");
            exit();
        }
        
        $success = 0;
        $failed = 0;
        
        foreach ($siswa_ids as $siswa_id) {
            $update = update('siswa', ['kelas_id' => $kelas_tujuan_id], "id = $siswa_id");
            if ($update) {
                $success++;
            } else {
                $failed++;
            }
        }
        
        $_SESSION['toast'] = [
            'message' => "Naikkan selesai! $success siswa berhasil, $failed siswa gagal.",
            'type' => $failed > 0 ? 'warning' : 'success'
        ];
        header("Location: " . APP_URL . "admin/kelas.php");
        exit();
    }
}

// Handle GET actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($action == 'delete' && $id > 0) {
        if (deleteKelas($id)) {
            $_SESSION['toast'] = [
                'message' => 'Kelas berhasil dihapus!',
                'type' => 'success'
            ];
        } else {
            $_SESSION['toast'] = [
                'message' => 'Kelas tidak bisa dihapus karena masih memiliki siswa!',
                'type' => 'danger'
            ];
        }
        header("Location: " . APP_URL . "admin/kelas.php");
        exit();
    }
}

// Get data
$kelas_list = getKelas();
$guru_list = getGuru();

include '../includes/header.php';
?>

<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
        <h4 class="mb-0">
            <i class="fas fa-building text-primary me-2"></i> Manajemen Kelas
        </h4>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalKelas">
            <i class="fas fa-plus me-1"></i> Tambah Kelas
        </button>
    </div>

    <!-- Toast Notification - Minimalis Kecil di Pojok Kanan Atas -->
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

    <!-- Tabel Kelas -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0" id="tableKelas">
                    <thead class="table-light">
                        <tr>
                            <th width="45" class="text-center">No</th>
                            <th width="65" class="text-center">Kelas ID</th>
                            <th>Nama Kelas</th>
                            <th>Tingkat</th>
                            <th>Jurusan</th>
                            <th>Wali Kelas</th>
                            <th>Tahun Ajaran</th>
                            <th class="text-center">Jumlah Murid</th>
                            <th class="text-center" width="210">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($kelas_list)): ?>
                            <?php $no = 1; foreach ($kelas_list as $kelas): ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-primary" style="font-size: 10px;"><?= $kelas['id'] ?></span>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($kelas['nama_kelas']) ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary" style="font-size: 10px;"><?= htmlspecialchars($kelas['tingkat'] ?? '-') ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($kelas['jurusan'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($kelas['wali_kelas'] ?? 'Belum ditentukan') ?></td>
                                    <td><?= htmlspecialchars($kelas['tahun_ajaran'] ?? '-') ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-info rounded-pill px-3 py-2" style="font-size: 11px;">
                                            <?= $kelas['jumlah_siswa'] ?? 0 ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button class="btn btn-outline-info" onclick="detailSiswa(<?= $kelas['id'] ?>, '<?= htmlspecialchars($kelas['nama_kelas']) ?>')" title="Detail Siswa" style="padding: 2px 6px; font-size: 11px;">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-outline-success" onclick="editKelas(<?= $kelas['id'] ?>)" title="Edit" style="padding: 2px 6px; font-size: 11px;">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-outline-warning" onclick="naikkanKelas(<?= $kelas['id'] ?>, '<?= htmlspecialchars($kelas['nama_kelas']) ?>')" title="Naikkan Siswa" style="padding: 2px 6px; font-size: 11px;">
                                                <i class="fas fa-arrow-up"></i>
                                            </button>
                                            <button class="btn btn-outline-danger" onclick="hapusKelas(<?= $kelas['id'] ?>)" title="Hapus" style="padding: 2px 6px; font-size: 11px;">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    <i class="fas fa-building fa-2x mb-2 d-block opacity-25"></i>
                                    Belum ada data kelas
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah/Edit Kelas -->
<div class="modal fade" id="modalKelas" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalKelasTitle">
                    <i class="fas fa-plus-circle text-primary me-2"></i> Tambah Kelas
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="" id="formKelas">
                <input type="hidden" name="action" id="formAction" value="tambah">
                <input type="hidden" name="id" id="kelasId" value="">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Nama Kelas <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nama_kelas" id="nama_kelas" 
                                   placeholder="Contoh: X IPA 1" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Tingkat <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="tingkat" id="tingkat" 
                                   placeholder="Contoh: X, XI, XII" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Jurusan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="jurusan" id="jurusan" 
                                   placeholder="Contoh: IPA, IPS" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Wali Kelas</label>
                            <select class="form-select" name="wali_kelas_id" id="wali_kelas_id">
                                <option value="">Pilih Wali Kelas</option>
                                <?php foreach ($guru_list as $guru): ?>
                                    <option value="<?= $guru['id'] ?>">
                                        <?= htmlspecialchars($guru['nama_lengkap']) ?> 
                                        (<?= htmlspecialchars($guru['nip'] ?? '-') ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Tahun Ajaran <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="tahun_ajaran" id="tahun_ajaran" 
                                   placeholder="Contoh: 2024/2025" value="<?= date('Y') . '/' . (date('Y') + 1) ?>" required>
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

<!-- Modal Detail Siswa -->
<div class="modal fade" id="modalDetailSiswa" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-users text-primary me-2"></i> 
                    Daftar Siswa - <span id="detailKelasName"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="tableDetailSiswa">
                        <thead class="table-light">
                            <tr>
                                <th width="45">No</th>
                                <th>NISN</th>
                                <th>Nama Siswa</th>
                                <th>JK</th>
                                <th class="text-center">Poin</th>
                                <th class="text-center" width="80">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="detailSiswaBody">
                            <tr>
                                <td colspan="6" class="text-center text-muted py-3">
                                    <i class="fas fa-spinner fa-spin me-2"></i> Memuat data...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Naikkan Kelas -->
<div class="modal fade" id="modalNaikkan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-warning">
                    <i class="fas fa-arrow-up text-warning me-2"></i> Naikkan Siswa
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="" id="formNaikkan">
                <input type="hidden" name="action" value="naikkan">
                <input type="hidden" name="kelas_asal_id" id="kelasAsalId" value="">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Kelas Asal: <span id="naikkanKelasName"></span></strong>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Pilih Kelas Tujuan <span class="text-danger">*</span></label>
                        <select class="form-select" name="kelas_tujuan_id" id="kelasTujuanId" required>
                            <option value="">-- Pilih Kelas Tujuan --</option>
                        </select>
                    </div>
                    
                    <hr>
                    
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold">Pilih Siswa yang akan Dinaikkan</span>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="selectAllSiswa" onclick="toggleAllSiswa(this)">
                                <label class="form-check-label" for="selectAllSiswa">
                                    <strong>Pilih Semua</strong>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th width="35" class="text-center">#</th>
                                    <th width="40" class="text-center">Pilih</th>
                                    <th>NISN</th>
                                    <th>Nama Siswa</th>
                                    <th class="text-center">JK</th>
                                    <th class="text-center">Poin</th>
                                </tr>
                            </thead>
                            <tbody id="naikkanSiswaBody">
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-3">
                                        <i class="fas fa-spinner fa-spin me-2"></i> Memuat data...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="alert alert-warning mt-3">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Perhatian:</strong> Siswa yang dipilih akan dipindahkan ke kelas tujuan.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning" id="btnNaikkan">
                        <i class="fas fa-arrow-up me-1"></i> Naikkan Siswa Terpilih
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Edit Kelas
function editKelas(id) {
    fetch('<?= APP_URL ?>admin/kelas.php?ajax=get_kelas&id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data) {
                document.getElementById('modalKelasTitle').innerHTML = 
                    '<i class="fas fa-edit text-primary me-2"></i> Edit Kelas (ID: ' + data.id + ')';
                document.getElementById('formAction').value = 'edit';
                document.getElementById('kelasId').value = data.id;
                document.getElementById('nama_kelas').value = data.nama_kelas;
                document.getElementById('tingkat').value = data.tingkat;
                document.getElementById('jurusan').value = data.jurusan;
                document.getElementById('wali_kelas_id').value = data.wali_kelas_id || '';
                document.getElementById('tahun_ajaran').value = data.tahun_ajaran;
                
                var modal = new bootstrap.Modal(document.getElementById('modalKelas'));
                modal.show();
            }
        })
        .catch(() => {
            alert('Gagal mengambil data kelas!');
        });
}

// Reset form untuk tambah
document.getElementById('modalKelas').addEventListener('hidden.bs.modal', function() {
    document.getElementById('modalKelasTitle').innerHTML = 
        '<i class="fas fa-plus-circle text-primary me-2"></i> Tambah Kelas';
    document.getElementById('formAction').value = 'tambah';
    document.getElementById('kelasId').value = '';
    document.getElementById('formKelas').reset();
    document.getElementById('tahun_ajaran').value = '<?= date('Y') . '/' . (date('Y') + 1) ?>';
});

// Detail Siswa - Buka di halaman terpisah
function detailSiswa(kelasId, kelasName) {
    window.location.href = '<?= APP_URL ?>admin/kelas_detail.php?id=' + kelasId + '&nama=' + encodeURIComponent(kelasName);
}

// Naikkan Kelas
function naikkanKelas(kelasId, kelasName) {
    document.getElementById('kelasAsalId').value = kelasId;
    document.getElementById('naikkanKelasName').textContent = kelasName + ' (ID: ' + kelasId + ')';
    document.getElementById('naikkanSiswaBody').innerHTML = 
        '<tr><td colspan="6" class="text-center text-muted py-3">' +
        '<i class="fas fa-spinner fa-spin me-2"></i> Memuat data...</td></tr>';
    
    var selectTujuan = document.getElementById('kelasTujuanId');
    selectTujuan.innerHTML = '<option value="">-- Pilih Kelas Tujuan --</option>';
    
    var modal = new bootstrap.Modal(document.getElementById('modalNaikkan'));
    modal.show();
    
    // Load kelas tujuan
    fetch('<?= APP_URL ?>admin/kelas.php?ajax=get_kelas_tujuan&kelas_id=' + kelasId)
        .then(response => response.json())
        .then(data => {
            if (data && data.length > 0) {
                data.forEach(function(kelas) {
                    var option = document.createElement('option');
                    option.value = kelas.id;
                    option.textContent = kelas.nama_kelas + ' (ID: ' + kelas.id + ')';
                    selectTujuan.appendChild(option);
                });
            } else {
                var option = document.createElement('option');
                option.value = '';
                option.textContent = '-- Tidak ada kelas lain --';
                selectTujuan.appendChild(option);
            }
        })
        .catch(() => {
            var option = document.createElement('option');
            option.value = '';
            option.textContent = '-- Gagal memuat kelas --';
            selectTujuan.appendChild(option);
        });
    
    // Load siswa
    fetch('<?= APP_URL ?>admin/kelas.php?ajax=get_siswa&kelas_id=' + kelasId)
        .then(response => response.json())
        .then(data => {
            var html = '';
            if (data && data.length > 0) {
                data.forEach(function(siswa, index) {
                    var poinBadge = siswa.poin >= 300 ? 'bg-danger' : 
                                   siswa.poin >= 200 ? 'bg-warning' : 
                                   siswa.poin >= 100 ? 'bg-info' : 'bg-success';
                    var checked = index < 5 ? 'checked' : '';
                    html += '<tr>' +
                        '<td class="text-center">' + (index + 1) + '</td>' +
                        '<td class="text-center">' +
                            '<input type="checkbox" class="form-check-input siswa-checkbox" name="siswa_ids[]" value="' + siswa.id + '" ' + checked + '>' +
                        '</td>' +
                        '<td><span class="badge bg-light text-dark" style="font-size: 10px;">' + (siswa.nisn || '-') + '</span></td>' +
                        '<td>' + siswa.nama_siswa + '</td>' +
                        '<td class="text-center">' + (siswa.jenis_kelamin == 'L' ? 'L' : 'P') + '</td>' +
                        '<td class="text-center"><span class="badge ' + poinBadge + '" style="font-size: 10px;">' + (siswa.poin || 0) + '</span></td>' +
                        '</tr>';
                });
            } else {
                html = '<tr><td colspan="6" class="text-center text-muted py-4">' +
                    '<i class="fas fa-user-slash fa-2x mb-2 d-block opacity-25"></i>' +
                    'Tidak ada siswa di kelas ini</td></tr>';
            }
            document.getElementById('naikkanSiswaBody').innerHTML = html;
            updateSelectedCount();
        })
        .catch(() => {
            document.getElementById('naikkanSiswaBody').innerHTML = 
                '<tr><td colspan="6" class="text-center text-danger py-3">' +
                'Gagal memuat data siswa!</td></tr>';
        });
}

// Toggle All Siswa
function toggleAllSiswa(source) {
    var checkboxes = document.getElementsByClassName('siswa-checkbox');
    for (var i = 0; i < checkboxes.length; i++) {
        checkboxes[i].checked = source.checked;
    }
    updateSelectedCount();
}

// Update selected count
function updateSelectedCount() {
    var checkboxes = document.getElementsByClassName('siswa-checkbox');
    var selected = 0;
    for (var i = 0; i < checkboxes.length; i++) {
        if (checkboxes[i].checked) selected++;
    }
    var btn = document.getElementById('btnNaikkan');
    btn.innerHTML = '<i class="fas fa-arrow-up me-1"></i> Naikkan ' + selected + ' Siswa Terpilih';
}

// Event listener untuk checkbox changes
document.addEventListener('change', function(e) {
    if (e.target && e.target.classList && e.target.classList.contains('siswa-checkbox')) {
        updateSelectedCount();
    }
});

// Hapus Kelas
function hapusKelas(id) {
    if (confirm('Apakah Anda yakin ingin menghapus kelas ini?\n\nKelas tidak bisa dihapus jika masih memiliki siswa!')) {
        window.location.href = '<?= APP_URL ?>admin/kelas.php?action=delete&id=' + id;
    }
}

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