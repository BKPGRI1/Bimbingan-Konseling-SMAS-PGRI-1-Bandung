<?php
// walikelas/rekap_absensi.php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Cek login
if (!isLoggedIn()) {
    header("Location: ../login.php");
    exit;
}

// Cek role (hanya walikelas)
if (!hasRole(['walikelas'])) {
    $_SESSION['toast'] = [
        'message' => 'Anda tidak memiliki akses ke halaman ini!',
        'type' => 'danger'
    ];
    header("Location: " . APP_URL . "dashboard.php");
    exit;
}

$page_title = 'Rekap Absensi';

// Ambil data wali kelas
$user = getCurrentUser();
$walikelas_id = $user['id'];

// Ambil kelas yang diampu oleh wali kelas
$kelas = getRow("SELECT * FROM kelas WHERE wali_kelas_id = $walikelas_id");
if (!$kelas) {
    echo '<div class="alert alert-danger">Anda belum ditugaskan sebagai wali kelas!</div>';
    include __DIR__ . '/../includes/footer.php';
    exit;
}

$kelas_id = $kelas['id'];
$nama_kelas = $kelas['nama_kelas'];

// ============================================
// FUNGSI CEK HARI LIBUR - DENGAN CACHE
// ============================================

function getSemuaHariLibur() {
    if (isset($_SESSION['hari_libur_cache_walikelas'])) {
        return $_SESSION['hari_libur_cache_walikelas'];
    }
    
    $conn = getDB();
    $check = $conn->query("SHOW TABLES LIKE 'hari_libur'");
    if (!$check || $check->num_rows == 0) {
        return [];
    }
    
    $check_col = $conn->query("SHOW COLUMNS FROM hari_libur LIKE 'hari'");
    if (!$check_col || $check_col->num_rows == 0) {
        return [];
    }
    
    $query = "SELECT * FROM hari_libur WHERE is_active = 1";
    $result = $conn->query($query);
    $data = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    
    $_SESSION['hari_libur_cache_walikelas'] = $data;
    $_SESSION['hari_libur_cache_walikelas_time'] = time();
    return $data;
}

function isHariLibur($tanggal) {
    if (!isset($_SESSION['hari_libur_cache_walikelas']) || 
        !isset($_SESSION['hari_libur_cache_walikelas_time']) || 
        (time() - $_SESSION['hari_libur_cache_walikelas_time']) > 300) {
        getSemuaHariLibur();
    }
    
    $hari_libur = $_SESSION['hari_libur_cache_walikelas'] ?? [];
    if (empty($hari_libur)) {
        return false;
    }
    
    $hari = date('l', strtotime($tanggal));
    $hari_indonesia = [
        'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu',
        'Sunday' => 'Minggu'
    ];
    $hari_indo = $hari_indonesia[$hari] ?? $hari;
    
    foreach ($hari_libur as $libur) {
        if ($libur['jenis'] == 'otomatis_harian' && $libur['hari'] == $hari_indo) {
            return true;
        }
        if (($libur['jenis'] == 'otomatis_tanggal' || $libur['jenis'] == 'custom') && 
            $libur['tanggal'] == $tanggal) {
            return true;
        }
    }
    return false;
}

$hari_libur_all = getSemuaHariLibur();

// ============================================
// AMBIL FILTER
// ============================================
$bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : date('m');
$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : date('Y');
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// ============================================
// FUNGSI AMBIL DATA ABSENSI
// ============================================
function getDataAbsensi($kelas_id, $bulan, $tahun) {
    $conn = getDB();
    
    $siswa_list = getSiswaByKelas($kelas_id);
    if (empty($siswa_list)) {
        return [];
    }
    
    $siswa_ids = array_column($siswa_list, 'id');
    $siswa_ids_str = implode(',', $siswa_ids);
    
    $start_date = $tahun . '-' . str_pad($bulan, 2, '0', STR_PAD_LEFT) . '-01';
    $end_date = date('Y-m-t', strtotime($start_date));
    
    $query = "SELECT siswa_id, tanggal, status FROM absensi 
              WHERE siswa_id IN ($siswa_ids_str) 
              AND tanggal BETWEEN '$start_date' AND '$end_date'";
    $result = $conn->query($query);
    
    $absensi_data = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $key = $row['siswa_id'] . '|' . $row['tanggal'];
            $absensi_data[$key] = $row['status'];
        }
    }
    
    $jumlah_hari = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);
    
    $data = [];
    foreach ($siswa_list as $siswa) {
        $row = [
            'siswa_id' => $siswa['id'],
            'nisn' => $siswa['nisn'],
            'nama_siswa' => $siswa['nama_siswa'],
            'jenis_kelamin' => $siswa['jenis_kelamin'],
            'absensi' => [],
            'total_hadir' => 0,
            'total_izin' => 0,
            'total_alfa' => 0,
            'total_sakit' => 0,
            'total_absensi' => 0
        ];
        
        for ($i = 1; $i <= $jumlah_hari; $i++) {
            $tanggal = $tahun . '-' . str_pad($bulan, 2, '0', STR_PAD_LEFT) . '-' . str_pad($i, 2, '0', STR_PAD_LEFT);
            $key = $siswa['id'] . '|' . $tanggal;
            
            if (isset($absensi_data[$key])) {
                $status = $absensi_data[$key];
                $row['absensi'][$i] = $status;
                if ($status == 'H') $row['total_hadir']++;
                elseif ($status == 'I') $row['total_izin']++;
                elseif ($status == 'A') $row['total_alfa']++;
                elseif ($status == 'S') $row['total_sakit']++;
            } else {
                $row['absensi'][$i] = '-';
            }
        }
        
        $row['total_absensi'] = $row['total_hadir'] + $row['total_izin'] + $row['total_alfa'] + $row['total_sakit'];
        $data[] = $row;
    }
    
    return $data;
}

