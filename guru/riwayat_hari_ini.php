<?php
// guru/riwayat_hari_ini.php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Cek login
if (!isLoggedIn()) {
    header("Location: ../login.php");
    exit;
}

// Cek role (guru, bk, walikelas, admin)
if (!hasRole(['admin', 'guru', 'bk', 'walikelas'])) {
    header("Location: ../dashboard.php");
    exit;
}

$page_title = 'Riwayat Hari Ini';

// Filter tanggal (default hari ini)
$tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');
$kelas_filter = isset($_GET['kelas']) ? (int)$_GET['kelas'] : null;

// Ambil data pelanggaran hari ini
$pelanggaran_list = [];
$query = "SELECT p.*, s.nama_siswa, s.nisn, s.kelas_id, k.nama_kelas, 
          jp.nama_pelanggaran, jp.poin, u.nama_lengkap as guru
          FROM pelanggaran p
          LEFT JOIN siswa s ON p.siswa_id = s.id
          LEFT JOIN kelas k ON s.kelas_id = k.id
          LEFT JOIN jenis_pelanggaran jp ON p.jenis_pelanggaran_id = jp.id
          LEFT JOIN users u ON p.guru_id = u.id
          WHERE DATE(p.tanggal_pelanggaran) = '$tanggal'";

if ($kelas_filter) {
    $query .= " AND s.kelas_id = $kelas_filter";
}

$query .= " ORDER BY p.tanggal_pelanggaran DESC, p.created_at DESC";
$pelanggaran_list = getRows($query);

// Statistik
$total_pelanggaran = count($pelanggaran_list);
$total_siswa = count(array_unique(array_column($pelanggaran_list, 'siswa_id')));
$total_poin = array_sum(array_column($pelanggaran_list, 'poin'));

// Ambil daftar kelas untuk filter
$kelas_list = getKelasOptions();

// Include header guru
include __DIR__ . '/../includes/header_guru.php';
?>

