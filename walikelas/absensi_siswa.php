<?php
// walikelas/absensi_siswa.php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Cek login
if (!isLoggedIn()) {
    header("Location: ../login.php");
    exit;
}

// Cek role (hanya walikelas)
if (!hasRole(['walikelas'])) {
    $_SESSION['toast'] = [
        'message' => 'Anda tidak memiliki akses ke halaman ini!',
        'type' => 'danger'
    ];
    header("Location: " . APP_URL . "dashboard.php");
    exit;
}

$page_title = 'Input Absensi Siswa';

// Ambil data wali kelas
$user = getCurrentUser();
$walikelas_id = $user['id'];

// Ambil kelas yang diampu oleh wali kelas
$kelas = getRow("SELECT * FROM kelas WHERE wali_kelas_id = $walikelas_id");
if (!$kelas) {
    echo '<div class="alert alert-danger">Anda belum ditugaskan sebagai wali kelas!</div>';
    include __DIR__ . '/../includes/footer.php';
    exit;
}

$kelas_id = $kelas['id'];
$nama_kelas = $kelas['nama_kelas'];

// Ambil tanggal
$tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');

// Ambil daftar siswa di kelas
$siswa_list = getSiswaByKelas($kelas_id);

// ============================================
// HANDLE SIMPAN ABSENSI
// ============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $conn = getDB();
    
    if ($action == 'simpan') {
        $tanggal_absensi = sanitize($_POST['tanggal_absensi']);
        $siswa_ids = isset($_POST['siswa_id']) ? $_POST['siswa_id'] : [];
        $statuses = isset($_POST['status']) ? $_POST['status'] : [];
        
        if (empty($tanggal_absensi)) {
            $_SESSION['toast'] = [
                'message' => 'Tanggal absensi harus diisi!',
                'type' => 'danger'
            ];
            header("Location: " . APP_URL . "walikelas/absensi_siswa.php?tanggal=" . $tanggal_absensi);
            exit();
        }
        
        $check = $conn->query("SELECT COUNT(*) as total FROM absensi WHERE kelas_id = $kelas_id AND tanggal = '$tanggal_absensi'");
        $total = $check->fetch_assoc()['total'];
        
        if ($total > 0) {
            foreach ($siswa_ids as $siswa_id) {
                $status = isset($statuses[$siswa_id]) ? $statuses[$siswa_id] : 'H';
                
                $update = "UPDATE absensi SET status = '$status', updated_at = NOW() 
                           WHERE siswa_id = $siswa_id AND tanggal = '$tanggal_absensi' AND kelas_id = $kelas_id";
                $conn->query($update);
            }
            
            $_SESSION['toast'] = [
                'message' => 'Data absensi berhasil diperbarui!',
                'type' => 'success'
            ];
        } else {
            foreach ($siswa_ids as $siswa_id) {
                $status = isset($statuses[$siswa_id]) ? $statuses[$siswa_id] : 'H';
                
                $insert = "INSERT INTO absensi (siswa_id, kelas_id, tanggal, status, created_at) 
                           VALUES ($siswa_id, $kelas_id, '$tanggal_absensi', '$status', NOW())";
                $conn->query($insert);
            }
            
            $_SESSION['toast'] = [
                'message' => 'Data absensi berhasil disimpan!',
                'type' => 'success'
            ];
        }
        
        header("Location: " . APP_URL . "walikelas/absensi_siswa.php?tanggal=" . $tanggal_absensi);
        exit();
    }
    
    if ($action == 'reset') {
        $tanggal_reset = sanitize($_POST['tanggal_reset']);
        $conn->query("DELETE FROM absensi WHERE kelas_id = $kelas_id AND tanggal = '$tanggal_reset'");
        
        $_SESSION['toast'] = [
            'message' => 'Data absensi tanggal ' . date('d-m-Y', strtotime($tanggal_reset)) . ' berhasil direset!',
            'type' => 'success'
        ];
        header("Location: " . APP_URL . "walikelas/absensi_siswa.php?tanggal=" . $tanggal_reset);
        exit();
    }
}

