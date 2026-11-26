<?php
// walikelas/monitoring_siswa.php
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

$page_title = 'Monitoring Siswa Bermasalah';

// Get data wali kelas
$user = getCurrentUser();
$kelas_wali = getRow("SELECT * FROM kelas WHERE wali_kelas_id = " . $user['id']);
$kelas_id = $kelas_wali ? $kelas_wali['id'] : 0;
$nama_kelas = $kelas_wali ? $kelas_wali['nama_kelas'] : 'Belum ditentukan';

// Get siswa dengan poin dan pelanggaran
$siswa_list = [];
if ($kelas_id > 0) {
    $siswa_list = getRows("SELECT s.*, 
                          (SELECT COUNT(*) FROM pelanggaran WHERE siswa_id = s.id) as total_pelanggaran,
                          (SELECT SUM(poin_diperoleh) FROM pelanggaran WHERE siswa_id = s.id AND status = 'disetujui') as total_poin
                          FROM siswa s
                          WHERE s.kelas_id = $kelas_id
                          ORDER BY total_poin DESC");
}

// Filter by search
$search = isset($_GET['search']) ? $_GET['search'] : '';
if (!empty($search)) {
    $search = strtolower($search);
    $siswa_list = array_filter($siswa_list, function($item) use ($search) {
        return strpos(strtolower($item['nama_siswa']), $search) !== false ||
               strpos(strtolower($item['nisn'] ?? ''), $search) !== false;
    });
}

// Filter by level
$level_filter = isset($_GET['level']) ? $_GET['level'] : '';
if (!empty($level_filter)) {
    $siswa_list = array_filter($siswa_list, function($item) use ($level_filter) {
        $poin = (int)($item['total_poin'] ?? 0);
        $level = getPoinLevel($poin);
        return $level['level'] == $level_filter;
    });
}

// Get pengaturan batas poin
$batas = getBatasPoin();

// Statistik
$total_siswa = count($siswa_list);
$total_bermasalah = 0;
$total_peringatan = 0;
$total_pemanggilan = 0;
$total_do = 0;

foreach ($siswa_list as $s) {
    $poin = (int)($s['total_poin'] ?? 0);
    $level = getPoinLevel($poin);
    if ($level['level'] != 'Aman') {
        $total_bermasalah++;
        if ($level['level'] == 'Peringatan') $total_peringatan++;
        elseif ($level['level'] == 'Pemanggilan') $total_pemanggilan++;
        elseif ($level['level'] == 'DO') $total_do++;
    }
}

include '../includes/header_walikelas.php';
?>

<div class="container-fluid px-2 px-md-3 px-lg-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
        <div>
            <h4 class="mb-0">
                <i class="fas fa-exclamation-triangle text-warning me-2"></i> Monitoring Siswa Bermasalah
            </h4>
            <small class="text-muted">
                <i class="fas fa-school me-1"></i> Kelas: <strong><?= htmlspecialchars($nama_kelas) ?></strong>
                <?php if ($kelas_id > 0): ?>
                    <span class="badge bg-primary ms-1">ID: <?= $kelas_id ?></span>
                <?php endif; ?>
                <span class="badge bg-danger ms-1">
                    <i class="fas fa-exclamation-circle me-1"></i> <?= $total_bermasalah ?> Bermasalah
                </span>
            </small>
        </div>
        <div>
            <a href="<?= APP_URL ?>walikelas/monitoring_siswa.php?export=pdf" class="btn btn-danger btn-sm" target="_blank">
                <i class="fas fa-file-pdf me-1"></i> PDF
            </a>
            <a href="<?= APP_URL ?>walikelas/monitoring_siswa.php?export=excel" class="btn btn-success btn-sm" target="_blank">
                <i class="fas fa-file-excel me-1"></i> Excel
            </a>
        </div>
    </div>

    <?php if ($kelas_id == 0): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Anda belum ditugaskan sebagai wali kelas. Silahkan hubungi administrator.
        </div>
    <?php else: ?>

    <!-- Summary Cards -->
    <div class="row g-2 g-md-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="card stat-card bg-success text-white h-100">
                <div class="card-body p-2 p-md-3 text-center">
                    <h6 class="text-white-50 mb-0 text-uppercase" style="font-size: 0.55rem; letter-spacing: 0.5px;">Total Siswa</h6>
                    <h2 class="mb-0" style="font-size: 1.3rem; font-weight: 700;"><?= $total_siswa ?></h2>
                    <small class="text-white-50" style="font-size: 0.5rem;">Di Kelas</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card bg-warning text-white h-100">
                <div class="card-body p-2 p-md-3 text-center">
                    <h6 class="text-white-50 mb-0 text-uppercase" style="font-size: 0.55rem; letter-spacing: 0.5px;">Peringatan</h6>
                    <h2 class="mb-0" style="font-size: 1.3rem; font-weight: 700;"><?= $total_peringatan ?></h2>
                    <small class="text-white-50" style="font-size: 0.5rem;">≥ <?= $batas['peringatan'] ?> Poin</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card bg-danger text-white h-100">
                <div class="card-body p-2 p-md-3 text-center">
                    <h6 class="text-white-50 mb-0 text-uppercase" style="font-size: 0.55rem; letter-spacing: 0.5px;">Pemanggilan</h6>
                    <h2 class="mb-0" style="font-size: 1.3rem; font-weight: 700;"><?= $total_pemanggilan ?></h2>
                    <small class="text-white-50" style="font-size: 0.5rem;">≥ <?= $batas['pemanggilan'] ?> Poin</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card bg-dark text-white h-100">
                <div class="card-body p-2 p-md-3 text-center">
                    <h6 class="text-white-50 mb-0 text-uppercase" style="font-size: 0.55rem; letter-spacing: 0.5px;">DO</h6>
                    <h2 class="mb-0" style="font-size: 1.3rem; font-weight: 700;"><?= $total_do ?></h2>
                    <small class="text-white-50" style="font-size: 0.5rem;">≥ <?= $batas['do'] ?> Poin</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="" class="row g-2">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" name="search" 
                               placeholder="Cari NISN atau Nama Siswa..." 
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <select class="form-select" name="level">
                        <option value="">Semua Level</option>
                        <option value="Peringatan" <?= $level_filter == 'Peringatan' ? 'selected' : '' ?>>Peringatan</option>
                        <option value="Pemanggilan" <?= $level_filter == 'Pemanggilan' ? 'selected' : '' ?>>Pemanggilan</option>
                        <option value="DO" <?= $level_filter == 'DO' ? 'selected' : '' ?>>DO</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-1"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Monitoring -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="45" class="text-center">No</th>
                            <th>NISN</th>
                            <th>Nama Siswa</th>
                            <th class="text-center">JK</th>
                            <th class="text-center">Total Pelanggaran</th>
                            <th class="text-center">Total Poin</th>
                            <th class="text-center">Level</th>
                            <th class="text-center">Rekomendasi</th>
                            <th class="text-center" width="100">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($siswa_list)): ?>
                            <?php $no = 1; foreach ($siswa_list as $siswa): ?>
                                <?php 
                                $poin = (int)($siswa['total_poin'] ?? 0);
                                $level = getPoinLevel($poin);
                                $total_pel = (int)($siswa['total_pelanggaran'] ?? 0);
                                
                                // Rekomendasi berdasarkan level
                                $rekomendasi = '';
                                $rekomendasi_badge = '';
                                if ($level['level'] == 'Aman') {
                                    $rekomendasi = 'Pertahankan';
                                    $rekomendasi_badge = 'success';
                                } elseif ($level['level'] == 'Peringatan') {
                                    $rekomendasi = 'Pembinaan';
                                    $rekomendasi_badge = 'warning';
                                } elseif ($level['level'] == 'Pemanggilan') {
                                    $rekomendasi = 'Panggil Orang Tua';
                                    $rekomendasi_badge = 'danger';
                                } elseif ($level['level'] == 'DO') {
                                    $rekomendasi = 'Konsultasi BK';
                                    $rekomendasi_badge = 'dark';
                                }
                                ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?></td>
                                    <td><span class="badge bg-light text-dark"><?= htmlspecialchars($siswa['nisn'] ?? '-') ?></span></td>
                                    <td>
                                        <strong><?= htmlspecialchars($siswa['nama_siswa']) ?></strong>
                                        <?php if ($level['level'] != 'Aman'): ?>
                                            <span class="badge bg-<?= $level['color'] ?> ms-1" style="font-size: 8px;">
                                                <i class="fas <?= $level['icon'] ?>"></i>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center"><?= $siswa['jenis_kelamin'] == 'L' ? 'L' : 'P' ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-info"><?= $total_pel ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-danger"><?= number_format($poin) ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-<?= $level['color'] ?>" style="font-size: 11px;">
                                            <i class="fas <?= $level['icon'] ?> me-1"></i> <?= $level['level'] ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-<?= $rekomendasi_badge ?>" style="font-size: 10px;">
                                            <?= $rekomendasi ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="<?= APP_URL ?>walikelas/siswa_detail_walikelas.php?id=<?= $siswa['id'] ?>" 
                                               class="btn btn-outline-info" title="Detail">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($level['level'] != 'Aman'): ?>
                                                <a href="<?= APP_URL ?>walikelas/catatan_pembinaan.php?siswa=<?= $siswa['id'] ?>" 
                                                   class="btn btn-outline-warning" title="Buat Catatan">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    <i class="fas fa-check-circle fa-2x mb-2 d-block text-success opacity-50"></i>
                                    <?php if (!empty($search) || !empty($level_filter)): ?>
                                        Tidak ada data yang sesuai dengan filter
                                    <?php else: ?>
                                        Semua siswa dalam keadaan baik! 🎉
                                    <?php endif; ?>
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
                Menampilkan <?= count($siswa_list) ?> siswa | 
                <span class="text-success">Aman: <?= $total_siswa - $total_bermasalah ?></span> | 
                <span class="text-warning">Peringatan: <?= $total_peringatan ?></span> | 
                <span class="text-danger">Pemanggilan: <?= $total_pemanggilan ?></span> | 
                <span class="text-dark">DO: <?= $total_do ?></span>
            </div>
        <?php endif; ?>
    </div>

    <?php endif; ?>
