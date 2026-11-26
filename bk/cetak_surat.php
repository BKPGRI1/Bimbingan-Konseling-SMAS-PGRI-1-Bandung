<?php
// bk/cetak_surat.php - VERSI FINAL
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Cek login
if (!isLoggedIn()) {
    header("Location: ../login.php");
    exit;
}

// Cek role (hanya BK)
if (!hasRole(['bk'])) {
    header("Location: ../dashboard.php");
    exit;
}

// Get parameters
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$jenis_surat = isset($_GET['jenis']) ? $_GET['jenis'] : '';
$surat_id = isset($_GET['surat_id']) ? (int)$_GET['surat_id'] : 0;

if ($surat_id > 0) {
    $surat = getSuratMenegById($surat_id);
    if ($surat) {
        $id = $surat['siswa_id'];
        $jenis_surat = $surat['jenis_surat'];
        $isi_surat_db = $surat['isi_surat'];
        $nomor_surat_db = $surat['nomor_surat'];
        $tanggal_surat_db = $surat['tanggal_surat'];
    }
}

if ($id == 0 || empty($jenis_surat)) {
    die("ID siswa atau jenis surat tidak valid!");
}

// Ambil data siswa
$siswa = getSiswaById($id);
if (!$siswa) {
    die("Data siswa tidak ditemukan!");
}

// Ambil pengaturan surat
$conn = getDB();

// Cek dan tambahkan kolom jika diperlukan
$required_columns = [
    'alamat_sekolah' => 'TEXT NULL',
    'no_telp_sekolah' => 'VARCHAR(20) NULL',
    'email_sekolah' => 'VARCHAR(100) NULL',
    'website_sekolah' => 'VARCHAR(100) NULL',
    'kementrian' => 'VARCHAR(255) NULL',
    'logo_kiri' => 'VARCHAR(255) NULL',
    'logo_kanan' => 'VARCHAR(255) NULL',
    'stempel_sekolah' => 'VARCHAR(255) NULL',
    'kepala_sekolah' => 'VARCHAR(255) NULL',
    'nip_kepala_sekolah' => 'VARCHAR(50) NULL',
    'ttd_kepala_sekolah' => 'VARCHAR(255) NULL',
    'ttd_kepala_sekolah_nama' => 'VARCHAR(255) NULL',
    'nomor_surat' => 'VARCHAR(50) NULL',
    'pembukaan_teguran' => 'TEXT NULL',
    'isi_teguran' => 'TEXT NULL',
    'penutup_teguran' => 'TEXT NULL',
    'pembukaan_pemanggilan' => 'TEXT NULL',
    'isi_pemanggilan' => 'TEXT NULL',
    'penutup_pemanggilan' => 'TEXT NULL'
];

foreach ($required_columns as $col => $type) {
    $check_col = $conn->query("SHOW COLUMNS FROM pengaturan_surat LIKE '$col'");
    if (!$check_col || $check_col->num_rows == 0) {
        $conn->query("ALTER TABLE pengaturan_surat ADD COLUMN $col $type");
    }
}

