<?php
// bk/surat_meneg.php - VERSI DIPERBAIKI (TANPA RESET TABEL)
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// Cek login
if (!isLoggedIn()) {
    header("Location: ../login.php");
    exit;
}

if (!hasRole(['bk'])) {
    header("Location: ../dashboard.php");
    exit;
}

$conn = getDB();

// ============================================
// CEK TABEL - BUAT JIKA BELUM ADA (TANPA DROP)
// ============================================
$table_check = $conn->query("SHOW TABLES LIKE 'pengaturan_surat'");
if ($table_check->num_rows == 0) {
    $sql = "CREATE TABLE `pengaturan_surat` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `nama_sekolah` varchar(255) DEFAULT NULL,
        `alamat_sekolah` text,
        `no_telp_sekolah` varchar(20) DEFAULT NULL,
        `email_sekolah` varchar(100) DEFAULT NULL,
        `website_sekolah` varchar(100) DEFAULT NULL,
        `kementrian` varchar(255) DEFAULT NULL,
        `logo_kiri` varchar(255) DEFAULT NULL,
        `logo_kanan` varchar(255) DEFAULT NULL,
        `stempel_sekolah` varchar(255) DEFAULT NULL,
        `kepala_sekolah` varchar(255) DEFAULT NULL,
        `nip_kepala_sekolah` varchar(50) DEFAULT NULL,
        `ttd_kepala_sekolah` varchar(255) DEFAULT NULL,
        `ttd_kepala_sekolah_nama` varchar(255) DEFAULT NULL,
        `nomor_surat` varchar(50) DEFAULT NULL,
        `pembukaan_teguran` text,
        `isi_teguran` text,
        `penutup_teguran` text,
        `pembukaan_pemanggilan` text,
        `isi_pemanggilan` text,
        `penutup_pemanggilan` text,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1";
    $conn->query($sql);
    
    // Insert default data
    $default = [
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
        'isi_teguran' => 'Berdasarkan hasil pengamatan dan data pelanggaran tata tertib sekolah yang tercatat, dengan ini kami sampaikan bahwa putra/putri Bapak/Ibu:

Nama Siswa    : {nama_siswa}
NISN          : {nisn}
Kelas         : {kelas}

Telah teridentifikasi melakukan pelanggaran tata tertib sekolah dengan total akumulasi poin sebesar {poin} poin.

Sehubungan dengan hal tersebut, sekolah memberikan Surat Teguran ini sebagai bentuk pembinaan dan peringatan awal. Kami berharap putra/putri Bapak/Ibu dapat memperbaiki sikap dan perilakunya serta tidak mengulangi pelanggaran di kemudian hari.

Kami sangat mengharapkan peran serta Bapak/Ibu untuk memberikan pengawasan dan bimbingan kepada putra/putri Bapak/Ibu di lingkungan keluarga.',
        'penutup_teguran' => 'Demikian Surat Teguran ini kami sampaikan. Atas perhatian dan kerja sama Bapak/Ibu, kami ucapkan terima kasih.',
        'pembukaan_pemanggilan' => 'Kepada Yth. Bapak/Ibu Orang Tua/Wali Siswa',
        'isi_pemanggilan' => 'Berdasarkan data pelanggaran tata tertib sekolah yang tercatat, dengan ini kami mengundang Bapak/Ibu untuk dapat hadir di sekolah guna membahas permasalahan putra/putri Bapak/Ibu:

Nama Siswa    : {nama_siswa}
NISN          : {nisn}
Kelas         : {kelas}
Total Poin    : {poin} poin

Undangan ini kami sampaikan mengingat akumulasi poin pelanggaran putra/putri Bapak/Ibu telah mencapai batas yang memerlukan tindak lanjut dari orang tua/wali.

Kami mohon Bapak/Ibu dapat hadir pada:
Hari/Tanggal  : {tanggal_pemanggilan}
Waktu         : {jam_pemanggilan}
Tempat        : Ruang Bimbingan Konseling (BK)

Kehadiran Bapak/Ibu sangat kami harapkan untuk bersama-sama mencari solusi terbaik dalam pembinaan dan pembentukan karakter putra/putri Bapak/Ibu.',
        'penutup_pemanggilan' => 'Demikian Surat Pemanggilan ini kami sampaikan. Atas perhatian dan kehadiran Bapak/Ibu, kami ucapkan terima kasih.'
    ];

    $cols = implode('`, `', array_keys($default));
    $vals = implode("', '", array_map(function($v) use ($conn) {
        return $conn->real_escape_string($v);
    }, array_values($default)));
    $conn->query("INSERT INTO pengaturan_surat (`$cols`) VALUES ('$vals')");
}

