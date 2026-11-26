<?php
// guru/detail_pelanggaran.php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Cek login
if (!isLoggedIn()) {
    header("Location: ../login.php");
    exit;
}

// Cek role
if (!hasRole(['admin', 'guru', 'bk', 'walikelas'])) {
    header("Location: ../dashboard.php");
    exit;
}

// Get ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id == 0) {
    header("Location: riwayat_hari_ini.php");
    exit;
}

// Get data pelanggaran
$pelanggaran = getPelanggaranById($id);
if (!$pelanggaran) {
    header("Location: riwayat_hari_ini.php");
    exit;
}

// Ambil data siswa dan jenis pelanggaran
$siswa = getSiswaById($pelanggaran['siswa_id']);
$jenis = getJenisPelanggaranById($pelanggaran['jenis_pelanggaran_id']);
$guru = getGuruById($pelanggaran['guru_id']);

// Jika ada parameter cetak
if (isset($_GET['cetak']) && $_GET['cetak'] == 'pdf') {
    require_once('../tcpdf/tcpdf.php');
    
    $pengaturan = getPengaturan();
    $nama_sekolah = $pengaturan['nama_sekolah'] ?? 'SMA Negeri 1 Tatib';
    $alamat = $pengaturan['alamat'] ?? '';
    $no_telp = $pengaturan['no_telp'] ?? '';
    $email = $pengaturan['email'] ?? '';
    $logo = $pengaturan['logo'] ?? '';
    
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('Tatib Sekolah');
    $pdf->SetAuthor($nama_sekolah);
    $pdf->SetTitle('Bukti Pelanggaran - ' . $siswa['nama_siswa']);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(20, 10, 20);
    $pdf->AddPage();
    
    // KOP SURAT
    $logo_path = APP_PATH . 'uploads/logo/' . $logo;
    if (!empty($logo) && file_exists($logo_path)) {
        $pdf->Image($logo_path, 20, 10, 22, 22);
    }
    
    $pdf->SetY(10);
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 7, strtoupper($nama_sekolah), 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 5, $alamat, 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 9);
    $pdf->Cell(0, 4, 'Telp: ' . $no_telp . ' | Email: ' . $email, 0, 1, 'C');
    
    $y_after_text = $pdf->GetY();
    $pdf->SetDrawColor(0, 0, 0);
    $pdf->SetLineWidth(0.8);
    $pdf->Line(20, $y_after_text + 3, 190, $y_after_text + 3);
    $pdf->SetLineWidth(0.3);
    $pdf->Line(20, $y_after_text + 5, 190, $y_after_text + 5);
    $pdf->SetY($y_after_text + 10);
    
    // JUDUL
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 7, 'BUKTI PELANGGARAN SISWA', 0, 1, 'C');
    $pdf->Ln(5);
    
    // DATA PELANGGARAN
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(0, 6, 'A. DATA SISWA', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 10);
    
    $pdf->Cell(35, 6, 'Nama Siswa', 0, 0, 'L');
    $pdf->Cell(5, 6, ':', 0, 0, 'C');
    $pdf->Cell(0, 6, ': ' . $siswa['nama_siswa'], 0, 1, 'L');
    
    $pdf->Cell(35, 6, 'NISN', 0, 0, 'L');
    $pdf->Cell(5, 6, ':', 0, 0, 'C');
    $pdf->Cell(0, 6, ': ' . $siswa['nisn'], 0, 1, 'L');
    
    $pdf->Cell(35, 6, 'Kelas', 0, 0, 'L');
    $pdf->Cell(5, 6, ':', 0, 0, 'C');
    $pdf->Cell(0, 6, ': ' . ($siswa['nama_kelas'] ?? '-'), 0, 1, 'L');
    
    $pdf->Cell(35, 6, 'Jenis Kelamin', 0, 0, 'L');
    $pdf->Cell(5, 6, ':', 0, 0, 'C');
    $pdf->Cell(0, 6, ': ' . ($siswa['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan'), 0, 1, 'L');
    
    $pdf->Ln(3);
    
    // Data Pelanggaran
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(0, 6, 'B. DATA PELANGGARAN', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 10);
    
    $pdf->Cell(40, 6, 'Jenis Pelanggaran', 0, 0, 'L');
    $pdf->Cell(5, 6, ':', 0, 0, 'C');
    $pdf->Cell(0, 6, ': ' . $jenis['nama_pelanggaran'], 0, 1, 'L');
    
    $pdf->Cell(40, 6, 'Poin', 0, 0, 'L');
    $pdf->Cell(5, 6, ':', 0, 0, 'C');
    $pdf->Cell(0, 6, ': ' . $jenis['poin'] . ' Poin', 0, 1, 'L');
    
    $pdf->Cell(40, 6, 'Kategori', 0, 0, 'L');
    $pdf->Cell(5, 6, ':', 0, 0, 'C');
    $pdf->Cell(0, 6, ': ' . ucfirst($jenis['kategori']), 0, 1, 'L');
    
    $pdf->Cell(40, 6, 'Tanggal', 0, 0, 'L');
    $pdf->Cell(5, 6, ':', 0, 0, 'C');
    $pdf->Cell(0, 6, ': ' . formatTanggalIndo($pelanggaran['tanggal_pelanggaran']), 0, 1, 'L');
    
    if (!empty($pelanggaran['jam_pelanggaran'])) {
        $pdf->Cell(40, 6, 'Jam', 0, 0, 'L');
        $pdf->Cell(5, 6, ':', 0, 0, 'C');
        $pdf->Cell(0, 6, ': ' . date('H:i', strtotime($pelanggaran['jam_pelanggaran'])), 0, 1, 'L');
    }
    
    if (!empty($pelanggaran['lokasi'])) {
        $pdf->Cell(40, 6, 'Lokasi', 0, 0, 'L');
        $pdf->Cell(5, 6, ':', 0, 0, 'C');
        $pdf->Cell(0, 6, ': ' . $pelanggaran['lokasi'], 0, 1, 'L');
    }
    
    if (!empty($pelanggaran['keterangan'])) {
        $pdf->MultiCell(0, 6, 'Keterangan: ' . $pelanggaran['keterangan'], 0, 'L');
    }
    
    $pdf->Cell(40, 6, 'Status', 0, 0, 'L');
    $pdf->Cell(5, 6, ':', 0, 0, 'C');
    $pdf->Cell(0, 6, ': ' . ucfirst($pelanggaran['status']), 0, 1, 'L');
    
    $pdf->Cell(40, 6, 'Input oleh', 0, 0, 'L');
    $pdf->Cell(5, 6, ':', 0, 0, 'C');
    $pdf->Cell(0, 6, ': ' . ($guru['nama_lengkap'] ?? '-'), 0, 1, 'L');
    
    // BUKTI FOTO DI PDF - UKURAN 3CM x 3CM
    if (!empty($pelanggaran['bukti'])) {
        $bukti_path = APP_PATH . 'uploads/bukti_pelanggaran/' . $pelanggaran['bukti'];
        $file_ext = strtolower(pathinfo($pelanggaran['bukti'], PATHINFO_EXTENSION));
        
        if (file_exists($bukti_path) && in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif'])) {
            $pdf->Ln(3);
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->Cell(0, 6, 'C. BUKTI FOTO', 0, 1, 'L');
            // Ukuran 30mm x 30mm (3cm x 3cm)
            $pdf->Image($bukti_path, 80, $pdf->GetY() + 2, 30, 30, '', '', '', false, 300);
        }
    }
    
    $pdf->Ln(35);
    
    // TANDA TANGAN
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 6, 'Mengetahui,', 0, 1, 'R');
    $pdf->Ln(20);
    $pdf->Cell(0, 6, 'Wali Kelas', 0, 1, 'R');
    $pdf->Cell(0, 6, '(_________________________)', 0, 1, 'R');
    
    $pdf->Ln(3);
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->Cell(0, 5, 'Dicetak pada: ' . date('d-m-Y H:i:s'), 0, 1, 'L');
    
    $pdf->Output('Bukti_Pelanggaran_' . $siswa['nama_siswa'] . '_' . date('Ymd') . '.pdf', 'I');
    exit;
}

