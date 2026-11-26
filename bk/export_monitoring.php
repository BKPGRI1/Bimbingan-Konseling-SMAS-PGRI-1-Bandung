<?php
// bk/export_monitoring.php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Cek login
if (!isLoggedIn()) {
    header("Location: ../login.php");
    exit;
}

// Cek role (hanya BK)
if (!hasRole(['bk'])) {
    exit('Akses ditolak!');
}

// Get parameters
$action = isset($_GET['action']) ? $_GET['action'] : '';
$kelas_id = isset($_GET['kelas_id']) ? (int)$_GET['kelas_id'] : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'poin_desc';

if (empty($action) || !in_array($action, ['excel', 'pdf'])) {
    die("Aksi tidak valid!");
}

// Ambil data siswa dengan poin
$conn = getDB();
$query = "SELECT s.*, k.nama_kelas,
          (SELECT COUNT(*) FROM pelanggaran WHERE siswa_id = s.id AND status = 'disetujui') as total_pelanggaran,
          (SELECT SUM(poin_diperoleh) FROM pelanggaran WHERE siswa_id = s.id AND status = 'disetujui') as total_poin
          FROM siswa s
          LEFT JOIN kelas k ON s.kelas_id = k.id
          WHERE 1=1";

if ($kelas_id > 0) {
    $query .= " AND s.kelas_id = $kelas_id";
}

if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $query .= " AND (s.nama_siswa LIKE '%$search%' OR s.nisn LIKE '%$search%')";
}

// Sorting
switch ($sort_by) {
    case 'poin_asc':
        $query .= " ORDER BY total_poin ASC";
        break;
    case 'nama_asc':
        $query .= " ORDER BY s.nama_siswa ASC";
        break;
    case 'nama_desc':
        $query .= " ORDER BY s.nama_siswa DESC";
        break;
    default:
        $query .= " ORDER BY total_poin DESC";
}

$siswa_list = getRows($query);

// Total statistik
$total_siswa = count($siswa_list);
$total_poin = array_sum(array_column($siswa_list, 'total_poin'));
$total_pelanggaran = array_sum(array_column($siswa_list, 'total_pelanggaran'));

// Batas poin
$batas = getBatasPoin();

// ============================================
// AMBIL PENGATURAN DARI TABEL pengaturan
// ============================================
$pengaturan = getPengaturan();
$nama_sekolah = $pengaturan['nama_sekolah'] ?? 'SMA Negeri 1 Tatib';
$alamat = $pengaturan['alamat'] ?? '';
$no_telp = $pengaturan['no_telp'] ?? '';
$email = $pengaturan['email'] ?? '';
$logo = $pengaturan['logo'] ?? '';

// Nama kelas
$kelas_nama = 'Semua Kelas';
if ($kelas_id > 0) {
    $kelas = getKelasById($kelas_id);
    $kelas_nama = $kelas ? $kelas['nama_kelas'] : 'Semua Kelas';
}