// ============================================
// CEK KOLOM YANG HILANG - TAMBAHKAN JIKA PERLU
// ============================================
$required_columns = [
    'pembukaan_teguran' => 'TEXT NULL',
    'isi_teguran' => 'TEXT NULL',
    'penutup_teguran' => 'TEXT NULL',
    'pembukaan_pemanggilan' => 'TEXT NULL',
    'isi_pemanggilan' => 'TEXT NULL',
    'penutup_pemanggilan' => 'TEXT NULL'
];

foreach ($required_columns as $col => $type) {
    $check_col = $conn->query("SHOW COLUMNS FROM pengaturan_surat LIKE '$col'");
    if ($check_col->num_rows == 0) {
        $conn->query("ALTER TABLE pengaturan_surat ADD COLUMN $col $type");
    }
}

// ============================================
// AMBIL DATA
// ============================================
$result = $conn->query("SELECT * FROM pengaturan_surat WHERE id = 1");
if ($result && $result->num_rows > 0) {
    $p = $result->fetch_assoc();
} else {
    // Jika tidak ada data, buat default
    $p = [
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
        'isi_teguran' => 'Berdasarkan hasil pengamatan dan data pelanggaran tata tertib sekolah yang tercatat, dengan ini kami sampaikan bahwa putra/putri Bapak/Ibu:

Nama Siswa    : {nama_siswa}
NISN          : {nisn}
Kelas         : {kelas}

Telah teridentifikasi melakukan pelanggaran tata tertib sekolah dengan total akumulasi poin sebesar {poin} poin.

Sehubungan dengan hal tersebut, sekolah memberikan Surat Teguran ini sebagai bentuk pembinaan dan peringatan awal. Kami berharap putra/putri Bapak/Ibu dapat memperbaiki sikap dan perilakunya serta tidak mengulangi pelanggaran di kemudian hari.

Kami sangat mengharapkan peran serta Bapak/Ibu untuk memberikan pengawasan dan bimbingan kepada putra/putri Bapak/Ibu di lingkungan keluarga.',
        'penutup_teguran' => 'Demikian Surat Teguran ini kami sampaikan. Atas perhatian dan kerja sama Bapak/Ibu, kami ucapkan terima kasih.',
        'pembukaan_pemanggilan' => 'Kepada Yth. Bapak/Ibu Orang Tua/Wali Siswa',
        'isi_pemanggilan' => 'Berdasarkan data pelanggaran tata tertib sekolah yang tercatat, dengan ini kami mengundang Bapak/Ibu untuk dapat hadir di sekolah guna membahas permasalahan putra/putri Bapak/Ibu:

Nama Siswa    : {nama_siswa}
NISN          : {nisn}
Kelas         : {kelas}
Total Poin    : {poin} poin

Undangan ini kami sampaikan mengingat akumulasi poin pelanggaran putra/putri Bapak/Ibu telah mencapai batas yang memerlukan tindak lanjut dari orang tua/wali.

Kami mohon Bapak/Ibu dapat hadir pada:
Hari/Tanggal  : {tanggal_pemanggilan}
Waktu         : {jam_pemanggilan}
Tempat        : Ruang Bimbingan Konseling (BK)

Kehadiran Bapak/Ibu sangat kami harapkan untuk bersama-sama mencari solusi terbaik dalam pembinaan dan pembentukan karakter putra/putri Bapak/Ibu.',
        'penutup_pemanggilan' => 'Demikian Surat Pemanggilan ini kami sampaikan. Atas perhatian dan kehadiran Bapak/Ibu, kami ucapkan terima kasih.'
    ];
    
    // Insert default
    $cols = implode('`, `', array_keys($p));
    $vals = implode("', '", array_map(function($v) use ($conn) {
        return $conn->real_escape_string($v);
    }, array_values($p)));
    $conn->query("INSERT INTO pengaturan_surat (`$cols`) VALUES ('$vals')");
    
    // Refresh
    $result = $conn->query("SELECT * FROM pengaturan_surat WHERE id = 1");
    $p = $result->fetch_assoc();
}

