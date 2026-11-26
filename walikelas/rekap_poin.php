<?php
// walikelas/rekap_poin.php
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

$page_title = 'Rekap Poin Siswa';

// Get data wali kelas
$user = getCurrentUser();
$kelas_wali = getRow("SELECT * FROM kelas WHERE wali_kelas_id = " . $user['id']);
$kelas_id = $kelas_wali ? $kelas_wali['id'] : 0;
$nama_kelas = $kelas_wali ? $kelas_wali['nama_kelas'] : 'Belum ditentukan';

// Get siswa dengan poin
$siswa_list = [];
if ($kelas_id > 0) {
    $siswa_list = getRows("SELECT s.*, 
                          (SELECT COUNT(*) FROM pelanggaran WHERE siswa_id = s.id) as total_pelanggaran,
                          (SELECT SUM(poin_diperoleh) FROM pelanggaran WHERE siswa_id = s.id AND status = 'disetujui') as total_poin
                          FROM siswa s
                          WHERE s.kelas_id = $kelas_id
                          ORDER BY total_poin DESC");
}

// Filter search
$search = isset($_GET['search']) ? $_GET['search'] : '';
if (!empty($search)) {
    $search = strtolower($search);
    $siswa_list = array_filter($siswa_list, function($item) use ($search) {
        return strpos(strtolower($item['nama_siswa']), $search) !== false ||
               strpos(strtolower($item['nisn'] ?? ''), $search) !== false;
    });
}

// Total
$total_siswa = count($siswa_list);
$total_poin = array_sum(array_column($siswa_list, 'total_poin'));
$total_pelanggaran = array_sum(array_column($siswa_list, 'total_pelanggaran'));

// Handle export
if (isset($_GET['export'])) {
    $export_type = $_GET['export'];
    
    if ($export_type == 'pdf') {
        exportRekapPoinPDF($siswa_list, $nama_kelas, $user['nama_lengkap']);
    } elseif ($export_type == 'excel') {
        exportRekapPoinExcel($siswa_list, $nama_kelas, $user['nama_lengkap']);
    }
    exit;
}

include '../includes/header.php';
include '../includes/sidebar_walikelas.php';
?>

<div class="container-fluid px-2 px-md-3 px-lg-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
        <div>
            <h4 class="mb-0">
                <i class="fas fa-star text-warning me-2"></i> Rekap Poin Siswa
            </h4>
            <small class="text-muted">
                <i class="fas fa-school me-1"></i> Kelas: <strong><?= htmlspecialchars($nama_kelas) ?></strong>
                <?php if ($kelas_id > 0): ?>
                    <span class="badge bg-primary ms-1">ID: <?= $kelas_id ?></span>
                <?php endif; ?>
                <span class="badge bg-secondary ms-1">
                    <i class="fas fa-user-graduate me-1"></i> <?= $total_siswa ?> Siswa
                </span>
            </small>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="?export=pdf<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="btn btn-danger btn-sm" target="_blank">
                <i class="fas fa-file-pdf me-1"></i> PDF
            </a>
            <a href="?export=excel<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="btn btn-success btn-sm" target="_blank">
                <i class="fas fa-file-excel me-1"></i> Excel
            </a>
        </div>
    </div>

    <?php if ($kelas_id == 0): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Anda belum ditugaskan sebagai wali kelas. Silahkan hubungi administrator.
        </div>
    <?php elseif (empty($siswa_list)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            Belum ada siswa di kelas <?= htmlspecialchars($nama_kelas) ?>.
        </div>
    <?php else: ?>

    <!-- Summary Cards -->
    <div class="row g-2 g-md-3 mb-3">
        <div class="col-4 col-md-4">
            <div class="card stat-card bg-primary text-white h-100">
                <div class="card-body p-2 p-md-3">
                    <div class="text-center">
                        <h6 class="text-white-50 mb-0 text-uppercase" style="font-size: 0.6rem; letter-spacing: 0.5px;">Total Siswa</h6>
                        <h3 class="mb-0" style="font-size: 1.3rem; font-weight: 700;"><?= $total_siswa ?></h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-4 col-md-4">
            <div class="card stat-card bg-danger text-white h-100">
                <div class="card-body p-2 p-md-3">
                    <div class="text-center">
                        <h6 class="text-white-50 mb-0 text-uppercase" style="font-size: 0.6rem; letter-spacing: 0.5px;">Total Pelanggaran</h6>
                        <h3 class="mb-0" style="font-size: 1.3rem; font-weight: 700;"><?= $total_pelanggaran ?></h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-4 col-md-4">
            <div class="card stat-card bg-warning text-white h-100">
                <div class="card-body p-2 p-md-3">
                    <div class="text-center">
                        <h6 class="text-white-50 mb-0 text-uppercase" style="font-size: 0.6rem; letter-spacing: 0.5px;">Total Poin</h6>
                        <h3 class="mb-0" style="font-size: 1.3rem; font-weight: 700;"><?= number_format($total_poin) ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="" class="row g-2">
                <div class="col-md-9">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" name="search" 
                               placeholder="Cari NISN atau Nama Siswa..." 
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

    <!-- Tabel Rekap Poin -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="50" class="text-center">No</th>
                            <th>NISN</th>
                            <th>Nama Siswa</th>
                            <th class="text-center">JK</th>
                            <th class="text-center">Total Pelanggaran</th>
                            <th class="text-center">Total Poin</th>
                            <th class="text-center">Level</th>
                            <th class="text-center" width="80">Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($siswa_list)): ?>
                            <?php $no = 1; foreach ($siswa_list as $siswa): ?>
                                <?php 
                                $poin = (int)($siswa['total_poin'] ?? 0);
                                $level = getPoinLevel($poin);
                                $total_pel = (int)($siswa['total_pelanggaran'] ?? 0);
                                ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?></td>
                                    <td><span class="badge bg-light text-dark"><?= htmlspecialchars($siswa['nisn'] ?? '-') ?></span></td>
                                    <td><strong><?= htmlspecialchars($siswa['nama_siswa']) ?></strong></td>
                                    <td class="text-center"><?= $siswa['jenis_kelamin'] == 'L' ? 'L' : 'P' ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-info"><?= $total_pel ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-danger"><?= number_format($poin) ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-<?= $level['color'] ?>">
                                            <i class="fas <?= $level['icon'] ?> me-1"></i> <?= $level['level'] ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?= APP_URL ?>walikelas/siswa_detail_walikelas.php?id=<?= $siswa['id'] ?>" 
                                           class="btn btn-outline-info btn-sm" title="Detail">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    <i class="fas fa-user-slash fa-2x mb-2 d-block opacity-25"></i>
                                    Tidak ada siswa
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
                Menampilkan <?= count($siswa_list) ?> siswa
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
    padding: 10px 12px;
    vertical-align: middle;
}