// ============================================
// EXPORT EXCEL
// ============================================
if ($action == 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="Monitoring_Poin_' . date('Ymd') . '.xls"');
    
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta charset="UTF-8"><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Monitoring Poin</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--></head>';
    echo '<body>';
    
    // Kop Surat
    echo '<table width="100%" border="0" cellpadding="3" cellspacing="0">';
    echo '<tr>';
    echo '<td width="15%" align="center">';
    if (!empty($logo) && file_exists(APP_PATH . 'uploads/logo/' . $logo)) {
        echo '<img src="' . APP_PATH . 'uploads/logo/' . $logo . '" width="60" height="60">';
    }
    echo '</td>';
    echo '<td width="70%" align="center">';
    echo '<b style="font-size:16px;">' . strtoupper($nama_sekolah) . '</b><br>';
    echo $alamat . '<br>';
    $info = '';
    if (!empty($no_telp)) $info .= 'Telp: ' . $no_telp;
    if (!empty($email)) $info .= ' | Email: ' . $email;
    echo $info;
    echo '</td>';
    echo '<td width="15%" align="center">&nbsp;</td>';
    echo '</tr>';
    echo '</table>';
    echo '<hr>';
    
    echo '<h3 style="text-align:center;">LAPORAN MONITORING POIN SISWA</h3>';
    echo '<p style="text-align:center;">Kelas: ' . $kelas_nama . '</p>';
    echo '<p style="text-align:center;">Total Siswa: ' . $total_siswa . ' | Total Poin: ' . number_format($total_poin) . ' | Total Pelanggaran: ' . $total_pelanggaran . '</p>';
    echo '<p style="text-align:center;">Batas Poin: Teguran ' . $batas['peringatan'] . ' | Pemanggilan ' . $batas['pemanggilan'] . ' | DO ' . $batas['do'] . '</p>';
    
    echo '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse:collapse;width:100%;font-size:11px;">';
    echo '<thead>';
    echo '<tr style="background-color:#f0f0f0;">';
    echo '<th>No</th>';
    echo '<th>NISN</th>';
    echo '<th>Nama Siswa</th>';
    echo '<th>Kelas</th>';
    echo '<th>JK</th>';
    echo '<th>Total Pelanggaran</th>';
    echo '<th>Total Poin</th>';
    echo '<th>Status</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    $no = 1;
    foreach ($siswa_list as $row) {
        $poin = (int)($row['total_poin'] ?? 0);
        $level = getPoinLevel($poin);
        
        echo '<tr>';
        echo '<td align="center">' . $no++ . '</td>';
        echo '<td>' . ($row['nisn'] ?? '-') . '</td>';
        echo '<td>' . $row['nama_siswa'] . '</td>';
        echo '<td>' . ($row['nama_kelas'] ?? '-') . '</td>';
        echo '<td align="center">' . ($row['jenis_kelamin'] ?? '-') . '</td>';
        echo '<td align="center">' . ((int)($row['total_pelanggaran'] ?? 0)) . '</td>';
        echo '<td align="center">' . $poin . '</td>';
        echo '<td align="center">' . $level['level'] . '</td>';
        echo '</tr>';
    }
    
    if ($total_siswa == 0) {
        echo '<tr><td colspan="8" align="center">Tidak ada data siswa</td></tr>';
    }
    
    echo '<tr style="background-color:#f8f9fa;font-weight:bold;">';
    echo '<td colspan="6" align="right">TOTAL</td>';
    echo '<td align="center">' . $total_poin . '</td>';
    echo '<td></td>';
    echo '</tr>';
    
    echo '</tbody>';
    echo '</table>';
    
    echo '<br>';
    echo '<p style="text-align:right;">Dicetak pada: ' . date('d-m-Y H:i:s') . '</p>';
    echo '<p style="text-align:right;">Petugas BK</p>';
    
    echo '</body></html>';
    exit;
}