$result = $conn->query("SELECT * FROM pengaturan_surat WHERE id = 1");
if ($result && $result->num_rows > 0) {
    $pengaturan = $result->fetch_assoc();
} else {
    $pengaturan = [
        'nama_sekolah' => 'SMA Viralytics_Shop',
        'alamat_sekolah' => 'Jl. Probolinggo No. 123, Probolinggo',
        'no_telp_sekolah' => '082131290542',
        'email_sekolah' => 'info@smaviralytics.sch.id',
        'website_sekolah' => 'www.smaviralytics.sch.id',
        'kementrian' => 'Kementerian Pendidikan, Kebudayaan, Riset, dan Teknologi',
        'logo_kiri' => '',
        'logo_kanan' => '',
        'stempel_sekolah' => '',
        'kepala_sekolah' => 'Dr. H. Ahmad Fauzi, M.Pd.',
        'nip_kepala_sekolah' => '196504251990011001',
        'ttd_kepala_sekolah' => '',
        'ttd_kepala_sekolah_nama' => 'Dr. H. Ahmad Fauzi, M.Pd.',
        'nomor_surat' => '001/TEG/2024',
        'pembukaan_teguran' => 'Kepada Yth. Bapak/Ibu Orang Tua/Wali Siswa',
        'isi_teguran' => "Berdasarkan hasil pengamatan dan data pelanggaran tata tertib sekolah yang tercatat, dengan ini kami sampaikan bahwa putra/putri Bapak/Ibu:\n\nNama Siswa    : {nama_siswa}\nNISN          : {nisn}\nKelas         : {kelas}\n\nTelah teridentifikasi melakukan pelanggaran tata tertib sekolah dengan total akumulasi poin sebesar {poin} poin.\n\nSehubungan dengan hal tersebut, sekolah memberikan Surat Teguran ini sebagai bentuk pembinaan dan peringatan awal. Kami berharap putra/putri Bapak/Ibu dapat memperbaiki sikap dan perilakunya serta tidak mengulangi pelanggaran di kemudian hari.\n\nKami sangat mengharapkan peran serta Bapak/Ibu untuk memberikan pengawasan dan bimbingan kepada putra/putri Bapak/Ibu di lingkungan keluarga.",
        'penutup_teguran' => 'Demikian Surat Teguran ini kami sampaikan. Atas perhatian dan kerja sama Bapak/Ibu, kami ucapkan terima kasih.',
        'pembukaan_pemanggilan' => 'Kepada Yth. Bapak/Ibu Orang Tua/Wali Siswa',
        'isi_pemanggilan' => "Berdasarkan data pelanggaran tata tertib sekolah yang tercatat, dengan ini kami mengundang Bapak/Ibu untuk dapat hadir di sekolah guna membahas permasalahan putra/putri Bapak/Ibu:\n\nNama Siswa    : {nama_siswa}\nNISN          : {nisn}\nKelas         : {kelas}\nTotal Poin    : {poin} poin\n\nUndangan ini kami sampaikan mengingat akumulasi poin pelanggaran putra/putri Bapak/Ibu telah mencapai batas yang memerlukan tindak lanjut dari orang tua/wali.\n\nKami mohon Bapak/Ibu dapat hadir pada:\nHari/Tanggal  : {tanggal_pemanggilan}\nWaktu         : {jam_pemanggilan}\nTempat        : Ruang Bimbingan Konseling (BK)\n\nKehadiran Bapak/Ibu sangat kami harapkan untuk bersama-sama mencari solusi terbaik dalam pembinaan dan pembentukan karakter putra/putri Bapak/Ibu.",
        'penutup_pemanggilan' => 'Demikian Surat Pemanggilan ini kami sampaikan. Atas perhatian dan kehadiran Bapak/Ibu, kami ucapkan terima kasih.'
    ];
}

// ============================================
// HITUNG TOTAL POIN - POIN BERSIH (PELANGGARAN - PRESTASI)
// ============================================

// Total poin pelanggaran
$query_pelanggaran = "SELECT SUM(poin_diperoleh) as total FROM pelanggaran WHERE siswa_id = $id AND status = 'disetujui'";
$result_pelanggaran = $conn->query($query_pelanggaran);
$total_pelanggaran = 0;
if ($result_pelanggaran && $result_pelanggaran->num_rows > 0) {
    $row = $result_pelanggaran->fetch_assoc();
    $total_pelanggaran = (int)$row['total'];
}

// Total poin prestasi (pengurang)
$query_prestasi = "SELECT SUM(poin_prestasi) as total FROM prestasi_siswa WHERE siswa_id = $id";
$result_prestasi = $conn->query($query_prestasi);
$total_prestasi = 0;
if ($result_prestasi && $result_prestasi->num_rows > 0) {
    $row = $result_prestasi->fetch_assoc();
    $total_prestasi = (int)$row['total'];
}

// Poin bersih = pelanggaran - prestasi
$total_poin = $total_pelanggaran - $total_prestasi;
if ($total_poin < 0) $total_poin = 0;