// ============================================
// PROSES UPDATE
// ============================================
$msg = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $data = [
        'nama_sekolah' => $_POST['nama_sekolah'] ?? '',
        'alamat_sekolah' => $_POST['alamat_sekolah'] ?? '',
        'no_telp_sekolah' => $_POST['no_telp_sekolah'] ?? '',
        'email_sekolah' => $_POST['email_sekolah'] ?? '',
        'website_sekolah' => $_POST['website_sekolah'] ?? '',
        'kementrian' => $_POST['kementrian'] ?? '',
        'kepala_sekolah' => $_POST['kepala_sekolah'] ?? '',
        'nip_kepala_sekolah' => $_POST['nip_kepala_sekolah'] ?? '',
        'ttd_kepala_sekolah_nama' => $_POST['ttd_kepala_sekolah_nama'] ?? '',
        'nomor_surat' => $_POST['nomor_surat'] ?? '',
        'pembukaan_teguran' => $_POST['pembukaan_teguran'] ?? '',
        'isi_teguran' => $_POST['isi_teguran'] ?? '',
        'penutup_teguran' => $_POST['penutup_teguran'] ?? '',
        'pembukaan_pemanggilan' => $_POST['pembukaan_pemanggilan'] ?? '',
        'isi_pemanggilan' => $_POST['isi_pemanggilan'] ?? '',
        'penutup_pemanggilan' => $_POST['penutup_pemanggilan'] ?? ''
    ];
    
    // Upload file
    $logo_dir = APP_PATH . 'uploads/logo/';
    if (!is_dir($logo_dir)) mkdir($logo_dir, 0777, true);
    $surat_dir = APP_PATH . 'uploads/surat/';
    if (!is_dir($surat_dir)) mkdir($surat_dir, 0777, true);
    
    if (isset($_FILES['logo_kiri']) && $_FILES['logo_kiri']['error'] == 0) {
        $ext = strtolower(pathinfo($_FILES['logo_kiri']['name'], PATHINFO_EXTENSION));
        $name = 'logo_kiri_' . date('Ymd_His') . '.' . $ext;
        if (move_uploaded_file($_FILES['logo_kiri']['tmp_name'], $logo_dir . $name)) {
            if (!empty($p['logo_kiri']) && file_exists($logo_dir . $p['logo_kiri'])) unlink($logo_dir . $p['logo_kiri']);
            $data['logo_kiri'] = $name;
        }
    }
    
    if (isset($_FILES['logo_kanan']) && $_FILES['logo_kanan']['error'] == 0) {
        $ext = strtolower(pathinfo($_FILES['logo_kanan']['name'], PATHINFO_EXTENSION));
        $name = 'logo_kanan_' . date('Ymd_His') . '.' . $ext;
        if (move_uploaded_file($_FILES['logo_kanan']['tmp_name'], $logo_dir . $name)) {
            if (!empty($p['logo_kanan']) && file_exists($logo_dir . $p['logo_kanan'])) unlink($logo_dir . $p['logo_kanan']);
            $data['logo_kanan'] = $name;
        }
    }
    
    if (isset($_FILES['stempel_sekolah']) && $_FILES['stempel_sekolah']['error'] == 0) {
        $ext = strtolower(pathinfo($_FILES['stempel_sekolah']['name'], PATHINFO_EXTENSION));
        $name = 'stempel_' . date('Ymd_His') . '.' . $ext;
        if (move_uploaded_file($_FILES['stempel_sekolah']['tmp_name'], $surat_dir . $name)) {
            if (!empty($p['stempel_sekolah']) && file_exists($surat_dir . $p['stempel_sekolah'])) unlink($surat_dir . $p['stempel_sekolah']);
            $data['stempel_sekolah'] = $name;
        }
    }
    
    if (isset($_FILES['ttd_kepala_sekolah']) && $_FILES['ttd_kepala_sekolah']['error'] == 0) {
        $ext = strtolower(pathinfo($_FILES['ttd_kepala_sekolah']['name'], PATHINFO_EXTENSION));
        $name = 'ttd_' . date('Ymd_His') . '.' . $ext;
        if (move_uploaded_file($_FILES['ttd_kepala_sekolah']['tmp_name'], $surat_dir . $name)) {
            if (!empty($p['ttd_kepala_sekolah']) && file_exists($surat_dir . $p['ttd_kepala_sekolah'])) unlink($surat_dir . $p['ttd_kepala_sekolah']);
            $data['ttd_kepala_sekolah'] = $name;
        }
    }
    
    // Update
    $sets = [];
    foreach ($data as $key => $value) {
        $sets[] = "`$key` = '" . $conn->real_escape_string($value) . "'";
    }
    $sql = "UPDATE pengaturan_surat SET " . implode(', ', $sets) . " WHERE id = 1";
    
    if ($conn->query($sql)) {
        $msg = "Pengaturan surat berhasil disimpan!";
        $msg_type = "success";
        $result = $conn->query("SELECT * FROM pengaturan_surat WHERE id = 1");
        $p = $result->fetch_assoc();
    } else {
        $msg = "Error: " . $conn->error;
        $msg_type = "danger";
    }
}