// ============================================
// AMBIL DATA ABSENSI
// ============================================
$conn = getDB();

$absensi_data = [];
$query_absensi = "SELECT * FROM absensi WHERE kelas_id = $kelas_id AND tanggal = '$tanggal'";
$result_absensi = $conn->query($query_absensi);
while ($row = $result_absensi->fetch_assoc()) {
    $absensi_data[$row['siswa_id']] = $row;
}

$statistik = ['H' => 0, 'I' => 0, 'A' => 0, 'S' => 0];
foreach ($absensi_data as $data) {
    if (isset($statistik[$data['status']])) {
        $statistik[$data['status']]++;
    }
}
$total_terisi = array_sum($statistik);
$total_siswa = count($siswa_list);
$belum_isi = $total_siswa - $total_terisi;

$hari_indonesia = [
    'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu',
    'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu',
    'Sunday' => 'Minggu'
];
$nama_hari = $hari_indonesia[date('l', strtotime($tanggal))];

include __DIR__ . '/../includes/header_walikelas.php';
?>

<div class="container-fluid dashboard-container">
    <div class="dashboard-header">
        <div>
            <h4 class="dashboard-title">
                <i class="fas fa-clipboard-check text-primary me-2"></i> Input Absensi
            </h4>
            <small class="text-muted dashboard-subtitle">
                <i class="fas fa-school me-1"></i> Kelas <?= htmlspecialchars($nama_kelas) ?> | 
                <?= $nama_hari ?>, <?= date('d-m-Y', strtotime($tanggal)) ?>
            </small>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <span class="badge bg-primary p-2">
                <i class="fas fa-users me-1"></i> <?= $total_siswa ?> Siswa
            </span>
            <span class="badge bg-success p-2">
                <i class="fas fa-check me-1"></i> <?= $statistik['H'] ?> H
            </span>
            <span class="badge bg-warning p-2">
                <i class="fas fa-phone me-1"></i> <?= $statistik['I'] ?> I
            </span>
            <span class="badge bg-danger p-2">
                <i class="fas fa-times me-1"></i> <?= $statistik['A'] ?> A
            </span>
            <span class="badge bg-info p-2">
                <i class="fas fa-hospital me-1"></i> <?= $statistik['S'] ?> S
            </span>
        </div>
    </div>

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

    <div class="card filter-card mb-3">
        <div class="card-body p-2">
            <form method="GET" action="" class="filter-form">
                <div class="row g-2 align-items-end">
                    <div class="col-6 col-md-4">
                        <label class="form-label">Tanggal</label>
                        <input type="date" class="form-control" name="tanggal" value="<?= $tanggal ?>">
                    </div>
                    <div class="col-3 col-md-4">
                        <label class="form-label d-none d-md-block">&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="fas fa-search me-1"></i> Tampilkan
                        </button>
                    </div>
                    <div class="col-3 col-md-4">
                        <label class="form-label d-none d-md-block">&nbsp;</label>
                        <a href="?tanggal=<?= date('Y-m-d') ?>" class="btn btn-secondary btn-sm w-100">
                            <i class="fas fa-undo me-1"></i> Hari Ini
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header py-1 px-2">
            <i class="fas fa-list me-1"></i> Daftar Siswa
            <span class="badge bg-secondary ms-1"><?= $total_terisi ?> Terisi</span>
            <span class="badge bg-warning ms-1"><?= $belum_isi ?> Belum</span>
            <span class="info-badge ms-auto">
                <i class="fas fa-info-circle"></i> 
                <span class="text-success">H</span>=Hadir, 
                <span class="text-warning">I</span>=Izin, 
                <span class="text-danger">A</span>=Alfa, 
                <span class="text-info">S</span>=Sakit
            </span>
        </div>
        <div class="card-body p-0">
            <form method="POST" action="" id="formAbsensi">
                <input type="hidden" name="action" value="simpan">
                <input type="hidden" name="tanggal_absensi" value="<?= $tanggal ?>">
                
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-striped mb-0" style="font-size:13px;width:100%;">
                        <thead>
                            <tr>
                                <th class="text-center" style="width:5%;">No</th>
                                <th style="width:12%;">NISN</th>
                                <th style="width:33%;">Nama Siswa</th>
                                <th style="width:50%;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($siswa_list) > 0): ?>
                                <?php $no = 1; ?>
                                <?php foreach ($siswa_list as $siswa): 
                                    $siswa_id = $siswa['id'];
                                    $data_absensi = isset($absensi_data[$siswa_id]) ? $absensi_data[$siswa_id] : null;
                                    $status = $data_absensi ? $data_absensi['status'] : 'H';
                                ?>
                                    <tr>
                                        <td class="text-center"><?= $no++ ?></td>
                                        <td><?= htmlspecialchars($siswa['nisn']) ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($siswa['nama_siswa']) ?></strong>
                                            <small class="text-muted d-block"><?= htmlspecialchars($siswa['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan') ?></small>
                                        </td>
                                        <td>
                                            <input type="hidden" name="siswa_id[]" value="<?= $siswa_id ?>">
                                            <div class="btn-group w-100" role="group">
                                                <input type="radio" class="btn-check" name="status[<?= $siswa_id ?>]" id="hadir_<?= $siswa_id ?>" value="H" <?= $status == 'H' ? 'checked' : '' ?>>
                                                <label class="btn btn-outline-success btn-sm" for="hadir_<?= $siswa_id ?>" style="font-size:11px;">H</label>
                                                
                                                <input type="radio" class="btn-check" name="status[<?= $siswa_id ?>]" id="izin_<?= $siswa_id ?>" value="I" <?= $status == 'I' ? 'checked' : '' ?>>
                                                <label class="btn btn-outline-warning btn-sm" for="izin_<?= $siswa_id ?>" style="font-size:11px;">I</label>
                                                
                                                <input type="radio" class="btn-check" name="status[<?= $siswa_id ?>]" id="alfa_<?= $siswa_id ?>" value="A" <?= $status == 'A' ? 'checked' : '' ?>>
                                                <label class="btn btn-outline-danger btn-sm" for="alfa_<?= $siswa_id ?>" style="font-size:11px;">A</label>
                                                
                                                <input type="radio" class="btn-check" name="status[<?= $siswa_id ?>]" id="sakit_<?= $siswa_id ?>" value="S" <?= $status == 'S' ? 'checked' : '' ?>>
                                                <label class="btn btn-outline-info btn-sm" for="sakit_<?= $siswa_id ?>" style="font-size:11px;">S</label>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">
                                        <i class="fas fa-inbox fa-2x d-block mb-2"></i>
                                        Belum ada siswa di kelas ini
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="card-footer py-2">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="d-flex gap-2 flex-wrap">
                            <span class="text-muted" style="font-size:11px;">
                                <i class="fas fa-info-circle me-1"></i>
                                Klik H (Hadir), I (Izin), A (Alfa), S (Sakit)
                            </span>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-save me-1"></i> Simpan Absensi
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="resetForm()">
                                <i class="fas fa-undo me-1"></i> Reset
                            </button>
                            <button type="button" class="btn btn-danger btn-sm" onclick="resetAbsensi()">
                                <i class="fas fa-trash me-1"></i> Reset All
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-2 mt-2">
        <div class="col-3">
            <div class="stat-card bg-success bg-opacity-10 text-success">
                <div class="stat-card-body">
                    <div class="stat-number"><?= $statistik['H'] ?></div>
                    <div class="stat-label">Hadir</div>
                </div>
            </div>
        </div>
        <div class="col-3">
            <div class="stat-card bg-warning bg-opacity-10 text-warning">
                <div class="stat-card-body">
                    <div class="stat-number"><?= $statistik['I'] ?></div>
                    <div class="stat-label">Izin</div>
                </div>
            </div>
        </div>
        <div class="col-3">
            <div class="stat-card bg-danger bg-opacity-10 text-danger">
                <div class="stat-card-body">
                    <div class="stat-number"><?= $statistik['A'] ?></div>
                    <div class="stat-label">Alfa</div>
                </div>
            </div>
        </div>
        <div class="col-3">
            <div class="stat-card bg-info bg-opacity-10 text-info">
                <div class="stat-card-body">
                    <div class="stat-number"><?= $statistik['S'] ?></div>
                    <div class="stat-label">Sakit</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-2">
        <div class="card-header py-1 px-2">
            <i class="fas fa-info-circle me-2"></i> Keterangan Status
        </div>
        <div class="card-body py-2">
            <div class="row g-2">
                <div class="col-6 col-md-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-success">H</span>
                        <span style="font-size:12px;">Hadir</span>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-warning">I</span>
                        <span style="font-size:12px;">Izin</span>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-danger">A</span>
                        <span style="font-size:12px;">Alfa</span>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-info">S</span>
                        <span style="font-size:12px;">Sakit</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalReset" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle text-danger me-2"></i> Reset Absensi
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>PERINGATAN!</strong><br>
                    Anda akan menghapus semua data absensi untuk tanggal <strong><?= date('d-m-Y', strtotime($tanggal)) ?></strong>.
                    <br><br>
                    Tindakan ini tidak dapat dibatalkan!
                </div>
                <form method="POST" action="" id="formReset">
                    <input type="hidden" name="action" value="reset">
                    <input type="hidden" name="tanggal_reset" value="<?= $tanggal ?>">
                    <div class="mb-2">
                        <label class="form-label">Ketik <strong>"RESET"</strong> untuk konfirmasi</label>
                        <input type="text" class="form-control" id="resetConfirm" placeholder="Ketik RESET" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" onclick="confirmReset()">
                    <i class="fas fa-trash me-1"></i> Reset
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function resetAbsensi() {
    var modal = new bootstrap.Modal(document.getElementById('modalReset'));
    modal.show();
    document.getElementById('resetConfirm').value = '';
}

function confirmReset() {
    var confirmText = document.getElementById('resetConfirm').value;
    if (confirmText !== 'RESET') {
        alert('Ketik "RESET" (huruf kapital) untuk mengkonfirmasi!');
        return;
    }
    document.getElementById('formReset').submit();
}

function resetForm() {
    if (confirm('Reset semua pilihan status ke default (Hadir)?')) {
        var radios = document.querySelectorAll('input[type="radio"]');
        radios.forEach(function(radio) {
            if (radio.value === 'H') {
                radio.checked = true;
            }
        });
    }
}

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

.card-header {
    background: transparent;
    border-bottom: 1px solid #e9ecef;
    padding: 8px 12px;
    font-weight: 600;
    font-size: 12px;
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 4px;
}

.card-body {
    padding: 12px;
}

.card-footer {
    background: transparent;
    border-top: 1px solid #e9ecef;
    padding: 8px 12px;
}

.filter-card .card-body {
    padding: 8px;
}

.table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
.table {
    font-size: 13px;
    margin-bottom: 0;
    width: 100% !important;
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
}

.btn-group .btn {
    padding: 4px 8px;
    font-size: 12px;
    font-weight: 700;
    min-width: 32px;
}
.btn-group .btn-outline-success { color: #10B981; border-color: #10B981; }
.btn-group .btn-outline-success:hover { background: #10B981; color: #fff; }
.btn-group .btn-check:checked + .btn-outline-success { background: #10B981; color: #fff; border-color: #10B981; }

.btn-group .btn-outline-warning { color: #F59E0B; border-color: #F59E0B; }
.btn-group .btn-outline-warning:hover { background: #F59E0B; color: #fff; }
.btn-group .btn-check:checked + .btn-outline-warning { background: #F59E0B; color: #fff; border-color: #F59E0B; }

.btn-group .btn-outline-danger { color: #EF4444; border-color: #EF4444; }
.btn-group .btn-outline-danger:hover { background: #EF4444; color: #fff; }
.btn-group .btn-check:checked + .btn-outline-danger { background: #EF4444; color: #fff; border-color: #EF4444; }

.btn-group .btn-outline-info { color: #3B82F6; border-color: #3B82F6; }
.btn-group .btn-outline-info:hover { background: #3B82F6; color: #fff; }
.btn-group .btn-check:checked + .btn-outline-info { background: #3B82F6; color: #fff; border-color: #3B82F6; }

.stat-card {
    padding: 8px 12px;
    border-radius: 8px;
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

.form-control, .form-select {
    border-radius: 6px;
    border: 1px solid #e2e8f0;
    padding: 4px 8px;
    font-size: 12px;
    height: 34px;
}
.form-control:focus, .form-select:focus {
    border-color: #4F46E5;
    box-shadow: 0 0 0 3px rgba(79,70,229,0.15);
}
.form-label {
    font-weight: 600;
    font-size: 11px;
    color: #4a5568;
    margin-bottom: 3px;
}

.btn-sm {
    padding: 4px 10px !important;
    font-size: 11px !important;
    border-radius: 6px !important;
}

.badge {
    font-size: 9px !important;
    padding: 3px 8px !important;
    border-radius: 4px;
}

.info-badge {
    font-size: 10px;
    color: #718096;
    margin-left: auto;
}

.info-badge .text-success { color: #10B981 !important; }
.info-badge .text-warning { color: #F59E0B !important; }
.info-badge .text-danger { color: #EF4444 !important; }
.info-badge .text-info { color: #3B82F6 !important; }

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
    .table {
        font-size: 11px;
    }
    .table th {
        font-size: 9px;
        padding: 4px 6px;
    }
    .table td {
        font-size: 11px;
        padding: 4px 6px;
    }
    .btn-group .btn {
        font-size: 10px;
        padding: 2px 6px;
        min-width: 28px;
    }
    .badge {
        font-size: 8px !important;
        padding: 2px 5px !important;
    }
    .form-control, .form-select {
        font-size: 11px;
        height: 30px;
        padding: 4px 8px;
    }
    .btn-sm {
        font-size: 10px !important;
        padding: 3px 8px !important;
    }
    .card-header {
        font-size: 11px;
        padding: 6px 10px;
    }
    .card-body {
        padding: 8px;
    }
    .filter-card .card-body {
        padding: 6px;
    }
    .stat-number {
        font-size: 1rem;
    }
    .stat-label {
        font-size: 0.55rem;
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
    .dashboard-container {
        padding: 2px !important;
    }
    .dashboard-title {
        font-size: 0.8rem;
    }
    .dashboard-subtitle {
        font-size: 0.55rem;
    }
    .table {
        font-size: 10px;
        min-width: 400px;
    }
    .table th {
        font-size: 8px;
        padding: 3px 4px;
    }
    .table td {
        font-size: 10px;
        padding: 3px 4px;
    }
    .btn-group .btn {
        font-size: 9px;
        padding: 1px 4px;
        min-width: 24px;
    }
    .badge {
        font-size: 7px !important;
        padding: 1px 4px !important;
    }
    .form-control, .form-select {
        font-size: 10px;
        height: 28px;
        padding: 3px 6px;
    }
    .btn-sm {
        font-size: 9px !important;
        padding: 2px 6px !important;
    }
    .stat-number {
        font-size: 0.85rem;
    }
    .stat-label {
        font-size: 0.5rem;
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

<?php include __DIR__ . '/../includes/footer.php'; ?>