// Debug - jika masih 0, ambil dari tabel siswa
if ($total_poin == 0) {
    $total_poin = (int)$siswa['poin'];
}

$tanggal = date('d F Y');
$kelas = $siswa['nama_kelas'] ?? 'Belum ditentukan';

// Generate nomor surat otomatis
if (!isset($nomor_surat_db) || empty($nomor_surat_db)) {
    if ($jenis_surat == 'Teguran' || $jenis_surat == 'teguran') {
        $nomor_surat = $pengaturan['nomor_surat'] ?? '001/TEG/2024';
    } elseif ($jenis_surat == 'Pemanggilan' || $jenis_surat == 'pemanggilan') {
        $nomor_surat = '001/PGL/' . date('Y');
    } elseif ($jenis_surat == 'Peringatan' || $jenis_surat == 'peringatan' || $jenis_surat == 'DO') {
        $nomor_surat = '001/DO/' . date('Y');
    } else {
        $nomor_surat = '001/SRT/' . date('Y');
    }
} else {
    $nomor_surat = $nomor_surat_db;
}

// Generate isi surat
if (!isset($isi_surat_db) || empty($isi_surat_db)) {
    if ($jenis_surat == 'Teguran' || $jenis_surat == 'teguran') {
        $judul = 'SURAT TEGURAN';
        $isi_template = $pengaturan['isi_teguran'] ?? '';
        $penutup = $pengaturan['penutup_teguran'] ?? '';
    } elseif ($jenis_surat == 'Pemanggilan' || $jenis_surat == 'pemanggilan') {
        $judul = 'SURAT PEMANGGILAN ORANG TUA/WALI';
        $isi_template = $pengaturan['isi_pemanggilan'] ?? '';
        $penutup = $pengaturan['penutup_pemanggilan'] ?? '';
    } else {
        $judul = 'SURAT PERINGATAN';
        $isi_template = "Berdasarkan data pelanggaran tata tertib sekolah, {nama_siswa} telah mencapai batas poin maksimal. Sehubungan dengan hal tersebut, sekolah memberikan Surat Peringatan DO.";
        $penutup = 'Demikian surat ini kami sampaikan.';
    }

    // Replace placeholders
    $isi = str_replace('{nama_siswa}', $siswa['nama_siswa'], $isi_template);
    $isi = str_replace('{nisn}', $siswa['nisn'], $isi);
    $isi = str_replace('{kelas}', $kelas, $isi);
    $isi = str_replace('{poin}', $total_poin, $isi);
    $isi = str_replace('{poin_pelanggaran}', $total_pelanggaran, $isi);
    $isi = str_replace('{poin_prestasi}', $total_prestasi, $isi);
    $isi = str_replace('{tanggal}', $tanggal, $isi);
    $isi = str_replace('{tanggal_pemanggilan}', date('l, d F Y', strtotime('+3 days')), $isi);
    $isi = str_replace('{jam_pemanggilan}', '09:00 - 12:00 WIB', $isi);
    
    $isi_surat = $isi . "\n\n" . $penutup;
} else {
    // Replace placeholders di isi surat dari database
    $isi_surat = str_replace('{nama_siswa}', $siswa['nama_siswa'], $isi_surat_db);
    $isi_surat = str_replace('{nisn}', $siswa['nisn'], $isi_surat);
    $isi_surat = str_replace('{kelas}', $kelas, $isi_surat);
    $isi_surat = str_replace('{poin}', $total_poin, $isi_surat);
    $isi_surat = str_replace('{poin_pelanggaran}', $total_pelanggaran, $isi_surat);
    $isi_surat = str_replace('{poin_prestasi}', $total_prestasi, $isi_surat);
    $isi_surat = str_replace('{tanggal}', $tanggal, $isi_surat);
    $isi_surat = str_replace('{tanggal_pemanggilan}', date('l, d F Y', strtotime('+3 days')), $isi_surat);
    $isi_surat = str_replace('{jam_pemanggilan}', '09:00 - 12:00 WIB', $isi_surat);
    
    $judul = isset($surat['jenis_surat']) ? 'SURAT ' . strtoupper($surat['jenis_surat']) : 'SURAT';
    $penutup = '';
}

