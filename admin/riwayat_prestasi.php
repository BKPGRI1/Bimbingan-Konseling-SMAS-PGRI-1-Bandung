<?php
// admin/riwayat_prestasi.php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Cek login
if (!isLoggedIn()) {
    header("Location: ../login.php");
    exit;
}

// Cek role (hanya admin)
if (!hasRole(['admin'])) {
    $_SESSION['toast'] = [
        'message' => 'Anda tidak memiliki akses ke halaman ini!',
        'type' => 'danger'
    ];
    header("Location: " . APP_URL . "login.php");
    exit();
}

$page_title = 'Riwayat Prestasi';
$conn = getDB();

// Handle export requests
if (isset($_GET['export'])) {
    $export_type = $_GET['export'];
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
    $siswa_id = isset($_GET['siswa_id']) ? (int)$_GET['siswa_id'] : null;
    $jenis_prestasi = isset($_GET['jenis_prestasi']) ? $_GET['jenis_prestasi'] : '';
    $tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : 0;
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    
    // Get data for export
    $where = [];
    if ($siswa_id > 0) {
        $where[] = "p.siswa_id = $siswa_id";
    }
    if (!empty($jenis_prestasi)) {
        $where[] = "p.jenis_prestasi = '$jenis_prestasi'";
    }
    if ($tahun > 0) {
        $where[] = "p.tahun = $tahun";
    }
    if (!empty($search)) {
        $where[] = "(s.nama_siswa LIKE '%$search%' OR s.nisn LIKE '%$search%' OR p.nama_prestasi LIKE '%$search%')";
    }
    if (!empty($start_date) && !empty($end_date)) {
        $where[] = "DATE(p.created_at) BETWEEN '$start_date' AND '$end_date'";
    }
    $where_sql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
    
    $query = "SELECT p.*, s.nama_siswa, s.nisn, k.nama_kelas
              FROM prestasi_siswa p
              JOIN siswa s ON p.siswa_id = s.id
              LEFT JOIN kelas k ON s.kelas_id = k.id
              $where_sql
              ORDER BY p.tahun DESC, p.created_at DESC";
    $data = getRows($query);
    
    if ($export_type == 'pdf') {
        exportPDFPrestasi($data, $start_date, $end_date);
    } elseif ($export_type == 'excel') {
        exportExcelPrestasi($data, $start_date, $end_date);
    }
    exit;
}

// ============================================
// HANDLE FILTER
// ============================================
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$filter_jenis = isset($_GET['jenis_prestasi']) ? sanitize($_GET['jenis_prestasi']) : '';
$filter_tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : 0;
$filter_siswa = isset($_GET['siswa_id']) ? (int)$_GET['siswa_id'] : 0;
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// ============================================
// BUILD QUERY
// ============================================
$where = [];
if (!empty($search)) {
    $where[] = "(s.nama_siswa LIKE '%$search%' OR s.nisn LIKE '%$search%' OR p.nama_prestasi LIKE '%$search%')";
}
if (!empty($filter_jenis)) {
    $where[] = "p.jenis_prestasi = '$filter_jenis'";
}
if ($filter_tahun > 0) {
    $where[] = "p.tahun = $filter_tahun";
}
if ($filter_siswa > 0) {
    $where[] = "p.siswa_id = $filter_siswa";
}
if (!empty($start_date) && !empty($end_date)) {
    $where[] = "DATE(p.created_at) BETWEEN '$start_date' AND '$end_date'";
}
$where_sql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// ============================================
// GET PRESTASI DATA
// ============================================
$query = "SELECT p.*, s.nama_siswa, s.nisn, k.nama_kelas
          FROM prestasi_siswa p
          JOIN siswa s ON p.siswa_id = s.id
          LEFT JOIN kelas k ON s.kelas_id = k.id
          $where_sql
          ORDER BY p.tahun DESC, p.created_at DESC";
$prestasi = getRows($query);

// ============================================
// GET DATA FOR FILTER
// ============================================
$siswa_list = getRows("SELECT id, nama_siswa, nisn FROM siswa ORDER BY nama_siswa");
$tahun_list = getRows("SELECT DISTINCT tahun FROM prestasi_siswa ORDER BY tahun DESC");

// Total prestasi
$total_prestasi = count($prestasi);
$total_poin = array_sum(array_column($prestasi, 'poin_prestasi'));

include '../includes/header.php';
?>