<div class="container-fluid dashboard-container">
    <!-- Header -->
    <div class="dashboard-header">
        <div>
            <h4 class="dashboard-title">
                <i class="fas fa-clock text-primary me-2"></i> Riwayat Hari Ini
            </h4>
            <small class="text-muted dashboard-subtitle">
                <i class="fas fa-calendar me-1"></i> Data pelanggaran pada tanggal <?= formatTanggalIndo($tanggal) ?>
            </small>
        </div>
        <div>
            <span class="badge bg-primary p-2">
                <i class="fas fa-file-alt me-1"></i> <?= $total_pelanggaran ?> Pelanggaran
            </span>
        </div>
    </div>

    <!-- Filter -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small mb-1">Tanggal</label>
                    <input type="date" class="form-control" name="tanggal" value="<?= $tanggal ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label small mb-1">Kelas</label>
                    <select class="form-select" name="kelas">
                        <option value="">Semua Kelas</option>
                        <?php foreach ($kelas_list as $kelas): ?>
                            <option value="<?= $kelas['id'] ?>" <?= $kelas_filter == $kelas['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($kelas['nama_kelas']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-1"></i> Tampilkan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Stats -->
    <div class="quick-stats-grid mb-3">
        <div class="quick-stat-card">
            <div class="quick-stat-icon bg-danger">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="quick-stat-info">
                <h6 class="quick-stat-label">Total Pelanggaran</h6>
                <h4 class="quick-stat-number"><?= $total_pelanggaran ?></h4>
                <small class="text-muted">Hari ini</small>
            </div>
        </div>
        <div class="quick-stat-card">
            <div class="quick-stat-icon bg-warning">
                <i class="fas fa-user-graduate"></i>
            </div>
            <div class="quick-stat-info">
                <h6 class="quick-stat-label">Siswa Terlibat</h6>
                <h4 class="quick-stat-number"><?= $total_siswa ?></h4>
                <small class="text-muted">Orang</small>
            </div>
        </div>
        <div class="quick-stat-card">
            <div class="quick-stat-icon bg-info">
                <i class="fas fa-tag"></i>
            </div>
            <div class="quick-stat-info">
                <h6 class="quick-stat-label">Total Poin</h6>
                <h4 class="quick-stat-number"><?= $total_poin ?></h4>
                <small class="text-muted">Dikumpulkan</small>
            </div>
        </div>
        <div class="quick-stat-card">
            <div class="quick-stat-icon bg-success">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="quick-stat-info">
                <h6 class="quick-stat-label">Status Disetujui</h6>
                <h4 class="quick-stat-number">
                    <?= count(array_filter($pelanggaran_list, function($p) { return $p['status'] == 'disetujui'; })) ?>
                </h4>
                <small class="text-muted">Pelanggaran</small>
            </div>
        </div>
    </div>

    <!-- Tabel Pelanggaran -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-list me-2"></i> Daftar Pelanggaran
            <span class="info-badge">
                <i class="fas fa-info-circle"></i> 
                <?= $total_pelanggaran ?> data ditemukan
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="40" class="text-center">No</th>
                            <th width="80" class="text-center">Foto Bukti</th>
                            <th>Waktu</th>
                            <th>Siswa</th>
                            <th>Kelas</th>
                            <th>Pelanggaran</th>
                            <th class="text-center">Poin</th>
                            <th class="text-center">Status</th>
                            <th class="text-center" width="100">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($pelanggaran_list)): ?>
                            <?php $no = 1; foreach ($pelanggaran_list as $p): ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?></td>
                                    <td class="text-center">
                                        <?php if (!empty($p['bukti']) && file_exists(APP_PATH . 'uploads/bukti_pelanggaran/' . $p['bukti'])): ?>
                                            <a href="#" data-bs-toggle="modal" data-bs-target="#fotoModal<?= $p['id'] ?>">
                                                <img src="<?= APP_URL ?>uploads/bukti_pelanggaran/<?= $p['bukti'] ?>" 
                                                     alt="Bukti" width="50" height="50" 
                                                     style="object-fit: cover; border-radius: 6px; cursor: pointer; border: 1px solid #e2e8f0;">
                                            </a>
                                            <!-- Modal Foto -->
                                            <div class="modal fade" id="fotoModal<?= $p['id'] ?>" tabindex="-1">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h6 class="modal-title">Foto Bukti Pelanggaran</h6>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body text-center">
                                                            <?php 
                                                            $file_ext = strtolower(pathinfo($p['bukti'], PATHINFO_EXTENSION));
                                                            if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif'])): 
                                                            ?>
                                                                <img src="<?= APP_URL ?>uploads/bukti_pelanggaran/<?= $p['bukti'] ?>" 
                                                                     alt="Bukti" class="img-fluid" style="max-height: 500px;">
                                                            <?php elseif ($file_ext == 'pdf'): ?>
                                                                <div class="p-5">
                                                                    <i class="fas fa-file-pdf text-danger" style="font-size: 80px;"></i>
                                                                    <p class="mt-3">
                                                                        <a href="<?= APP_URL ?>uploads/bukti_pelanggaran/<?= $p['bukti'] ?>" 
                                                                           target="_blank" class="btn btn-danger">
                                                                            <i class="fas fa-file-pdf me-1"></i> Lihat PDF
                                                                        </a>
                                                                    </p>
                                                                </div>
                                                            <?php endif; ?>
                                                            <div class="mt-2 text-start">
                                                                <small class="text-muted">
                                                                    <strong>Siswa:</strong> <?= htmlspecialchars($p['nama_siswa'] ?? '-') ?><br>
                                                                    <strong>Pelanggaran:</strong> <?= htmlspecialchars($p['nama_pelanggaran'] ?? '-') ?>
                                                                </small>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php elseif (!empty($p['bukti'])): ?>
                                            <span class="text-muted">File hilang</span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($p['jam_pelanggaran'])): ?>
                                            <span class="badge bg-light text-dark">
                                                <i class="fas fa-clock me-1"></i>
                                                <?= date('H:i', strtotime($p['jam_pelanggaran'])) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($p['nama_siswa'] ?? '-') ?></strong>
                                        <br>
                                        <small class="text-muted">NISN: <?= htmlspecialchars($p['nisn'] ?? '-') ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($p['nama_kelas'] ?? '-') ?></td>
                                    <td>
                                        <?= htmlspecialchars($p['nama_pelanggaran'] ?? '-') ?>
                                        <?php if (!empty($p['keterangan'])): ?>
                                            <br>
                                            <small class="text-muted">
                                                <i class="fas fa-comment me-1"></i> <?= htmlspecialchars(substr($p['keterangan'], 0, 30)) ?>
                                                <?= strlen($p['keterangan']) > 30 ? '...' : '' ?>
                                            </small>
                                        <?php endif; ?>
                                        <?php if (!empty($p['lokasi'])): ?>
                                            <br>
                                            <small class="text-muted">
                                                <i class="fas fa-map-marker-alt me-1"></i> <?= htmlspecialchars($p['lokasi']) ?>
                                            </small>
                                        <?php endif; ?>
                                        <?php if (!empty($p['guru'])): ?>
                                            <br>
                                            <small class="text-muted">
                                                <i class="fas fa-user me-1"></i> Input oleh: <?= htmlspecialchars($p['guru']) ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-danger"><?= $p['poin'] ?? 0 ?></span>
                                    </td>
                                    <td class="text-center">
                                        <?php 
                                        $status = $p['status'] ?? 'pending';
                                        $badge = $status == 'disetujui' ? 'success' : ($status == 'ditolak' ? 'danger' : 'warning');
                                        $icon = $status == 'disetujui' ? 'fa-check-circle' : ($status == 'ditolak' ? 'fa-times-circle' : 'fa-clock');
                                        ?>
                                        <span class="badge bg-<?= $badge ?>">
                                            <i class="fas <?= $icon ?> me-1"></i>
                                            <?= ucfirst($status) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group" role="group">
                                            <a href="<?= APP_URL ?>guru/detail_pelanggaran.php?id=<?= $p['id'] ?>" 
                                               class="btn btn-outline-info btn-sm" title="Detail">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($p['status'] == 'pending'): ?>
                                                <a href="<?= APP_URL ?>guru/approve_pelanggaran.php?id=<?= $p['id'] ?>" 
                                                   class="btn btn-outline-success btn-sm" title="Setujui"
                                                   onclick="return confirm('Setujui pelanggaran ini?')">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                                <a href="<?= APP_URL ?>guru/reject_pelanggaran.php?id=<?= $p['id'] ?>" 
                                                   class="btn btn-outline-danger btn-sm" title="Tolak"
                                                   onclick="return confirm('Tolak pelanggaran ini?')">
                                                    <i class="fas fa-times"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    <i class="fas fa-check-circle fa-2x mb-2 d-block opacity-25 text-success"></i>
                                    <p class="mb-0">Belum ada data pelanggaran pada tanggal ini</p>
                                    <small class="text-muted"><?= formatTanggalIndo($tanggal) ?></small>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if (!empty($pelanggaran_list)): ?>
            <div class="card-footer bg-transparent text-muted d-flex justify-content-between align-items-center flex-wrap">
                <span>
                    <i class="fas fa-info-circle me-1"></i>
                    Menampilkan <?= count($pelanggaran_list) ?> pelanggaran
                </span>
                <span>
                    <i class="fas fa-user-graduate me-1"></i>
                    <?= $total_siswa ?> siswa terlibat
                    <i class="fas fa-tag ms-2 me-1"></i>
                    <?= $total_poin ?> total poin
                </span>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* ============================================
   STYLE RIWAYAT HARI INI
   ============================================ */

