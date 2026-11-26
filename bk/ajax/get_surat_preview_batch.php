<?php
// bk/ajax/get_surat_preview_batch.php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

// Cek login
if (!isLoggedIn()) {
    exit('Akses ditolak!');
}

// Cek role (hanya BK)
if (!hasRole(['bk'])) {
    exit('Akses ditolak!');
}

// Get parameters
$ids = isset($_GET['ids']) ? $_GET['ids'] : '';
if (empty($ids)) {
    echo '<div class="alert alert-danger">Tidak ada siswa dipilih!</div>';
    exit;
}

$id_array = explode(',', $ids);
$html = '';

$batas = getBatasPoin();

foreach ($id_array as $id) {
    $id = (int)$id;
    if ($id == 0) continue;
    
    $siswa = getSiswaById($id);
    if (!$siswa) continue;
    
    $total_poin = getTotalPoinSiswa($id);
    
    // Tentukan jenis surat berdasarkan poin
    if ($total_poin >= $batas['do']) {
        $jenis = 'DO';
    } elseif ($total_poin >= $batas['pemanggilan']) {
        $jenis = 'Pemanggilan';
    } elseif ($total_poin >= $batas['peringatan']) {
        $jenis = 'Teguran';
    } else {
        continue; // Skip jika tidak bermasalah
    }
    
    // Generate surat
    $pengaturan = getPengaturanSurat();
    $surat = generateSuratPreview($siswa, $jenis, $pengaturan, $total_poin);
    $html .= $surat;
    $html .= '<hr style="border: 2px dashed #ccc; margin: 20px 0;">';
}

if (empty($html)) {
    echo '<div class="alert alert-warning">Tidak ada siswa yang memenuhi kriteria untuk dicetak surat!</div>';
} else {
    echo $html;
}

