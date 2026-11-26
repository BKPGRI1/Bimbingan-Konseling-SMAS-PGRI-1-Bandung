<?php
// bk/export_laporan.php
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
$bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : date('m');
$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : date('Y');
$jenis = isset($_GET['jenis']) ? $_GET['jenis'] : 'pelanggaran';

// Validasi action
if (empty($action)) {
    $action = 'pdf';
}

if (!in_array($action, ['excel', 'pdf'])) {
    die("Aksi tidak valid! Gunakan action=excel atau action=pdf");
}

// Nama bulan
$nama_bulan = getBulanIndonesia($bulan);

// ============================================
// AMBIL PENGATURAN
// ============================================
$pengaturan = getPengaturan();
$nama_sekolah = $pengaturan['nama_sekolah'] ?? 'SMA Negeri 1 Tatib';
$alamat = $pengaturan['alamat'] ?? '';
$no_telp = $pengaturan['no_telp'] ?? '';
$email = $pengaturan['email'] ?? '';
$logo = $pengaturan['logo'] ?? '';

// ============================================
// AMBIL DATA
// ============================================
$conn = getDB();

// Data pelanggaran bulan ini
$pelanggaran_bulan = getPelanggaranBulan($bulan);
$total_pelanggaran = getTotalPelanggaran();
$total_siswa = getTotalSiswa();
$siswa_bermasalah = countSiswaBermasalah();
$kategori_data = getKategoriPelanggaran();
$status_konseling = getStatusKonseling();

// Detail pelanggaran bulan ini dengan prestasi per siswa
$query_detail = "SELECT p.*, s.nama_siswa, s.nisn, k.nama_kelas, jp.nama_pelanggaran, jp.kategori, jp.poin,
                  COALESCE((SELECT SUM(poin_prestasi) FROM prestasi_siswa WHERE siswa_id = p.siswa_id), 0) as total_prestasi_siswa,
                  COALESCE((SELECT SUM(poin_diperoleh) FROM pelanggaran WHERE siswa_id = p.siswa_id AND status = 'disetujui'), 0) as total_poin_pelanggaran_siswa
                FROM pelanggaran p
                LEFT JOIN siswa s ON p.siswa_id = s.id
                LEFT JOIN kelas k ON s.kelas_id = k.id
                LEFT JOIN jenis_pelanggaran jp ON p.jenis_pelanggaran_id = jp.id
                WHERE MONTH(p.tanggal_pelanggaran) = $bulan AND YEAR(p.tanggal_pelanggaran) = $tahun
                AND p.status = 'disetujui'
                ORDER BY p.tanggal_pelanggaran DESC";
$detail_data = getRows($query_detail);

