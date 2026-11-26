<?php
// guru/cetak_bukti.php
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

$page_title = 'Cetak Bukti Pelanggaran';

// Filter
$tanggal_awal = isset($_GET['tanggal_awal']) ? $_GET['tanggal_awal'] : date('Y-m-01');
$tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : date('Y-m-d');
$siswa_id = isset($_GET['siswa_id']) ? (int)$_GET['siswa_id'] : null;
$kelas_id = isset($_GET['kelas_id']) ? (int)$_GET['kelas_id'] : null;
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Ambil data pelanggaran berdasarkan filter
$query = "SELECT p.*, s.nama_siswa, s.nisn, s.kelas_id, k.nama_kelas, 
          jp.nama_pelanggaran, jp.poin, jp.kategori,
          u.nama_lengkap as guru
          FROM pelanggaran p
          LEFT JOIN siswa s ON p.siswa_id = s.id
          LEFT JOIN kelas k ON s.kelas_id = k.id
          LEFT JOIN jenis_pelanggaran jp ON p.jenis_pelanggaran_id = jp.id
          LEFT JOIN users u ON p.guru_id = u.id
          WHERE DATE(p.tanggal_pelanggaran) BETWEEN '$tanggal_awal' AND '$tanggal_akhir'";

if ($siswa_id) {
    $query .= " AND p.siswa_id = $siswa_id";
}
if ($kelas_id) {
    $query .= " AND s.kelas_id = $kelas_id";
}
if (!empty($status)) {
    $query .= " AND p.status = '$status'";
}

$query .= " ORDER BY p.tanggal_pelanggaran DESC, p.created_at DESC";
$pelanggaran_list = getRows($query);

// Ambil data untuk filter
$siswa_list = getSiswa();
$kelas_list = getKelasOptions();