<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
        <h4 class="mb-0">
            <i class="fas fa-trophy text-warning me-2"></i> Riwayat Prestasi
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
                        <label class="form-label" style="font-size: 12px;">Siswa</label>
                        <select class="form-select" name="siswa_id">
                            <option value="">Semua Siswa</option>
                            <?php foreach ($siswa_list as $siswa): ?>
                                <option value="<?= $siswa['id'] ?>" <?= $filter_siswa == $siswa['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($siswa['nama_siswa']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" style="font-size: 12px;">Jenis</label>
                        <select class="form-select" name="jenis_prestasi">
                            <option value="">Semua Jenis</option>
                            <option value="akademik" <?= $filter_jenis == 'akademik' ? 'selected' : '' ?>>Akademik</option>
                            <option value="non_akademik" <?= $filter_jenis == 'non_akademik' ? 'selected' : '' ?>>Non-Akademik</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" style="font-size: 12px;">Tahun</label>
                        <select class="form-select" name="tahun">
                            <option value="">Semua Tahun</option>
                            <?php foreach ($tahun_list as $t): ?>
                                <option value="<?= $t['tahun'] ?>" <?= $filter_tahun == $t['tahun'] ? 'selected' : '' ?>>
                                    <?= $t['tahun'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" style="font-size: 12px;">Cari Nama/NISN/Prestasi</label>
                        <input type="text" class="form-control" name="search" placeholder="Cari..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-8 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter me-1"></i> Filter
                        </button>
                        <a href="<?= APP_URL ?>admin/riwayat_prestasi.php" class="btn btn-secondary">
                            <i class="fas fa-undo me-1"></i> Reset
                        </a>
                        <span class="text-muted ms-auto" style="font-size: 13px;">
                            <i class="fas fa-file-alt me-1"></i> Total: <?= $total_prestasi ?> data
                        </span>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Riwayat Prestasi -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0" id="tableRiwayat">
                    <thead class="table-light">
                        <tr>
                            <th width="45" class="text-center">No</th>
                            <th>No. Prestasi</th>
                            <th>NISN</th>
                            <th>Nama Siswa</th>
                            <th>Kelas</th>
                            <th>Jenis</th>
                            <th>Prestasi</th>
                            <th>Tingkat</th>
                            <th>Tahun</th>
                            <th class="text-center">Poin</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($prestasi)): ?>
                            <?php $no = 1; foreach ($prestasi as $p): ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?></td>
                                    <td><span class="badge bg-primary" style="font-size: 10px;"><?= $p['no_prestasi'] ?? 'PRS' ?></span></td>
                                    <td><span class="badge bg-light text-dark" style="font-size: 10px;"><?= htmlspecialchars($p['nisn'] ?? '-') ?></span></td>
                                    <td><strong><?= htmlspecialchars($p['nama_siswa']) ?></strong></td>
                                    <td><?= htmlspecialchars($p['nama_kelas'] ?? '-') ?></td>
                                    <td>
                                        <span class="badge bg-<?= $p['jenis_prestasi'] == 'akademik' ? 'success' : 'info' ?>" style="font-size: 10px;">
                                            <?= ucfirst(str_replace('_', ' ', $p['jenis_prestasi'])) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($p['nama_prestasi']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $p['tingkat'] == 'internasional' ? 'danger' : 
                                            ($p['tingkat'] == 'nasional' ? 'warning' : 
                                            ($p['tingkat'] == 'provinsi' ? 'info' : 
                                            ($p['tingkat'] == 'kota' ? 'success' : 
                                            ($p['tingkat'] == 'kecamatan' ? 'primary' : 'secondary')))) ?>" style="font-size: 10px;">
                                            <?= ucfirst($p['tingkat']) ?>
                                        </span>
                                    </td>
                                    <td><?= $p['tahun'] ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-success" style="font-size: 11px;">+<?= $p['poin_prestasi'] ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="table-info fw-bold">
                                <td colspan="9" class="text-end">Total Pengurangan Poin</td>
                                <td class="text-center">+<?= $total_poin ?></td>
                            </tr>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">
                                    <i class="fas fa-trophy fa-2x mb-2 d-block opacity-25"></i>
                                    Tidak ada data prestasi
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

function exportPDFPrestasi($data, $start_date, $end_date) {
    require_once('../tcpdf/tcpdf.php');
    
    $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('Tatib Sekolah');
    $pdf->SetAuthor('Tatib Sekolah');
    $pdf->SetTitle('Laporan Prestasi');
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->AddPage();
    
    $pengaturan = getPengaturan();
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, $pengaturan['nama_sekolah'], 0, 1, 'C');
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'LAPORAN PRESTASI SISWA', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 7, 'Periode: ' . formatTanggalIndo($start_date) . ' s/d ' . formatTanggalIndo($end_date), 0, 1, 'C');
    $pdf->Ln(5);
    
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetFillColor(240, 240, 240);
    
    $headers = ['No', 'No. Prestasi', 'NISN', 'Nama Siswa', 'Kelas', 'Jenis', 'Prestasi', 'Tingkat', 'Tahun', 'Poin'];
    $widths = [6, 18, 20, 28, 18, 20, 30, 18, 12, 10];
    
    foreach ($headers as $i => $header) {
        $pdf->Cell($widths[$i], 8, $header, 1, 0, 'C', true);
    }
    $pdf->Ln();
    
    $pdf->SetFont('helvetica', '', 8);
    $no = 1;
    $total_poin = 0;
    foreach ($data as $row) {
        $total_poin += $row['poin_prestasi'];
        $pdf->Cell($widths[0], 7, $no++, 1, 0, 'C');
        $pdf->Cell($widths[1], 7, $row['no_prestasi'] ?? 'PRS', 1, 0, 'C');
        $pdf->Cell($widths[2], 7, $row['nisn'], 1, 0, 'C');
        $pdf->Cell($widths[3], 7, substr($row['nama_siswa'], 0, 18), 1, 0, 'L');
        $pdf->Cell($widths[4], 7, $row['nama_kelas'] ?? '-', 1, 0, 'C');
        $pdf->Cell($widths[5], 7, ucfirst(str_replace('_', ' ', $row['jenis_prestasi'])), 1, 0, 'C');
        $pdf->Cell($widths[6], 7, substr($row['nama_prestasi'], 0, 20), 1, 0, 'L');
        $pdf->Cell($widths[7], 7, ucfirst($row['tingkat']), 1, 0, 'C');
        $pdf->Cell($widths[8], 7, $row['tahun'], 1, 0, 'C');
        $pdf->Cell($widths[9], 7, $row['poin_prestasi'], 1, 0, 'C');
        $pdf->Ln();
    }
    
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(array_sum(array_slice($widths, 0, 9)), 7, 'Total Pengurangan Poin', 1, 0, 'R');
    $pdf->Cell($widths[9], 7, $total_poin, 1, 0, 'C');
    $pdf->Ln();
    
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->Cell(0, 7, 'Total Prestasi: ' . count($data), 0, 1, 'R');
    $pdf->Cell(0, 7, 'Dicetak: ' . date('d-m-Y H:i:s'), 0, 1, 'L');
    
    $pdf->Output('Laporan_Prestasi_' . date('Ymd') . '.pdf', 'I');
    exit;
}

