<?php
// admin/riwayat.php
include '../includes/config.php';
checkLogin();

$page_title = 'Riwayat Pelanggaran';

// Handle export requests
if (isset($_GET['export'])) {
    $export_type = $_GET['export'];
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
    $kelas_id = isset($_GET['kelas_id']) ? (int)$_GET['kelas_id'] : null;
    $siswa_id = isset($_GET['siswa_id']) ? (int)$_GET['siswa_id'] : null;
    
    $data = getReportPelanggaran($start_date, $end_date, $kelas_id, $siswa_id);
    
    if ($export_type == 'pdf') {
        exportPDF($data, $start_date, $end_date);
    } elseif ($export_type == 'excel') {
        exportExcel($data, $start_date, $end_date);
    }
    exit;
}

// Get data for filter
$kelas_list = getKelasOptions();
$siswa_list = getSiswa();

// Filter
$search = isset($_GET['search']) ? $_GET['search'] : '';
$kelas_filter = isset($_GET['kelas']) ? (int)$_GET['kelas'] : null;
$siswa_filter = isset($_GET['siswa']) ? (int)$_GET['siswa'] : null;
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Get data
$pelanggaran = getReportPelanggaran($start_date, $end_date, $kelas_filter, $siswa_filter);

// Filter by search
if (!empty($search)) {
    $search = strtolower($search);
    $pelanggaran = array_filter($pelanggaran, function($item) use ($search) {
        return strpos(strtolower($item['nama_siswa']), $search) !== false ||
               strpos(strtolower($item['nisn'] ?? ''), $search) !== false ||
               strpos(strtolower($item['nama_pelanggaran']), $search) !== false;
    });
}

include '../includes/header.php';
?>