/* Dashboard */
.dashboard-container {
    padding: 12px !important;
    max-width: 100%;
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
    font-size: 1.25rem;
    margin-bottom: 0;
}

.dashboard-subtitle {
    font-size: 0.75rem;
}

/* Quick Stats */
.quick-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    margin-bottom: 16px;
}

.quick-stat-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}

.quick-stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 16px;
    flex-shrink: 0;
}
.quick-stat-icon.bg-danger { background: #EF4444; }
.quick-stat-icon.bg-warning { background: #F59E0B; }
.quick-stat-icon.bg-info { background: #3B82F6; }
.quick-stat-icon.bg-success { background: #10B981; }

.quick-stat-info {
    flex: 1;
    min-width: 0;
}

.quick-stat-label {
    font-size: 0.6rem;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    color: #6B7280;
    margin-bottom: 0;
}

.quick-stat-number {
    font-size: 1.2rem;
    font-weight: 700;
    margin-bottom: 0;
}

.quick-stat-info small {
    font-size: 0.55rem;
}

/* Card */
.card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 2px 15px rgba(0,0,0,0.08);
    margin-bottom: 25px;
    background: #fff;
}
.card-header {
    background: transparent;
    border-bottom: 1px solid #e9ecef;
    padding: 16px 20px;
    font-weight: 600;
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
}
.card-body {
    padding: 20px;
}
.card-footer {
    background: transparent;
    border-top: 1px solid #e9ecef;
    padding: 12px 20px;
    font-size: 13px;
}

/* Table */
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
    background-color: rgba(79, 70, 229, 0.04);
}
.table tbody td {
    font-size: 13px;
}

/* Form */
.form-control, .form-select {
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    padding: 10px 15px;
    transition: all 0.3s;
    font-size: 14px;
}
.form-control:focus, .form-select:focus {
    border-color: #4F46E5;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
}
.form-label {
    font-weight: 600;
    color: #4a5568;
    font-size: 14px;
    margin-bottom: 6px;
}

/* Buttons */
.btn-primary {
    background: linear-gradient(135deg, #4F46E5 0%, #4338CA 100%);
    border: none;
    padding: 10px 24px;
    border-radius: 10px;
    font-weight: 600;
    color: #fff;
    transition: all 0.3s;
}
.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(79, 70, 229, 0.4);
    color: #fff;
}

.btn-outline-info, .btn-outline-success, .btn-outline-danger {
    padding: 2px 6px !important;
    font-size: 12px !important;
    border-radius: 6px;
}

.info-badge {
    font-size: 12px;
    color: #718096;
    margin-left: 8px;
}

/* Badge */
.badge {
    font-size: 10px !important;
    padding: 3px 8px !important;
}

/* Modal Foto */
.modal-content {
    border-radius: 15px;
    border: none;
}
.modal-header {
    border-bottom: 1px solid #e9ecef;
    padding: 16px 20px;
}
.modal-body {
    padding: 20px;
}
.modal-footer {
    border-top: 1px solid #e9ecef;
    padding: 12px 20px;
}

/* Responsive */
@media (max-width: 992px) {
    .quick-stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
    }
}

@media (max-width: 768px) {
    .card-header {
        flex-direction: column;
        align-items: flex-start;
    }
    .card-header .info-badge {
        margin-left: 0;
    }
    .card-body {
        padding: 15px;
    }
    .quick-stats-grid {
        grid-template-columns: 1fr 1fr;
        gap: 6px;
    }
    .quick-stat-card {
        padding: 8px 10px;
        gap: 8px;
    }
    .quick-stat-icon {
        width: 30px;
        height: 30px;
        font-size: 12px;
    }
    .quick-stat-number {
        font-size: 1rem;
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
    .table thead th {
        font-size: 9px;
        padding: 4px 6px;
    }
    .table tbody td {
        font-size: 10px;
        padding: 4px 6px;
    }
    .btn-outline-info, .btn-outline-success, .btn-outline-danger {
        padding: 1px 4px !important;
        font-size: 9px !important;
    }
}

@media (max-width: 576px) {
    .card-body {
        padding: 12px;
    }
    .card-header {
        padding: 12px 16px;
        font-size: 14px;
    }
    .quick-stats-grid {
        grid-template-columns: 1fr 1fr;
        gap: 4px;
    }
    .quick-stat-card {
        padding: 4px 8px;
        gap: 4px;
    }
    .quick-stat-icon {
        width: 24px;
        height: 24px;
        font-size: 10px;
    }
    .quick-stat-number {
        font-size: 0.8rem;
    }
    .quick-stat-label {
        font-size: 0.45rem;
    }
    .dashboard-container {
        padding: 6px !important;
    }
}
</style>

<?php include '../includes/footer.php'; ?>