// Akumulasi per siswa (UNIQUE)
$siswa_ids = array_unique(array_column($detail_data, 'siswa_id'));
$siswa_akumulasi = [];
foreach ($siswa_ids as $siswa_id) {
    $siswa = getSiswaById($siswa_id);
    if (!$siswa) continue;
    
    $q_pel = $conn->query("SELECT COUNT(*) as total, SUM(poin_diperoleh) as total_poin 
                           FROM pelanggaran 
                           WHERE siswa_id = $siswa_id AND status = 'disetujui'
                           AND MONTH(tanggal_pelanggaran) = $bulan AND YEAR(tanggal_pelanggaran) = $tahun");
    $pel = $q_pel->fetch_assoc();
    
    $q_pres = $conn->query("SELECT SUM(poin_prestasi) as total_poin FROM prestasi_siswa WHERE siswa_id = $siswa_id");
    $pres = $q_pres->fetch_assoc();
    
    $poin_pel = (int)$pel['total_poin'];
    $total_pel = (int)$pel['total'];
    $poin_pres = (int)$pres['total_poin'];
    $poin_akhir = $poin_pel - $poin_pres;
    if ($poin_akhir < 0) $poin_akhir = 0;
    
    $siswa_akumulasi[] = [
        'nisn' => $siswa['nisn'],
        'nama' => $siswa['nama_siswa'],
        'kelas' => $siswa['nama_kelas'] ?? '-',
        'total_pelanggaran' => $total_pel,
        'poin_pelanggaran' => $poin_pel,
        'poin_prestasi' => $poin_pres,
        'poin_akhir' => $poin_akhir
    ];
}
usort($siswa_akumulasi, function($a, $b) {
    return $b['poin_akhir'] - $a['poin_akhir'];
});

$total_poin_pelanggaran = array_sum(array_column($siswa_akumulasi, 'poin_pelanggaran'));
$total_poin_prestasi_siswa = array_sum(array_column($siswa_akumulasi, 'poin_prestasi'));
$total_poin_akhir = array_sum(array_column($siswa_akumulasi, 'poin_akhir'));

// Data siswa bermasalah (dengan poin akhir yang benar)
$batas = getBatasPoin();
$query_siswa = "SELECT s.*, k.nama_kelas FROM siswa s LEFT JOIN kelas k ON s.kelas_id = k.id";
$siswa_all = getRows($query_siswa);
$siswa_bermasalah_data = [];
foreach ($siswa_all as $row) {
    $q_pel = $conn->query("SELECT SUM(poin_diperoleh) as total FROM pelanggaran WHERE siswa_id = {$row['id']} AND status = 'disetujui'");
    $pel = $q_pel->fetch_assoc();
    $poin_pel = (int)$pel['total'];
    
    $q_pres = $conn->query("SELECT SUM(poin_prestasi) as total FROM prestasi_siswa WHERE siswa_id = {$row['id']}");
    $pres = $q_pres->fetch_assoc();
    $poin_pres = (int)$pres['total'];
    
    $poin_akhir = $poin_pel - $poin_pres;
    if ($poin_akhir < 0) $poin_akhir = 0;
    
    if ($poin_akhir >= $batas['peringatan']) {
        $row['poin_akhir'] = $poin_akhir;
        $row['poin_pelanggaran'] = $poin_pel;
        $row['poin_prestasi'] = $poin_pres;
        $siswa_bermasalah_data[] = $row;
    }
}
usort($siswa_bermasalah_data, function($a, $b) {
    return $b['poin_akhir'] - $a['poin_akhir'];
});

// Data prestasi
$query_prestasi = "SELECT p.*, s.nama_siswa, s.nisn, k.nama_kelas
                   FROM prestasi_siswa p
                   LEFT JOIN siswa s ON p.siswa_id = s.id
                   LEFT JOIN kelas k ON s.kelas_id = k.id
                   ORDER BY p.tahun DESC, p.created_at DESC";
$prestasi_data = getRows($query_prestasi);

$total_prestasi = count($prestasi_data);
$total_poin_prestasi = array_sum(array_column($prestasi_data, 'poin_prestasi'));

// Data konseling
$konseling_data = getCatatanKonseling(null, 100);

// ============================================
// EXPORT EXCEL
// ============================================
if ($action == 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="Laporan_BK_' . date('Ymd') . '.xls"');
    
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta charset="UTF-8"><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Laporan BK</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--></head>';
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
    
    echo '<h3 style="text-align:center;">LAPORAN BK</h3>';
    
    if ($jenis == 'prestasi' || $jenis == 'semua_prestasi') {
        echo '<p style="text-align:center;">Laporan Prestasi Siswa</p>';
    } else {
        echo '<p style="text-align:center;">Periode: ' . $nama_bulan . ' ' . $tahun . '</p>';
    }
    echo '<hr>';
    
    // ============================================
    // STATISTIK
    // ============================================
    echo '<h4>STATISTIK</h4>';
    echo '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse:collapse;width:100%;font-size:11px;">';
    echo '<tr style="background-color:#f0f0f0;">';
    echo '<th>Total Siswa</th>';
    echo '<th>Total Pelanggaran</th>';
    echo '<th>Pelanggaran Bulan Ini</th>';
    echo '<th>Siswa Bermasalah</th>';
    echo '<th>Konseling Pending</th>';
    echo '<th>Konseling Selesai</th>';
    echo '<th>Total Prestasi</th>';
    echo '<th>Total Poin Prestasi</th>';
    echo '</tr>';
    echo '<tr>';
    echo '<td align="center">' . $total_siswa . '</td>';
    echo '<td align="center">' . $total_pelanggaran . '</td>';
    echo '<td align="center">' . $pelanggaran_bulan . '</td>';
    echo '<td align="center">' . count($siswa_bermasalah_data) . '</td>';
    echo '<td align="center">' . ($status_konseling['pending'] ?? 0) . '</td>';
    echo '<td align="center">' . ($status_konseling['selesai'] ?? 0) . '</td>';
    echo '<td align="center">' . $total_prestasi . '</td>';
    echo '<td align="center">' . $total_poin_prestasi . '</td>';
    echo '</tr>';
    echo '</table>';
    echo '<br>';
    
    // ============================================
    // LAPORAN PRESTASI (RIWAYAT PRESTASI)
    // ============================================
    echo '<h4>RIWAYAT PRESTASI SISWA</h4>';
    echo '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse:collapse;width:100%;font-size:10px;">';
    echo '<thead>';
    echo '<tr style="background-color:#f0f0f0;">';
    echo '<th>No</th>';
    echo '<th>No. Prestasi</th>';
    echo '<th>NISN</th>';
    echo '<th>Nama Siswa</th>';
    echo '<th>Kelas</th>';
    echo '<th>Jenis</th>';
    echo '<th>Prestasi</th>';
    echo '<th>Juara</th>';
    echo '<th>Tingkat</th>';
    echo '<th>Tahun</th>';
    echo '<th>Poin</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    $no = 1;
    foreach ($prestasi_data as $row) {
        echo '<tr>';
        echo '<td align="center">' . $no++ . '</td>';
        echo '<td>' . ($row['no_prestasi'] ?? 'PRS') . '</td>';
        echo '<td>' . $row['nisn'] . '</td>';
        echo '<td>' . $row['nama_siswa'] . '</td>';
        echo '<td>' . ($row['nama_kelas'] ?? '-') . '</td>';
        echo '<td>' . ucfirst(str_replace('_', ' ', $row['jenis_prestasi'])) . '</td>';
        echo '<td>' . $row['nama_prestasi'] . '</td>';
        echo '<td>' . ($row['juara'] ?? '-') . '</td>';
        echo '<td>' . ucfirst($row['tingkat']) . '</td>';
        echo '<td>' . $row['tahun'] . '</td>';
        echo '<td align="center">+' . $row['poin_prestasi'] . '</td>';
        echo '</tr>';
    }
    
    if (count($prestasi_data) == 0) {
        echo '<tr><td colspan="11" align="center">Belum ada data prestasi</td></tr>';
    } else {
        echo '<tr style="background-color:#e3f2fd;font-weight:bold;">';
        echo '<td colspan="10" align="right">TOTAL</td>';
        echo '<td align="center">+' . $total_poin_prestasi . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    echo '<br>';
    
    // ============================================
    // LAPORAN PELANGGARAN (jika bukan laporan prestasi)
    // ============================================
    if ($jenis != 'prestasi' && $jenis != 'semua_prestasi') {
        // RINGKASAN POIN
        echo '<h4>RINGKASAN POIN</h4>';
        echo '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse:collapse;width:100%;font-size:11px;">';
        echo '<tr style="background-color:#f0f0f0;">';
        echo '<th>Total Pelanggaran</th>';
        echo '<th>Total Prestasi</th>';
        echo '<th>Poin Akhir</th>';
        echo '</tr>';
        echo '<tr>';
        echo '<td align="center" style="color:red;font-weight:bold;">' . $total_poin_pelanggaran . '</td>';
        echo '<td align="center" style="color:green;font-weight:bold;">' . $total_poin_prestasi_siswa . '</td>';
        echo '<td align="center" style="color:blue;font-weight:bold;">' . $total_poin_akhir . '</td>';
        echo '</tr>';
        echo '</table>';
        echo '<br>';
        
        // KATEGORI PELANGGARAN
        echo '<h4>KATEGORI PELANGGARAN</h4>';
        echo '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse:collapse;width:50%;font-size:11px;">';
        echo '<tr style="background-color:#f0f0f0;">';
        echo '<th>Kategori</th>';
        echo '<th>Jumlah</th>';
        echo '</tr>';
        echo '<tr><td>Ringan</td><td align="center">' . ($kategori_data['ringan'] ?? 0) . '</td></tr>';
        echo '<tr><td>Sedang</td><td align="center">' . ($kategori_data['sedang'] ?? 0) . '</td></tr>';
        echo '<tr><td>Berat</td><td align="center">' . ($kategori_data['berat'] ?? 0) . '</td></tr>';
        echo '<tr style="background-color:#f8f9fa;font-weight:bold;"><td>TOTAL</td><td align="center">' . array_sum($kategori_data) . '</td></tr>';
        echo '</table>';
        echo '<br>';
        
        // DETAIL PELANGGARAN
        echo '<h4>DETAIL PELANGGARAN BULAN ' . strtoupper($nama_bulan) . ' ' . $tahun . '</h4>';
        echo '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse:collapse;width:100%;font-size:10px;">';
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
        echo '<th>Prestasi</th>';
        echo '<th>Sisa Poin</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        $no = 1;
        foreach ($detail_data as $row) {
            $prestasi_siswa = (int)$row['total_prestasi_siswa'];
            $sisa_poin = $row['poin'] - $prestasi_siswa;
            if ($sisa_poin < 0) $sisa_poin = 0;
            echo '<tr>';
            echo '<td align="center">' . $no++ . '</td>';
            echo '<td>' . date('d-m-Y', strtotime($row['tanggal_pelanggaran'])) . '</td>';
            echo '<td>' . $row['nisn'] . '</td>';
            echo '<td>' . $row['nama_siswa'] . '</td>';
            echo '<td>' . ($row['nama_kelas'] ?? '-') . '</td>';
            echo '<td>' . $row['nama_pelanggaran'] . '</td>';
            echo '<td>' . ucfirst($row['kategori']) . '</td>';
            echo '<td align="center">' . $row['poin'] . '</td>';
            echo '<td align="center">' . ($prestasi_siswa > 0 ? $prestasi_siswa : 0) . '</td>';
            echo '<td align="center" style="font-weight:bold;color:' . ($sisa_poin > 50 ? 'red' : ($sisa_poin > 20 ? 'orange' : 'green')) . ';">' . $sisa_poin . '</td>';
            echo '</tr>';
        }
        
        if (count($detail_data) == 0) {
            echo '<tr><td colspan="10" align="center">Tidak ada data pelanggaran</td></tr>';
        } else {
            echo '<tr style="background-color:#e3f2fd;font-weight:bold;">';
            echo '<td colspan="7" align="right">TOTAL</td>';
            echo '<td align="center">' . $total_poin_pelanggaran . '</td>';
            echo '<td align="center">' . $total_poin_prestasi_siswa . '</td>';
            echo '<td align="center">' . $total_poin_akhir . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        echo '<br>';
        
        // AKUMULASI POIN PER SISWA
        echo '<h4>AKUMULASI POIN SISWA (Pelanggaran - Prestasi)</h4>';
        echo '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse:collapse;width:100%;font-size:10px;">';
        echo '<thead>';
        echo '<tr style="background-color:#f0f0f0;">';
        echo '<th>No</th>';
        echo '<th>NISN</th>';
        echo '<th>Nama Siswa</th>';
        echo '<th>Kelas</th>';
        echo '<th>Pelanggaran</th>';
        echo '<th>Prestasi</th>';
        echo '<th>Sisa Poin</th>';
        echo '<th>Status</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        $no = 1;
        foreach ($siswa_akumulasi as $row) {
            $level = getPoinLevel($row['poin_akhir']);
            echo '<tr>';
            echo '<td align="center">' . $no++ . '</td>';
            echo '<td>' . $row['nisn'] . '</td>';
            echo '<td>' . $row['nama'] . '</td>';
            echo '<td>' . $row['kelas'] . '</td>';
            echo '<td align="center">' . $row['total_pelanggaran'] . ' (' . $row['poin_pelanggaran'] . ')</td>';
            echo '<td align="center">' . $row['poin_prestasi'] . '</td>';
            echo '<td align="center" style="font-weight:bold;color:' . ($row['poin_akhir'] >= 100 ? 'red' : ($row['poin_akhir'] >= 50 ? 'orange' : 'green')) . ';">' . $row['poin_akhir'] . '</td>';
            echo '<td align="center">' . $level['level'] . '</td>';
            echo '</tr>';
        }
        
        if (count($siswa_akumulasi) == 0) {
            echo '<tr><td colspan="8" align="center">Tidak ada data</td></tr>';
        } else {
            echo '<tr style="background-color:#e3f2fd;font-weight:bold;">';
            echo '<td colspan="4" align="right">TOTAL</td>';
            echo '<td align="center">' . $total_poin_pelanggaran . '</td>';
            echo '<td align="center">' . $total_poin_prestasi_siswa . '</td>';
            echo '<td align="center">' . $total_poin_akhir . '</td>';
            echo '<td></td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        echo '<br>';
        
        // SISWA BERMASALAH
        echo '<h4>SISWA BERMASALAH</h4>';
        echo '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse:collapse;width:100%;font-size:11px;">';
        echo '<thead>';
        echo '<tr style="background-color:#f0f0f0;">';
        echo '<th>No</th>';
        echo '<th>NISN</th>';
        echo '<th>Nama Siswa</th>';
        echo '<th>Kelas</th>';
        echo '<th>Pelanggaran</th>';
        echo '<th>Prestasi</th>';
        echo '<th>Sisa Poin</th>';
        echo '<th>Status</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        $no = 1;
        foreach ($siswa_bermasalah_data as $row) {
            $level = getPoinLevel($row['poin_akhir']);
            echo '<tr>';
            echo '<td align="center">' . $no++ . '</td>';
            echo '<td>' . $row['nisn'] . '</td>';
            echo '<td>' . $row['nama_siswa'] . '</td>';
            echo '<td>' . ($row['nama_kelas'] ?? '-') . '</td>';
            echo '<td align="center">' . $row['poin_pelanggaran'] . '</td>';
            echo '<td align="center">' . $row['poin_prestasi'] . '</td>';
            echo '<td align="center" style="font-weight:bold;color:' . ($row['poin_akhir'] >= 100 ? 'red' : 'orange') . ';">' . $row['poin_akhir'] . '</td>';
            echo '<td align="center">' . $level['level'] . '</td>';
            echo '</tr>';
        }
        
        if (count($siswa_bermasalah_data) == 0) {
            echo '<tr><td colspan="8" align="center">Tidak ada siswa bermasalah</td></tr>';
        } else {
            echo '<tr style="background-color:#e3f2fd;font-weight:bold;">';
            echo '<td colspan="4" align="right">TOTAL</td>';
            echo '<td align="center">' . array_sum(array_column($siswa_bermasalah_data, 'poin_pelanggaran')) . '</td>';
            echo '<td align="center">' . array_sum(array_column($siswa_bermasalah_data, 'poin_prestasi')) . '</td>';
            echo '<td align="center">' . array_sum(array_column($siswa_bermasalah_data, 'poin_akhir')) . '</td>';
            echo '<td></td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        echo '<br>';
    }
    
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
    $pdf->SetTitle('Laporan BK');
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(12, 10, 12);
    $pdf->AddPage();
    
    $total_width = 273;
    
    // ============================================
    // KOP SURAT
    // ============================================
    
    if (!empty($logo) && file_exists(APP_PATH . 'uploads/logo/' . $logo)) {
        $pdf->Image(APP_PATH . 'uploads/logo/' . $logo, 12, 8, 20, 20);
    }
    
    $pdf->SetY(8);
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 6, strtoupper($nama_sekolah), 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 9);
    $pdf->Cell(0, 4, $alamat, 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 8);
    $info = '';
    if (!empty($no_telp)) $info .= 'Telp: ' . $no_telp;
    if (!empty($email)) $info .= ' | Email: ' . $email;
    $pdf->Cell(0, 3, $info, 0, 1, 'C');
    
    $y_after = $pdf->GetY();
    $pdf->SetDrawColor(0, 0, 0);
    $pdf->SetLineWidth(0.8);
    $pdf->Line(12, $y_after + 2, 285, $y_after + 2);
    $pdf->SetLineWidth(0.3);
    $pdf->Line(12, $y_after + 4, 285, $y_after + 4);
    $pdf->SetY($y_after + 7);
    
    $pdf->SetFont('helvetica', 'B', 13);
    $pdf->Cell(0, 6, 'LAPORAN BK', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 9);
    
    if ($jenis == 'prestasi' || $jenis == 'semua_prestasi') {
        $pdf->Cell(0, 4, 'Laporan Prestasi Siswa', 0, 1, 'C');
    } else {
        $pdf->Cell(0, 4, 'Periode: ' . $nama_bulan . ' ' . $tahun, 0, 1, 'C');
    }
    $pdf->Ln(3);
    
    // ============================================
    // STATISTIK
    // ============================================
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 5, 'STATISTIK', 0, 1, 'L');
    $pdf->Ln(1);
    
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetFillColor(240, 240, 245);
    
    $stats = [
        ['Total Siswa', $total_siswa],
        ['Total Pelanggaran', $total_pelanggaran],
        ['Pelanggaran Bulan Ini', $pelanggaran_bulan],
        ['Siswa Bermasalah', count($siswa_bermasalah_data)],
        ['Konseling Pending', $status_konseling['pending'] ?? 0],
        ['Konseling Selesai', $status_konseling['selesai'] ?? 0],
        ['Total Prestasi', $total_prestasi],
        ['Total Poin Prestasi', $total_poin_prestasi]
    ];
    
    $col_width = $total_width / 4;
    foreach ($stats as $i => $stat) {
        $x = 12 + ($i % 4) * $col_width;
        $y = $pdf->GetY();
        if ($i % 4 == 0 && $i > 0) {
            $pdf->Ln(7);
        }
        $pdf->SetXY($x, $y);
        $pdf->Cell($col_width, 6, $stat[0] . ': ' . $stat[1], 1, 0, 'C', true);
    }
    $pdf->Ln(10);
    
    // ============================================
    // RIWAYAT PRESTASI
    // ============================================
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 5, 'RIWAYAT PRESTASI SISWA', 0, 1, 'L');
    $pdf->Ln(1);
    
    $pdf->SetFont('helvetica', 'B', 7);
    $pdf->SetFillColor(220, 220, 230);
    
    $col_widths = [7, 22, 22, 48, 20, 22, 56, 22, 22, 16, 16];
    $headers = ['No', 'No. Prestasi', 'NISN', 'Nama Siswa', 'Kelas', 'Jenis', 'Prestasi', 'Juara', 'Tingkat', 'Tahun', 'Poin'];
    foreach ($headers as $i => $header) {
        $pdf->Cell($col_widths[$i], 5, $header, 1, 0, 'C', true);
    }
    $pdf->Ln();
    
    $pdf->SetFont('helvetica', '', 6.5);
    $no = 1;
    foreach ($prestasi_data as $row) {
        $pdf->Cell($col_widths[0], 4, $no++, 1, 0, 'C');
        $pdf->Cell($col_widths[1], 4, $row['no_prestasi'] ?? 'PRS', 1, 0, 'C');
        $pdf->Cell($col_widths[2], 4, $row['nisn'], 1, 0, 'C');
        $pdf->Cell($col_widths[3], 4, substr($row['nama_siswa'], 0, 20), 1, 0, 'L');
        $pdf->Cell($col_widths[4], 4, $row['nama_kelas'] ?? '-', 1, 0, 'C');
        $pdf->Cell($col_widths[5], 4, ucfirst(str_replace('_', ' ', $row['jenis_prestasi'])), 1, 0, 'C');
        $pdf->Cell($col_widths[6], 4, substr($row['nama_prestasi'], 0, 25), 1, 0, 'L');
        $pdf->Cell($col_widths[7], 4, $row['juara'] ?? '-', 1, 0, 'C');
        $pdf->Cell($col_widths[8], 4, ucfirst($row['tingkat']), 1, 0, 'C');
        $pdf->Cell($col_widths[9], 4, $row['tahun'], 1, 0, 'C');
        $pdf->Cell($col_widths[10], 4, '+' . $row['poin_prestasi'], 1, 0, 'C');
        $pdf->Ln();
    }
    
    if (count($prestasi_data) == 0) {
        $total_col = array_sum($col_widths);
        $pdf->Cell($total_col, 4, 'Belum ada data prestasi', 1, 0, 'C');
        $pdf->Ln();
    } else {
        $pdf->SetFillColor(227, 242, 253);
        $pdf->SetFont('helvetica', 'B', 7);
        $total_cols = array_slice($col_widths, 0, 10);
        $pdf->Cell(array_sum($total_cols), 5, 'TOTAL', 1, 0, 'R', true);
        $pdf->Cell($col_widths[10], 5, '+' . $total_poin_prestasi, 1, 0, 'C', true);
        $pdf->Ln();
    }
    $pdf->Ln(6);
    
    // ============================================
    // LAPORAN PELANGGARAN (jika bukan laporan prestasi)
    // ============================================
    if ($jenis != 'prestasi' && $jenis != 'semua_prestasi') {
        // RINGKASAN POIN
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 5, 'RINGKASAN POIN', 0, 1, 'L');
        $pdf->Ln(1);
        
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetFillColor(240, 240, 245);
        
        $pdf->Cell(80, 6, 'Total Pelanggaran', 1, 0, 'C', true);
        $pdf->Cell(80, 6, 'Total Prestasi', 1, 0, 'C', true);
        $pdf->Cell(80, 6, 'Poin Akhir', 1, 0, 'C', true);
        $pdf->Ln();
        
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetTextColor(255, 0, 0);
        $pdf->Cell(80, 7, $total_poin_pelanggaran, 1, 0, 'C');
        $pdf->SetTextColor(0, 128, 0);
        $pdf->Cell(80, 7, $total_poin_prestasi_siswa, 1, 0, 'C');
        $pdf->SetTextColor(0, 0, 255);
        $pdf->Cell(80, 7, $total_poin_akhir, 1, 0, 'C');
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(8);
        
        // KATEGORI PELANGGARAN
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 5, 'KATEGORI PELANGGARAN', 0, 1, 'L');
        $pdf->Ln(1);
        
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetFillColor(240, 240, 245);
        $pdf->Cell(60, 6, 'Kategori', 1, 0, 'C', true);
        $pdf->Cell(40, 6, 'Jumlah', 1, 0, 'C', true);
        $pdf->Ln();
        
        $pdf->Cell(60, 5, 'Ringan', 1, 0, 'L');
        $pdf->Cell(40, 5, $kategori_data['ringan'] ?? 0, 1, 0, 'C');
        $pdf->Ln();
        $pdf->Cell(60, 5, 'Sedang', 1, 0, 'L');
        $pdf->Cell(40, 5, $kategori_data['sedang'] ?? 0, 1, 0, 'C');
        $pdf->Ln();
        $pdf->Cell(60, 5, 'Berat', 1, 0, 'L');
        $pdf->Cell(40, 5, $kategori_data['berat'] ?? 0, 1, 0, 'C');
        $pdf->Ln();
        $pdf->SetFillColor(240, 240, 245);
        $pdf->Cell(60, 5, 'TOTAL', 1, 0, 'C', true);
        $pdf->Cell(40, 5, array_sum($kategori_data), 1, 0, 'C', true);
        $pdf->Ln(8);
        
        // DETAIL PELANGGARAN
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 5, 'DETAIL PELANGGARAN BULAN ' . strtoupper($nama_bulan) . ' ' . $tahun, 0, 1, 'L');
        $pdf->Ln(1);
        
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->SetFillColor(220, 220, 230);
        
        $col_widths3 = [10, 22, 22, 50, 18, 45, 22, 16, 18, 22];
        $headers3 = ['No', 'Tanggal', 'NISN', 'Nama Siswa', 'Kelas', 'Pelanggaran', 'Kategori', 'Poin', 'Prestasi', 'Sisa'];
        foreach ($headers3 as $i => $header) {
            $pdf->Cell($col_widths3[$i], 5, $header, 1, 0, 'C', true);
        }
        $pdf->Ln();
        
        $pdf->SetFont('helvetica', '', 6);
        $no = 1;
        foreach ($detail_data as $row) {
            $prestasi_siswa = (int)$row['total_prestasi_siswa'];
            $sisa_poin = $row['poin'] - $prestasi_siswa;
            if ($sisa_poin < 0) $sisa_poin = 0;
            
            $pdf->Cell($col_widths3[0], 4, $no++, 1, 0, 'C');
            $pdf->Cell($col_widths3[1], 4, date('d-m-Y', strtotime($row['tanggal_pelanggaran'])), 1, 0, 'C');
            $pdf->Cell($col_widths3[2], 4, $row['nisn'], 1, 0, 'C');
            $pdf->Cell($col_widths3[3], 4, substr($row['nama_siswa'], 0, 18), 1, 0, 'L');
            $pdf->Cell($col_widths3[4], 4, $row['nama_kelas'] ?? '-', 1, 0, 'C');
            $pdf->Cell($col_widths3[5], 4, substr($row['nama_pelanggaran'], 0, 18), 1, 0, 'L');
            $pdf->Cell($col_widths3[6], 4, ucfirst($row['kategori']), 1, 0, 'C');
            $pdf->Cell($col_widths3[7], 4, $row['poin'], 1, 0, 'C');
            $pdf->Cell($col_widths3[8], 4, $prestasi_siswa > 0 ? $prestasi_siswa : 0, 1, 0, 'C');
            
            if ($sisa_poin > 50) {
                $pdf->SetTextColor(255, 0, 0);
            } elseif ($sisa_poin > 20) {
                $pdf->SetTextColor(255, 165, 0);
            } else {
                $pdf->SetTextColor(0, 128, 0);
            }
            $pdf->Cell($col_widths3[9], 4, $sisa_poin, 1, 0, 'C');
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Ln();
        }
        
        if (count($detail_data) == 0) {
            $total_col3 = array_sum($col_widths3);
            $pdf->Cell($total_col3, 4, 'Tidak ada data pelanggaran', 1, 0, 'C');
            $pdf->Ln();
        } else {
            $pdf->SetFillColor(227, 242, 253);
            $pdf->SetFont('helvetica', 'B', 7);
            $pdf->Cell(array_sum(array_slice($col_widths3, 0, 7)), 5, 'TOTAL', 1, 0, 'R', true);
            $pdf->Cell($col_widths3[7], 5, $total_poin_pelanggaran, 1, 0, 'C', true);
            $pdf->Cell($col_widths3[8], 5, $total_poin_prestasi_siswa, 1, 0, 'C', true);
            $pdf->Cell($col_widths3[9], 5, $total_poin_akhir, 1, 0, 'C', true);
            $pdf->Ln();
        }
        $pdf->Ln(6);
        
        // AKUMULASI POIN PER SISWA
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 5, 'AKUMULASI POIN SISWA (Pelanggaran - Prestasi)', 0, 1, 'L');
        $pdf->Ln(1);
        
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->SetFillColor(220, 220, 230);
        
        $col_widths5 = [8, 22, 45, 22, 25, 25, 25, 30];
        $headers5 = ['No', 'NISN', 'Nama Siswa', 'Kelas', 'Pelanggaran', 'Prestasi', 'Sisa Poin', 'Status'];
        foreach ($headers5 as $i => $header) {
            $pdf->Cell($col_widths5[$i], 5, $header, 1, 0, 'C', true);
        }
        $pdf->Ln();
        
        $pdf->SetFont('helvetica', '', 6.5);
        $no = 1;
        foreach ($siswa_akumulasi as $row) {
            $level = getPoinLevel($row['poin_akhir']);
            $pdf->Cell($col_widths5[0], 4, $no++, 1, 0, 'C');
            $pdf->Cell($col_widths5[1], 4, $row['nisn'], 1, 0, 'C');
            $pdf->Cell($col_widths5[2], 4, substr($row['nama'], 0, 18), 1, 0, 'L');
            $pdf->Cell($col_widths5[3], 4, $row['kelas'], 1, 0, 'C');
            $pdf->Cell($col_widths5[4], 4, $row['total_pelanggaran'] . ' (' . $row['poin_pelanggaran'] . ')', 1, 0, 'C');
            $pdf->Cell($col_widths5[5], 4, $row['poin_prestasi'], 1, 0, 'C');
            $pdf->Cell($col_widths5[6], 4, $row['poin_akhir'], 1, 0, 'C');
            $pdf->Cell($col_widths5[7], 4, $level['level'], 1, 0, 'C');
            $pdf->Ln();
        }
        
        if (count($siswa_akumulasi) == 0) {
            $total_col5 = array_sum($col_widths5);
            $pdf->Cell($total_col5, 4, 'Tidak ada data', 1, 0, 'C');
            $pdf->Ln();
        } else {
            $pdf->SetFillColor(227, 242, 253);
            $pdf->SetFont('helvetica', 'B', 7);
            $pdf->Cell(array_sum(array_slice($col_widths5, 0, 4)), 5, 'TOTAL', 1, 0, 'R', true);
            $pdf->Cell($col_widths5[4], 5, $total_poin_pelanggaran, 1, 0, 'C', true);
            $pdf->Cell($col_widths5[5], 5, $total_poin_prestasi_siswa, 1, 0, 'C', true);
            $pdf->Cell($col_widths5[6], 5, $total_poin_akhir, 1, 0, 'C', true);
            $pdf->Cell($col_widths5[7], 5, '', 1, 0, 'C', true);
            $pdf->Ln();
        }
        $pdf->Ln(6);
        
        // SISWA BERMASALAH
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 5, 'SISWA BERMASALAH', 0, 1, 'L');
        $pdf->Ln(1);
        
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->SetFillColor(220, 220, 230);
        
        $col_widths4 = [8, 22, 40, 20, 22, 22, 22, 28];
        $headers4 = ['No', 'NISN', 'Nama Siswa', 'Kelas', 'Pelanggaran', 'Prestasi', 'Sisa Poin', 'Status'];
        foreach ($headers4 as $i => $header) {
            $pdf->Cell($col_widths4[$i], 5, $header, 1, 0, 'C', true);
        }
        $pdf->Ln();
        
        $pdf->SetFont('helvetica', '', 6.5);
        $no = 1;
        foreach ($siswa_bermasalah_data as $row) {
            $level = getPoinLevel($row['poin_akhir']);
            $pdf->Cell($col_widths4[0], 4, $no++, 1, 0, 'C');
            $pdf->Cell($col_widths4[1], 4, $row['nisn'], 1, 0, 'C');
            $pdf->Cell($col_widths4[2], 4, substr($row['nama_siswa'], 0, 16), 1, 0, 'L');
            $pdf->Cell($col_widths4[3], 4, $row['nama_kelas'] ?? '-', 1, 0, 'C');
            $pdf->Cell($col_widths4[4], 4, $row['poin_pelanggaran'], 1, 0, 'C');
            $pdf->Cell($col_widths4[5], 4, $row['poin_prestasi'], 1, 0, 'C');
            $pdf->Cell($col_widths4[6], 4, $row['poin_akhir'], 1, 0, 'C');
            $pdf->Cell($col_widths4[7], 4, $level['level'], 1, 0, 'C');
            $pdf->Ln();
        }
        
        if (count($siswa_bermasalah_data) == 0) {
            $total_col4 = array_sum($col_widths4);
            $pdf->Cell($total_col4, 4, 'Tidak ada siswa bermasalah', 1, 0, 'C');
            $pdf->Ln();
        }
    }
    
    // ============================================
    // FOOTER
    // ============================================
    $pdf->Ln(6);
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->Cell(0, 4, 'Dicetak pada: ' . date('d-m-Y H:i:s'), 0, 1, 'L');
    $pdf->Cell(0, 4, 'Petugas BK', 0, 1, 'L');
    
    $filename = 'Laporan_BK_' . date('Ymd') . '.pdf';
    $pdf->Output($filename, 'I');
    exit;
}
?>