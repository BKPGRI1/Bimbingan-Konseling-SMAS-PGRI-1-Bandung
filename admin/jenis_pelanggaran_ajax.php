<?php
// admin/jenis_pelanggaran_ajax.php
include '../includes/config.php';
checkLogin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$data = getJenisPelanggaranById($id);

header('Content-Type: application/json');
echo json_encode($data);
exit;
?>