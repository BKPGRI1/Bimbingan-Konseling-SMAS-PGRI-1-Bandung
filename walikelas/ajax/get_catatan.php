<?php
// walikelas/ajax/get_catatan.php
include '../../includes/config.php';
checkLogin();

if (!hasRole('walikelas')) {
    exit('Akses ditolak!');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$data = getRow("SELECT * FROM catatan_pembinaan WHERE id = $id");

header('Content-Type: application/json');
echo json_encode($data);
exit;
?>