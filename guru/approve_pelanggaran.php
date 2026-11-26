<?php
// guru/approve_pelanggaran.php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Cek login
if (!isLoggedIn()) {
    header("Location: ../login.php");
    exit;
}

// Cek role
if (!hasRole(['admin', 'guru', 'bk', 'walikelas'])) {
    header("Location: ../dashboard.php");
    exit;
}

// Get ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id == 0) {
    $_SESSION['toast'] = [
        'message' => 'ID pelanggaran tidak valid!',
        'type' => 'danger'
    ];
    header("Location: riwayat_hari_ini.php");
    exit;
}

// Get data pelanggaran
$pelanggaran = getPelanggaranById($id);
if (!$pelanggaran) {
    $_SESSION['toast'] = [
        'message' => 'Data pelanggaran tidak ditemukan!',
        'type' => 'danger'
    ];
    header("Location: riwayat_hari_ini.php");
    exit;
}

// Cek status
if ($pelanggaran['status'] != 'pending') {
    $_SESSION['toast'] = [
        'message' => 'Pelanggaran sudah ' . $pelanggaran['status'] . '!',
        'type' => 'warning'
    ];
    header("Location: detail_pelanggaran.php?id=" . $id);
    exit;
}

// Proses approve
$result = approvePelanggaran($id);

if ($result) {
    // Ambil data siswa untuk update poin
    $siswa = getSiswaById($pelanggaran['siswa_id']);
    $total_poin = getTotalPoinSiswa($pelanggaran['siswa_id']);
    updateSiswa(['poin' => $total_poin], $pelanggaran['siswa_id']);
    
    // Kirim notifikasi WhatsApp jika ada nomor HP
    $wa_sent = false;
    if (!empty($siswa['no_hp'])) {
        $jenis = getJenisPelanggaranById($pelanggaran['jenis_pelanggaran_id']);
        $wa_sent = sendWhatsAppPelanggaran($siswa, $jenis, $jenis['poin'], $pelanggaran['tanggal_pelanggaran'], $pelanggaran['keterangan']);
    }
    
    $_SESSION['toast'] = [
        'message' => 'Pelanggaran berhasil disetujui!' . ($wa_sent ? ' WhatsApp terkirim.' : ''),
        'type' => 'success'
    ];
} else {
    $_SESSION['toast'] = [
        'message' => 'Gagal menyetujui pelanggaran!',
        'type' => 'danger'
    ];
}

// Redirect ke halaman sebelumnya
$redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'riwayat_hari_ini.php';
header("Location: " . $redirect);
exit;
?>