.table thead th {
    background: #f8f9fa;
    font-weight: 600;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    color: #495057;
    border-bottom: 2px solid #dee2e6;
}

.table tbody tr:hover {
    background-color: rgba(108, 99, 255, 0.04);
}

.table tbody td {
    font-size: 14px;
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

/* Responsive - Mobile */
@media (max-width: 768px) {
    .table thead th {
        font-size: 10px;
        padding: 6px 8px;
    }
    
    .table tbody td {
        font-size: 12px;
        padding: 6px 8px;
    }
    
    .btn-group-sm .btn {
        padding: 2px 6px;
        font-size: 10px;
    }
    
    .badge {
        font-size: 9px !important;
        padding: 2px 6px !important;
    }
}

@media (max-width: 576px) {
    .table thead th {
        font-size: 9px;
        padding: 4px 6px;
    }
    
    .table tbody td {
        font-size: 10px;
        padding: 4px 6px;
    }
    
    .table tbody td .badge {
        font-size: 8px !important;
        padding: 2px 5px !important;
    }
    
    .btn-group-sm .btn {
        padding: 1px 4px;
        font-size: 9px;
    }
}
</style>

<?php include '../includes/footer.php'; ?>

<?php
// ============================================
// EXPORT FUNCTIONS
// ============================================

function exportRekapPoinPDF($data, $nama_kelas, $wali_kelas) {
    require_once('../tcpdf/tcpdf.php');
    
    // Get pengaturan untuk kop surat
    $pengaturan = getPengaturan();
    $nama_sekolah = $pengaturan['nama_sekolah'] ?? 'SMA Negeri 1 Tatib';
    $alamat = $pengaturan['alamat'] ?? '';
    $no_telp = $pengaturan['no_telp'] ?? '';
    $email = $pengaturan['email'] ?? '';
    $logo = $pengaturan['logo'] ?? '';
    
    // Create PDF
    $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('Tatib Sekolah');
    $pdf->SetAuthor($nama_sekolah);
    $pdf->SetTitle('Rekap Poin Siswa - ' . $nama_kelas);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->AddPage();
    
    // ============================================
    // KOP SURAT
    // ============================================
    
    // Logo
    $logo_path = APP_PATH . 'uploads/logo/' . $logo;
    if (!empty($logo) && file_exists($logo_path)) {
        $pdf->Image($logo_path, 15, 8, 25, 25);
    }
    
    // Nama Sekolah
    $pdf->SetY(10);
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 8, strtoupper($nama_sekolah), 0, 1, 'C');
    
    // Alamat
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 5, $alamat, 0, 1, 'C');
    $pdf->Cell(0, 5, 'Telp: ' . $no_telp . ' | Email: ' . $email, 0, 1, 'C');
    
    // Garis Kop
    $pdf->Ln(2);
    $pdf->SetDrawColor(0, 0, 0);
    $pdf->SetLineWidth(0.5);
    $pdf->Line(15, 48, 280, 48);
    $pdf->SetLineWidth(0.2);
    $pdf->Line(15, 49, 280, 49);
    $pdf->Ln(8);
    
    // ============================================
    // JUDUL LAPORAN
    // ============================================
    
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 8, 'REKAP POIN SISWA', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(0, 6, 'Kelas: ' . $nama_kelas, 0, 1, 'C');
    $pdf->Cell(0, 6, 'Wali Kelas: ' . $wali_kelas, 0, 1, 'C');
    $pdf->Cell(0, 6, 'Tanggal Cetak: ' . date('d F Y'), 0, 1, 'C');
    $pdf->Ln(4);
    
    // ============================================
    // TABEL
    // ============================================
    
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetFillColor(240, 240, 240);
    
    $headers = ['No', 'NISN', 'Nama Siswa', 'JK', 'Total Pelanggaran', 'Total Poin', 'Level'];
    $widths = [12, 30, 50, 15, 30, 30, 25];
    
    foreach ($headers as $i => $header) {
        $pdf->Cell($widths[$i], 8, $header, 1, 0, 'C', true);
    }
    $pdf->Ln();
    
    $pdf->SetFont('helvetica', '', 8);
    $no = 1;
    foreach ($data as $row) {
        $poin = (int)($row['total_poin'] ?? 0);
        $level = getPoinLevel($poin);
        $total_pel = (int)($row['total_pelanggaran'] ?? 0);
        
        $pdf->Cell($widths[0], 7, $no++, 1, 0, 'C');
        $pdf->Cell($widths[1], 7, $row['nisn'] ?? '-', 1, 0, 'C');
        $pdf->Cell($widths[2], 7, substr($row['nama_siswa'], 0, 25), 1, 0, 'L');
        $pdf->Cell($widths[3], 7, $row['jenis_kelamin'] ?? '-', 1, 0, 'C');
        $pdf->Cell($widths[4], 7, $total_pel, 1, 0, 'C');
        $pdf->Cell($widths[5], 7, number_format($poin), 1, 0, 'C');
        $pdf->Cell($widths[6], 7, $level['level'], 1, 0, 'C');
        $pdf->Ln();
    }
    
    // ============================================
    // FOOTER LAPORAN
    // ============================================
    
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 7, 'Total Siswa: ' . count($data), 0, 1, 'R');
    
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->Cell(0, 5, 'Dicetak oleh: ' . $wali_kelas, 0, 1, 'L');
    $pdf->Cell(0, 5, 'Dicetak pada: ' . date('d-m-Y H:i:s'), 0, 1, 'L');
    
    // Output PDF
    $pdf->Output('Rekap_Poin_' . $nama_kelas . '_' . date('Ymd') . '.pdf', 'I');
    exit;
}