include __DIR__ . '/../includes/header_bk.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4><i class="fas fa-file-signature text-primary me-2"></i> Pengaturan Surat Teguran & Pemanggilan</h4>
                <span class="badge bg-primary">Template Surat</span>
            </div>
            
            <?php if ($msg): ?>
                <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show">
                    <?= $msg ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update">
                
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab1">Kop Surat</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab2">Surat Teguran</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab3">Surat Pemanggilan</a></li>
                </ul>
                
                <div class="tab-content pt-3">
                    <!-- TAB 1 -->
                    <div class="tab-pane active" id="tab1">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="text-primary">Informasi Sekolah</h6>
                                <hr>
                                <div class="row">
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label">Nama Sekolah</label>
                                        <input type="text" class="form-control" name="nama_sekolah" value="<?= htmlspecialchars($p['nama_sekolah'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label">Alamat Sekolah</label>
                                        <input type="text" class="form-control" name="alamat_sekolah" value="<?= htmlspecialchars($p['alamat_sekolah'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <label class="form-label">No. Telp</label>
                                        <input type="text" class="form-control" name="no_telp_sekolah" value="<?= htmlspecialchars($p['no_telp_sekolah'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" name="email_sekolah" value="<?= htmlspecialchars($p['email_sekolah'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <label class="form-label">Website</label>
                                        <input type="text" class="form-control" name="website_sekolah" value="<?= htmlspecialchars($p['website_sekolah'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-12 mb-2">
                                        <label class="form-label">Kementrian</label>
                                        <input type="text" class="form-control" name="kementrian" value="<?= htmlspecialchars($p['kementrian'] ?? '') ?>">
                                    </div>
                                </div>
                                
                                <h6 class="text-primary mt-3">Logo & Tanda Tangan</h6>
                                <hr>
                                <div class="row">
                                    <div class="col-md-3 mb-2">
                                        <label class="form-label">Logo Kiri</label>
                                        <input type="file" class="form-control" name="logo_kiri" accept="image/*">
                                        <?php if (!empty($p['logo_kiri'])): ?>
                                            <div class="mt-1">
                                                <img src="<?= APP_URL ?>uploads/logo/<?= $p['logo_kiri'] ?>" style="max-height:40px;border:1px solid #ddd;padding:3px;border-radius:4px;">
                                                <small class="d-block text-muted" style="font-size:9px;"><?= $p['logo_kiri'] ?></small>
                                            </div>
                                        <?php else: ?>
                                            <small class="text-muted">Belum ada logo</small>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <label class="form-label">Logo Kanan</label>
                                        <input type="file" class="form-control" name="logo_kanan" accept="image/*">
                                        <?php if (!empty($p['logo_kanan'])): ?>
                                            <div class="mt-1">
                                                <img src="<?= APP_URL ?>uploads/logo/<?= $p['logo_kanan'] ?>" style="max-height:40px;border:1px solid #ddd;padding:3px;border-radius:4px;">
                                                <small class="d-block text-muted" style="font-size:9px;"><?= $p['logo_kanan'] ?></small>
                                            </div>
                                        <?php else: ?>
                                            <small class="text-muted">Belum ada logo</small>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <label class="form-label">Stempel</label>
                                        <input type="file" class="form-control" name="stempel_sekolah" accept="image/*">
                                        <?php if (!empty($p['stempel_sekolah'])): ?>
                                            <div class="mt-1">
                                                <img src="<?= APP_URL ?>uploads/surat/<?= $p['stempel_sekolah'] ?>" style="max-height:40px;border:1px solid #ddd;padding:3px;border-radius:4px;">
                                                <small class="d-block text-muted" style="font-size:9px;"><?= $p['stempel_sekolah'] ?></small>
                                            </div>
                                        <?php else: ?>
                                            <small class="text-muted">Belum ada stempel</small>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <label class="form-label">TTD Kepsek</label>
                                        <input type="file" class="form-control" name="ttd_kepala_sekolah" accept="image/*">
                                        <?php if (!empty($p['ttd_kepala_sekolah'])): ?>
                                            <div class="mt-1">
                                                <img src="<?= APP_URL ?>uploads/surat/<?= $p['ttd_kepala_sekolah'] ?>" style="max-height:40px;border:1px solid #ddd;padding:3px;border-radius:4px;">
                                                <small class="d-block text-muted" style="font-size:9px;"><?= $p['ttd_kepala_sekolah'] ?></small>
                                            </div>
                                        <?php else: ?>
                                            <small class="text-muted">Belum ada TTD</small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <h6 class="text-primary mt-3">Kepala Sekolah</h6>
                                <hr>
                                <div class="row">
                                    <div class="col-md-4 mb-2">
                                        <label class="form-label">Nama Kepala Sekolah</label>
                                        <input type="text" class="form-control" name="kepala_sekolah" value="<?= htmlspecialchars($p['kepala_sekolah'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <label class="form-label">NIP</label>
                                        <input type="text" class="form-control" name="nip_kepala_sekolah" value="<?= htmlspecialchars($p['nip_kepala_sekolah'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <label class="form-label">Nama TTD</label>
                                        <input type="text" class="form-control" name="ttd_kepala_sekolah_nama" value="<?= htmlspecialchars($p['ttd_kepala_sekolah_nama'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-12 mb-2">
                                        <label class="form-label">Nomor Surat Default</label>
                                        <input type="text" class="form-control" name="nomor_surat" value="<?= htmlspecialchars($p['nomor_surat'] ?? '001/TEG/2024') ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- TAB 2: SURAT TEGURAN -->
                    <div class="tab-pane" id="tab2">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="text-warning">Template Surat Teguran</h6>
                                <hr>
                                <div class="alert alert-info">
                                    <strong>Placeholder:</strong>
                                    <span class="badge bg-primary">{nama_siswa}</span>
                                    <span class="badge bg-primary">{nisn}</span>
                                    <span class="badge bg-primary">{kelas}</span>
                                    <span class="badge bg-primary">{tanggal}</span>
                                    <span class="badge bg-primary">{poin}</span>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Pembukaan Surat Teguran</label>
                                    <textarea class="form-control" name="pembukaan_teguran" rows="2"><?= htmlspecialchars($p['pembukaan_teguran'] ?? '') ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Isi Surat Teguran</label>
                                    <textarea class="form-control" name="isi_teguran" rows="10"><?= htmlspecialchars($p['isi_teguran'] ?? '') ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Penutup Surat Teguran</label>
                                    <textarea class="form-control" name="penutup_teguran" rows="2"><?= htmlspecialchars($p['penutup_teguran'] ?? '') ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- TAB 3: SURAT PEMANGGILAN -->
                    <div class="tab-pane" id="tab3">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="text-danger">Template Surat Pemanggilan</h6>
                                <hr>
                                <div class="alert alert-info">
                                    <strong>Placeholder:</strong>
                                    <span class="badge bg-primary">{nama_siswa}</span>
                                    <span class="badge bg-primary">{nisn}</span>
                                    <span class="badge bg-primary">{kelas}</span>
                                    <span class="badge bg-primary">{tanggal}</span>
                                    <span class="badge bg-primary">{poin}</span>
                                    <span class="badge bg-primary">{tanggal_pemanggilan}</span>
                                    <span class="badge bg-primary">{jam_pemanggilan}</span>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Pembukaan Surat Pemanggilan</label>
                                    <textarea class="form-control" name="pembukaan_pemanggilan" rows="2"><?= htmlspecialchars($p['pembukaan_pemanggilan'] ?? '') ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Isi Surat Pemanggilan</label>
                                    <textarea class="form-control" name="isi_pemanggilan" rows="10"><?= htmlspecialchars($p['isi_pemanggilan'] ?? '') ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Penutup Surat Pemanggilan</label>
                                    <textarea class="form-control" name="penutup_pemanggilan" rows="2"><?= htmlspecialchars($p['penutup_pemanggilan'] ?? '') ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card mt-3">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Simpan Pengaturan
                        </button>
                        <a href="dashboard_bk.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Kembali
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.card { border: none; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
.card-body { padding: 16px; }
.form-label { font-weight: 600; font-size: 12px; color: #4a5568; }
.form-control { border-radius: 6px; font-size: 13px; }
.nav-tabs .nav-link { color: #6c757d; font-weight: 500; }
.nav-tabs .nav-link.active { color: #4F46E5; border-bottom: 3px solid #4F46E5; }
.btn-primary { background: #4F46E5; border: none; border-radius: 6px; padding: 8px 24px; }
.btn-primary:hover { background: #4338CA; }
</style>

<?php include '../includes/footer.php'; ?>