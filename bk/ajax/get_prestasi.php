<?php
// bk/ajax/get_prestasi.php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';

// Cek login
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Cek role
if (!hasRole(['bk'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
    exit;
}

$conn = getDB();
$query = "SELECT * FROM prestasi_siswa WHERE id = $id";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    $data = $result->fetch_assoc();
    header('Content-Type: application/json');
    echo json_encode(['success' => true] + $data);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan']);
}