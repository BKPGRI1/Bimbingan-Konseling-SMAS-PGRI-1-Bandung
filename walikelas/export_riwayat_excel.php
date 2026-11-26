<?php
// walikelas/export_riwayat_excel.php
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

$pengaturan = getPengaturan();
$nama_sekolah = $pengaturan['nama_sekolah'] ?? 'SMA Negeri 1 Tatib';
$alamat = $pengaturan['alamat'] ?? '';
$no_telp = $pengaturan['no_telp'] ?? '';
$email = $pengaturan['email'] ?? '';

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Riwayat_Pelanggaran_' . $nama_kelas . '_' . date('Ymd') . '.xls"');
header('Cache-Control: max-age=0');

echo '<html><head><meta charset="UTF-8"><title>Riwayat Pelanggaran</title>';
echo '<style>';
echo 'body { font-family: Arial, sans-serif; }';
echo '.header { text-align: center; margin-bottom: 20px; }';
echo '.header h1 { font-size: 20px; font-weight: bold; margin: 0; }';
echo '.header p { font-size: 12px; margin: 2px 0; color: #666; }';
echo 'table { width: 100%; border-collapse: collapse; margin-top: 15px; }';
echo 'th { background-color: #4F46E5; color: white; font-weight: bold; padding: 8px; border: 1px solid #ddd; text-align: center; }';
echo 'td { padding: 6px; border: 1px solid #ddd; }';
echo '.total { font-weight: bold; text-align: right; margin-top: 10px; }';
echo '</style></head><body>';

// KOP SURAT
echo '<div class="header">';
echo '<h1>' . strtoupper($nama_sekolah) . '</h1>';
echo '<p>' . $alamat . ' | Telp: ' . $no_telp . ' | Email: ' . $email . '</p>';
echo '<hr style="border: 2px solid #000; margin: 10px 0;">';
echo '</div>';

// JUDUL
echo '<h2 style="text-align:center;">RIWAYAT PELANGGARAN</h2>';
echo '<p style="text-align:center;">Kelas: ' . $nama_kelas . ' | Wali Kelas: ' . $user['nama_lengkap'] . '</p>';
echo '<p style="text-align:center;color:#666;">Periode: ' . formatTanggalIndo($start_date) . ' s/d ' . formatTanggalIndo($end_date) . '</p>';

// TABEL
echo '<table>';
echo '<thead><tr>';
echo '<th>No</th><th>Tanggal</th><th>NISN</th><th>Nama Siswa</th><th>Pelanggaran</th><th>Poin</th><th>Guru</th><th>Status</th>';
echo '</tr></thead><tbody>';

$no = 1;
foreach ($data as $row) {
    echo '<tr>';
    echo '<td style="text-align:center;">' . $no++ . '</td>';
    echo '<td style="text-align:center;">' . formatTanggal($row['tanggal_pelanggaran']) . '</td>';
    echo '<td style="text-align:center;">' . ($row['nisn'] ?? '-') . '</td>';
    echo '<td>' . $row['nama_siswa'] . '</td>';
    echo '<td>' . $row['nama_pelanggaran'] . '</td>';
    echo '<td style="text-align:center;">' . $row['poin'] . '</td>';
    echo '<td>' . ($row['guru'] ?? '-') . '</td>';
    echo '<td style="text-align:center;">' . getStatusLabel($row['status']) . '</td>';
    echo '</tr>';
}

echo '</tbody></table>';
echo '<p class="total">Total Data: ' . count($data) . '</p>';
echo '<p style="font-size:10px;color:#999;">Dicetak pada: ' . date('d-m-Y H:i:s') . '</p>';
echo '</body></html>';
exit;
?>