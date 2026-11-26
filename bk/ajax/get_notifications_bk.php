<?php
// bk/ajax/get_notifications_bk.php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['bermasalah' => 0, 'konseling' => 0]);
    exit;
}

$batas = getBatasPoin();
$batas_peringatan = $batas['peringatan'];

// Hitung siswa bermasalah (poin >= batas peringatan)
$result = query("SELECT COUNT(*) as total FROM siswa WHERE poin >= $batas_peringatan");
$bermasalah = $result->fetch_assoc()['total'];

// Hitung catatan konseling pending
$result = query("SELECT COUNT(*) as total FROM catatan_konseling WHERE status = 'pending'");
$konseling = $result->fetch_assoc()['total'];

echo json_encode([
    'bermasalah' => (int)$bermasalah,
    'konseling' => (int)$konseling
]);
?>