</div>

<style>
/* Stat Cards */
.stat-card {
    border-radius: 10px;
    border: none;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}

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

@media (max-width: 576px) {
    .stat-card h2 {
        font-size: 1rem !important;
    }
    .stat-card h6 {
        font-size: 0.45rem !important;
    }
}
</style>

<?php include '../includes/footer.php'; ?>

<?php
// ============================================
// EXPORT FUNCTIONS
// ============================================

if (isset($_GET['export'])) {
    $export_type = $_GET['export'];
    
    if ($export_type == 'pdf') {
        exportMonitoringPDF($siswa_list, $nama_kelas, $user['nama_lengkap']);
    } elseif ($export_type == 'excel') {
        exportMonitoringExcel($siswa_list, $nama_kelas, $user['nama_lengkap']);
    }
    exit;
}

function exportMonitoringPDF($data, $nama_kelas, $wali_kelas) {
    require_once('../tcpdf/tcpdf.php');
    
    $pengaturan = getPengaturan();
    $nama_sekolah = $pengaturan['nama_sekolah'] ?? 'SMA Negeri 1 Tatib';
    $alamat = $pengaturan['alamat'] ?? '';
    $no_telp = $pengaturan['no_telp'] ?? '';
    $email = $pengaturan['email'] ?? '';
    $logo = $pengaturan['logo'] ?? '';
    $batas = getBatasPoin();
    
    $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('Tatib Sekolah');
    $pdf->SetAuthor($nama_sekolah);
    $pdf->SetTitle('Monitoring Siswa Bermasalah - ' . $nama_kelas);
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
    $pdf->SetFont('helvetica', 'B', 15);
    $pdf->Cell(0, 7, strtoupper($nama_sekolah), 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 9);
    $pdf->Cell(0, 5, $alamat, 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 8);
    $pdf->Cell(0, 4, 'Telp: ' . $no_telp . ' | Email: ' . $email, 0, 1, 'C');
    
    $y_after_text = $pdf->GetY();
    $pdf->SetDrawColor(0, 0, 0);
    $pdf->SetLineWidth(0.8);
    $pdf->Line(15, $y_after_text + 3, 280, $y_after_text + 3);
    $pdf->SetLineWidth(0.3);
    $pdf->Line(15, $y_after_text + 5, 280, $y_after_text + 5);
    $pdf->SetY($y_after_text + 9);
    
    // JUDUL
    $pdf->SetFont('helvetica', 'B', 13);
    $pdf->Cell(0, 7, 'LAPORAN MONITORING SISWA BERMASALAH', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 5, 'Kelas: ' . $nama_kelas . ' | Wali Kelas: ' . $wali_kelas, 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 8);
    $pdf->Cell(0, 4, 'Batas Poin: Peringatan ' . $batas['peringatan'] . ' | Pemanggilan ' . $batas['pemanggilan'] . ' | DO ' . $batas['do'], 0, 1, 'C');
    $pdf->Ln(4);
    
    // TABEL
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetFillColor(220, 220, 230);
    
    $headers = ['No', 'NISN', 'Nama Siswa', 'JK', 'Total Pelanggaran', 'Total Poin', 'Level', 'Rekomendasi'];
    $widths = [10, 25, 40, 12, 25, 25, 25, 35];
    
    foreach ($headers as $i => $header) {
        $pdf->Cell($widths[$i], 7, $header, 1, 0, 'C', true);
    }
    $pdf->Ln();
    
    $pdf->SetFont('helvetica', '', 7.5);
    $no = 1;
    foreach ($data as $row) {
        $poin = (int)($row['total_poin'] ?? 0);
        $level = getPoinLevel($poin);
        $total_pel = (int)($row['total_pelanggaran'] ?? 0);
        
        $rekomendasi = 'Pertahankan';
        if ($level['level'] == 'Peringatan') $rekomendasi = 'Pembinaan';
        elseif ($level['level'] == 'Pemanggilan') $rekomendasi = 'Panggil Orang Tua';
        elseif ($level['level'] == 'DO') $rekomendasi = 'Konsultasi BK';
        
        $pdf->Cell($widths[0], 6, $no++, 1, 0, 'C');
        $pdf->Cell($widths[1], 6, $row['nisn'] ?? '-', 1, 0, 'C');
        $pdf->Cell($widths[2], 6, substr($row['nama_siswa'], 0, 20), 1, 0, 'L');
        $pdf->Cell($widths[3], 6, $row['jenis_kelamin'] ?? '-', 1, 0, 'C');
        $pdf->Cell($widths[4], 6, $total_pel, 1, 0, 'C');
        $pdf->Cell($widths[5], 6, number_format($poin), 1, 0, 'C');
        $pdf->Cell($widths[6], 6, $level['level'], 1, 0, 'C');
        $pdf->Cell($widths[7], 6, $rekomendasi, 1, 0, 'C');
        $pdf->Ln();
    }
    
    // FOOTER
    $pdf->Ln(4);
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(0, 6, 'Total Siswa: ' . count($data), 0, 1, 'R');
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->Cell(0, 5, 'Dicetak pada: ' . date('d-m-Y H:i:s'), 0, 1, 'L');
    
    $pdf->Output('Monitoring_Siswa_' . $nama_kelas . '_' . date('Ymd') . '.pdf', 'I');
    exit;
}

