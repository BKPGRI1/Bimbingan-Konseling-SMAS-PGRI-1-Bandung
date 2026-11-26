<?php
// walikelas/export_rekap_pdf.php
include '../includes/config.php';
checkLogin();

// Cek role
if (!hasRole('walikelas')) {
    exit('Akses ditolak!');
}

// Get parameters
$kelas_id = isset($_GET['kelas_id']) ? (int)$_GET['kelas_id'] : 0;
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Get wali kelas
$user = getCurrentUser();
$kelas_wali = getRow("SELECT * FROM kelas WHERE id = $kelas_id");
$nama_kelas = $kelas_wali ? $kelas_wali['nama_kelas'] : 'Belum ditentukan';

// Get data
$siswa_list = getRows("SELECT s.*, 
                      (SELECT COUNT(*) FROM pelanggaran WHERE siswa_id = s.id) as total_pelanggaran,
                      (SELECT SUM(poin_diperoleh) FROM pelanggaran WHERE siswa_id = s.id AND status = 'disetujui') as total_poin
                      FROM siswa s
                      WHERE s.kelas_id = $kelas_id
                      ORDER BY s.nama_siswa");

// Filter by search
if (!empty($search)) {
    $search = strtolower($search);
    $siswa_list = array_filter($siswa_list, function($item) use ($search) {
        return strpos(strtolower($item['nama_siswa']), $search) !== false ||
               strpos(strtolower($item['nisn'] ?? ''), $search) !== false;
    });
}

// Total statistik
$total_siswa = count($siswa_list);
$total_poin = array_sum(array_column($siswa_list, 'total_poin'));
$total_pelanggaran = array_sum(array_column($siswa_list, 'total_pelanggaran'));

// Require TCPDF
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
$pdf->SetTitle('Rekap Data Siswa - ' . $nama_kelas);
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
$pdf->Cell(0, 7, 'REKAP DATA SISWA', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 5, 'Kelas: ' . $nama_kelas . ' | Wali Kelas: ' . $user['nama_lengkap'], 0, 1, 'C');
$pdf->Ln(4);

// Summary
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(0, 5, 'Total Siswa: ' . $total_siswa . ' | Total Pelanggaran: ' . $total_pelanggaran . ' | Total Poin: ' . number_format($total_poin), 0, 1, 'L');
$pdf->Ln(3);

// TABEL
$pdf->SetFont('helvetica', 'B', 8);
$pdf->SetFillColor(220, 220, 230);

$headers = ['No', 'NISN', 'Nama Siswa', 'JK', 'Total Pelanggaran', 'Total Poin', 'Level'];
$widths = [10, 25, 45, 12, 25, 25, 25];

foreach ($headers as $i => $header) {
    $pdf->Cell($widths[$i], 7, $header, 1, 0, 'C', true);
}
$pdf->Ln();

$pdf->SetFont('helvetica', '', 7.5);
$no = 1;
foreach ($siswa_list as $row) {
    $poin = (int)($row['total_poin'] ?? 0);
    $level = getPoinLevel($poin);
    $total_pel = (int)($row['total_pelanggaran'] ?? 0);
    
    $pdf->Cell($widths[0], 6, $no++, 1, 0, 'C');
    $pdf->Cell($widths[1], 6, $row['nisn'] ?? '-', 1, 0, 'C');
    $pdf->Cell($widths[2], 6, substr($row['nama_siswa'], 0, 25), 1, 0, 'L');
    $pdf->Cell($widths[3], 6, $row['jenis_kelamin'] ?? '-', 1, 0, 'C');
    $pdf->Cell($widths[4], 6, $total_pel, 1, 0, 'C');
    $pdf->Cell($widths[5], 6, number_format($poin), 1, 0, 'C');
    $pdf->Cell($widths[6], 6, $level['level'], 1, 0, 'C');
    $pdf->Ln();
}

// FOOTER
$pdf->Ln(4);
$pdf->SetFont('helvetica', 'I', 8);
$pdf->Cell(0, 5, 'Dicetak pada: ' . date('d-m-Y H:i:s'), 0, 1, 'L');

$pdf->Output('Rekap_Siswa_' . $nama_kelas . '_' . date('Ymd') . '.pdf', 'I');
exit;
?>