<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
        <h4 class="mb-0">
            <i class="fas fa-history text-primary me-2"></i> Riwayat Pelanggaran
        </h4>
        <div class="d-flex gap-2 flex-wrap">
            <button class="btn btn-danger btn-sm" onclick="exportPDF()">
                <i class="fas fa-file-pdf me-1"></i> PDF
            </button>
            <button class="btn btn-success btn-sm" onclick="exportExcel()">
                <i class="fas fa-file-excel me-1"></i> Excel
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
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="" id="filterForm">
                <div class="row g-2">
                    <div class="col-md-3">
                        <label class="form-label" style="font-size: 12px;">Tanggal Mulai</label>
                        <input type="date" class="form-control" name="start_date" value="<?= $start_date ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" style="font-size: 12px;">Tanggal Selesai</label>
                        <input type="date" class="form-control" name="end_date" value="<?= $end_date ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" style="font-size: 12px;">Kelas</label>
                        <select class="form-select" name="kelas">
                            <option value="">Semua Kelas</option>
                            <?php foreach ($kelas_list as $kelas): ?>
                                <option value="<?= $kelas['id'] ?>" <?= $kelas_filter == $kelas['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($kelas['nama_kelas']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" style="font-size: 12px;">Siswa</label>
                        <select class="form-select" name="siswa">
                            <option value="">Semua Siswa</option>
                            <?php foreach ($siswa_list as $siswa): ?>
                                <option value="<?= $siswa['id'] ?>" <?= $siswa_filter == $siswa['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($siswa['nama_siswa']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" style="font-size: 12px;">Cari Nama/NISN/Pelanggaran</label>
                        <input type="text" class="form-control" name="search" placeholder="Cari..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-8 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter me-1"></i> Filter
                        </button>
                        <a href="<?= APP_URL ?>admin/riwayat.php" class="btn btn-secondary">
                            <i class="fas fa-undo me-1"></i> Reset
                        </a>
                        <span class="text-muted ms-auto" style="font-size: 13px;">
                            <i class="fas fa-file-alt me-1"></i> Total: <?= count($pelanggaran) ?> data
                        </span>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Riwayat -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0" id="tableRiwayat">
                    <thead class="table-light">
                        <tr>
                            <th width="45" class="text-center">No</th>
                            <th>Tanggal</th>
                            <th>NISN</th>
                            <th>Nama Siswa</th>
                            <th>Kelas</th>
                            <th>Pelanggaran</th>
                            <th class="text-center">Poin</th>
                            <th>Guru</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($pelanggaran)): ?>
                            <?php $no = 1; foreach ($pelanggaran as $p): ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?></td>
                                    <td><?= formatTanggalIndo($p['tanggal_pelanggaran']) ?></td>
                                    <td><span class="badge bg-light text-dark" style="font-size: 10px;"><?= htmlspecialchars($p['nisn'] ?? '-') ?></span></td>
                                    <td><strong><?= htmlspecialchars($p['nama_siswa']) ?></strong></td>
                                    <td><?= htmlspecialchars($p['nama_kelas'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($p['nama_pelanggaran']) ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-danger" style="font-size: 11px;"><?= $p['poin'] ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($p['guru'] ?? '-') ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-<?= getStatusBadge($p['status']) ?>" style="font-size: 10px;">
                                            <?= getStatusLabel($p['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block opacity-25"></i>
                                    Tidak ada data pelanggaran
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Export PDF
function exportPDF() {
    var form = document.getElementById('filterForm');
    var action = form.action || window.location.pathname;
    var params = new URLSearchParams(new FormData(form));
    params.append('export', 'pdf');
    window.open(action + '?' + params.toString(), '_blank');
}

// Export Excel
function exportExcel() {
    var form = document.getElementById('filterForm');
    var action = form.action || window.location.pathname;
    var params = new URLSearchParams(new FormData(form));
    params.append('export', 'excel');
    window.open(action + '?' + params.toString(), '_blank');
}

// Auto close toast
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

<?php
// ============================================
// EXPORT FUNCTIONS
// ============================================

function exportPDF($data, $start_date, $end_date) {
    require_once('../tcpdf/tcpdf.php');
    
    // Create new PDF document
    $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('Tatib Sekolah');
    $pdf->SetAuthor('Tatib Sekolah');
    $pdf->SetTitle('Laporan Pelanggaran');
    $pdf->SetSubject('Laporan Pelanggaran Siswa');
    
    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Add a page
    $pdf->AddPage();
    
    // Set font
    $pdf->SetFont('helvetica', '', 10);
    
    // Title
    $pengaturan = getPengaturan();
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, $pengaturan['nama_sekolah'], 0, 1, 'C');
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'LAPORAN PELANGGARAN SISWA', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 7, 'Periode: ' . formatTanggalIndo($start_date) . ' s/d ' . formatTanggalIndo($end_date), 0, 1, 'C');
    $pdf->Ln(5);
    
    // Table header
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetFillColor(240, 240, 240);
    
    $headers = ['No', 'Tanggal', 'NISN', 'Nama Siswa', 'Kelas', 'Pelanggaran', 'Poin', 'Guru', 'Status'];
    $widths = [10, 25, 25, 35, 25, 40, 15, 30, 25];
    
    foreach ($headers as $i => $header) {
        $pdf->Cell($widths[$i], 8, $header, 1, 0, 'C', true);
    }
    $pdf->Ln();
    
    // Table data
    $pdf->SetFont('helvetica', '', 8);
    $no = 1;
    foreach ($data as $row) {
        $pdf->Cell($widths[0], 7, $no++, 1, 0, 'C');
        $pdf->Cell($widths[1], 7, formatTanggal($row['tanggal_pelanggaran']), 1, 0, 'C');
        $pdf->Cell($widths[2], 7, $row['nisn'] ?? '-', 1, 0, 'C');
        $pdf->Cell($widths[3], 7, substr($row['nama_siswa'], 0, 20), 1, 0, 'L');
        $pdf->Cell($widths[4], 7, $row['nama_kelas'] ?? '-', 1, 0, 'C');
        $pdf->Cell($widths[5], 7, substr($row['nama_pelanggaran'], 0, 20), 1, 0, 'L');
        $pdf->Cell($widths[6], 7, $row['poin'], 1, 0, 'C');
        $pdf->Cell($widths[7], 7, substr($row['guru'] ?? '-', 0, 15), 1, 0, 'L');
        $pdf->Cell($widths[8], 7, getStatusLabel($row['status']), 1, 0, 'C');
        $pdf->Ln();
    }
    
    // Summary
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 7, 'Total Pelanggaran: ' . count($data), 0, 1, 'R');
    
    // Footer
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->Cell(0, 7, 'Dicetak: ' . date('d-m-Y H:i:s'), 0, 1, 'L');
    
    // Output PDF
    $pdf->Output('Laporan_Pelanggaran_' . date('Ymd') . '.pdf', 'I');
    exit;
}

function exportExcel($data, $start_date, $end_date) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="Laporan_Pelanggaran_' . date('Ymd') . '.xls"');
    header('Cache-Control: max-age=0');
    
    $pengaturan = getPengaturan();
    
    echo '<html>';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<title>Laporan Pelanggaran</title>';
    echo '<style>';
    echo 'th { background-color: #4F46E5; color: white; font-weight: bold; padding: 8px; border: 1px solid #ddd; text-align: center; }';
    echo 'td { padding: 6px; border: 1px solid #ddd; }';
    echo '.header { font-size: 16px; font-weight: bold; text-align: center; }';
    echo '.sub-header { font-size: 12px; text-align: center; margin-bottom: 10px; }';
    echo '.total { font-weight: bold; text-align: right; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    
    // Header
    echo '<h2 class="header">' . $pengaturan['nama_sekolah'] . '</h2>';
    echo '<h3 class="header">LAPORAN PELANGGARAN SISWA</h3>';
    echo '<p class="sub-header">Periode: ' . formatTanggalIndo($start_date) . ' s/d ' . formatTanggalIndo($end_date) . '</p>';
    
    // Table
    echo '<table cellpadding="0" cellspacing="0" border="1" style="width:100%;border-collapse:collapse;">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>No</th>';
    echo '<th>Tanggal</th>';
    echo '<th>NISN</th>';
    echo '<th>Nama Siswa</th>';
    echo '<th>Kelas</th>';
    echo '<th>Pelanggaran</th>';
    echo '<th>Poin</th>';
    echo '<th>Guru</th>';
    echo '<th>Status</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    $no = 1;
    foreach ($data as $row) {
        echo '<tr>';
        echo '<td style="text-align:center;">' . $no++ . '</td>';
        echo '<td style="text-align:center;">' . formatTanggal($row['tanggal_pelanggaran']) . '</td>';
        echo '<td style="text-align:center;">' . ($row['nisn'] ?? '-') . '</td>';
        echo '<td>' . $row['nama_siswa'] . '</td>';
        echo '<td>' . ($row['nama_kelas'] ?? '-') . '</td>';
        echo '<td>' . $row['nama_pelanggaran'] . '</td>';
        echo '<td style="text-align:center;">' . $row['poin'] . '</td>';
        echo '<td>' . ($row['guru'] ?? '-') . '</td>';
        echo '<td style="text-align:center;">' . getStatusLabel($row['status']) . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    
    // Total
    echo '<p class="total">Total Pelanggaran: ' . count($data) . '</p>';
    echo '<p style="font-size:10px;color:#999;">Dicetak: ' . date('d-m-Y H:i:s') . '</p>';
    
    echo '</body>';
    echo '</html>';
    exit;
}
?>