function generateSuratPreview($siswa, $jenis_surat, $pengaturan, $poin) {
    $tanggal = date('d F Y');
    $kelas = $siswa['nama_kelas'] ?? 'Belum ditentukan';
    
    if ($jenis_surat == 'Teguran' || $jenis_surat == 'teguran') {
        $pembukaan = $pengaturan['pembukaan_teguran'] ?? 'Kepada Yth. Bapak/Ibu Orang Tua/Wali dari siswa {nama_siswa} di tempat.';
        $isi = $pengaturan['isi_teguran'] ?? 'Berdasarkan data pelanggaran tata tertib sekolah yang tercatat, {nama_siswa} dengan NISN {nisn} dari kelas {kelas} telah melakukan beberapa pelanggaran dengan total akumulasi poin sebanyak {poin} poin. Sehubungan dengan hal tersebut, sekolah memberikan Surat Teguran ini sebagai bentuk pembinaan agar siswa yang bersangkutan dapat memperbaiki sikap dan tidak mengulangi pelanggaran yang sama di kemudian hari. Kami mengharapkan kerja sama dari orang tua/wali untuk turut melakukan pembinaan dan pengawasan terhadap putra/putrinya.';
        $penutup = $pengaturan['penutup_teguran'] ?? 'Demikian surat teguran ini kami sampaikan. Atas perhatian dan kerja sama Bapak/Ibu, kami ucapkan terima kasih.';
        $judul = 'SURAT TEGURAN';
        $nomor_surat = $pengaturan['nomor_surat'] ?? '001/TEG/2024';
    } elseif ($jenis_surat == 'Pemanggilan' || $jenis_surat == 'pemanggilan') {
        $pembukaan = $pengaturan['pembukaan_pemanggilan'] ?? 'Kepada Yth. Bapak/Ibu Orang Tua/Wali dari siswa {nama_siswa} di tempat.';
        $isi = $pengaturan['isi_pemanggilan'] ?? 'Dengan ini kami mengundang Bapak/Ibu untuk hadir di sekolah pada hari {tanggal_pemanggilan} pukul {jam_pemanggilan} untuk membahas masalah pelanggaran yang dilakukan oleh {nama_siswa} dengan NISN {nisn} dari kelas {kelas} yang telah mencapai total poin {poin}. Kehadiran Bapak/Ibu sangat kami harapkan untuk bersama-sama mencari solusi terbaik bagi putra/putri Bapak/Ibu.';
        $penutup = $pengaturan['penutup_pemanggilan'] ?? 'Demikian surat pemanggilan ini kami sampaikan. Atas perhatian dan kehadiran Bapak/Ibu, kami ucapkan terima kasih.';
        $judul = 'SURAT PEMANGGILAN';
        $nomor_surat = $pengaturan['nomor_surat'] ?? '001/PGL/2024';
    } else {
        $pembukaan = 'Kepada Yth. Bapak/Ibu Orang Tua/Wali dari siswa {nama_siswa} di tempat.';
        $isi = 'Berdasarkan data pelanggaran tata tertib sekolah, {nama_siswa} telah mencapai batas poin maksimal. Sehubungan dengan hal tersebut, sekolah memberikan Surat Peringatan DO.';
        $penutup = 'Demikian surat ini kami sampaikan.';
        $judul = 'SURAT PERINGATAN DO';
        $nomor_surat = $pengaturan['nomor_surat'] ?? '001/DO/2024';
    }
    
    // Ganti placeholder
    $pembukaan = str_replace('{nama_siswa}', $siswa['nama_siswa'], $pembukaan);
    $pembukaan = str_replace('{nisn}', $siswa['nisn'], $pembukaan);
    $pembukaan = str_replace('{kelas}', $kelas, $pembukaan);
    $pembukaan = str_replace('{poin}', $poin, $pembukaan);
    $pembukaan = str_replace('{tanggal}', $tanggal, $pembukaan);
    
    $isi = str_replace('{nama_siswa}', $siswa['nama_siswa'], $isi);
    $isi = str_replace('{nisn}', $siswa['nisn'], $isi);
    $isi = str_replace('{kelas}', $kelas, $isi);
    $isi = str_replace('{poin}', $poin, $isi);
    $isi = str_replace('{tanggal}', $tanggal, $isi);
    $isi = str_replace('{tanggal_pemanggilan}', date('d F Y', strtotime('+3 days')), $isi);
    $isi = str_replace('{jam_pemanggilan}', '09:00 WIB', $isi);
    
    $penutup = str_replace('{nama_siswa}', $siswa['nama_siswa'], $penutup);
    
    $html = '
    <div class="surat-preview">
        <div class="text-center mb-3">
            <h5 class="text-uppercase fw-bold">' . $judul . '</h5>
            <p><strong>Nomor:</strong> ' . $nomor_surat . '</p>
            <p><strong>Tanggal:</strong> ' . $tanggal . '</p>
            <p><strong>Untuk:</strong> ' . $siswa['nama_siswa'] . ' (' . $siswa['nisn'] . ') - Kelas ' . $kelas . '</p>
        </div>
        <hr>
        <div class="mt-3">
            <p>' . nl2br($pembukaan) . '</p>
            <p>' . nl2br($isi) . '</p>
            <p>' . nl2br($penutup) . '</p>
        </div>
        <div class="text-end mt-4">
            <p>Hormat kami,</p>
            <p><strong>' . ($pengaturan['ttd_kepala_sekolah_nama'] ?? $pengaturan['kepala_sekolah'] ?? 'Kepala Sekolah') . '</strong></p>
            <p>NIP. ' . ($pengaturan['nip_kepala_sekolah'] ?? '-') . '</p>
        </div>
    </div>
    <style>
        .surat-preview {
            font-family: "Times New Roman", Times, serif;
            font-size: 13px;
            line-height: 1.8;
            padding: 10px;
        }
        .surat-preview h5 {
            font-size: 16px;
        }
        .surat-preview hr {
            border: 1px solid #000;
            margin: 10px 0;
        }
    </style>
    ';
    
    return $html;
}
?>