// Path logo dan stempel
$logo_kiri_file = $pengaturan['logo_kiri'] ?? '';
$logo_kanan_file = $pengaturan['logo_kanan'] ?? '';
$stempel_file = $pengaturan['stempel_sekolah'] ?? '';
$ttd_file = $pengaturan['ttd_kepala_sekolah'] ?? '';

$logo_kiri_abs = APP_PATH . 'uploads/logo/' . $logo_kiri_file;
$logo_kanan_abs = APP_PATH . 'uploads/logo/' . $logo_kanan_file;
$stempel_abs = APP_PATH . 'uploads/surat/' . $stempel_file;
$ttd_abs = APP_PATH . 'uploads/surat/' . $ttd_file;

$logo_kiri_exists = !empty($logo_kiri_file) && file_exists($logo_kiri_abs);
$logo_kanan_exists = !empty($logo_kanan_file) && file_exists($logo_kanan_abs);
$stempel_exists = !empty($stempel_file) && file_exists($stempel_abs);
$ttd_exists = !empty($ttd_file) && file_exists($ttd_abs);

// Jika diminta cetak PDF
if (isset($_GET['cetak']) && $_GET['cetak'] == 'pdf') {
    require_once('../tcpdf/tcpdf.php');
    
    class MYPDF extends TCPDF {
        public function Header() {}
        public function Footer() {}
    }
    
    $pdf = new MYPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('Tatib Sekolah');
    $pdf->SetAuthor($pengaturan['nama_sekolah'] ?? 'SMA Negeri 1 Tatib');
    $pdf->SetTitle('Surat ' . ucfirst($jenis_surat) . ' - ' . $siswa['nama_siswa']);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(20, 10, 20);
    $pdf->AddPage();
    
    // ============================================
    // KOP SURAT
    // ============================================
    
    $y_start = 15;
    $pdf->SetY($y_start);
    
    if ($logo_kiri_exists) {
        $pdf->Image($logo_kiri_abs, 20, $y_start, 25, 25);
    }
    
    $pdf->SetY($y_start + 1);
    
    if (!empty($pengaturan['kementrian'])) {
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(0, 4, strtoupper($pengaturan['kementrian']), 0, 1, 'C');
    }
    
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 6, strtoupper($pengaturan['nama_sekolah'] ?? 'SMA NEGERI 1 TATIB'), 0, 1, 'C');
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 4, $pengaturan['alamat_sekolah'] ?? '', 0, 1, 'C');
    
    $pdf->SetFont('helvetica', '', 9);
    $telp = $pengaturan['no_telp_sekolah'] ?? '';
    $email = $pengaturan['email_sekolah'] ?? '';
    $info = '';
    if (!empty($telp)) $info .= 'Telp: ' . $telp;
    if (!empty($email)) $info .= ' | Email: ' . $email;
    $pdf->Cell(0, 4, $info, 0, 1, 'C');
    
    $website = $pengaturan['website_sekolah'] ?? '';
    if (!empty($website)) {
        $pdf->Cell(0, 4, 'Website: ' . $website, 0, 1, 'C');
    }
    
    if ($logo_kanan_exists) {
        $pdf->Image($logo_kanan_abs, 165, $y_start, 25, 25);
    }
    
    $y_after = $pdf->GetY() + 5;
    $pdf->SetDrawColor(0, 0, 0);
    $pdf->SetLineWidth(1.2);
    $pdf->Line(20, $y_after, 190, $y_after);
    $pdf->SetLineWidth(0.4);
    $pdf->Line(20, $y_after + 2.5, 190, $y_after + 2.5);
    $pdf->SetY($y_after + 10);
    
    // ============================================
    // JUDUL SURAT
    // ============================================
    
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 7, strtoupper($judul), 0, 1, 'C');
    $pdf->Ln(3);
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 5, 'Nomor: ' . $nomor_surat, 0, 1, 'L');
    $pdf->Cell(0, 5, 'Lampiran: -', 0, 1, 'L');
    $pdf->Cell(0, 5, 'Perihal: ' . ucfirst($jenis_surat), 0, 1, 'L');
    $pdf->Ln(5);
    
    // ============================================
    // ALAMAT SURAT
    // ============================================
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->MultiCell(0, 5, 'Kepada Yth. Bapak/Ibu Orang Tua/Wali dari', 0, 'L');
    $pdf->MultiCell(0, 5, $siswa['nama_siswa'], 0, 'L');
    $pdf->MultiCell(0, 5, 'NISN: ' . $siswa['nisn'] . ' | Kelas: ' . $kelas, 0, 'L');
    $pdf->MultiCell(0, 5, 'di Tempat', 0, 'L');
    $pdf->Ln(5);
    
    // ============================================
    // ISI SURAT
    // ============================================
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->MultiCell(0, 5, 'Dengan hormat,', 0, 'L');
    $pdf->Ln(2);
    
    // Parse isi surat (support newline)
    $isi_lines = explode("\n", $isi_surat);
    foreach ($isi_lines as $line) {
        if (trim($line) == '') {
            $pdf->Ln(2);
        } else {
            $pdf->MultiCell(0, 5, $line, 0, 'L');
        }
    }
    $pdf->Ln(10);
    
    // ============================================
    // TANDA TANGAN - RATA KANAN
    // ============================================
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 5, 'Hormat kami,', 0, 1, 'R');
    $pdf->Cell(0, 5, 'Kepala Sekolah', 0, 1, 'R');
    $pdf->Ln(10);
    
    $ttd_y = $pdf->GetY();
    
    if ($ttd_exists) {
        $pdf->Image($ttd_abs, 150, $ttd_y, 40, 15);
    }
    
    if ($stempel_exists) {
        $pdf->Image($stempel_abs, 130, $ttd_y - 2, 25, 25);
    }
    
    $pdf->SetY($ttd_y + 22);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 5, $pengaturan['ttd_kepala_sekolah_nama'] ?? $pengaturan['kepala_sekolah'] ?? 'Kepala Sekolah', 0, 1, 'R');
    $pdf->SetFont('helvetica', '', 9);
    $pdf->Cell(0, 4, 'NIP. ' . ($pengaturan['nip_kepala_sekolah'] ?? '-'), 0, 1, 'R');
    
    // Footer
    $pdf->Ln(3);
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->Cell(0, 4, 'Dicetak pada: ' . date('d-m-Y H:i:s'), 0, 1, 'C');
    
    $filename = 'Surat_' . ucfirst($jenis_surat) . '_' . $siswa['nama_siswa'] . '_' . date('Ymd') . '.pdf';
    $pdf->Output($filename, 'I');
    exit;
}

