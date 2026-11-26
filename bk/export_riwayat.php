<?php
// bk/export_riwayat.php
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
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

if (empty($action) || !in_array($action, ['excel', 'pdf'])) {
    die("Aksi tidak valid!");
}

// Ambil data pelanggaran
$conn = getDB();
$query = "SELECT p.*, s.nama_siswa, s.nisn, k.nama_kelas, 
          jp.nama_pelanggaran, jp.poin, jp.kategori,
          u.nama_lengkap as guru
          FROM pelanggaran p
          LEFT JOIN siswa s ON p.siswa_id = s.id
          LEFT JOIN kelas k ON s.kelas_id = k.id
          LEFT JOIN jenis_pelanggaran jp ON p.jenis_pelanggaran_id = jp.id
          LEFT JOIN users u ON p.guru_id = u.id
          WHERE p.status = 'disetujui'
          AND p.tanggal_pelanggaran BETWEEN '$start_date' AND '$end_date'";

if ($kelas_id > 0) {
    $query .= " AND s.kelas_id = $kelas_id";
}

if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $query .= " AND (s.nama_siswa LIKE '%$search%' OR s.nisn LIKE '%$search%')";
}

$query .= " ORDER BY p.tanggal_pelanggaran DESC, p.created_at DESC";

$pelanggaran = getRows($query);

// Total statistik
$total_pelanggaran = count($pelanggaran);
$total_poin = array_sum(array_column($pelanggaran, 'poin_diperoleh'));