// ============================================
// HANDLE EXPORT PDF & EXCEL
// ============================================
if (isset($_GET['export'])) {
    $export_type = $_GET['export'];
    $bulan_export = isset($_GET['bulan']) ? (int)$_GET['bulan'] : date('m');
    $tahun_export = isset($_GET['tahun']) ? (int)$_GET['tahun'] : date('Y');
    $status_filter_export = isset($_GET['status']) ? $_GET['status'] : '';
    
    $data_export = getDataAbsensi($kelas_id, $bulan_export, $tahun_export);
    
    // Filter by status
    if (!empty($status_filter_export)) {
        foreach ($data_export as $key => $row) {
            $found = false;
            foreach ($row['absensi'] as $status) {
                if ($status == $status_filter_export) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                unset($data_export[$key]);
            }
        }
        $data_export = array_values($data_export);
    }
    
    $jumlah_hari_export = cal_days_in_month(CAL_GREGORIAN, $bulan_export, $tahun_export);
    $nama_bulan_export = getBulanIndonesia($bulan_export);
    $pengaturan = getPengaturan();
    $nama_sekolah = $pengaturan['nama_sekolah'] ?? 'SMA';
    
    // Cache hari libur untuk export
    $hari_libur_export = [];
    for ($i = 1; $i <= $jumlah_hari_export; $i++) {
        $tanggal = $tahun_export . '-' . str_pad($bulan_export, 2, '0', STR_PAD_LEFT) . '-' . str_pad($i, 2, '0', STR_PAD_LEFT);
        $hari_libur_export[$i] = isHariLibur($tanggal);
    }
    
    if ($export_type == 'pdf') {
        // PDF tampil di browser (tidak download otomatis)
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: inline; filename="Rekap_Absensi_' . $nama_kelas . '_' . date('Ymd') . '.html"');
        
        echo '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Rekap Absensi Kelas ' . $nama_kelas . '</title>
            <style>
                body { font-family: Arial, sans-serif; font-size: 10px; margin: 15px; }
                .header { text-align: center; margin-bottom: 15px; }
                .header h1 { font-size: 16px; margin: 0; }
                .header h2 { font-size: 14px; margin: 3px 0; }
                .header p { font-size: 11px; color: #555; margin: 2px 0; }
                table { width: 100%; border-collapse: collapse; font-size: 8px; }
                table th { background: #4a6cf7; color: white; font-weight: bold; padding: 4px 3px; border: 1px solid #999; text-align: center; }
                table td { padding: 3px 3px; border: 1px solid #999; text-align: center; }
                .text-left { text-align: left; }
                .text-success { color: #10B981; font-weight: bold; }
                .text-warning { color: #F59E0B; font-weight: bold; }
                .text-danger { color: #EF4444; font-weight: bold; }
                .text-info { color: #3B82F6; font-weight: bold; }
                .bg-hadir { background: #d1fae5; }
                .bg-izin { background: #fef3c7; }
                .bg-alfa { background: #fecaca; }
                .bg-sakit { background: #dbeafe; }
                .bg-libur { background: #fef3c7; color: #92400e; font-weight: bold; }
                .total-row { background: #e3f2fd; font-weight: bold; }
                .footer { text-align: center; font-size: 8px; color: #999; margin-top: 10px; border-top: 1px solid #ddd; padding-top: 8px; }
                .legend { font-size: 8px; margin-top: 5px; }
                .legend span { margin-right: 8px; }
                .libur-label { background: #fef3c7; padding: 0 4px; border-radius: 2px; }
                @media print {
                    body { margin: 10px; }
                    table th { background: #4a6cf7 !important; color: white !important; }
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>' . htmlspecialchars($nama_sekolah) . '</h1>
                <h2>REKAP ABSENSI SISWA</h2>
                <p>Kelas: ' . htmlspecialchars($nama_kelas) . ' | Bulan: ' . $nama_bulan_export . ' ' . $tahun_export . '</p>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th style="width:3%;">No</th>
                        <th style="width:10%;">NISN</th>
                        <th style="width:18%;">Nama Siswa</th>
                        <th style="width:5%;">JK</th>';
        
        for ($i = 1; $i <= $jumlah_hari_export; $i++) {
            $is_libur = $hari_libur_export[$i];
            $style = $is_libur ? 'background:#fef3c7;color:#92400e;' : '';
            echo '<th style="width:2.5%; font-size:6px; ' . $style . '">' . $i . ($is_libur ? ' L' : '') . '</th>';
        }
        
        echo '          <th style="width:4%;">H</th>
                        <th style="width:4%;">I</th>
                        <th style="width:4%;">A</th>
                        <th style="width:4%;">S</th>
                        <th style="width:5%;">Total</th>
                    </tr>
                </thead>
                <tbody>';
        
        $no = 1;
        $total_hadir = 0;
        $total_izin = 0;
        $total_alfa = 0;
        $total_sakit = 0;
        
        foreach ($data_export as $row) {
            $total_hadir += $row['total_hadir'];
            $total_izin += $row['total_izin'];
            $total_alfa += $row['total_alfa'];
            $total_sakit += $row['total_sakit'];
            
            echo '<tr>
                    <td>' . $no++ . '</td>
                    <td>' . $row['nisn'] . '</td>
                    <td class="text-left">' . htmlspecialchars($row['nama_siswa']) . '</td>
                    <td>' . ($row['jenis_kelamin'] == 'L' ? 'L' : 'P') . '</td>';
            
            for ($i = 1; $i <= $jumlah_hari_export; $i++) {
                $status = isset($row['absensi'][$i]) ? $row['absensi'][$i] : '-';
                $is_libur = $hari_libur_export[$i];
                $bg_class = '';
                if ($is_libur) {
                    $bg_class = 'bg-libur';
                } elseif ($status == 'H') {
                    $bg_class = 'bg-hadir';
                } elseif ($status == 'I') {
                    $bg_class = 'bg-izin';
                } elseif ($status == 'A') {
                    $bg_class = 'bg-alfa';
                } elseif ($status == 'S') {
                    $bg_class = 'bg-sakit';
                }
                echo '<td class="' . $bg_class . '">' . $status . ($is_libur ? ' L' : '') . '</td>';
            }
            
            echo '      <td class="text-success">' . $row['total_hadir'] . '</td>
                    <td class="text-warning">' . $row['total_izin'] . '</td>
                    <td class="text-danger">' . $row['total_alfa'] . '</td>
                    <td class="text-info">' . $row['total_sakit'] . '</td>
                    <td>' . $row['total_absensi'] . '</td>
                </tr>';
        }
        
        echo '<tr class="total-row">
                <td colspan="4" style="text-align:right;">TOTAL</td>';
        
        for ($i = 1; $i <= $jumlah_hari_export; $i++) {
            $total_hari = 0;
            $is_libur = $hari_libur_export[$i];
            foreach ($data_export as $row) {
                if (isset($row['absensi'][$i]) && $row['absensi'][$i] != '-') {
                    $total_hari++;
                }
            }
            echo '<td ' . ($is_libur ? 'style="background:#fef3c7;color:#92400e;"' : '') . '>' . ($total_hari > 0 ? $total_hari : '') . '</td>';
        }
        
        echo '  <td>' . $total_hadir . '</td>
                <td>' . $total_izin . '</td>
                <td>' . $total_alfa . '</td>
                <td>' . $total_sakit . '</td>
                <td>' . ($total_hadir + $total_izin + $total_alfa + $total_sakit) . '</td>
            </tr>
            </tbody>
        </table>
        
        <div class="footer">
            <div class="legend">
                <span class="text-success">H</span>=Hadir 
                <span class="text-warning">I</span>=Izin 
                <span class="text-danger">A</span>=Alfa 
                <span class="text-info">S</span>=Sakit 
                <span>-</span>=Tidak Ada Data
                <span class="libur-label">L</span>=Hari Libur
            </div>
            <p>Total Siswa: ' . count($data_export) . ' | Dicetak: ' . date('d-m-Y H:i:s') . '</p>
            <p style="font-size:7px;color:#aaa;">Gunakan Ctrl+P atau Print untuk menyimpan sebagai PDF</p>
        </div>
        </body>
        </html>';
        exit;
        
    } elseif ($export_type == 'excel') {
        // Excel download otomatis
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="Rekap_Absensi_' . $nama_kelas . '_' . date('Ymd') . '.xls"');
        header('Cache-Control: max-age=0');
        
        echo '<html>
        <head><meta charset="UTF-8"><title>Rekap Absensi</title>
        <style>
            th { background-color: #4a6cf7; color: white; font-weight: bold; padding: 4px; border: 1px solid #999; text-align: center; font-size: 8px; }
            td { padding: 3px 4px; border: 1px solid #999; font-size: 8px; text-align: center; }
            .header { font-size: 14px; font-weight: bold; text-align: center; }
            .sub-header { font-size: 10px; text-align: center; margin-bottom: 6px; }
            .text-left { text-align: left; }
            .text-success { color: #10B981; font-weight: bold; }
            .text-warning { color: #F59E0B; font-weight: bold; }
            .text-danger { color: #EF4444; font-weight: bold; }
            .text-info { color: #3B82F6; font-weight: bold; }
            .bg-hadir { background: #d1fae5; }
            .bg-izin { background: #fef3c7; }
            .bg-alfa { background: #fecaca; }
            .bg-sakit { background: #dbeafe; }
            .bg-libur { background: #fef3c7; color: #92400e; font-weight: bold; }
            .total-row { background: #e3f2fd; font-weight: bold; }
        </style>
        </head><body>';
        
        echo '<h2 class="header">' . htmlspecialchars($nama_sekolah) . '</h2>';
        echo '<h3 class="header">REKAP ABSENSI SISWA</h3>';
        echo '<p class="sub-header">Kelas: ' . htmlspecialchars($nama_kelas) . ' | Bulan: ' . $nama_bulan_export . ' ' . $tahun_export . '</p>';
        
        echo '<table cellpadding="0" cellspacing="0" border="1" style="width:100%;border-collapse:collapse;">';
        echo '<thead><tr>';
        echo '<th>No</th>';
        echo '<th>NISN</th>';
        echo '<th>Nama Siswa</th>';
        echo '<th>JK</th>';
        
        for ($i = 1; $i <= $jumlah_hari_export; $i++) {
            $is_libur = $hari_libur_export[$i];
            echo '<th style="font-size:6px;' . ($is_libur ? 'background:#fef3c7;color:#92400e;' : '') . '">' . $i . ($is_libur ? ' L' : '') . '</th>';
        }
        
        echo '<th>H</th>';
        echo '<th>I</th>';
        echo '<th>A</th>';
        echo '<th>S</th>';
        echo '<th>Total</th>';
        echo '</tr></thead><tbody>';
        
        $no = 1;
        $total_hadir = 0;
        $total_izin = 0;
        $total_alfa = 0;
        $total_sakit = 0;
        
        foreach ($data_export as $row) {
            $total_hadir += $row['total_hadir'];
            $total_izin += $row['total_izin'];
            $total_alfa += $row['total_alfa'];
            $total_sakit += $row['total_sakit'];
            
            echo '<tr>';
            echo '<td>' . $no++ . '</td>';
            echo '<td>' . $row['nisn'] . '</td>';
            echo '<td style="text-align:left;">' . htmlspecialchars($row['nama_siswa']) . '</td>';
            echo '<td>' . ($row['jenis_kelamin'] == 'L' ? 'L' : 'P') . '</td>';
            
            for ($i = 1; $i <= $jumlah_hari_export; $i++) {
                $status = isset($row['absensi'][$i]) ? $row['absensi'][$i] : '-';
                $is_libur = $hari_libur_export[$i];
                $bg_class = '';
                if ($is_libur) {
                    $bg_class = 'bg-libur';
                } elseif ($status == 'H') {
                    $bg_class = 'bg-hadir';
                } elseif ($status == 'I') {
                    $bg_class = 'bg-izin';
                } elseif ($status == 'A') {
                    $bg_class = 'bg-alfa';
                } elseif ($status == 'S') {
                    $bg_class = 'bg-sakit';
                }
                echo '<td class="' . $bg_class . '">' . $status . ($is_libur ? ' L' : '') . '</td>';
            }
            
            echo '<td class="text-success">' . $row['total_hadir'] . '</td>';
            echo '<td class="text-warning">' . $row['total_izin'] . '</td>';
            echo '<td class="text-danger">' . $row['total_alfa'] . '</td>';
            echo '<td class="text-info">' . $row['total_sakit'] . '</td>';
            echo '<td>' . $row['total_absensi'] . '</td>';
            echo '</tr>';
        }
        
        echo '<tr class="total-row">';
        echo '<td colspan="4" style="text-align:right;">TOTAL</td>';
        
        for ($i = 1; $i <= $jumlah_hari_export; $i++) {
            $total_hari = 0;
            $is_libur = $hari_libur_export[$i];
            foreach ($data_export as $row) {
                if (isset($row['absensi'][$i]) && $row['absensi'][$i] != '-') {
                    $total_hari++;
                }
            }
            echo '<td ' . ($is_libur ? 'style="background:#fef3c7;color:#92400e;"' : '') . '>' . ($total_hari > 0 ? $total_hari : '') . '</td>';
        }
        
        echo '<td>' . $total_hadir . '</td>';
        echo '<td>' . $total_izin . '</td>';
        echo '<td>' . $total_alfa . '</td>';
        echo '<td>' . $total_sakit . '</td>';
        echo '<td>' . ($total_hadir + $total_izin + $total_alfa + $total_sakit) . '</td>';
        echo '</tr>';
        
        echo '</tbody></table>';
        echo '<p style="font-size:8px;color:#999;margin-top:6px;">Total Siswa: ' . count($data_export) . ' | Dicetak: ' . date('d-m-Y H:i:s') . '</p>';
        echo '<p style="font-size:8px;color:#999;">L = Hari Libur | H=Hadir, I=Izin, A=Alfa, S=Sakit, -=Tidak Ada Data</p>';
        echo '</body></html>';
        exit;
    }
}

// ============================================
// AMBIL DATA
// ============================================
$data_absensi = getDataAbsensi($kelas_id, $bulan, $tahun);
$jumlah_hari = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);
$nama_bulan = getBulanIndonesia($bulan);

// Cache hari libur
$hari_libur_cache = [];
for ($i = 1; $i <= $jumlah_hari; $i++) {
    $tanggal = $tahun . '-' . str_pad($bulan, 2, '0', STR_PAD_LEFT) . '-' . str_pad($i, 2, '0', STR_PAD_LEFT);
    $hari_libur_cache[$i] = isHariLibur($tanggal);
}

// Filter by status
if (!empty($status_filter)) {
    foreach ($data_absensi as $key => $row) {
        $found = false;
        foreach ($row['absensi'] as $status) {
            if ($status == $status_filter) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            unset($data_absensi[$key]);
        }
    }
    $data_absensi = array_values($data_absensi);
}

$total_hadir = array_sum(array_column($data_absensi, 'total_hadir'));
$total_izin = array_sum(array_column($data_absensi, 'total_izin'));
$total_alfa = array_sum(array_column($data_absensi, 'total_alfa'));
$total_sakit = array_sum(array_column($data_absensi, 'total_sakit'));

include __DIR__ . '/../includes/header_walikelas.php';
?>

<div class="container-fluid dashboard-container">
    <div class="dashboard-header">
        <div>
            <h4 class="dashboard-title">Rekap Absensi</h4>
            <small class="text-muted dashboard-subtitle">Kelas <?= htmlspecialchars($nama_kelas) ?> | <?= $nama_bulan ?> <?= $tahun ?></small>
        </div>
        <div class="d-flex gap-1 flex-wrap stat-badges">
            <span class="badge bg-primary stat-badge"><?= count($data_absensi) ?></span>
            <span class="badge bg-success stat-badge"><?= $total_hadir ?></span>
            <span class="badge bg-warning stat-badge"><?= $total_izin ?></span>
            <span class="badge bg-danger stat-badge"><?= $total_alfa ?></span>
            <span class="badge bg-info stat-badge"><?= $total_sakit ?></span>
        </div>
    </div>

    <?php if (isset($_SESSION['toast'])): ?>
        <div id="toastNotification" class="toast-container position-fixed top-0 end-0 p-2" style="z-index: 9999;">
            <div class="toast align-items-center text-white bg-<?= $_SESSION['toast']['type'] ?> border-0 show" role="alert" aria-live="assertive" aria-atomic="true" style="min-width: 200px; max-width: 320px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.12); font-size: 12px;">
                <div class="d-flex align-items-center p-2">
                    <div class="flex-shrink-0 ms-1">
                        <i class="fas fa-<?= $_SESSION['toast']['type'] == 'success' ? 'check-circle' : ($_SESSION['toast']['type'] == 'warning' ? 'exclamation-triangle' : 'times-circle') ?> fa-sm"></i>
                    </div>
                    <div class="toast-body flex-grow-1 text-start ms-2" style="font-size: 12px; font-weight: 500; padding: 2px 0; line-height: 1.4;">
                        <?= $_SESSION['toast']['message'] ?>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-0" data-bs-dismiss="toast" aria-label="Close" style="font-size: 9px; padding: 3px;"></button>
                </div>
            </div>
        </div>
        <?php unset($_SESSION['toast']); ?>
    <?php endif; ?>

    <div class="card filter-card mb-2">
        <div class="card-body p-2">
            <form method="GET" action="" class="filter-form">
                <div class="row g-1 align-items-end">
                    <div class="col-4 col-md-3">
                        <label class="form-label">Bulan</label>
                        <select class="form-select" name="bulan">
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                <option value="<?= $i ?>" <?= $bulan == $i ? 'selected' : '' ?>>
                                    <?= getBulanIndonesia($i) ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-4 col-md-3">
                        <label class="form-label">Tahun</label>
                        <select class="form-select" name="tahun">
                            <?php for ($i = date('Y') - 2; $i <= date('Y'); $i++): ?>
                                <option value="<?= $i ?>" <?= $tahun == $i ? 'selected' : '' ?>><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-4 col-md-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="">Semua</option>
                            <option value="H" <?= $status_filter == 'H' ? 'selected' : '' ?>>Hadir</option>
                            <option value="I" <?= $status_filter == 'I' ? 'selected' : '' ?>>Izin</option>
                            <option value="A" <?= $status_filter == 'A' ? 'selected' : '' ?>>Alfa</option>
                            <option value="S" <?= $status_filter == 'S' ? 'selected' : '' ?>>Sakit</option>
                        </select>
                    </div>
                    <div class="col-4 col-md-2">
                        <label class="form-label d-none d-md-block">&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-sm w-100">Tampilkan</button>
                    </div>
                    <div class="col-4 col-md-1">
                        <label class="form-label d-none d-md-block">&nbsp;</label>
                        <a href="?bulan=<?= date('m') ?>&tahun=<?= date('Y') ?>" class="btn btn-secondary btn-sm w-100">Reset</a>
                    </div>
                </div>
                <div class="row mt-1">
                    <div class="col-12">
                        <div class="d-flex gap-1 flex-wrap">
                            <a href="?export=pdf&bulan=<?= $bulan ?>&tahun=<?= $tahun ?>&status=<?= $status_filter ?>" class="btn btn-danger btn-sm" target="_blank">PDF</a>
                            <a href="?export=excel&bulan=<?= $bulan ?>&tahun=<?= $tahun ?>&status=<?= $status_filter ?>" class="btn btn-success btn-sm">Excel</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if (count($hari_libur_all) > 0): ?>
        <div class="alert alert-warning py-1 px-2 mb-2 libur-alert">
            <strong>Libur:</strong>
            <?php foreach ($hari_libur_all as $libur): 
                if ($libur['jenis'] == 'otomatis_harian') {
                    $display = $libur['hari'];
                } else {
                    $display = date('d-m-Y', strtotime($libur['tanggal']));
                }
            ?>
                <span class="badge bg-warning text-dark mx-1"><?= $display ?></span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="card table-card">
        <div class="card-header py-1 px-2">
            Rekap Absensi
            <span class="badge bg-secondary ms-1"><?= count($data_absensi) ?> Siswa</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-striped mb-0 rekap-table">
                    <thead>
                        <tr>
                            <th class="text-center">#</th>
                            <th>NISN</th>
                            <th>Nama</th>
                            <th>JK</th>
                            <?php for ($i = 1; $i <= $jumlah_hari; $i++): ?>
                                <th class="text-center day-header <?= $hari_libur_cache[$i] ? 'libur-day' : '' ?>">
                                    <?= $i ?>
                                    <?php if ($hari_libur_cache[$i]): ?>
                                        <span class="libur-icon">L</span>
                                    <?php endif; ?>
                                </th>
                            <?php endfor; ?>
                            <th class="text-center">H</th>
                            <th class="text-center">I</th>
                            <th class="text-center">A</th>
                            <th class="text-center">S</th>
                            <th class="text-center">Tot</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($data_absensi) > 0): ?>
                            <?php $no = 1; ?>
                            <?php foreach ($data_absensi as $row): ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?></td>
                                    <td><?= $row['nisn'] ?></td>
                                    <td><strong><?= htmlspecialchars($row['nama_siswa']) ?></strong></td>
                                    <td><?= $row['jenis_kelamin'] == 'L' ? 'L' : 'P' ?></td>
                                    <?php for ($i = 1; $i <= $jumlah_hari; $i++): 
                                        $status = isset($row['absensi'][$i]) ? $row['absensi'][$i] : '-';
                                        $is_libur = $hari_libur_cache[$i];
                                        $badge_class = $status == 'H' ? 'bg-success' : ($status == 'I' ? 'bg-warning' : ($status == 'A' ? 'bg-danger' : ($status == 'S' ? 'bg-info' : 'bg-secondary')));
                                        if ($is_libur) {
                                            $badge_class = 'bg-warning text-dark';
                                        }
                                    ?>
                                        <td class="text-center status-cell">
                                            <span class="badge <?= $badge_class ?> status-badge">
                                                <?= $status ?>
                                            </span>
                                        </td>
                                    <?php endfor; ?>
                                    <td class="text-center fw-bold text-success"><?= $row['total_hadir'] ?></td>
                                    <td class="text-center fw-bold text-warning"><?= $row['total_izin'] ?></td>
                                    <td class="text-center fw-bold text-danger"><?= $row['total_alfa'] ?></td>
                                    <td class="text-center fw-bold text-info"><?= $row['total_sakit'] ?></td>
                                    <td class="text-center fw-bold"><?= $row['total_absensi'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                            
                            <tr class="table-info fw-bold total-row">
                                <td colspan="4" class="text-end">TOTAL</td>
                                <?php for ($i = 1; $i <= $jumlah_hari; $i++): 
                                    $total_hari = 0;
                                    foreach ($data_absensi as $row) {
                                        if (isset($row['absensi'][$i]) && $row['absensi'][$i] != '-') {
                                            $total_hari++;
                                        }
                                    }
                                ?>
                                    <td class="text-center total-day <?= $hari_libur_cache[$i] ? 'libur-day' : '' ?>">
                                        <?= $total_hari > 0 ? $total_hari : '' ?>
                                    </td>
                                <?php endfor; ?>
                                <td class="text-center"><?= $total_hadir ?></td>
                                <td class="text-center"><?= $total_izin ?></td>
                                <td class="text-center"><?= $total_alfa ?></td>
                                <td class="text-center"><?= $total_sakit ?></td>
                                <td class="text-center"><?= $total_hadir + $total_izin + $total_alfa + $total_sakit ?></td>
                            </tr>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?= 4 + $jumlah_hari + 5 ?>" class="text-center py-4 text-muted">
                                    Tidak ada data absensi
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer text-muted py-1 legend-footer">
            <span class="text-success">H</span>=Hadir 
            <span class="text-warning">I</span>=Izin 
            <span class="text-danger">A</span>=Alfa 
            <span class="text-info">S</span>=Sakit 
            <span class="text-secondary">-</span>=Tidak Ada
            <span class="libur-legend">L=Libur</span>
        </div>
    </div>

    <div class="row g-1 mt-1 stats-row">
        <div class="col-3 col-md-3">
            <div class="stat-card bg-success bg-opacity-10 text-success">
                <div class="stat-card-body">
                    <span class="stat-number"><?= $total_hadir ?></span>
                    <span class="stat-label">Hadir</span>
                </div>
            </div>
        </div>
        <div class="col-3 col-md-3">
            <div class="stat-card bg-warning bg-opacity-10 text-warning">
                <div class="stat-card-body">
                    <span class="stat-number"><?= $total_izin ?></span>
                    <span class="stat-label">Izin</span>
                </div>
            </div>
        </div>
        <div class="col-3 col-md-3">
            <div class="stat-card bg-danger bg-opacity-10 text-danger">
                <div class="stat-card-body">
                    <span class="stat-number"><?= $total_alfa ?></span>
                    <span class="stat-label">Alfa</span>
                </div>
            </div>
        </div>
        <div class="col-3 col-md-3">
            <div class="stat-card bg-info bg-opacity-10 text-info">
                <div class="stat-card-body">
                    <span class="stat-number"><?= $total_sakit ?></span>
                    <span class="stat-label">Sakit</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
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
.dashboard-container {
    padding: 6px !important;
    max-width: 100%;
    overflow-x: hidden;
}

.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    margin-bottom: 8px;
    gap: 4px;
}

.dashboard-title {
    font-size: 0.95rem;
    margin-bottom: 0;
    font-weight: 600;
}

.dashboard-subtitle {
    font-size: 0.6rem;
    display: block;
}

.stat-badges {
    gap: 3px !important;
}

.stat-badge {
    font-size: 9px !important;
    padding: 3px 6px !important;
    border-radius: 12px !important;
    font-weight: 500;
}

.card {
    border: none;
    border-radius: 8px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.05);
    margin-bottom: 6px;
    background: #fff;
}

.card-header {
    background: transparent;
    border-bottom: 1px solid #e9ecef;
    padding: 6px 10px;
    font-weight: 600;
    font-size: 11px;
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 4px;
}

.card-body {
    padding: 0;
}

.card-footer {
    background: transparent;
    border-top: 1px solid #e9ecef;
    padding: 4px 10px;
}

.filter-card .card-body {
    padding: 6px 8px;
}

.filter-card .form-label {
    font-weight: 500;
    font-size: 10px;
    color: #4a5568;
    margin-bottom: 2px;
}

.filter-card .form-control,
.filter-card .form-select {
    border-radius: 6px;
    border: 1px solid #e2e8f0;
    padding: 3px 6px;
    font-size: 11px;
    height: 28px;
}

.filter-card .btn-sm {
    padding: 3px 8px !important;
    font-size: 10px !important;
    border-radius: 6px !important;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.libur-alert {
    font-size: 9px !important;
    padding: 3px 8px !important;
    margin-bottom: 4px !important;
    border-radius: 6px !important;
}

.libur-alert .badge {
    font-size: 8px !important;
    padding: 1px 5px !important;
}

.table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    max-height: 600px;
    overflow-y: auto;
}

.rekap-table {
    font-size: 10px;
    margin-bottom: 0;
    width: 100% !important;
    border-collapse: collapse;
}

.rekap-table th,
.rekap-table td {
    border: 1px solid #dee2e6;
    padding: 3px 4px;
    vertical-align: middle;
}

.rekap-table th {
    font-weight: 600;
    font-size: 8px;
    text-transform: uppercase;
    letter-spacing: 0.2px;
    white-space: nowrap;
    background-color: #f8f9fa;
    position: sticky;
    top: 0;
    z-index: 10;
}

.rekap-table td {
    vertical-align: middle;
    font-size: 9px;
}

.day-header {
    text-align: center;
    font-size: 7px !important;
    padding: 2px 1px !important;
    min-width: 18px;
}

.day-header.libur-day {
    background: #fef3c7 !important;
}

.libur-icon {
    font-size: 5px;
    display: block;
    line-height: 1;
}

.status-badge {
    font-size: 7px !important;
    padding: 1px 4px !important;
    border-radius: 3px !important;
    display: inline-block;
    min-width: 16px;
    text-align: center;
}

.status-cell {
    text-align: center;
    padding: 2px 1px !important;
}

.total-row td {
    font-size: 9px;
    padding: 3px 4px;
}

.total-day {
    font-size: 8px;
}

.total-day.libur-day {
    background: #fef3c7 !important;
}

.legend-footer {
    font-size: 8px !important;
    padding: 3px 8px !important;
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
}

.legend-footer .libur-legend {
    color: #92400e;
}

.stats-row {
    margin-top: 4px !important;
}

.stat-card {
    padding: 4px 6px;
    border-radius: 6px;
    text-align: center;
}

.stat-card-body {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
}

.stat-number {
    font-size: 1.1rem;
    font-weight: 700;
    line-height: 1;
}

.stat-label {
    font-size: 0.55rem;
    text-transform: uppercase;
    letter-spacing: 0.2px;
    font-weight: 500;
}

.toast-container .toast {
    border-radius: 8px !important;
    box-shadow: 0 4px 20px rgba(0,0,0,0.12) !important;
}

@media (max-width: 768px) {
    .dashboard-container {
        padding: 4px !important;
    }
    .dashboard-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 3px;
        margin-bottom: 6px;
    }
    .dashboard-title {
        font-size: 0.85rem;
    }
    .dashboard-subtitle {
        font-size: 0.55rem;
    }
    .stat-badges {
        gap: 2px !important;
    }
    .stat-badge {
        font-size: 8px !important;
        padding: 2px 5px !important;
    }
    .rekap-table {
        font-size: 9px;
        min-width: 600px;
    }
    .rekap-table th {
        font-size: 7px;
        padding: 2px 3px;
    }
    .rekap-table td {
        font-size: 8px;
        padding: 2px 3px;
    }
    .day-header {
        font-size: 6px !important;
        min-width: 14px;
    }
    .status-badge {
        font-size: 6px !important;
        padding: 1px 3px !important;
        min-width: 12px;
    }
    .stat-number {
        font-size: 0.95rem;
    }
    .stat-label {
        font-size: 0.5rem;
    }
    .filter-card .form-control,
    .filter-card .form-select {
        font-size: 10px;
        height: 26px;
        padding: 2px 5px;
    }
    .filter-card .btn-sm {
        font-size: 9px !important;
        height: 26px;
        padding: 2px 6px !important;
    }
    .filter-card .form-label {
        font-size: 9px;
    }
    .card-header {
        font-size: 10px;
        padding: 4px 8px;
    }
    .legend-footer {
        font-size: 7px !important;
        padding: 2px 6px !important;
    }
}

@media (max-width: 576px) {
    .dashboard-container {
        padding: 2px !important;
    }
    .dashboard-title {
        font-size: 0.75rem;
    }
    .dashboard-subtitle {
        font-size: 0.5rem;
    }
    .stat-badge {
        font-size: 7px !important;
        padding: 1px 4px !important;
    }
    .rekap-table {
        font-size: 8px;
        min-width: 500px;
    }
    .rekap-table th {
        font-size: 6px;
        padding: 1px 2px;
    }
    .rekap-table td {
        font-size: 7px;
        padding: 1px 2px;
    }
    .day-header {
        font-size: 5px !important;
        min-width: 12px;
        padding: 1px 0 !important;
    }
    .status-badge {
        font-size: 5px !important;
        padding: 1px 2px !important;
        min-width: 10px;
    }
    .stat-number {
        font-size: 0.8rem;
    }
    .stat-label {
        font-size: 0.45rem;
    }
    .filter-card .form-control,
    .filter-card .form-select {
        font-size: 9px;
        height: 24px;
        padding: 2px 4px;
    }
    .filter-card .btn-sm {
        font-size: 8px !important;
        height: 24px;
        padding: 1px 4px !important;
    }
    .filter-card .form-label {
        font-size: 8px;
    }
    .card-header {
        font-size: 9px;
        padding: 3px 6px;
    }
    .card-footer {
        padding: 2px 6px;
    }
    .legend-footer {
        font-size: 6px !important;
        padding: 2px 4px !important;
        gap: 2px;
    }
    .libur-alert {
        font-size: 7px !important;
        padding: 2px 6px !important;
    }
    .libur-alert .badge {
        font-size: 6px !important;
        padding: 1px 3px !important;
    }
    .stats-row .col-3 {
        padding: 0 2px !important;
    }
    .stat-card {
        padding: 3px 4px;
        border-radius: 4px;
    }
    .stat-card-body {
        gap: 2px;
        flex-direction: column;
    }
    .toast-container .toast {
        min-width: 160px !important;
        max-width: 240px !important;
    }
    .toast-container .toast .toast-body {
        font-size: 10px !important;
    }
}

.table-responsive::-webkit-scrollbar {
    width: 4px;
    height: 4px;
}

.table-responsive::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 2px;
}

.table-responsive::-webkit-scrollbar-thumb {
    background: #c1c7cd;
    border-radius: 2px;
}

.table-responsive::-webkit-scrollbar-thumb:hover {
    background: #a8b0b8;
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>