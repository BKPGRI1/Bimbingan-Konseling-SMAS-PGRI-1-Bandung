<?php
// walikelas/export_rekap_excel.php
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

$pengaturan = getPengaturan();
$nama_sekolah = $pengaturan['nama_sekolah'] ?? 'SMA Negeri 1 Tatib';
$alamat = $pengaturan['alamat'] ?? '';
$no_telp = $pengaturan['no_telp'] ?? '';
$email = $pengaturan['email'] ?? '';

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Rekap_Siswa_' . $nama_kelas . '_' . date('Ymd') . '.xls"');
header('Cache-Control: max-age=0');

echo '<html><head><meta charset="UTF-8"><title>Rekap Data Siswa</title>';
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
echo '<h2 style="text-align:center;">REKAP DATA SISWA</h2>';
echo '<p style="text-align:center;">Kelas: ' . $nama_kelas . ' | Wali Kelas: ' . $user['nama_lengkap'] . '</p>';
echo '<p style="text-align:center;color:#666;">Total Siswa: ' . $total_siswa . ' | Total Pelanggaran: ' . $total_pelanggaran . ' | Total Poin: ' . number_format($total_poin) . '</p>';

// TABEL
echo '<table>';
echo '<thead><tr>';
echo '<th>No</th><th>NISN</th><th>Nama Siswa</th><th>JK</th><th>Total Pelanggaran</th><th>Total Poin</th><th>Level</th>';
echo '</tr></thead><tbody>';

$no = 1;
foreach ($siswa_list as $row) {
    $poin = (int)($row['total_poin'] ?? 0);
    $level = getPoinLevel($poin);
    $total_pel = (int)($row['total_pelanggaran'] ?? 0);
    
    echo '<tr>';
    echo '<td style="text-align:center;">' . $no++ . '</td>';
    echo '<td style="text-align:center;">' . ($row['nisn'] ?? '-') . '</td>';
    echo '<td>' . $row['nama_siswa'] . '</td>';
    echo '<td style="text-align:center;">' . ($row['jenis_kelamin'] ?? '-') . '</td>';
    echo '<td style="text-align:center;">' . $total_pel . '</td>';
    echo '<td style="text-align:center;">' . number_format($poin) . '</td>';
    echo '<td style="text-align:center;">' . $level['level'] . '</td>';
    echo '</tr>';
}

echo '</tbody></table>';
echo '<p class="total">Total Siswa: ' . $total_siswa . ' | Total Poin: ' . number_format($total_poin) . '</p>';
echo '<p style="font-size:10px;color:#999;">Dicetak pada: ' . date('d-m-Y H:i:s') . '</p>';
echo '</body></html>';
exit;
?>