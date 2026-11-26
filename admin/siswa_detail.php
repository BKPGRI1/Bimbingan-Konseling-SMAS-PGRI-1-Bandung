<?php
// admin/siswa_detail.php
include '../includes/config.php';
checkLogin();

$page_title = 'Detail Siswa';
$conn = getDB();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$siswa = getSiswaById($id);

if (!$siswa) {
    $_SESSION['toast'] = [
        'message' => 'Data siswa tidak ditemukan!',
        'type' => 'danger'
    ];
    header("Location: " . APP_URL . "admin/siswa.php");
    exit();
}

// ============================================
// AMBIL DATA PELANGGARAN
// ============================================
$pelanggaran = getPelanggaranBySiswa($id);

// ============================================
// AMBIL DATA PRESTASI
// ============================================
$query_prestasi = "SELECT p.*, k.nama_kelas 
                   FROM prestasi_siswa p
                   LEFT JOIN siswa s ON p.siswa_id = s.id
                   LEFT JOIN kelas k ON s.kelas_id = k.id
                   WHERE p.siswa_id = $id
                   ORDER BY p.tahun DESC, p.created_at DESC";
$prestasi = getRows($query_prestasi);

// ============================================
// HITUNG POIN DENGAN BENAR
// ============================================
$total_poin_pelanggaran = 0;
foreach ($pelanggaran as $p) {
    if ($p['status'] == 'disetujui') {
        $total_poin_pelanggaran += $p['poin'];
    }
}

$total_poin_prestasi = 0;
foreach ($prestasi as $p) {
    $total_poin_prestasi += $p['poin_prestasi'];
}

// Poin akhir = pelanggaran - prestasi
$poin_akhir = $total_poin_pelanggaran - $total_poin_prestasi;
if ($poin_akhir < 0) $poin_akhir = 0;

$level = getPoinLevel($poin_akhir);