// Jika ada parameter cetak
if (isset($_GET['cetak']) && $_GET['cetak'] == 'pdf' && !empty($pelanggaran_list)) {
    require_once('../tcpdf/tcpdf.php');
    
    $pengaturan = getPengaturan();
    $nama_sekolah = $pengaturan['nama_sekolah'] ?? 'SMA Negeri 1 Tatib';
    $alamat = $pengaturan['alamat'] ?? '';
    $no_telp = $pengaturan['no_telp'] ?? '';
    $email = $pengaturan['email'] ?? '';
    $logo = $pengaturan['logo'] ?? '';
    
    $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('Tatib Sekolah');
    $pdf->SetAuthor($nama_sekolah);
    $pdf->SetTitle('Laporan Pelanggaran');
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(15, 10, 15);
    $pdf->AddPage();
    
    // KOP SURAT
    $logo_path = APP_PATH . 'uploads/logo/' . $logo;
    if (!empty($logo) && file_exists($logo_path)) {
        $pdf->Image($logo_path, 15, 10, 22, 22);
    }
    
    $pdf->SetY(10);
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 7, strtoupper($nama_sekolah), 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 5, $alamat, 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 9);
    $pdf->Cell(0, 4, 'Telp: ' . $no_telp . ' | Email: ' . $email, 0, 1, 'C');
    
    $y_after_text = $pdf->GetY();
    $pdf->SetDrawColor(0, 0, 0);
    $pdf->SetLineWidth(0.8);
    $pdf->Line(15, $y_after_text + 3, 280, $y_after_text + 3);
    $pdf->SetLineWidth(0.3);
    $pdf->Line(15, $y_after_text + 5, 280, $y_after_text + 5);
    $pdf->SetY($y_after_text + 10);
    
    // JUDUL
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 7, 'LAPORAN PELANGGARAN SISWA', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 5, 'Periode: ' . formatTanggalIndo($tanggal_awal) . ' s/d ' . formatTanggalIndo($tanggal_akhir), 0, 1, 'C');
    $pdf->Ln(3);
    
    // Summary
    $pdf->SetFont('helvetica', '', 9);
    $total_poin = array_sum(array_column($pelanggaran_list, 'poin'));
    $pdf->Cell(0, 5, 'Total Pelanggaran: ' . count($pelanggaran_list) . ' | Total Poin: ' . number_format($total_poin), 0, 1, 'L');
    $pdf->Ln(3);
    
    // TABEL
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetFillColor(220, 220, 230);
    
    $headers = ['No', 'Tanggal', 'Jam', 'NISN', 'Nama Siswa', 'Kelas', 'Pelanggaran', 'Poin', 'Status'];
    $widths = [10, 22, 15, 25, 35, 20, 35, 15, 20];
    
    foreach ($headers as $i => $header) {
        $pdf->Cell($widths[$i], 7, $header, 1, 0, 'C', true);
    }
    $pdf->Ln();
    
    $pdf->SetFont('helvetica', '', 7);
    $no = 1;
    foreach ($pelanggaran_list as $row) {
        $pdf->Cell($widths[0], 6, $no++, 1, 0, 'C');
        $pdf->Cell($widths[1], 6, formatTanggal($row['tanggal_pelanggaran']), 1, 0, 'C');
        $pdf->Cell($widths[2], 6, !empty($row['jam_pelanggaran']) ? date('H:i', strtotime($row['jam_pelanggaran'])) : '-', 1, 0, 'C');
        $pdf->Cell($widths[3], 6, $row['nisn'] ?? '-', 1, 0, 'C');
        $pdf->Cell($widths[4], 6, substr($row['nama_siswa'] ?? '-', 0, 20), 1, 0, 'L');
        $pdf->Cell($widths[5], 6, $row['nama_kelas'] ?? '-', 1, 0, 'C');
        $pdf->Cell($widths[6], 6, substr($row['nama_pelanggaran'] ?? '-', 0, 20), 1, 0, 'L');
        $pdf->Cell($widths[7], 6, $row['poin'] ?? 0, 1, 0, 'C');
        $pdf->Cell($widths[8], 6, ucfirst($row['status'] ?? 'pending'), 1, 0, 'C');
        $pdf->Ln();
    }
    
    // FOOTER
    $pdf->Ln(4);
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->Cell(0, 5, 'Dicetak pada: ' . date('d-m-Y H:i:s'), 0, 1, 'L');
    $pdf->Cell(0, 5, 'Total Data: ' . count($pelanggaran_list) . ' pelanggaran', 0, 1, 'L');
    
    $pdf->Output('Laporan_Pelanggaran_' . date('Ymd') . '.pdf', 'I');
    exit;
}

// Include header guru
include __DIR__ . '/../includes/header_guru.php';
?>