function exportExcelPrestasi($data, $start_date, $end_date) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="Laporan_Prestasi_' . date('Ymd') . '.xls"');
    header('Cache-Control: max-age=0');
    
    $pengaturan = getPengaturan();
    
    echo '<html>';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<title>Laporan Prestasi</title>';
    echo '<style>';
    echo 'th { background-color: #10B981; color: white; font-weight: bold; padding: 8px; border: 1px solid #ddd; text-align: center; }';
    echo 'td { padding: 6px; border: 1px solid #ddd; }';
    echo '.header { font-size: 16px; font-weight: bold; text-align: center; }';
    echo '.sub-header { font-size: 12px; text-align: center; margin-bottom: 10px; }';
    echo '.total { font-weight: bold; text-align: right; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    
    echo '<h2 class="header">' . $pengaturan['nama_sekolah'] . '</h2>';
    echo '<h3 class="header">LAPORAN PRESTASI SISWA</h3>';
    echo '<p class="sub-header">Periode: ' . formatTanggalIndo($start_date) . ' s/d ' . formatTanggalIndo($end_date) . '</p>';
    
    echo '<table cellpadding="0" cellspacing="0" border="1" style="width:100%;border-collapse:collapse;">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>No</th>';
    echo '<th>No. Prestasi</th>';
    echo '<th>NISN</th>';
    echo '<th>Nama Siswa</th>';
    echo '<th>Kelas</th>';
    echo '<th>Jenis</th>';
    echo '<th>Prestasi</th>';
    echo '<th>Tingkat</th>';
    echo '<th>Tahun</th>';
    echo '<th>Poin</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    $no = 1;
    $total_poin = 0;
    foreach ($data as $row) {
        $total_poin += $row['poin_prestasi'];
        echo '<tr>';
        echo '<td style="text-align:center;">' . $no++ . '</td>';
        echo '<td style="text-align:center;">' . ($row['no_prestasi'] ?? 'PRS') . '</td>';
        echo '<td style="text-align:center;">' . $row['nisn'] . '</td>';
        echo '<td>' . $row['nama_siswa'] . '</td>';
        echo '<td>' . ($row['nama_kelas'] ?? '-') . '</td>';
        echo '<td>' . ucfirst(str_replace('_', ' ', $row['jenis_prestasi'])) . '</td>';
        echo '<td>' . $row['nama_prestasi'] . '</td>';
        echo '<td>' . ucfirst($row['tingkat']) . '</td>';
        echo '<td style="text-align:center;">' . $row['tahun'] . '</td>';
        echo '<td style="text-align:center;">' . $row['poin_prestasi'] . '</td>';
        echo '</tr>';
    }
    
    echo '<tr style="background-color:#d1fae5;font-weight:bold;">';
    echo '<td colspan="9" style="text-align:right;">Total Pengurangan Poin</td>';
    echo '<td style="text-align:center;">' . $total_poin . '</td>';
    echo '</tr>';
    
    echo '</tbody>';
    echo '</table>';
    
    echo '<p class="total">Total Prestasi: ' . count($data) . '</p>';
    echo '<p style="font-size:10px;color:#999;">Dicetak: ' . date('d-m-Y H:i:s') . '</p>';
    
    echo '</body>';
    echo '</html>';
    exit;
}
?>