// ============================================
// PREVIEW SURAT (HTML)
// ============================================
$page_title = 'Preview Surat - ' . ucfirst($jenis_surat);
include __DIR__ . '/../includes/header_bk.php';
?>

<div class="container-fluid dashboard-container">
    <div class="dashboard-header">
        <div>
            <h4 class="dashboard-title">
                <i class="fas fa-file-pdf text-primary me-2"></i> Preview Surat <?= ucfirst($jenis_surat) ?>
            </h4>
            <small class="text-muted dashboard-subtitle">
                <i class="fas fa-print me-1"></i> Preview dan cetak surat
            </small>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="<?= APP_URL ?>bk/siswa_bermasalah.php" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Kembali
            </a>
            <a href="?id=<?= $id ?>&jenis=<?= $jenis_surat ?>&cetak=pdf" target="_blank" class="btn btn-primary btn-sm">
                <i class="fas fa-print me-1"></i> Cetak PDF
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="fas fa-file-alt me-2"></i> Surat <?= ucfirst($jenis_surat) ?>
            <span class="info-badge"><?= $nomor_surat ?? '' ?></span>
        </div>
        <div class="card-body">
            <div class="surat-container">
                <!-- Kop Surat -->
                <div class="kop-surat">
                    <div class="row align-items-center">
                        <div class="col-2 text-center">
                            <?php if ($logo_kiri_exists): ?>
                                <img src="<?= APP_URL ?>uploads/logo/<?= $logo_kiri_file ?>" alt="Logo Kiri" style="max-height: 70px; max-width: 100%;">
                            <?php endif; ?>
                        </div>
                        <div class="col-8 text-center">
                            <?php if (!empty($pengaturan['kementrian'])): ?>
                                <p class="mb-0" style="font-size:11px; font-weight:bold;"><?= strtoupper($pengaturan['kementrian']) ?></p>
                            <?php endif; ?>
                            <h4 class="mb-0"><?= strtoupper($pengaturan['nama_sekolah'] ?? 'SMA NEGERI 1 TATIB') ?></h4>
                            <p class="mb-0"><?= $pengaturan['alamat_sekolah'] ?? '' ?></p>
                            <small>
                                <?php 
                                if (!empty($pengaturan['no_telp_sekolah'])) echo 'Telp: ' . $pengaturan['no_telp_sekolah'];
                                if (!empty($pengaturan['email_sekolah'])) echo ' | Email: ' . $pengaturan['email_sekolah'];
                                ?>
                            </small>
                            <?php if (!empty($pengaturan['website_sekolah'])): ?>
                                <div><small>Website: <?= $pengaturan['website_sekolah'] ?></small></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-2 text-center">
                            <?php if ($logo_kanan_exists): ?>
                                <img src="<?= APP_URL ?>uploads/logo/<?= $logo_kanan_file ?>" alt="Logo Kanan" style="max-height: 70px; max-width: 100%;">
                            <?php endif; ?>
                        </div>
                    </div>
                    <hr class="kop-garis">
                </div>

                <!-- Isi Surat -->
                <div class="isi-surat">
                    <h5 class="text-center text-uppercase fw-bold"><?= $judul ?? 'SURAT' ?></h5>
                    
                    <div class="row mt-3">
                        <div class="col-6">
                            <p><strong>Nomor:</strong> <?= $nomor_surat ?? '' ?></p>
                        </div>
                        <div class="col-6">
                            <p><strong>Tanggal:</strong> <?= $tanggal ?></p>
                        </div>
                    </div>
                    <p><strong>Lampiran:</strong> -</p>
                    <p><strong>Perihal:</strong> <?= ucfirst($jenis_surat) ?></p>

                    <div class="mt-4">
                        <p><strong>Kepada Yth. Bapak/Ibu Orang Tua/Wali dari</strong></p>
                        <p style="font-weight: bold; font-size: 14px;"><?= $siswa['nama_siswa'] ?></p>
                        <p>NISN: <?= $siswa['nisn'] ?> | Kelas: <?= $kelas ?></p>
                        <p><strong>di Tempat</strong></p>
                    </div>

                    <div class="mt-4" style="text-align: justify;">
                        <p>Dengan hormat,</p>
                        <div style="white-space: pre-wrap; text-align: justify; font-size: 13px; line-height: 1.8;"><?= nl2br(htmlspecialchars($isi_surat)) ?></div>
                    </div>

                    <div class="mt-5 text-end">
                        <p>Hormat kami,</p>
                        <p><strong>Kepala Sekolah</strong></p>
                        <div class="mt-2 d-flex justify-content-end align-items-center">
                            <?php if ($stempel_exists): ?>
                                <img src="<?= APP_URL ?>uploads/surat/<?= $stempel_file ?>" alt="Stempel" style="max-height: 50px; margin-right: -10px;">
                            <?php endif; ?>
                            <?php if ($ttd_exists): ?>
                                <img src="<?= APP_URL ?>uploads/surat/<?= $ttd_file ?>" alt="Tanda Tangan" style="max-height: 40px; margin-left: 0px;">
                            <?php endif; ?>
                        </div>
                        <p><strong><?= $pengaturan['ttd_kepala_sekolah_nama'] ?? $pengaturan['kepala_sekolah'] ?? 'Kepala Sekolah' ?></strong></p>
                        <p>NIP. <?= $pengaturan['nip_kepala_sekolah'] ?? '-' ?></p>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer text-muted">
            <i class="fas fa-info-circle me-1"></i> Dicetak pada: <?= date('d-m-Y H:i:s') ?>
            <br>
            <small>Total Poin Pelanggaran: <?= $total_pelanggaran ?> | Total Poin Prestasi: <?= $total_prestasi ?> | Poin Bersih: <?= $total_poin ?></small>
        </div>
    </div>