<div class="container-fluid dashboard-container">
    <!-- Header -->
    <div class="dashboard-header">
        <div>
            <h4 class="dashboard-title">
                <i class="fas fa-print text-primary me-2"></i> Cetak Bukti Pelanggaran
            </h4>
            <small class="text-muted dashboard-subtitle">
                <i class="fas fa-filter me-1"></i> Filter dan cetak laporan pelanggaran
            </small>
        </div>
        <div>
            <?php if (!empty($pelanggaran_list)): ?>
            <a href="?<?= $_SERVER['QUERY_STRING'] ?>&cetak=pdf" target="_blank" class="btn btn-primary btn-sm">
                <i class="fas fa-file-pdf me-1"></i> Cetak PDF
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filter -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="fas fa-filter me-2"></i> Filter Data
        </div>
        <div class="card-body">
            <form method="GET" action="" class="row g-2 align-items-end">
                <div class="col-6 col-md-3">
                    <label class="form-label small mb-1">Tanggal Awal</label>
                    <input type="date" class="form-control form-control-sm" name="tanggal_awal" value="<?= $tanggal_awal ?>">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small mb-1">Tanggal Akhir</label>
                    <input type="date" class="form-control form-control-sm" name="tanggal_akhir" value="<?= $tanggal_akhir ?>">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small mb-1">Kelas</label>
                    <select class="form-select form-select-sm" name="kelas_id">
                        <option value="">Semua Kelas</option>
                        <?php foreach ($kelas_list as $kelas): ?>
                            <option value="<?= $kelas['id'] ?>" <?= $kelas_id == $kelas['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($kelas['nama_kelas']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small mb-1">Status</label>
                    <select class="form-select form-select-sm" name="status">
                        <option value="">Semua Status</option>
                        <option value="pending" <?= $status == 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="disetujui" <?= $status == 'disetujui' ? 'selected' : '' ?>>Disetujui</option>
                        <option value="ditolak" <?= $status == 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
                    </select>
                </div>
                <div class="col-12 mt-2">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-search me-1"></i> Tampilkan
                    </button>
                    <a href="cetak_bukti.php" class="btn btn-secondary btn-sm">
                        <i class="fas fa-undo me-1"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Statistik -->
    <?php if (!empty($pelanggaran_list)): ?>
    <div class="quick-stats-grid mb-3">
        <div class="quick-stat-card">
            <div class="quick-stat-icon bg-danger">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="quick-stat-info">
                <h6 class="quick-stat-label">Total</h6>
                <h4 class="quick-stat-number"><?= count($pelanggaran_list) ?></h4>
                <small class="text-muted">Pelanggaran</small>
            </div>
        </div>
        <div class="quick-stat-card">
            <div class="quick-stat-icon bg-warning">
                <i class="fas fa-user-graduate"></i>
            </div>
            <div class="quick-stat-info">
                <h6 class="quick-stat-label">Siswa</h6>
                <h4 class="quick-stat-number"><?= count(array_unique(array_column($pelanggaran_list, 'siswa_id'))) ?></h4>
                <small class="text-muted">Terlibat</small>
            </div>
        </div>
        <div class="quick-stat-card">
            <div class="quick-stat-icon bg-info">
                <i class="fas fa-tag"></i>
            </div>
            <div class="quick-stat-info">
                <h6 class="quick-stat-label">Poin</h6>
                <h4 class="quick-stat-number"><?= array_sum(array_column($pelanggaran_list, 'poin')) ?></h4>
                <small class="text-muted">Total</small>
            </div>
        </div>
        <div class="quick-stat-card">
            <div class="quick-stat-icon bg-success">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="quick-stat-info">
                <h6 class="quick-stat-label">Disetujui</h6>
                <h4 class="quick-stat-number"><?= count(array_filter($pelanggaran_list, function($p) { return $p['status'] == 'disetujui'; })) ?></h4>
                <small class="text-muted">Pelanggaran</small>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Tabel Data - Mobile Friendly -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-list me-2"></i> Data Pelanggaran
            <span class="info-badge">
                <i class="fas fa-info-circle"></i> 
                <?= count($pelanggaran_list) ?> data
            </span>
        </div>
        <div class="card-body p-0">
            <!-- Mobile View: Card List -->
            <?php if (!empty($pelanggaran_list)): ?>
            <div class="d-block d-md-none">
                <?php $no = 1; foreach ($pelanggaran_list as $p): ?>
                <div class="mobile-card-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="badge bg-secondary me-2">#<?= $no++ ?></span>
                            <span class="badge bg-<?= $p['status'] == 'disetujui' ? 'success' : ($p['status'] == 'ditolak' ? 'danger' : 'warning') ?>">
                                <?= ucfirst($p['status'] ?? 'pending') ?>
                            </span>
                        </div>
                        <span class="badge bg-danger"><?= $p['poin'] ?? 0 ?> Poin</span>
                    </div>
                    <div class="mt-2">
                        <div><strong><?= htmlspecialchars($p['nama_siswa'] ?? '-') ?></strong></div>
                        <div class="text-muted small">
                            <i class="fas fa-calendar me-1"></i> <?= formatTanggalIndo($p['tanggal_pelanggaran']) ?>
                            <?php if (!empty($p['jam_pelanggaran'])): ?>
                            <i class="fas fa-clock ms-2 me-1"></i> <?= date('H:i', strtotime($p['jam_pelanggaran'])) ?>
                            <?php endif; ?>
                        </div>
                        <div class="text-muted small">
                            <i class="fas fa-school me-1"></i> <?= htmlspecialchars($p['nama_kelas'] ?? '-') ?>
                            <i class="fas fa-tag ms-2 me-1"></i> <?= htmlspecialchars($p['nama_pelanggaran'] ?? '-') ?>
                        </div>
                        <?php if (!empty($p['lokasi'])): ?>
                        <div class="text-muted small">
                            <i class="fas fa-map-marker-alt me-1"></i> <?= htmlspecialchars($p['lokasi']) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="mt-2 d-flex gap-1">
                        <a href="<?= APP_URL ?>guru/detail_pelanggaran.php?id=<?= $p['id'] ?>" 
                           class="btn btn-outline-info btn-sm">
                            <i class="fas fa-eye"></i> Detail
                        </a>
                        <?php if (!empty($p['bukti'])): ?>
                        <a href="<?= APP_URL ?>uploads/bukti_pelanggaran/<?= $p['bukti'] ?>" 
                           target="_blank" class="btn btn-outline-success btn-sm">
                            <i class="fas fa-camera"></i> Bukti
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Desktop View: Table -->
            <div class="d-none d-md-block">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="40" class="text-center">No</th>
                                <th>Tanggal</th>
                                <th>Jam</th>
                                <th>Siswa</th>
                                <th>Kelas</th>
                                <th>Pelanggaran</th>
                                <th class="text-center">Poin</th>
                                <th class="text-center">Status</th>
                                <th class="text-center" width="90">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($pelanggaran_list as $p): ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?></td>
                                    <td><?= formatTanggalIndo($p['tanggal_pelanggaran']) ?></td>
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
                                        <?php if (!empty($p['lokasi'])): ?>
                                            <br>
                                            <small class="text-muted">
                                                <i class="fas fa-map-marker-alt me-1"></i> <?= htmlspecialchars($p['lokasi']) ?>
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
                                        ?>
                                        <span class="badge bg-<?= $badge ?>">
                                            <?= ucfirst($status) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?= APP_URL ?>guru/detail_pelanggaran.php?id=<?= $p['id'] ?>" 
                                           class="btn btn-outline-info btn-sm" title="Detail">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if (!empty($p['bukti'])): ?>
                                            <a href="<?= APP_URL ?>uploads/bukti_pelanggaran/<?= $p['bukti'] ?>" 
                                               target="_blank" class="btn btn-outline-success btn-sm" title="Lihat Bukti">
                                                <i class="fas fa-camera"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php else: ?>
                <div class="text-center text-muted py-4">
                    <i class="fas fa-file-alt fa-2x mb-2 d-block opacity-25"></i>
                    <p class="mb-0">Tidak ada data pelanggaran</p>
                    <small class="text-muted">Ubah filter untuk melihat data</small>
                </div>
            <?php endif; ?>
        </div>
        <?php if (!empty($pelanggaran_list)): ?>
            <div class="card-footer bg-transparent text-muted d-flex justify-content-between align-items-center flex-wrap">
                <span>
                    <i class="fas fa-info-circle me-1"></i>
                    <?= count($pelanggaran_list) ?> pelanggaran
                </span>
                <span>
                    <i class="fas fa-calendar me-1"></i>
                    <?= formatTanggalIndo($tanggal_awal) ?> - <?= formatTanggalIndo($tanggal_akhir) ?>
                </span>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* ============================================
   STYLE CETAK BUKTI - MOBILE FRIENDLY
   ============================================ */

.dashboard-container {
    padding: 12px !important;
    max-width: 100%;
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
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
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
.quick-stat-icon.bg-danger { background: #EF4444; }
.quick-stat-icon.bg-warning { background: #F59E0B; }
.quick-stat-icon.bg-info { background: #3B82F6; }
.quick-stat-icon.bg-success { background: #10B981; }

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

/* Card */
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
    font-size: 13px;
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 6px;
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

/* Mobile Card Item */
.mobile-card-item {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 10px 12px;
    margin-bottom: 8px;
    border: 1px solid #e9ecef;
}
.mobile-card-item:last-child {
    margin-bottom: 0;
}

/* Form */
.form-control-sm, .form-select-sm {
    border-radius: 6px;
    border: 1px solid #e2e8f0;
    padding: 4px 8px;
    font-size: 12px;
    height: 30px;
}
.form-control-sm:focus, .form-select-sm:focus {
    border-color: #4F46E5;
    box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.15);
}
.form-label {
    font-weight: 600;
    color: #4a5568;
    font-size: 10px;
    margin-bottom: 2px;
}

/* Buttons */
.btn-sm {
    padding: 4px 12px !important;
    font-size: 11px !important;
    border-radius: 6px !important;
    min-height: 28px;
}

.btn-primary {
    background: linear-gradient(135deg, #4F46E5 0%, #4338CA 100%);
    border: none;
    color: #fff;
}
.btn-primary:hover {
    background: #4338CA;
    color: #fff;
}
.btn-secondary {
    background: #e2e8f0;
    border: none;
    color: #4a5568;
}
.btn-secondary:hover {
    background: #cbd5e0;
    color: #4a5568;
}

/* Badge */
.badge {
    font-size: 8px !important;
    padding: 2px 6px !important;
}

.info-badge {
    font-size: 10px;
    color: #718096;
    margin-left: 4px;
}

/* Table */
.table-bordered {
    border: 1px solid #dee2e6;
}
.table-bordered th,
.table-bordered td {
    border: 1px solid #dee2e6;
    padding: 4px 6px;
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
.table tbody td {
    font-size: 11px;
}
.table tbody tr:hover {
    background-color: rgba(79, 70, 229, 0.04);
}

/* Responsive */
@media (max-width: 768px) {
    .dashboard-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 4px;
        margin-bottom: 8px;
    }
    .dashboard-title {
        font-size: 0.95rem;
    }
    .quick-stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 4px;
    }
    .quick-stat-card {
        padding: 6px 8px;
        gap: 6px;
    }
    .quick-stat-icon {
        width: 26px;
        height: 26px;
        font-size: 10px;
    }
    .quick-stat-number {
        font-size: 0.85rem;
    }
    .quick-stat-label {
        font-size: 0.4rem;
    }
    .card-body {
        padding: 8px 10px;
    }
    .card-header {
        padding: 8px 10px;
        font-size: 12px;
    }
    .card-footer {
        padding: 6px 10px;
        font-size: 10px;
    }
    .btn-sm {
        padding: 3px 8px !important;
        font-size: 10px !important;
        min-height: 24px;
    }
}

@media (max-width: 576px) {
    .dashboard-container {
        padding: 4px !important;
    }
    .dashboard-title {
        font-size: 0.85rem;
    }
    .dashboard-subtitle {
        font-size: 0.6rem;
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
        width: 22px;
        height: 22px;
        font-size: 9px;
        border-radius: 6px;
    }
    .quick-stat-number {
        font-size: 0.75rem;
    }
    .quick-stat-label {
        font-size: 0.35rem;
    }
    .mobile-card-item {
        padding: 8px 10px;
        font-size: 12px;
    }
    .btn-sm {
        padding: 2px 6px !important;
        font-size: 9px !important;
        min-height: 20px;
    }
    .badge {
        font-size: 7px !important;
        padding: 1px 4px !important;
    }
    .form-control-sm, .form-select-sm {
        font-size: 11px;
        height: 26px;
        padding: 2px 6px;
    }
}
</style>

<?php include '../includes/footer.php'; ?>