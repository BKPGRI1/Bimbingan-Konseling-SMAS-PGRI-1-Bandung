<?php
// assets/sample/download_guru_sample.php
// File untuk download sample CSV guru

// Tidak perlu include config karena ini hanya download file
// Set header untuk download file CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="sample_guru.csv"');

// Buat file CSV
$output = fopen('php://output', 'w');

// Tambahkan BOM untuk UTF-8
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Header CSV
fputcsv($output, [
    'Username',
    'Password',
    'Nama Lengkap',
    'NIP',
    'Role',
    'Jabatan',
    'No HP'
], ';');

// Data sample (5 guru)
$samples = [
    [
        'guru1',
        'password123',
        'Dr. Ahmad Fauzi, M.Pd.',
        '197501011999031001',
        'guru',
        'Guru Matematika',
        '081234567890'
    ],
    [
        'guru2',
        'password123',
        'Dra. Siti Rahayu, M.Si.',
        '197503151999032002',
        'guru',
        'Guru IPA',
        '081234567891'
    ],
    [
        'guru3',
        'password123',
        'Drs. Budi Santoso, M.Pd.',
        '197507201999031003',
        'walikelas',
        'Wali Kelas 7A',
        '081234567892'
    ],
    [
        'bk1',
        'password123',
        'Dewi Anggraini, S.Psi.',
        '197511251999032004',
        'bk',
        'Guru BK',
        '081234567893'
    ],
    [
        'guru4',
        'password123',
        'Eko Prasetyo, S.Pd.',
        '197509121999031005',
        'guru',
        'Guru Bahasa Inggris',
        '081234567894'
    ]
];

foreach ($samples as $row) {
    fputcsv($output, $row, ';');
}

fclose($output);
exit;
?>