// ============================================
// CETAK PDF
// ============================================
if (isset($_GET['cetak']) && $_GET['cetak'] == 'pdf') {
    require_once('../tcpdf/tcpdf.php');
    
    class MYPDF extends TCPDF {
        public function Header() {
            $this->SetY(10);
            $this->SetFont('helvetica', 'B', 14);
            $this->Cell(0, 6, 'DETAIL SISWA', 0, 1, 'C');
            $this->SetFont('helvetica', '', 10);
            $this->Cell(0, 4, 'Dicetak pada: ' . date('d-m-Y H:i:s'), 0, 1, 'C');
            $this->Ln(5);
        }
        public function Footer() {
            $this->SetY(-15);
            $this->SetFont('helvetica', 'I', 8);
            $this->Cell(0, 10, 'Hal ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, 0, 'C');
        }
    }
    
    $pdf = new MYPDF('L', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('Tatib Sekolah');
    $pdf->SetAuthor('SMA Viralytics_Shop');
    $pdf->SetTitle('Detail Siswa - ' . $siswa['nama_siswa']);
    $pdf->SetMargins(15, 25, 15);
    $pdf->AddPage();
    
    // Informasi Siswa
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 6, 'INFORMASI SISWA', 0, 1, 'L');
    $pdf->Ln(2);
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(40, 6, 'NISN', 0, 0, 'L');
    $pdf->Cell(1, 6, ':', 0, 0, 'L');
    $pdf->Cell(0, 6, $siswa['nisn'], 0, 1, 'L');
    
    $pdf->Cell(40, 6, 'Nama Siswa', 0, 0, 'L');
    $pdf->Cell(1, 6, ':', 0, 0, 'L');
    $pdf->Cell(0, 6, $siswa['nama_siswa'], 0, 1, 'L');
    
    $pdf->Cell(40, 6, 'Kelas', 0, 0, 'L');
    $pdf->Cell(1, 6, ':', 0, 0, 'L');
    $pdf->Cell(0, 6, $siswa['nama_kelas'] ?? 'Belum ditentukan', 0, 1, 'L');
    
    $pdf->Cell(40, 6, 'Jenis Kelamin', 0, 0, 'L');
    $pdf->Cell(1, 6, ':', 0, 0, 'L');
    $pdf->Cell(0, 6, getJenisKelaminLabel($siswa['jenis_kelamin']), 0, 1, 'L');
    
    $pdf->Cell(40, 6, 'Tempat Lahir', 0, 0, 'L');
    $pdf->Cell(1, 6, ':', 0, 0, 'L');
    $pdf->Cell(0, 6, $siswa['tempat_lahir'] ?? '-', 0, 1, 'L');
    
    $pdf->Cell(40, 6, 'Tanggal Lahir', 0, 0, 'L');
    $pdf->Cell(1, 6, ':', 0, 0, 'L');
    $pdf->Cell(0, 6, formatTanggalIndo($siswa['tanggal_lahir']), 0, 1, 'L');
    
    $pdf->Cell(40, 6, 'Alamat', 0, 0, 'L');
    $pdf->Cell(1, 6, ':', 0, 0, 'L');
    $pdf->Cell(0, 6, $siswa['alamat'] ?? '-', 0, 1, 'L');
    
    $pdf->Cell(40, 6, 'No. HP', 0, 0, 'L');
    $pdf->Cell(1, 6, ':', 0, 0, 'L');
    $pdf->Cell(0, 6, $siswa['no_hp'] ?? '-', 0, 1, 'L');
    
    $pdf->Cell(40, 6, 'Total Poin', 0, 0, 'L');
    $pdf->Cell(1, 6, ':', 0, 0, 'L');
    $pdf->Cell(0, 6, $poin_akhir . ' Poin (' . $level['level'] . ')', 0, 1, 'L');
    
    $pdf->Ln(5);
    
    // RINGKASAN POIN
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 6, 'RINGKASAN POIN', 0, 1, 'L');
    $pdf->Ln(2);
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(50, 6, 'Total Poin Pelanggaran', 0, 0, 'L');
    $pdf->Cell(1, 6, ':', 0, 0, 'L');
    $pdf->Cell(0, 6, '+ ' . $total_poin_pelanggaran, 0, 1, 'L');
    
    $pdf->Cell(50, 6, 'Total Poin Prestasi', 0, 0, 'L');
    $pdf->Cell(1, 6, ':', 0, 0, 'L');
    $pdf->Cell(0, 6, '+ ' . $total_poin_prestasi, 0, 1, 'L');
    
    $pdf->Cell(50, 6, 'Total Poin Akhir', 0, 0, 'L');
    $pdf->Cell(1, 6, ':', 0, 0, 'L');
    $pdf->Cell(0, 6, $poin_akhir, 0, 1, 'L');
    
    $pdf->Ln(5);
    
    // PRESTASI
    if (!empty($prestasi)) {
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 6, 'PRESTASI SISWA', 0, 1, 'L');
        $pdf->Ln(2);
        
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(220, 220, 230);
        $pdf->Cell(10, 6, 'No', 1, 0, 'C', true);
        $pdf->Cell(25, 6, 'No. Prestasi', 1, 0, 'C', true);
        $pdf->Cell(40, 6, 'Nama Prestasi', 1, 0, 'C', true);
        $pdf->Cell(30, 6, 'Jenis', 1, 0, 'C', true);
        $pdf->Cell(30, 6, 'Tingkat', 1, 0, 'C', true);
        $pdf->Cell(25, 6, 'Juara', 1, 0, 'C', true);
        $pdf->Cell(15, 6, 'Tahun', 1, 0, 'C', true);
        $pdf->Cell(15, 6, 'Poin', 1, 0, 'C', true);
        $pdf->Ln();
        
        $pdf->SetFont('helvetica', '', 8);
        $no = 1;
        foreach ($prestasi as $p) {
            $pdf->Cell(10, 5, $no++, 1, 0, 'C');
            $pdf->Cell(25, 5, $p['no_prestasi'] ?? 'PRS', 1, 0, 'C');
            $pdf->Cell(40, 5, substr($p['nama_prestasi'], 0, 20), 1, 0, 'L');
            $pdf->Cell(30, 5, ucfirst(str_replace('_', ' ', $p['jenis_prestasi'])), 1, 0, 'C');
            $pdf->Cell(30, 5, ucfirst($p['tingkat']), 1, 0, 'C');
            $pdf->Cell(25, 5, $p['juara'] ?? '-', 1, 0, 'C');
            $pdf->Cell(15, 5, $p['tahun'], 1, 0, 'C');
            $pdf->Cell(15, 5, '+ ' . $p['poin_prestasi'], 1, 0, 'C');
            $pdf->Ln();
        }
        $pdf->Ln(3);
    }
    
    // PELANGGARAN
    if (!empty($pelanggaran)) {
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 6, 'RIWAYAT PELANGGARAN', 0, 1, 'L');
        $pdf->Ln(2);
        
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(220, 220, 230);
        $pdf->Cell(10, 6, 'No', 1, 0, 'C', true);
        $pdf->Cell(25, 6, 'Tanggal', 1, 0, 'C', true);
        $pdf->Cell(40, 6, 'Pelanggaran', 1, 0, 'C', true);
        $pdf->Cell(20, 6, 'Poin', 1, 0, 'C', true);
        $pdf->Cell(35, 6, 'Guru', 1, 0, 'C', true);
        $pdf->Cell(25, 6, 'Status', 1, 0, 'C', true);
        $pdf->Ln();
        
        $pdf->SetFont('helvetica', '', 8);
        $no = 1;
        foreach ($pelanggaran as $p) {
            $pdf->Cell(10, 5, $no++, 1, 0, 'C');
            $pdf->Cell(25, 5, formatTanggal($p['tanggal_pelanggaran']), 1, 0, 'C');
            $pdf->Cell(40, 5, substr($p['nama_pelanggaran'], 0, 20), 1, 0, 'L');
            $pdf->Cell(20, 5, '+ ' . $p['poin'], 1, 0, 'C');
            $pdf->Cell(35, 5, $p['guru'] ?? '-', 1, 0, 'C');
            $pdf->Cell(25, 5, getStatusLabel($p['status']), 1, 0, 'C');
            $pdf->Ln();
        }
    }
    
    $pdf->Output('Detail_Siswa_' . $siswa['nama_siswa'] . '.pdf', 'I');
    exit;
}

