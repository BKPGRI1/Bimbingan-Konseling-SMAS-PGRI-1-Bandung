<?php
// assets/sample/download_sample.php
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="siswa_sample.csv"');
header('Cache-Control: max-age=0');

// Buat output
$output = fopen('php://output', 'w');

// BOM untuk UTF-8
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Header CSV (pemisah ;)
fputcsv($output, ['NISN', 'Nama Siswa', 'Kelas ID', 'Jenis Kelamin', 'Tempat Lahir', 'Tanggal Lahir', 'Alamat', 'No HP'], ';');

// Data sample
$samples = [
    ['1234567890', 'Ahmad Fauzi', '1', 'L', 'Jakarta', '2005-01-15', 'Jl. Merdeka No. 45 Jakarta Pusat', '081234567890'],
    ['1234567891', 'Siti Rahayu', '1', 'P', 'Bandung', '2005-02-20', 'Jl. Asia Afrika No. 12 Bandung', '081234567891'],
    ['1234567892', 'Budi Santoso', '2', 'L', 'Surabaya', '2005-03-10', 'Jl. Tunjungan No. 78 Surabaya', '081234567892'],
    ['1234567893', 'Dewi Anggraini', '2', 'P', 'Yogyakarta', '2005-04-05', 'Jl. Malioboro No. 34 Yogyakarta', '081234567893'],
    ['1234567894', 'Eko Prasetyo', '3', 'L', 'Semarang', '2005-05-12', 'Jl. Pandanaran No. 56 Semarang', '081234567894'],
    ['1234567895', 'Fitri Handayani', '3', 'P', 'Medan', '2005-06-18', 'Jl. Sudirman No. 78 Medan', '081234567895'],
    ['1234567896', 'Gilang Ramadhan', '4', 'L', 'Makassar', '2005-07-22', 'Jl. Ahmad Yani No. 90 Makassar', '081234567896'],
    ['1234567897', 'Hana Permata', '4', 'P', 'Palembang', '2005-08-30', 'Jl. Veteran No. 23 Palembang', '081234567897'],
    ['1234567898', 'Indra Wijaya', '5', 'L', 'Balikpapan', '2005-09-14', 'Jl. Diponegoro No. 45 Balikpapan', '081234567898'],
    ['1234567899', 'Jihan Safitri', '5', 'P', 'Manado', '2005-10-25', 'Jl. Sam Ratulangi No. 67 Manado', '081234567899']
];

foreach ($samples as $row) {
    fputcsv($output, $row, ';');
}

fclose($output);
exit;
?>