function exportMonitoringExcel($data, $nama_kelas, $wali_kelas) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="Monitoring_Siswa_' . $nama_kelas . '_' . date('Ymd') . '.xls"');
    header('Cache-Control: max-age=0');
    
    $pengaturan = getPengaturan();
    $nama_sekolah = $pengaturan['nama_sekolah'] ?? 'SMA Negeri 1 Tatib';
    $alamat = $pengaturan['alamat'] ?? '';
    $no_telp = $pengaturan['no_telp'] ?? '';
    $email = $pengaturan['email'] ?? '';
    $batas = getBatasPoin();
    
    echo '<html><head><meta charset="UTF-8"><title>Monitoring Siswa Bermasalah</title>';
    echo '<style>';
    echo 'body { font-family: Arial, sans-serif; }';
    echo '.header { text-align: center; margin-bottom: 20px; }';
    echo '.header h1 { font-size: 20px; font-weight: bold; margin: 0; }';
    echo '.header p { font-size: 12px; margin: 2px 0; color: #666; }';
    echo 'table { width: 100%; border-collapse: collapse; margin-top: 15px; }';
    echo 'th { background-color: #4F46E5; color: white; font-weight: bold; padding: 8px; border: 1px solid #ddd; text-align: center; }';
    echo 'td { padding: 6px; border: 1px solid #ddd; }';
    echo '.total { font-weight: bold; text-align: right; margin-top: 10px; }';
    echo '.bg-success { background-color: #28a745; color: white; }';
    echo '.bg-warning { background-color: #ffc107; color: #212529; }';
    echo '.bg-danger { background-color: #dc3545; color: white; }';
    echo '.bg-dark { background-color: #343a40; color: white; }';
    echo '</style></head><body>';
    
    echo '<div class="header">';
    echo '<h1>' . strtoupper($nama_sekolah) . '</h1>';
    echo '<p>' . $alamat . ' | Telp: ' . $no_telp . ' | Email: ' . $email . '</p>';
    echo '<hr style="border: 2px solid #000; margin: 10px 0;">';
    echo '</div>';
    
    echo '<h2 style="text-align:center;">LAPORAN MONITORING SISWA BERMASALAH</h2>';
    echo '<p style="text-align:center;">Kelas: ' . $nama_kelas . ' | Wali Kelas: ' . $wali_kelas . '</p>';
    echo '<p style="text-align:center;color:#666;">Batas Poin: Peringatan ' . $batas['peringatan'] . ' | Pemanggilan ' . $batas['pemanggilan'] . ' | DO ' . $batas['do'] . '</p>';
    
    echo '<table>';
    echo '<thead><tr>';
    echo '<th>No</th><th>NISN</th><th>Nama Siswa</th><th>JK</th><th>Total Pelanggaran</th><th>Total Poin</th><th>Level</th><th>Rekomendasi</th>';
    echo '</tr></thead><tbody>';
    
    $no = 1;
    foreach ($data as $row) {
        $poin = (int)($row['total_poin'] ?? 0);
        $level = getPoinLevel($poin);
        $total_pel = (int)($row['total_pelanggaran'] ?? 0);
        
        $rekomendasi = 'Pertahankan';
        $level_class = 'bg-success';
        if ($level['level'] == 'Peringatan') { $rekomendasi = 'Pembinaan'; $level_class = 'bg-warning'; }
        elseif ($level['level'] == 'Pemanggilan') { $rekomendasi = 'Panggil Orang Tua'; $level_class = 'bg-danger'; }
        elseif ($level['level'] == 'DO') { $rekomendasi = 'Konsultasi BK'; $level_class = 'bg-dark'; }
        
        echo '<tr>';
        echo '<td style="text-align:center;">' . $no++ . '</td>';
        echo '<td style="text-align:center;">' . ($row['nisn'] ?? '-') . '</td>';
        echo '<td>' . $row['nama_siswa'] . '</td>';
        echo '<td style="text-align:center;">' . ($row['jenis_kelamin'] ?? '-') . '</td>';
        echo '<td style="text-align:center;">' . $total_pel . '</td>';
        echo '<td style="text-align:center;">' . number_format($poin) . '</td>';
        echo '<td style="text-align:center;"><span class="' . $level_class . '" style="padding:2px 8px;border-radius:4px;">' . $level['level'] . '</span></td>';
        echo '<td style="text-align:center;">' . $rekomendasi . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody></table>';
    echo '<p class="total">Total Siswa: ' . count($data) . '</p>';
    echo '<p style="font-size:10px;color:#999;">Dicetak pada: ' . date('d-m-Y H:i:s') . '</p>';
    echo '</body></html>';
    exit;
}
?>