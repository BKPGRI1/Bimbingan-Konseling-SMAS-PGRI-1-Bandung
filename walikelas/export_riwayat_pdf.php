<?php
// walikelas/export_riwayat_pdf.php
include '../includes/config.php';
checkLogin();

// Cek role
if (!hasRole('walikelas')) {
    exit('Akses ditolak!');
}

// Get parameters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Get data wali kelas
$user = getCurrentUser();
$kelas_wali = getRow("SELECT * FROM kelas WHERE wali_kelas_id = " . $user['id']);
$kelas_id = $kelas_wali ? $kelas_wali['id'] : 0;
$nama_kelas = $kelas_wali ? $kelas_wali['nama_kelas'] : 'Belum ditentukan';

if ($kelas_id == 0) {
    exit('Anda belum ditugaskan sebagai wali kelas!');
}

// Get data
$where = "s.kelas_id = $kelas_id";
$where .= " AND p.tanggal_pelanggaran BETWEEN '$start_date' AND '$end_date'";
if (!empty($status_filter)) {
    $where .= " AND p.status = '$status_filter'";
}

$query = "SELECT p.*, s.nama_siswa, s.nisn, jp.nama_pelanggaran, jp.poin, u.nama_lengkap as guru
          FROM pelanggaran p
          LEFT JOIN siswa s ON p.siswa_id = s.id
          LEFT JOIN jenis_pelanggaran jp ON p.jenis_pelanggaran_id = jp.id
          LEFT JOIN users u ON p.guru_id = u.id
          WHERE $where
          ORDER BY p.tanggal_pelanggaran DESC, p.created_at DESC";
$data = getRows($query);

// Filter by search
if (!empty($search)) {
    $search = strtolower($search);
    $data = array_filter($data, function($item) use ($search) {
        return strpos(strtolower($item['nama_siswa']), $search) !== false ||
               strpos(strtolower($item['nisn'] ?? ''), $search) !== false ||
               strpos(strtolower($item['nama_pelanggaran']), $search) !== false;
    });
}

// Require TCPDF
require_once('../tcpdf/tcpdf.php');

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
$pdf->SetTitle('Riwayat Pelanggaran - ' . $nama_kelas);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(15, 10, 15);
$pdf->AddPage();

// ============================================
// KOP SURAT - RAPI
// ============================================

// Logo di kiri
$logo_path = APP_PATH . 'uploads/logo/' . $logo;
if (!empty($logo) && file_exists($logo_path)) {
    $pdf->Image($logo_path, 15, 10, 22, 22);
}

// Posisi Y untuk teks
$y_start = 10;

// Nama Sekolah - Tengah
$pdf->SetY($y_start);
$pdf->SetFont('helvetica', 'B', 15);
$pdf->Cell(0, 7, strtoupper($nama_sekolah), 0, 1, 'C');

// Alamat - Tengah
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(0, 5, $alamat, 0, 1, 'C');

// Kontak - Tengah
$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(0, 4, 'Telp: ' . $no_telp . ' | Email: ' . $email, 0, 1, 'C');

// Posisi Y setelah teks
$y_after_text = $pdf->GetY();

// GARIS KOP - Double Line (langsung di bawah teks)
$pdf->SetDrawColor(0, 0, 0);
$pdf->SetLineWidth(0.8);
$pdf->Line(15, $y_after_text + 3, 280, $y_after_text + 3);
$pdf->SetLineWidth(0.3);
$pdf->Line(15, $y_after_text + 5, 280, $y_after_text + 5);

// Posisi Y setelah garis
$y_after_line = $y_after_text + 9;
$pdf->SetY($y_after_line);

// ============================================
// JUDUL LAPORAN
// ============================================

$pdf->SetFont('helvetica', 'B', 13);
$pdf->Cell(0, 7, 'LAPORAN RIWAYAT PELANGGARAN SISWA', 0, 1, 'C');

$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 5, 'Kelas: ' . $nama_kelas . ' | Wali Kelas: ' . $user['nama_lengkap'], 0, 1, 'C');
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(0, 5, 'Periode: ' . formatTanggalIndo($start_date) . ' s/d ' . formatTanggalIndo($end_date), 0, 1, 'C');

$pdf->Ln(4);

// ============================================
// TABEL
// ============================================

$pdf->SetFont('helvetica', 'B', 8);
$pdf->SetFillColor(220, 220, 230);

$headers = ['No', 'Tanggal', 'NISN', 'Nama Siswa', 'Pelanggaran', 'Poin', 'Guru', 'Status'];
$widths = [10, 24, 24, 35, 42, 14, 30, 22];

foreach ($headers as $i => $header) {
    $pdf->Cell($widths[$i], 7, $header, 1, 0, 'C', true);
}
$pdf->Ln();

$pdf->SetFont('helvetica', '', 7.5);
$no = 1;
foreach ($data as $row) {
    // Warna status
    $status_color = '';
    if ($row['status'] == 'pending') $status_color = '255,255,200';
    elseif ($row['status'] == 'disetujui') $status_color = '200,255,200';
    elseif ($row['status'] == 'ditolak') $status_color = '255,200,200';
    
    $pdf->Cell($widths[0], 6, $no++, 1, 0, 'C');
    $pdf->Cell($widths[1], 6, formatTanggal($row['tanggal_pelanggaran']), 1, 0, 'C');
    $pdf->Cell($widths[2], 6, $row['nisn'] ?? '-', 1, 0, 'C');
    $pdf->Cell($widths[3], 6, substr($row['nama_siswa'], 0, 20), 1, 0, 'L');
    $pdf->Cell($widths[4], 6, substr($row['nama_pelanggaran'], 0, 25), 1, 0, 'L');
    $pdf->Cell($widths[5], 6, $row['poin'], 1, 0, 'C');
    $pdf->Cell($widths[6], 6, substr($row['guru'] ?? '-', 0, 15), 1, 0, 'L');
    $pdf->SetFillColor(255, 255, 255);
    if (!empty($status_color)) {
        list($r, $g, $b) = explode(',', $status_color);
        $pdf->SetFillColor($r, $g, $b);
    }
    $pdf->Cell($widths[7], 6, getStatusLabel($row['status']), 1, 0, 'C', true);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->Ln();
}

// ============================================
// FOOTER LAPORAN
// ============================================

$pdf->Ln(4);
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(0, 6, 'Total Data: ' . count($data) . ' pelanggaran', 0, 1, 'R');

// Tanda tangan
$pdf->Ln(8);
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(0, 5, 'Mengetahui,', 0, 1, 'L');
$pdf->Ln(12);
$pdf->Cell(0, 5, 'Wali Kelas', 0, 1, 'L');
$pdf->Ln(5);
$pdf->SetFont('helvetica', 'BU', 9);
$pdf->Cell(0, 5, $user['nama_lengkap'], 0, 1, 'L');
$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(0, 4, 'NIP. ' . ($user['nip'] ?? '-'), 0, 1, 'L');

// Informasi cetak
$pdf->SetFont('helvetica', 'I', 7);
$pdf->Ln(5);
$pdf->Cell(0, 4, 'Dicetak pada: ' . date('d-m-Y H:i:s'), 0, 1, 'R');

$pdf->Output('Riwayat_Pelanggaran_' . $nama_kelas . '_' . date('Ymd') . '.pdf', 'I');
exit;
?>