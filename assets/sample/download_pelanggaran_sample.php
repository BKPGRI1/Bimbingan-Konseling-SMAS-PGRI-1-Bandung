<?php
// assets/sample/download_pelanggaran_sample.php
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="pelanggaran_sample.csv"');
header('Cache-Control: max-age=0');

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Header CSV (pemisah ;)
fputcsv($output, ['Nama Pelanggaran', 'Poin', 'Kategori', 'Status'], ';');

// Data sample
$samples = [
    ['Terlambat', '10', 'ringan', '1'],
    ['Tidak memakai seragam', '15', 'ringan', '1'],
    ['Membawa HP', '20', 'sedang', '1'],
    ['Merokok', '50', 'berat', '1'],
    ['Berkelahi', '75', 'berat', '1'],
    ['Mencontek', '30', 'sedang', '1'],
    ['Tidak mengerjakan PR', '5', 'ringan', '1'],
    ['Keluar kelas tanpa izin', '25', 'sedang', '1'],
    ['Membawa senjata tajam', '100', 'berat', '0'],
    ['Merusak fasilitas sekolah', '60', 'berat', '1']
];

foreach ($samples as $row) {
    fputcsv($output, $row, ';');
}

fclose($output);
exit;
?>