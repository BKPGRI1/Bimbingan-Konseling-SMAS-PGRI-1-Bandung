<?php
// guru/reject_pelanggaran.php
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

// Proses reject
$result = rejectPelanggaran($id);

if ($result) {
    $_SESSION['toast'] = [
        'message' => 'Pelanggaran berhasil ditolak!',
        'type' => 'success'
    ];
} else {
    $_SESSION['toast'] = [
        'message' => 'Gagal menolak pelanggaran!',
        'type' => 'danger'
    ];
}

// Redirect ke halaman sebelumnya
$redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'riwayat_hari_ini.php';
header("Location: " . $redirect);
exit;
?>