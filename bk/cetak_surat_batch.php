<?php
// bk/cetak_surat_batch.php
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
$ids = isset($_GET['ids']) ? $_GET['ids'] : '';
if (empty($ids)) {
    die('Tidak ada siswa dipilih!');
}

$id_array = explode(',', $ids);
$batas = getBatasPoin();

foreach ($id_array as $id) {
    $id = (int)$id;
    if ($id == 0) continue;
    
    $siswa = getSiswaById($id);
    if (!$siswa) continue;
    
    $total_poin = getTotalPoinSiswa($id);
    
    // Tentukan jenis surat berdasarkan poin
    if ($total_poin >= $batas['do']) {
        $jenis = 'do';
        $judul = 'SURAT PERINGATAN DO';
        $nomor = '001/DO/2024';
    } elseif ($total_poin >= $batas['pemanggilan']) {
        $jenis = 'pemanggilan';
        $judul = 'SURAT PEMANGGILAN';
        $nomor = '001/PGL/2024';
    } elseif ($total_poin >= $batas['peringatan']) {
        $jenis = 'teguran';
        $judul = 'SURAT TEGURAN';
        $nomor = '001/TEG/2024';
    } else {
        continue;
    }
    
    // Simpan ke database
    $data_surat = [
        'siswa_id' => $id,
        'jenis_surat' => $jenis,
        'nomor_surat' => $nomor,
        'tanggal_surat' => date('Y-m-d'),
        'isi_surat' => 'Surat ' . $jenis . ' untuk ' . $siswa['nama_siswa'],
        'status' => 'draft'
    ];
    tambahSuratMeneg($data_surat);
}

// Redirect ke halaman surat
$_SESSION['toast'] = [
    'message' => 'Surat berhasil dibuat untuk ' . count($id_array) . ' siswa!',
    'type' => 'success'
];
header("Location: " . APP_URL . "bk/surat_meneg.php");
exit;
?>