$page_title = 'Detail Pelanggaran';

// Include header guru
include __DIR__ . '/../includes/header_guru.php';
?>

<div class="container-fluid dashboard-container">
    <!-- Header -->
    <div class="dashboard-header">
        <div>
            <h4 class="dashboard-title">
                <i class="fas fa-file-alt text-primary me-2"></i> Detail Pelanggaran
            </h4>
            <small class="text-muted dashboard-subtitle">
                <i class="fas fa-info-circle me-1"></i> Informasi lengkap pelanggaran
            </small>
        </div>
        <div>
            <a href="riwayat_hari_ini.php" class="btn btn-secondary me-2">
                <i class="fas fa-arrow-left me-1"></i> Kembali
            </a>
            <a href="?id=<?= $id ?>&cetak=pdf" target="_blank" class="btn btn-primary">
                <i class="fas fa-print me-1"></i> Cetak PDF
            </a>
        </div>
    </div>

    <!-- Detail dalam satu card -->
    <div class="card">
        <div class="card-body">
            <div class="row">
                <!-- Kolom Kiri: Data Siswa -->
                <div class="col-md-6">
                    <h6 class="text-primary mb-3"><i class="fas fa-user-graduate me-2"></i>Data Siswa</h6>
                    <table class="table table-borderless table-sm mb-0">
                        <tr>
                            <td width="100"><strong>Nama</strong></td>
                            <td>: <?= htmlspecialchars($siswa['nama_siswa'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td><strong>NISN</strong></td>
                            <td>: <?= htmlspecialchars($siswa['nisn'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td><strong>Kelas</strong></td>
                            <td>: <?= htmlspecialchars($siswa['nama_kelas'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td><strong>JK</strong></td>
                            <td>: <?= $siswa['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan' ?></td>
                        </tr>
                        <tr>
                            <td><strong>No HP</strong></td>
                            <td>: <?= htmlspecialchars($siswa['no_hp'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td><strong>Total Poin</strong></td>
                            <td>: <span class="badge bg-danger"><?= $siswa['poin'] ?? 0 ?></span></td>
                        </tr>
                    </table>
                </div>

                <!-- Kolom Kanan: Data Pelanggaran -->
                <div class="col-md-6">
                    <h6 class="text-danger mb-3"><i class="fas fa-exclamation-triangle me-2"></i>Data Pelanggaran</h6>
                    <table class="table table-borderless table-sm mb-0">
                        <tr>
                            <td width="100"><strong>Jenis</strong></td>
                            <td>: <?= htmlspecialchars($jenis['nama_pelanggaran'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td><strong>Poin</strong></td>
                            <td>: <span class="badge bg-danger"><?= $jenis['poin'] ?? 0 ?></span></td>
                        </tr>
                        <tr>
                            <td><strong>Kategori</strong></td>
                            <td>: 
                                <?php 
                                $kategori = $jenis['kategori'] ?? 'ringan';
                                $badge = $kategori == 'berat' ? 'danger' : ($kategori == 'sedang' ? 'warning' : 'success');
                                ?>
                                <span class="badge bg-<?= $badge ?>"><?= ucfirst($kategori) ?></span>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Tanggal</strong></td>
                            <td>: <?= formatTanggalIndo($pelanggaran['tanggal_pelanggaran']) ?></td>
                        </tr>
                        <?php if (!empty($pelanggaran['jam_pelanggaran'])): ?>
                        <tr>
                            <td><strong>Jam</strong></td>
                            <td>: <?= date('H:i', strtotime($pelanggaran['jam_pelanggaran'])) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (!empty($pelanggaran['lokasi'])): ?>
                        <tr>
                            <td><strong>Lokasi</strong></td>
                            <td>: <?= htmlspecialchars($pelanggaran['lokasi']) ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <td><strong>Status</strong></td>
                            <td>: 
                                <?php 
                                $status = $pelanggaran['status'] ?? 'pending';
                                $badge = $status == 'disetujui' ? 'success' : ($status == 'ditolak' ? 'danger' : 'warning');
                                ?>
                                <span class="badge bg-<?= $badge ?>"><?= ucfirst($status) ?></span>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Input oleh</strong></td>
                            <td>: <?= htmlspecialchars($guru['nama_lengkap'] ?? '-') ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Keterangan -->
            <?php if (!empty($pelanggaran['keterangan'])): ?>
            <hr>
            <div class="row">
                <div class="col-12">
                    <h6 class="text-muted"><i class="fas fa-comment me-2"></i>Keterangan</h6>
                    <p class="mb-0"><?= nl2br(htmlspecialchars($pelanggaran['keterangan'])) ?></p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Bukti Foto - UKURAN 3cm x 3cm -->
            <?php if (!empty($pelanggaran['bukti'])): 
                $bukti_path = APP_PATH . 'uploads/bukti_pelanggaran/' . $pelanggaran['bukti'];
                $file_ext = strtolower(pathinfo($pelanggaran['bukti'], PATHINFO_EXTENSION));
            ?>
            <hr>
            <div class="row">
                <div class="col-12">
                    <h6 class="text-success"><i class="fas fa-camera me-2"></i>Bukti Foto</h6>
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <?php if (file_exists($bukti_path) && in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif'])): ?>
                            <a href="<?= APP_URL ?>uploads/bukti_pelanggaran/<?= $pelanggaran['bukti'] ?>" target="_blank">
                                <img src="<?= APP_URL ?>uploads/bukti_pelanggaran/<?= $pelanggaran['bukti'] ?>" 
                                     alt="Bukti Pelanggaran" 
                                     class="bukti-thumbnail">
                            </a>
                            <div>
                                <small class="text-muted d-block">
                                    <i class="fas fa-file me-1"></i> <?= htmlspecialchars($pelanggaran['bukti']) ?>
                                </small>
                                <div class="mt-1">
                                    <a href="<?= APP_URL ?>uploads/bukti_pelanggaran/<?= $pelanggaran['bukti'] ?>" 
                                       target="_blank" class="btn btn-sm btn-primary">
                                        <i class="fas fa-external-link-alt me-1"></i> Lihat
                                    </a>
                                    <a href="<?= APP_URL ?>uploads/bukti_pelanggaran/<?= $pelanggaran['bukti'] ?>" 
                                       download class="btn btn-sm btn-success">
                                        <i class="fas fa-download me-1"></i> Download
                                    </a>
                                </div>
                            </div>
                        <?php elseif (file_exists($bukti_path) && $file_ext == 'pdf'): ?>
                            <div class="d-flex align-items-center gap-3">
                                <i class="fas fa-file-pdf text-danger" style="font-size: 40px;"></i>
                                <div>
                                    <small class="text-muted d-block"><?= htmlspecialchars($pelanggaran['bukti']) ?></small>
                                    <div class="mt-1">
                                        <a href="<?= APP_URL ?>uploads/bukti_pelanggaran/<?= $pelanggaran['bukti'] ?>" 
                                           target="_blank" class="btn btn-sm btn-danger">
                                            <i class="fas fa-file-pdf me-1"></i> Lihat PDF
                                        </a>
                                        <a href="<?= APP_URL ?>uploads/bukti_pelanggaran/<?= $pelanggaran['bukti'] ?>" 
                                           download class="btn btn-sm btn-success">
                                            <i class="fas fa-download me-1"></i> Download
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">File bukti tidak ditemukan</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tombol Aksi -->
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between flex-wrap gap-2">
                <div>
                    <a href="riwayat_hari_ini.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Kembali
                    </a>
                </div>
                <div>
                    <?php if ($pelanggaran['status'] == 'pending'): ?>
                        <a href="approve_pelanggaran.php?id=<?= $id ?>" 
                           class="btn btn-success" onclick="return confirm('Setujui pelanggaran ini?')">
                            <i class="fas fa-check me-1"></i> Setujui
                        </a>
                        <a href="reject_pelanggaran.php?id=<?= $id ?>" 
                           class="btn btn-danger" onclick="return confirm('Tolak pelanggaran ini?')">
                            <i class="fas fa-times me-1"></i> Tolak
                        </a>
                    <?php endif; ?>
                    <a href="?id=<?= $id ?>&cetak=pdf" target="_blank" class="btn btn-primary">
                        <i class="fas fa-print me-1"></i> Cetak PDF
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* ============================================
   STYLE DETAIL PELANGGARAN
   ============================================ */

.dashboard-container {
    padding: 12px !important;
    max-width: 100%;
}

.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    margin-bottom: 16px;
    gap: 8px;
}

.dashboard-title {
    font-size: 1.25rem;
    margin-bottom: 0;
}

.dashboard-subtitle {
    font-size: 0.75rem;
}

/* Card */
.card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 2px 15px rgba(0,0,0,0.08);
    margin-bottom: 20px;
    background: #fff;
}
.card-body {
    padding: 20px;
}

/* Table Detail */
.table-borderless td {
    padding: 4px 0;
    border: none;
    font-size: 13px;
}
.table-borderless td:first-child {
    font-weight: 600;
    color: #4a5568;
}
.table-sm td {
    padding: 4px 0;
}

/* Bukti Thumbnail - UKURAN 3cm x 3cm */
.bukti-thumbnail {
    width: 113px;  /* 3cm = 113px */
    height: 113px; /* 3cm = 113px */
    object-fit: cover;
    border-radius: 8px;
    border: 2px solid #e2e8f0;
    padding: 3px;
    background: #fff;
    transition: all 0.3s;
    cursor: pointer;
}
.bukti-thumbnail:hover {
    border-color: #4F46E5;
    transform: scale(1.05);
    box-shadow: 0 4px 15px rgba(0,0,0,0.15);
}

/* Buttons */
.btn-primary {
    background: linear-gradient(135deg, #4F46E5 0%, #4338CA 100%);
    border: none;
    padding: 8px 20px;
    border-radius: 10px;
    font-weight: 600;
    color: #fff;
    transition: all 0.3s;
}
.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(79, 70, 229, 0.4);
    color: #fff;
}
.btn-secondary {
    background: #e2e8f0;
    border: none;
    padding: 8px 20px;
    border-radius: 10px;
    font-weight: 600;
    color: #4a5568;
    transition: all 0.3s;
}
.btn-secondary:hover {
    background: #cbd5e0;
    color: #4a5568;
}
.btn-success {
    background: #10B981;
    border: none;
    padding: 8px 20px;
    border-radius: 10px;
    font-weight: 600;
    color: #fff;
}
.btn-success:hover {
    background: #059669;
    color: #fff;
}
.btn-danger {
    background: #EF4444;
    border: none;
    padding: 8px 20px;
    border-radius: 10px;
    font-weight: 600;
    color: #fff;
}
.btn-danger:hover {
    background: #DC2626;
    color: #fff;
}

.btn-sm {
    padding: 2px 10px !important;
    font-size: 12px !important;
    border-radius: 6px !important;
}

/* Badge */
.badge {
    font-size: 10px !important;
    padding: 3px 8px !important;
}

/* Responsive */
@media (max-width: 768px) {
    .dashboard-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 4px;
        margin-bottom: 10px;
    }
    .dashboard-title {
        font-size: 1rem;
    }
    .card-body {
        padding: 15px;
    }
    .table-borderless td {
        font-size: 12px;
        padding: 3px 0;
    }
    .btn-primary, .btn-secondary, .btn-success, .btn-danger {
        padding: 6px 14px;
        font-size: 12px;
        width: 100%;
    }
    .d-flex.justify-content-between {
        flex-direction: column;
        gap: 6px;
    }
    .d-flex.justify-content-between > div {
        display: flex;
        flex-direction: column;
        gap: 6px;
        width: 100%;
    }
    .d-flex.justify-content-between > div .btn {
        width: 100%;
    }
    .bukti-thumbnail {
        width: 80px;
        height: 80px;
    }
}

@media (max-width: 576px) {
    .dashboard-container {
        padding: 6px !important;
    }
    .card-body {
        padding: 12px;
    }
    .table-borderless td {
        font-size: 11px;
        padding: 2px 0;
    }
    .bukti-thumbnail {
        width: 70px;
        height: 70px;
    }
}
</style>

<?php include '../includes/footer.php'; ?>