// ============================================
// EXPORT PDF
// ============================================
if ($action == 'pdf') {
    require_once('../tcpdf/tcpdf.php');
    
    class MYPDF extends TCPDF {
        public function Header() {}
        public function Footer() {}
    }
    
    $pdf = new MYPDF('L', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('Tatib Sekolah');
    $pdf->SetAuthor($nama_sekolah);
    $pdf->SetTitle('Monitoring Poin Siswa');
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(15, 10, 15);
    $pdf->AddPage();
    
    // ============================================
    // KOP SURAT
    // ============================================
    
    // Logo
    if (!empty($logo) && file_exists(APP_PATH . 'uploads/logo/' . $logo)) {
        $pdf->Image(APP_PATH . 'uploads/logo/' . $logo, 15, 10, 22, 22);
    }
    
    $pdf->SetY(10);
    $pdf->SetFont('helvetica', 'B', 15);
    $pdf->Cell(0, 7, strtoupper($nama_sekolah), 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 9);
    $pdf->Cell(0, 5, $alamat, 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 8);
    $info = '';
    if (!empty($no_telp)) $info .= 'Telp: ' . $no_telp;
    if (!empty($email)) $info .= ' | Email: ' . $email;
    $pdf->Cell(0, 4, $info, 0, 1, 'C');
    
    // Garis Kop
    $y_after_text = $pdf->GetY();
    $pdf->SetDrawColor(0, 0, 0);
    $pdf->SetLineWidth(0.8);
    $pdf->Line(15, $y_after_text + 3, 280, $y_after_text + 3);
    $pdf->SetLineWidth(0.3);
    $pdf->Line(15, $y_after_text + 5, 280, $y_after_text + 5);
    $pdf->SetY($y_after_text + 9);
    
    // ============================================
    // JUDUL
    // ============================================
    
    $pdf->SetFont('helvetica', 'B', 13);
    $pdf->Cell(0, 7, 'LAPORAN MONITORING POIN SISWA', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 9);
    $pdf->Cell(0, 5, 'Kelas: ' . $kelas_nama, 0, 1, 'C');
    $pdf->Ln(2);
    
    // Summary
    $pdf->SetFont('helvetica', '', 9);
    $pdf->Cell(0, 5, 'Total Siswa: ' . $total_siswa . ' | Total Poin: ' . number_format($total_poin) . ' | Total Pelanggaran: ' . $total_pelanggaran, 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 8);
    $pdf->Cell(0, 4, 'Batas Poin: Teguran ' . $batas['peringatan'] . ' | Pemanggilan ' . $batas['pemanggilan'] . ' | DO ' . $batas['do'], 0, 1, 'L');
    $pdf->Ln(3);
    
    // ============================================
    // TABEL
    // ============================================
    
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetFillColor(220, 220, 230);
    
    $headers = ['No', 'NISN', 'Nama Siswa', 'Kelas', 'JK', 'Pelanggaran', 'Poin', 'Status'];
    $widths = [8, 22, 40, 20, 10, 18, 18, 22];
    
    foreach ($headers as $i => $header) {
        $pdf->Cell($widths[$i], 7, $header, 1, 0, 'C', true);
    }
    $pdf->Ln();
    
    $pdf->SetFont('helvetica', '', 7.5);
    $no = 1;
    foreach ($siswa_list as $row) {
        $poin = (int)($row['total_poin'] ?? 0);
        $level = getPoinLevel($poin);
        
        $pdf->Cell($widths[0], 6, $no++, 1, 0, 'C');
        $pdf->Cell($widths[1], 6, $row['nisn'] ?? '-', 1, 0, 'C');
        $pdf->Cell($widths[2], 6, substr($row['nama_siswa'], 0, 22), 1, 0, 'L');
        $pdf->Cell($widths[3], 6, $row['nama_kelas'] ?? '-', 1, 0, 'C');
        $pdf->Cell($widths[4], 6, $row['jenis_kelamin'] ?? '-', 1, 0, 'C');
        $pdf->Cell($widths[5], 6, (int)($row['total_pelanggaran'] ?? 0), 1, 0, 'C');
        $pdf->Cell($widths[6], 6, $poin, 1, 0, 'C');
        $pdf->Cell($widths[7], 6, $level['level'], 1, 0, 'C');
        $pdf->Ln();
    }
    
    if ($total_siswa == 0) {
        $total_width = array_sum($widths);
        $pdf->Cell($total_width, 6, 'Tidak ada data siswa', 1, 0, 'C');
        $pdf->Ln();
    }
    
    // Total
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetFillColor(240, 240, 245);
    $total_width = array_sum($widths);
    $pdf->Cell($total_width - 18 - 22, 6, 'TOTAL POIN', 1, 0, 'R', true);
    $pdf->Cell(18, 6, $total_poin, 1, 0, 'C', true);
    $pdf->Cell(22, 6, '', 1, 0, 'C', true);
    $pdf->Ln(8);
    
    // ============================================
    // FOOTER
    // ============================================
    
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->Cell(0, 4, 'Dicetak pada: ' . date('d-m-Y H:i:s'), 0, 1, 'L');
    $pdf->Cell(0, 4, 'Petugas BK', 0, 1, 'L');
    
    $filename = 'Monitoring_Poin_' . date('Ymd') . '.pdf';
    $pdf->Output($filename, 'I');
    exit;
}
?>