</div>

<style>
.dashboard-container { padding: 12px !important; max-width: 100%; overflow-x: hidden; }
.dashboard-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; margin-bottom: 16px; gap: 8px; }
.dashboard-title { font-size: 1.1rem; margin-bottom: 0; }
.dashboard-subtitle { font-size: 0.7rem; }

.surat-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 30px 40px;
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    font-family: 'Times New Roman', Times, serif;
}

.kop-surat {
    border-bottom: 2px solid #000;
    padding-bottom: 10px;
    margin-bottom: 15px;
}
.kop-surat h4 { font-size: 18px; font-weight: bold; margin-bottom: 2px; }
.kop-surat p { font-size: 12px; margin-bottom: 2px; }
.kop-surat small { font-size: 11px; }
.kop-surat .kop-garis { border: none; border-top: 2px solid #000; margin: 5px 0; }

.isi-surat { font-size: 13px; line-height: 1.8; }
.isi-surat h5 { font-size: 16px; margin-bottom: 20px; }
.isi-surat p { margin-bottom: 5px; }
.isi-surat .row { display: flex; flex-wrap: wrap; }
.isi-surat .col-6 { width: 50%; float: left; }

.card { border: none; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.06); margin-bottom: 16px; background: #fff; }
.card-header { background: transparent; border-bottom: 1px solid #e9ecef; padding: 10px 14px; font-weight: 600; font-size: 12px; display: flex; align-items: center; flex-wrap: wrap; gap: 4px; }
.card-body { padding: 14px; background: #f8f9fa; }
.card-footer { background: transparent; border-top: 1px solid #e9ecef; padding: 8px 14px; font-size: 11px; }
.badge { font-size: 9px !important; padding: 2px 6px !important; }
.info-badge { font-size: 10px; color: #718096; margin-left: 4px; }
.btn-sm { padding: 2px 10px !important; font-size: 11px !important; border-radius: 4px !important; }

@media (max-width: 768px) {
    .dashboard-container { padding: 8px !important; }
    .dashboard-header { flex-direction: column; align-items: flex-start; gap: 4px; margin-bottom: 10px; }
    .dashboard-title { font-size: 1rem; }
    .surat-container { padding: 15px; }
    .kop-surat h4 { font-size: 14px; }
    .isi-surat { font-size: 12px; }
    .isi-surat h5 { font-size: 14px; }
    .btn-primary, .btn-secondary { padding: 6px 14px; font-size: 12px; width: 100%; }
    .d-flex.justify-content-between { flex-direction: column; gap: 6px; }
    .d-flex.justify-content-between > div { display: flex; flex-direction: column; gap: 6px; width: 100%; }
    .d-flex.justify-content-between > div .btn { width: 100%; }
    .surat-container .row .col-2 { width: 20%; }
    .surat-container .row .col-8 { width: 60%; }
}

@media (max-width: 576px) {
    .dashboard-container { padding: 4px !important; }
    .surat-container { padding: 10px; }
    .kop-surat h4 { font-size: 12px; }
    .isi-surat { font-size: 11px; }
    .isi-surat h5 { font-size: 12px; }
    .card-body { padding: 8px; }
    .isi-surat .col-6 { width: 100%; float: none; }
    .surat-container .row .col-2 { width: 25%; }
    .surat-container .row .col-8 { width: 50%; }
    .surat-container .row .col-2 img { max-height: 40px; }
}
</style>

<?php include '../includes/footer.php'; ?>