include '../includes/header.php';
?>

<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
        <h4 class="mb-0">
            <i class="fas fa-user-graduate text-primary me-2"></i> Detail Siswa
        </h4>
        <div class="d-flex gap-2 flex-wrap">
            <a href="<?= APP_URL ?>admin/siswa.php" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Kembali
            </a>
            <a href="?id=<?= $id ?>&cetak=pdf" target="_blank" class="btn btn-primary btn-sm">
                <i class="fas fa-file-pdf me-1"></i> Cetak PDF
            </a>
            <?php if (!empty($siswa['no_hp'])): ?>
                <a href="https://wa.me/62<?= ltrim($siswa['no_hp'], '0') ?>" target="_blank" class="btn btn-success btn-sm">
                    <i class="fab fa-whatsapp me-1"></i> WhatsApp
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Toast Notification -->
    <?php if (isset($_SESSION['toast'])): ?>
        <div id="toastNotification" class="toast-container position-fixed top-0 end-0 p-2" style="z-index: 9999;">
            <div class="toast align-items-center text-white bg-<?= $_SESSION['toast']['type'] ?> border-0 show" role="alert" aria-live="assertive" aria-atomic="true" style="min-width: 280px; max-width: 380px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.12); font-size: 13px;">
                <div class="d-flex align-items-center p-2">
                    <div class="flex-shrink-0 ms-1">
                        <i class="fas fa-<?= $_SESSION['toast']['type'] == 'success' ? 'check-circle' : ($_SESSION['toast']['type'] == 'warning' ? 'exclamation-triangle' : 'times-circle') ?> fa-sm"></i>
                    </div>
                    <div class="toast-body flex-grow-1 text-start ms-2" style="font-size: 13px; font-weight: 500; padding: 2px 0; line-height: 1.4;">
                        <?= $_SESSION['toast']['message'] ?>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-0" data-bs-dismiss="toast" aria-label="Close" style="font-size: 10px; padding: 4px;"></button>
                </div>
            </div>
        </div>
        <?php unset($_SESSION['toast']); ?>
    <?php endif; ?>

    <!-- Data Siswa -->
    <div class="row g-3">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <?php if (!empty($siswa['foto']) && file_exists(APP_PATH . 'uploads/siswa/' . $siswa['foto'])): ?>
                        <img src="<?= APP_URL ?>uploads/siswa/<?= $siswa['foto'] ?>" 
                             alt="Foto" width="150" height="150" 
                             style="object-fit: cover; border-radius: 50%; border: 4px solid #e9ecef;">
                    <?php else: ?>
                        <div class="avatar-placeholder" style="width:150px;height:150px;border-radius:50%;background:#e9ecef;display:inline-flex;align-items:center;justify-content:center;color:#6c757d;font-weight:600;font-size:60px;margin:0 auto;">
                            <?= strtoupper(substr($siswa['nama_siswa'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    
                    <h5 class="mt-3 mb-1"><?= htmlspecialchars($siswa['nama_siswa']) ?></h5>
                    <p class="text-muted mb-0"><?= htmlspecialchars($siswa['nisn']) ?></p>
                    
                    <div class="mt-2">
                        <span class="badge bg-<?= $level['color'] ?> px-3 py-2" style="font-size: 14px;">
                            <i class="fas <?= $level['icon'] ?> me-1"></i> <?= $level['level'] ?> (<?= $poin_akhir ?> Poin)
                        </span>
                    </div>
                    
                    <!-- Ringkasan Poin -->
                    <div class="mt-3 p-2 bg-light rounded">
                        <div class="row g-0">
                            <div class="col-4">
                                <small class="text-muted" style="font-size:0.55rem;">Pelanggaran</small>
                                <div class="fw-bold text-danger" style="font-size:1rem;">+<?= $total_poin_pelanggaran ?></div>
                            </div>
                            <div class="col-4">
                                <small class="text-muted" style="font-size:0.55rem;">Prestasi</small>
                                <div class="fw-bold text-success" style="font-size:1rem;">+<?= $total_poin_prestasi ?></div>
                            </div>
                            <div class="col-4">
                                <small class="text-muted" style="font-size:0.55rem;">Sisa</small>
                                <div class="fw-bold text-<?= $level['color'] ?>" style="font-size:1rem;"><?= $poin_akhir ?></div>
                            </div>
                        </div>
                        <?php if ($total_poin_pelanggaran > 0 && $total_poin_prestasi > 0): ?>
                            <div class="mt-1 border-top pt-1">
                                <small class="text-muted" style="font-size:0.55rem;">
                                    <?= $total_poin_pelanggaran ?> - <?= $total_poin_prestasi ?> = <?= $poin_akhir ?>
                                </small>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($siswa['no_hp'])): ?>
                        <div class="mt-2">
                            <span class="badge bg-success px-3 py-2" style="font-size: 13px;">
                                <i class="fab fa-whatsapp me-1"></i> <?= htmlspecialchars($siswa['no_hp']) ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle text-primary me-2"></i> Informasi Siswa
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="text-muted mb-0" style="font-size: 12px;">NISN</label>
                            <p class="fw-bold mb-0"><?= htmlspecialchars($siswa['nisn']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted mb-0" style="font-size: 12px;">Nama Lengkap</label>
                            <p class="fw-bold mb-0"><?= htmlspecialchars($siswa['nama_siswa']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted mb-0" style="font-size: 12px;">Kelas</label>
                            <p class="fw-bold mb-0"><?= htmlspecialchars($siswa['nama_kelas'] ?? 'Belum ditentukan') ?></p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted mb-0" style="font-size: 12px;">Jenis Kelamin</label>
                            <p class="fw-bold mb-0"><?= getJenisKelaminLabel($siswa['jenis_kelamin']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted mb-0" style="font-size: 12px;">Tempat Lahir</label>
                            <p class="fw-bold mb-0"><?= htmlspecialchars($siswa['tempat_lahir'] ?? '-') ?></p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted mb-0" style="font-size: 12px;">Tanggal Lahir</label>
                            <p class="fw-bold mb-0"><?= formatTanggalIndo($siswa['tanggal_lahir']) ?></p>
                        </div>
                        <div class="col-md-12">
                            <label class="text-muted mb-0" style="font-size: 12px;">Alamat</label>
                            <p class="fw-bold mb-0"><?= htmlspecialchars($siswa['alamat'] ?? '-') ?></p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted mb-0" style="font-size: 12px;">No. HP</label>
                            <p class="fw-bold mb-0">
                                <?php if (!empty($siswa['no_hp'])): ?>
                                    <a href="https://wa.me/62<?= ltrim($siswa['no_hp'], '0') ?>" target="_blank" class="text-success">
                                        <i class="fab fa-whatsapp me-1"></i> <?= htmlspecialchars($siswa['no_hp']) ?>
                                    </a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted mb-0" style="font-size: 12px;">Sisa Poin</label>
                            <p class="fw-bold mb-0 text-<?= $level['color'] ?>"><?= $poin_akhir ?> Poin</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Riwayat Prestasi -->
    <div class="card mt-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="fas fa-trophy text-success me-2"></i> Riwayat Prestasi
                <span class="badge bg-secondary ms-2"><?= count($prestasi) ?> Prestasi</span>
                <span class="badge bg-success ms-2">+<?= $total_poin_prestasi ?> poin</span>
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="50">No</th>
                            <th>No. Prestasi</th>
                            <th>Nama Prestasi</th>
                            <th class="text-center">Jenis</th>
                            <th class="text-center">Tingkat</th>
                            <th class="text-center">Juara</th>
                            <th class="text-center">Tahun</th>
                            <th class="text-center">Poin</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($prestasi)): ?>
                            <?php $no = 1; foreach ($prestasi as $p): ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?></td>
                                    <td><span class="badge bg-primary"><?= $p['no_prestasi'] ?? 'PRS' ?></span></td>
                                    <td><?= htmlspecialchars($p['nama_prestasi']) ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-<?= $p['jenis_prestasi'] == 'akademik' ? 'success' : 'info' ?>">
                                            <?= ucfirst(str_replace('_', ' ', $p['jenis_prestasi'])) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-<?= 
                                            $p['tingkat'] == 'internasional' ? 'danger' : 
                                            ($p['tingkat'] == 'nasional' ? 'warning' : 
                                            ($p['tingkat'] == 'provinsi' ? 'info' : 
                                            ($p['tingkat'] == 'kota' ? 'success' : 
                                            ($p['tingkat'] == 'kecamatan' ? 'primary' : 'secondary')))) ?>">
                                            <?= ucfirst($p['tingkat']) ?>
                                        </span>
                                    </td>
                                    <td class="text-center"><?= htmlspecialchars($p['juara'] ?? '-') ?></td>
                                    <td class="text-center"><?= $p['tahun'] ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-success">+<?= $p['poin_prestasi'] ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="table-success fw-bold">
                                <td colspan="7" class="text-end">Total Poin Prestasi</td>
                                <td class="text-center">+<?= $total_poin_prestasi ?></td>
                            </tr>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    <i class="fas fa-trophy fa-2x mb-2 d-block text-warning opacity-50"></i>
                                    Siswa ini belum memiliki prestasi
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Riwayat Pelanggaran -->
    <div class="card mt-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="fas fa-history text-warning me-2"></i> Riwayat Pelanggaran
                <span class="badge bg-secondary ms-2"><?= count($pelanggaran) ?> Pelanggaran</span>
                <span class="badge bg-danger ms-2">+<?= $total_poin_pelanggaran ?> poin</span>
            </h5>
            <?php if (!empty($pelanggaran)): ?>
                <span class="text-muted" style="font-size: 12px;">
                    <i class="fas fa-print me-1"></i> Cetak: 
                    <a href="?id=<?= $id ?>&cetak=pdf" target="_blank" class="text-primary">PDF</a>
                </span>
            <?php endif; ?>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="50">No</th>
                            <th>Tanggal</th>
                            <th>Pelanggaran</th>
                            <th>Poin</th>
                            <th>Guru</th>
                            <th>Status</th>
                            <th class="text-center">Bukti</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($pelanggaran)): ?>
                            <?php $no = 1; foreach ($pelanggaran as $p): ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?></td>
                                    <td><?= formatTanggalIndo($p['tanggal_pelanggaran']) ?></td>
                                    <td><?= htmlspecialchars($p['nama_pelanggaran']) ?></td>
                                    <td><span class="badge bg-danger">+<?= $p['poin'] ?></span></td>
                                    <td><?= htmlspecialchars($p['guru'] ?? '-') ?></td>
                                    <td>
                                        <span class="badge bg-<?= getStatusBadge($p['status']) ?>">
                                            <?= getStatusLabel($p['status']) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php if (!empty($p['bukti']) && file_exists(APP_PATH . 'uploads/bukti_pelanggaran/' . $p['bukti'])): ?>
                                            <a href="<?= APP_URL ?>uploads/bukti_pelanggaran/<?= $p['bukti'] ?>" 
                                               target="_blank" class="btn btn-outline-success btn-sm" title="Lihat Bukti">
                                                <i class="fas fa-camera"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="table-danger fw-bold">
                                <td colspan="3" class="text-end">Total Poin Pelanggaran</td>
                                <td>+<?= $total_poin_pelanggaran ?></td>
                                <td colspan="3"></td>
                            </tr>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="fas fa-check-circle fa-2x mb-2 d-block text-success opacity-50"></i>
                                    Siswa ini belum memiliki riwayat pelanggaran
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if (!empty($pelanggaran)): ?>
            <div class="card-footer bg-transparent text-muted" style="font-size: 12px;">
                <i class="fas fa-info-circle me-1"></i>
                Menampilkan <?= count($pelanggaran) ?> riwayat pelanggaran | 
                Total Poin: +<?= $total_poin_pelanggaran ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Auto close toast setelah 3 detik
document.addEventListener('DOMContentLoaded', function() {
    var toastElement = document.querySelector('.toast');
    if (toastElement) {
        setTimeout(function() {
            var toast = bootstrap.Toast.getInstance(toastElement);
            if (toast) {
                toast.hide();
            } else {
                toastElement.style.opacity = '0';
                setTimeout(function() {
                    toastElement.style.display = 'none';
                }, 300);
            }
        }, 3000);
    }
});
</script>

<style>
/* Toast Kecil */
.toast-container .toast {
    border-radius: 8px !important;
    box-shadow: 0 4px 20px rgba(0,0,0,0.12) !important;
}

.toast-container .toast .toast-body {
    font-size: 13px !important;
    font-weight: 500;
    padding: 2px 0 !important;
}

/* Avatar */
.avatar-placeholder {
    background: #e9ecef;
    color: #6c757d;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

/* Responsive */
@media (max-width: 768px) {
    .toast-container .toast {
        min-width: 200px !important;
        max-width: 280px !important;
    }
    
    .toast-container .toast .toast-body {
        font-size: 11px !important;
    }
}

@media (max-width: 576px) {
    .toast-container .toast {
        min-width: 160px !important;
        max-width: 240px !important;
    }
    
    .toast-container .toast .toast-body {
        font-size: 10px !important;
    }
}

/* Print Styles */
@media print {
    .btn,
    .btn-group,
    .toast-container {
        display: none !important;
    }
    
    .card {
        border: 1px solid #dee2e6 !important;
        box-shadow: none !important;
    }
}
</style>

<?php include '../includes/footer.php'; ?>