// ============================================
// AMBIL PENGATURAN DARI TABEL pengaturan (bukan pengaturan_surat)
// ============================================
$pengaturan = getPengaturan();
$nama_sekolah = $pengaturan['nama_sekolah'] ?? 'SMA Negeri 1 Tatib';
$alamat = $pengaturan['alamat'] ?? '';
$no_telp = $pengaturan['no_telp'] ?? '';
$email = $pengaturan['email'] ?? '';
$logo = $pengaturan['logo'] ?? ''; // Logo dari admin/pengaturan/profil.php

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
    header('Content-Disposition: attachment; filename="Riwayat_Pelanggaran_' . date('Ymd') . '.xls"');
    
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta charset="UTF-8"><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Riwayat Pelanggaran</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--></head>';
    echo '<body>';
    
    // Kop Surat - 1 LOGO SAJA
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
    
    echo '<h3 style="text-align:center;">LAPORAN RIWAYAT PELANGGARAN</h3>';
    echo '<p style="text-align:center;">Periode: ' . date('d-m-Y', strtotime($start_date)) . ' s/d ' . date('d-m-Y', strtotime($end_date)) . '</p>';
    echo '<p style="text-align:center;">Kelas: ' . $kelas_nama . '</p>';
    echo '<p style="text-align:center;">Total Data: ' . $total_pelanggaran . ' | Total Poin: ' . number_format($total_poin) . '</p>';
    
    echo '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse:collapse;width:100%;font-size:11px;">';
    echo '<thead>';
    echo '<tr style="background-color:#f0f0f0;">';
    echo '<th>No</th>';
    echo '<th>Tanggal</th>';
    echo '<th>NISN</th>';
    echo '<th>Nama Siswa</th>';
    echo '<th>Kelas</th>';
    echo '<th>Pelanggaran</th>';
    echo '<th>Kategori</th>';
    echo '<th>Poin</th>';
    echo '<th>Guru</th>';
    echo '<th>Keterangan</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    $no = 1;
    foreach ($pelanggaran as $row) {
        echo '<tr>';
        echo '<td align="center">' . $no++ . '</td>';
        echo '<td>' . date('d-m-Y', strtotime($row['tanggal_pelanggaran'])) . '</td>';
        echo '<td>' . $row['nisn'] . '</td>';
        echo '<td>' . $row['nama_siswa'] . '</td>';
        echo '<td>' . ($row['nama_kelas'] ?? '-') . '</td>';
        echo '<td>' . $row['nama_pelanggaran'] . '</td>';
        echo '<td>' . ucfirst($row['kategori']) . '</td>';
        echo '<td align="center">' . $row['poin_diperoleh'] . '</td>';
        echo '<td>' . ($row['guru'] ?? '-') . '</td>';
        echo '<td>' . ($row['keterangan'] ?? '-') . '</td>';
        echo '</tr>';
    }
    
    if ($total_pelanggaran == 0) {
        echo '<tr><td colspan="10" align="center">Tidak ada data pelanggaran</td></tr>';
    }
    
    echo '<tr style="background-color:#f8f9fa;font-weight:bold;">';
    echo '<td colspan="7" align="right">TOTAL</td>';
    echo '<td align="center">' . $total_poin . '</td>';
    echo '<td colspan="2"></td>';
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
    $pdf->SetTitle('Riwayat Pelanggaran');
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(15, 10, 15);
    $pdf->AddPage();
    
    // ============================================
    // KOP SURAT - 1 LOGO SAJA
    // ============================================
    
    // Logo - 1 LOGO SAJA (dari admin/pengaturan/profil.php)
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
    $pdf->Cell(0, 7, 'LAPORAN RIWAYAT PELANGGARAN', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 9);
    $pdf->Cell(0, 5, 'Periode: ' . date('d-m-Y', strtotime($start_date)) . ' s/d ' . date('d-m-Y', strtotime($end_date)), 0, 1, 'C');
    $pdf->Cell(0, 5, 'Kelas: ' . $kelas_nama, 0, 1, 'C');
    $pdf->Ln(2);
    
    // Summary
    $pdf->SetFont('helvetica', '', 9);
    $pdf->Cell(0, 5, 'Total Data: ' . $total_pelanggaran . ' | Total Poin: ' . number_format($total_poin), 0, 1, 'L');
    $pdf->Ln(3);
    
    // ============================================
    // TABEL
    // ============================================
    
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetFillColor(220, 220, 230);
    
    $headers = ['No', 'Tanggal', 'NISN', 'Nama Siswa', 'Kelas', 'Pelanggaran', 'Kategori', 'Poin', 'Guru', 'Keterangan'];
    $widths = [8, 18, 20, 35, 18, 30, 20, 12, 25, 35];
    
    foreach ($headers as $i => $header) {
        $pdf->Cell($widths[$i], 7, $header, 1, 0, 'C', true);
    }
    $pdf->Ln();
    
    $pdf->SetFont('helvetica', '', 7.5);
    $no = 1;
    foreach ($pelanggaran as $row) {
        $pdf->Cell($widths[0], 6, $no++, 1, 0, 'C');
        $pdf->Cell($widths[1], 6, date('d-m-Y', strtotime($row['tanggal_pelanggaran'])), 1, 0, 'C');
        $pdf->Cell($widths[2], 6, $row['nisn'], 1, 0, 'C');
        $pdf->Cell($widths[3], 6, substr($row['nama_siswa'], 0, 20), 1, 0, 'L');
        $pdf->Cell($widths[4], 6, $row['nama_kelas'] ?? '-', 1, 0, 'C');
        $pdf->Cell($widths[5], 6, substr($row['nama_pelanggaran'], 0, 18), 1, 0, 'L');
        $pdf->Cell($widths[6], 6, ucfirst($row['kategori']), 1, 0, 'C');
        $pdf->Cell($widths[7], 6, $row['poin_diperoleh'], 1, 0, 'C');
        $pdf->Cell($widths[8], 6, substr($row['guru'] ?? '-', 0, 15), 1, 0, 'L');
        $pdf->Cell($widths[9], 6, substr($row['keterangan'] ?? '-', 0, 25), 1, 0, 'L');
        $pdf->Ln();
    }
    
    if ($total_pelanggaran == 0) {
        $total_width = array_sum($widths);
        $pdf->Cell($total_width, 6, 'Tidak ada data pelanggaran', 1, 0, 'C');
        $pdf->Ln();
    }
    
    // Total
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetFillColor(240, 240, 245);
    $total_width = array_sum($widths);
    $pdf->Cell($total_width - 12 - 35, 6, 'TOTAL POIN', 1, 0, 'R', true);
    $pdf->Cell(12, 6, $total_poin, 1, 0, 'C', true);
    $pdf->Cell(35, 6, '', 1, 0, 'C', true);
    $pdf->Ln(8);
    
    // ============================================
    // FOOTER - TANPA TTD DAN STEMPEL
    // ============================================
    
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->Cell(0, 4, 'Dicetak pada: ' . date('d-m-Y H:i:s'), 0, 1, 'L');
    $pdf->Cell(0, 4, 'Petugas BK', 0, 1, 'L');
    
    $filename = 'Riwayat_Pelanggaran_' . date('Ymd') . '.pdf';
    $pdf->Output($filename, 'I');
    exit;
}
?>