function exportRekapPoinExcel($data, $nama_kelas, $wali_kelas) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="Rekap_Poin_' . $nama_kelas . '_' . date('Ymd') . '.xls"');
    header('Cache-Control: max-age=0');
    
    $pengaturan = getPengaturan();
    $nama_sekolah = $pengaturan['nama_sekolah'] ?? 'SMA Negeri 1 Tatib';
    $alamat = $pengaturan['alamat'] ?? '';
    $no_telp = $pengaturan['no_telp'] ?? '';
    $email = $pengaturan['email'] ?? '';
    
    echo '<html>';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<title>Rekap Poin Siswa</title>';
    echo '<style>';
    echo 'body { font-family: Arial, sans-serif; }';
    echo '.header { text-align: center; margin-bottom: 20px; }';
    echo '.header h1 { font-size: 20px; font-weight: bold; margin: 0; }';
    echo '.header h2 { font-size: 16px; margin: 5px 0; }';
    echo '.header p { font-size: 12px; margin: 2px 0; color: #666; }';
    echo 'table { width: 100%; border-collapse: collapse; margin-top: 15px; }';
    echo 'th { background-color: #4F46E5; color: white; font-weight: bold; padding: 8px; border: 1px solid #ddd; text-align: center; }';
    echo 'td { padding: 6px; border: 1px solid #ddd; text-align: center; }';
    echo '.td-left { text-align: left; }';
    echo '.footer { margin-top: 20px; font-size: 11px; color: #666; }';
    echo '.footer-right { text-align: right; }';
    echo '.badge-level { padding: 2px 8px; border-radius: 4px; font-size: 10px; }';
    echo '.bg-danger { background-color: #dc3545; color: white; }';
    echo '.bg-warning { background-color: #ffc107; color: #212529; }';
    echo '.bg-info { background-color: #17a2b8; color: white; }';
    echo '.bg-success { background-color: #28a745; color: white; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    
    // ============================================
    // KOP SURAT
    // ============================================
    
    echo '<div class="header">';
    echo '<h1>' . strtoupper($nama_sekolah) . '</h1>';
    echo '<p>' . $alamat . '</p>';
    echo '<p>Telp: ' . $no_telp . ' | Email: ' . $email . '</p>';
    echo '<hr style="border: 2px solid #000; margin: 10px 0;">';
    echo '<hr style="border: 1px solid #000; margin: 2px 0 15px 0;">';
    echo '</div>';
    
    // ============================================
    // JUDUL LAPORAN
    // ============================================
    
    echo '<h2 style="text-align:center; margin:10px 0;">REKAP POIN SISWA</h2>';
    echo '<p style="text-align:center; margin:5px 0;"><strong>Kelas:</strong> ' . $nama_kelas . ' | <strong>Wali Kelas:</strong> ' . $wali_kelas . '</p>';
    echo '<p style="text-align:center; margin:5px 0 15px 0; color:#666; font-size:12px;">Tanggal Cetak: ' . date('d F Y') . '</p>';
    
    // ============================================
    // TABEL
    // ============================================
    
    echo '<table>';
    echo '<thead>';
    echo '<tr>';
    echo '<th>No</th>';
    echo '<th>NISN</th>';
    echo '<th style="text-align:left;">Nama Siswa</th>';
    echo '<th>JK</th>';
    echo '<th>Total Pelanggaran</th>';
    echo '<th>Total Poin</th>';
    echo '<th>Level</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    $no = 1;
    foreach ($data as $row) {
        $poin = (int)($row['total_poin'] ?? 0);
        $level = getPoinLevel($poin);
        $total_pel = (int)($row['total_pelanggaran'] ?? 0);
        
        $level_class = '';
        $level_label = $level['level'];
        if ($level['color'] == 'danger') $level_class = 'bg-danger';
        elseif ($level['color'] == 'warning') $level_class = 'bg-warning';
        elseif ($level['color'] == 'info') $level_class = 'bg-info';
        else $level_class = 'bg-success';
        
        echo '<tr>';
        echo '<td>' . $no++ . '</td>';
        echo '<td>' . ($row['nisn'] ?? '-') . '</td>';
        echo '<td class="td-left">' . $row['nama_siswa'] . '</td>';
        echo '<td>' . ($row['jenis_kelamin'] ?? '-') . '</td>';
        echo '<td>' . $total_pel . '</td>';
        echo '<td>' . number_format($poin) . '</td>';
        echo '<td><span class="badge-level ' . $level_class . '">' . $level_label . '</span></td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    
    // ============================================
    // FOOTER
    // ============================================
    
    echo '<div class="footer">';
    echo '<div class="footer-right">';
    echo '<p><strong>Total Siswa:</strong> ' . count($data) . '</p>';
    echo '<p>Dicetak oleh: ' . $wali_kelas . '</p>';
    echo '<p>Dicetak pada: ' . date('d-m-Y H:i:s') . '</p>';
    echo '</div>';
    echo '</div>';
    
    echo '</body>';
    echo '</html>';
    exit;
}
?>