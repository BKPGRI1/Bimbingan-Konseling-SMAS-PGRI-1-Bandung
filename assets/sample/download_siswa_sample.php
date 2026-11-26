<?php
// assets/sample/download_siswa_sample.php
// File untuk download sample CSV siswa

// Set header untuk download file CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="sample_siswa.csv"');

// Buat file CSV
$output = fopen('php://output', 'w');

// Tambahkan BOM untuk UTF-8
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Header CSV
fputcsv($output, [
    'NISN',
    'Nama Siswa',
    'Kelas ID',
    'Jenis Kelamin',
    'Tempat Lahir',
    'Tanggal Lahir',
    'Alamat',
    'No HP'
], ';');

// Data sample (5 siswa)
$samples = [
    [
        '1234567890',
        'Ahmad Fauzi',
        '3',
        'L',
        'Jakarta',
        '2005-01-15',
        'Jl. Merdeka No. 10, Jakarta',
        '081234567890'
    ],
    [
        '1234567891',
        'Siti Rahayu',
        '3',
        'P',
        'Bandung',
        '2005-03-20',
        'Jl. Asia Afrika No. 12, Bandung',
        '081234567891'
    ],
    [
        '1234567892',
        'Budi Santoso',
        '3',
        'L',
        'Surabaya',
        '2005-07-08',
        'Jl. Tunjungan No. 78, Surabaya',
        '081234567892'
    ],
    [
        '1234567893',
        'Dewi Anggraini',
        '3',
        'P',
        'Yogyakarta',
        '2005-11-25',
        'Jl. Malioboro No. 34, Yogyakarta',
        '081234567893'
    ],
    [
        '1234567894',
        'Eko Prasetyo',
        '3',
        'L',
        'Semarang',
        '2005-09-12',
        'Jl. Pandanaran No. 56, Semarang',
        '081234567894'
    ]
];

foreach ($samples as $row) {
    fputcsv($output, $row, ';');
}

fclose($output);
exit;
?>