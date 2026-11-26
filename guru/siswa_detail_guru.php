<?php
// guru/siswa_detail_guru.php
include '../includes/config.php';
checkLogin();

// Cek role
if (!hasRole(['guru', 'bk'])) {
    $_SESSION['toast'] = [
        'message' => 'Anda tidak memiliki akses ke halaman ini!',
        'type' => 'danger'
    ];
    header("Location: " . APP_URL . "login.php");
    exit();
}

$page_title = 'Detail Siswa';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$siswa = getSiswaById($id);

if (!$siswa) {
    $_SESSION['toast'] = [
        'message' => 'Data siswa tidak ditemukan!',
        'type' => 'danger'
    ];
    header("Location: " . APP_URL . "guru/data_siswa_guru.php");
    exit();
}

$pelanggaran = getPelanggaranBySiswa($id);
$conn = getDB();

// ============================================
// AMBIL DATA PRESTASI SISWA
// ============================================
$query_prestasi = "SELECT p.*, k.nama_kelas 
                   FROM prestasi_siswa p
                   LEFT JOIN siswa s ON p.siswa_id = s.id
                   LEFT JOIN kelas k ON s.kelas_id = k.id
                   WHERE p.siswa_id = $id
                   ORDER BY p.tahun DESC, p.created_at DESC";
$prestasi = getRows($query_prestasi);

// ============================================
// HITUNG TOTAL POIN DENGAN BENAR
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

// Jika poin di tabel siswa berbeda, gunakan yang sudah dihitung
$poin_display = $poin_akhir;

include '../includes/header_guru.php';
?>

<div class="container-fluid dashboard-container">
    <!-- Header -->
    <div class="dashboard-header">
        <div>
            <h4 class="dashboard-title">
                <i class="fas fa-user-graduate text-primary me-2"></i> Detail Siswa
            </h4>
            <small class="text-muted dashboard-subtitle">
                <i class="fas fa-id-card me-1"></i> NISN: <strong><?= htmlspecialchars($siswa['nisn']) ?></strong>
            </small>
        </div>
        <div class="header-right">
            <a href="<?= APP_URL ?>guru/data_siswa_guru.php" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Kembali
            </a>
            <a href="<?= APP_URL ?>guru/input_pelanggaran.php?siswa=<?= $siswa['id'] ?>" class="btn btn-danger btn-sm">
                <i class="fas fa-plus-circle me-1"></i> Input Pelanggaran
            </a>
        </div>
    </div>

    <!-- Data Siswa -->
    <div class="row g-2 g-md-3">
        <!-- Profile Card -->
        <div class="col-12 col-md-4">
            <div class="card">
                <div class="card-body text-center p-2 p-md-3">
                    <?php if (!empty($siswa['foto']) && file_exists(APP_PATH . 'uploads/siswa/' . $siswa['foto'])): ?>
                        <img src="<?= APP_URL ?>uploads/siswa/<?= $siswa['foto'] ?>" 
                             alt="Foto" width="100" height="100" 
                             class="profile-img"
                             style="object-fit: cover; border-radius: 50%; border: 3px solid #e9ecef;">
                    <?php else: ?>
                        <div class="avatar-placeholder" style="width:100px;height:100px;border-radius:50%;background:#e9ecef;display:inline-flex;align-items:center;justify-content:center;color:#6c757d;font-weight:600;font-size:40px;">
                            <?= strtoupper(substr($siswa['nama_siswa'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    
                    <h5 class="mt-2 mb-0" style="font-size:1rem;"><?= htmlspecialchars($siswa['nama_siswa']) ?></h5>
                    <p class="text-muted mb-0" style="font-size: 0.75rem;"><?= htmlspecialchars($siswa['nisn']) ?></p>
                    
                    <?php 
                    $level = getPoinLevel($poin_display);
                    ?>
                    <div class="mt-2">
                        <span class="badge bg-<?= $level['color'] ?> px-3 py-2" style="font-size: 0.75rem;">
                            <i class="fas <?= $level['icon'] ?> me-1"></i> <?= $level['level'] ?> (<?= $poin_display ?> Poin)
                        </span>
                    </div>
                    <div class="mt-1">
                        <small class="text-muted" style="font-size:0.7rem;">
                            <i class="fas fa-school me-1"></i> Kelas: <?= htmlspecialchars($siswa['nama_kelas'] ?? 'Belum ditentukan') ?>
                        </small>
                    </div>
                    
                    <!-- Ringkasan Poin -->
                    <div class="mt-2 p-2 bg-light rounded">
                        <div class="row g-0">
                            <div class="col-4">
                                <small class="text-muted" style="font-size:0.55rem;">Pelanggaran</small>
                                <div class="fw-bold text-danger" style="font-size:0.9rem;">+<?= $total_poin_pelanggaran ?></div>
                            </div>
                            <div class="col-4">
                                <small class="text-muted" style="font-size:0.55rem;">Prestasi</small>
                                <div class="fw-bold text-success" style="font-size:0.9rem;">+<?= $total_poin_prestasi ?></div>
                            </div>
                            <div class="col-4">
                                <small class="text-muted" style="font-size:0.55rem;">Sisa Poin</small>
                                <div class="fw-bold text-<?= $level['color'] ?>" style="font-size:0.9rem;"><?= $poin_display ?></div>
                            </div>
                        </div>
                        <?php if ($total_poin_pelanggaran > 0 && $total_poin_prestasi > 0): ?>
                            <div class="row g-0 mt-1 border-top pt-1">
                                <div class="col-12">
                                    <small class="text-muted" style="font-size:0.55rem;">
                                        <?= $total_poin_pelanggaran ?> - <?= $total_poin_prestasi ?> = <?= $poin_display ?>
                                    </small>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mt-2">
                        <a href="<?= APP_URL ?>guru/input_pelanggaran.php?siswa=<?= $siswa['id'] ?>" class="btn btn-danger btn-sm w-100" style="font-size:0.8rem;">
                            <i class="fas fa-plus-circle me-1"></i> Input Pelanggaran
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Informasi Lengkap -->
        <div class="col-12 col-md-8">
            <div class="card">
                <div class="card-header py-2">
                    <h5 class="card-title mb-0" style="font-size:0.9rem;">
                        <i class="fas fa-info-circle text-primary me-2"></i> Informasi Siswa
                    </h5>
                </div>
                <div class="card-body p-2 p-md-3">
                    <div class="row g-2">
                        <div class="col-6 col-md-6">
                            <label class="text-muted mb-0" style="font-size: 0.6rem;">NISN</label>
                            <p class="fw-bold mb-0" style="font-size: 0.8rem;"><?= htmlspecialchars($siswa['nisn']) ?></p>
                        </div>
                        <div class="col-6 col-md-6">
                            <label class="text-muted mb-0" style="font-size: 0.6rem;">Nama Lengkap</label>
                            <p class="fw-bold mb-0" style="font-size: 0.8rem;"><?= htmlspecialchars($siswa['nama_siswa']) ?></p>
                        </div>
                        <div class="col-6 col-md-6">
                            <label class="text-muted mb-0" style="font-size: 0.6rem;">Kelas</label>
                            <p class="fw-bold mb-0" style="font-size: 0.8rem;"><?= htmlspecialchars($siswa['nama_kelas'] ?? 'Belum ditentukan') ?></p>
                        </div>
                        <div class="col-6 col-md-6">
                            <label class="text-muted mb-0" style="font-size: 0.6rem;">Jenis Kelamin</label>
                            <p class="fw-bold mb-0" style="font-size: 0.8rem;"><?= getJenisKelaminLabel($siswa['jenis_kelamin']) ?></p>
                        </div>
                        <div class="col-6 col-md-6">
                            <label class="text-muted mb-0" style="font-size: 0.6rem;">Tempat Lahir</label>
                            <p class="fw-bold mb-0" style="font-size: 0.8rem;"><?= htmlspecialchars($siswa['tempat_lahir'] ?? '-') ?></p>
                        </div>
                        <div class="col-6 col-md-6">
                            <label class="text-muted mb-0" style="font-size: 0.6rem;">Tanggal Lahir</label>
                            <p class="fw-bold mb-0" style="font-size: 0.8rem;"><?= formatTanggalIndo($siswa['tanggal_lahir']) ?></p>
                        </div>
                        <div class="col-12">
                            <label class="text-muted mb-0" style="font-size: 0.6rem;">Alamat</label>
                            <p class="fw-bold mb-0" style="font-size: 0.8rem;"><?= htmlspecialchars($siswa['alamat'] ?? '-') ?></p>
                        </div>
                        <div class="col-6 col-md-6">
                            <label class="text-muted mb-0" style="font-size: 0.6rem;">No. HP</label>
                            <p class="fw-bold mb-0" style="font-size: 0.8rem;"><?= htmlspecialchars($siswa['no_hp'] ?? '-') ?></p>
                        </div>
                        <div class="col-6 col-md-6">
                            <label class="text-muted mb-0" style="font-size: 0.6rem;">Sisa Poin</label>
                            <p class="fw-bold mb-0 text-<?= $level['color'] ?>" style="font-size: 0.8rem;"><?= $poin_display ?> Poin</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- TAB PRESTASI & PELANGGARAN -->
    <!-- ========================================== -->
    <div class="card mt-3">
        <div class="card-header py-2">
            <ul class="nav nav-tabs card-header-tabs" id="siswaTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="pelanggaran-tab" data-bs-toggle="tab" data-bs-target="#pelanggaran" type="button" role="tab" style="font-size:0.75rem; padding:4px 10px;">
                        <i class="fas fa-exclamation-triangle text-danger me-1"></i> Pelanggaran
                        <span class="badge bg-danger ms-1" style="font-size:0.6rem;"><?= count($pelanggaran) ?></span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="prestasi-tab" data-bs-toggle="tab" data-bs-target="#prestasi" type="button" role="tab" style="font-size:0.75rem; padding:4px 10px;">
                        <i class="fas fa-trophy text-success me-1"></i> Prestasi
                        <span class="badge bg-success ms-1" style="font-size:0.6rem;"><?= count($prestasi) ?></span>
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body p-0">
            <div class="tab-content">
                <!-- ========================================== -->
                <!-- TAB PELANGGARAN -->
                <!-- ========================================== -->
                <div class="tab-pane fade show active" id="pelanggaran" role="tabpanel">
                    <!-- DESKTOP VIEW -->
                    <div class="d-none d-md-block">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover mb-0 table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th width="35" class="text-center">No</th>
                                        <th>Tanggal</th>
                                        <th>Pelanggaran</th>
                                        <th class="text-center">Poin</th>
                                        <th>Guru</th>
                                        <th class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($pelanggaran)): ?>
                                        <?php $no = 1; foreach ($pelanggaran as $p): ?>
                                            <tr>
                                                <td class="text-center"><?= $no++ ?></td>
                                                <td><?= formatTanggalIndo($p['tanggal_pelanggaran']) ?></td>
                                                <td><?= htmlspecialchars($p['nama_pelanggaran']) ?></td>
                                                <td class="text-center">
                                                    <span class="badge bg-danger">+<?= $p['poin'] ?></span>
                                                </td>
                                                <td><?= htmlspecialchars($p['guru'] ?? '-') ?></td>
                                                <td class="text-center">
                                                    <span class="badge bg-<?= getStatusBadge($p['status']) ?>">
                                                        <?= getStatusLabel($p['status']) ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <tr class="table-danger fw-bold">
                                            <td colspan="3" class="text-end">Total Poin Pelanggaran</td>
                                            <td class="text-center">+<?= $total_poin_pelanggaran ?></td>
                                            <td colspan="2"></td>
                                        </tr>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-3">
                                                <i class="fas fa-check-circle fa-2x mb-1 d-block text-success opacity-50"></i>
                                                Siswa ini belum memiliki riwayat pelanggaran
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- MOBILE VIEW -->
                    <div class="d-block d-md-none">
                        <?php if (!empty($pelanggaran)): ?>
                            <?php $no = 1; foreach ($pelanggaran as $p): ?>
                                <div class="card mobile-card">
                                    <div class="card-body p-2">
                                        <div class="row g-1">
                                            <div class="col-7">
                                                <span class="badge bg-secondary" style="font-size:0.6rem;">#<?= $no++ ?></span>
                                                <span class="fw-bold" style="font-size:0.75rem;"><?= htmlspecialchars($p['nama_pelanggaran']) ?></span>
                                            </div>
                                            <div class="col-5 text-end">
                                                <span class="badge bg-danger" style="font-size:0.65rem;">+<?= $p['poin'] ?></span>
                                            </div>
                                        </div>
                                        <div class="row g-1 mt-1">
                                            <div class="col-6">
                                                <small class="text-muted" style="font-size:0.55rem;">Tanggal</small>
                                                <div style="font-size:0.65rem;"><?= formatTanggalIndo($p['tanggal_pelanggaran']) ?></div>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted" style="font-size:0.55rem;">Status</small>
                                                <div>
                                                    <span class="badge bg-<?= getStatusBadge($p['status']) ?>" style="font-size:0.55rem;">
                                                        <?= getStatusLabel($p['status']) ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <?php if (!empty($p['guru'])): ?>
                                            <div class="row g-1 mt-1">
                                                <div class="col-12">
                                                    <small class="text-muted" style="font-size:0.55rem;">Guru</small>
                                                    <div style="font-size:0.65rem;"><?= htmlspecialchars($p['guru']) ?></div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <div class="card" style="background:#fff3f3; border:1px solid #f5c6cb;">
                                <div class="card-body p-2 text-center">
                                    <span class="text-muted" style="font-size:0.6rem;">Total Poin Pelanggaran</span>
                                    <div class="fw-bold text-danger" style="font-size:1rem;">+<?= $total_poin_pelanggaran ?></div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="card">
                                <div class="card-body p-3 text-center text-muted">
                                    <i class="fas fa-check-circle fa-2x mb-1 d-block text-success opacity-50"></i>
                                    <span style="font-size:0.7rem;">Siswa ini belum memiliki riwayat pelanggaran</span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($pelanggaran)): ?>
                        <div class="card-footer bg-transparent text-muted py-1 d-none d-md-block" style="font-size:0.65rem;">
                            <i class="fas fa-info-circle me-1"></i>
                            Menampilkan <?= count($pelanggaran) ?> pelanggaran | Total Poin: +<?= $total_poin_pelanggaran ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- ========================================== -->
                <!-- TAB PRESTASI (DENGAN NILAI POSITIF) -->
                <!-- ========================================== -->
                <div class="tab-pane fade" id="prestasi" role="tabpanel">
                    <!-- DESKTOP VIEW -->
                    <div class="d-none d-md-block">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover mb-0 table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th width="35" class="text-center">No</th>
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
                                                <td>
                                                    <span class="badge bg-primary" style="font-size:0.6rem;"><?= $p['no_prestasi'] ?? 'PRS' ?></span>
                                                </td>
                                                <td><?= htmlspecialchars($p['nama_prestasi']) ?></td>
                                                <td class="text-center">
                                                    <span class="badge bg-<?= $p['jenis_prestasi'] == 'akademik' ? 'success' : 'info' ?>" style="font-size:0.55rem;">
                                                        <?= ucfirst(str_replace('_', ' ', $p['jenis_prestasi'])) ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-<?= 
                                                        $p['tingkat'] == 'internasional' ? 'danger' : 
                                                        ($p['tingkat'] == 'nasional' ? 'warning' : 
                                                        ($p['tingkat'] == 'provinsi' ? 'info' : 
                                                        ($p['tingkat'] == 'kota' ? 'success' : 
                                                        ($p['tingkat'] == 'kecamatan' ? 'primary' : 'secondary')))) ?>" style="font-size:0.55rem;">
                                                        <?= ucfirst($p['tingkat']) ?>
                                                    </span>
                                                </td>
                                                <td class="text-center"><?= htmlspecialchars($p['juara'] ?? '-') ?></td>
                                                <td class="text-center"><?= $p['tahun'] ?></td>
                                                <td class="text-center">
                                                    <span class="badge bg-success" style="font-size:0.65rem;">+<?= $p['poin_prestasi'] ?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <tr class="table-success fw-bold">
                                            <td colspan="7" class="text-end">Total Poin Prestasi</td>
                                            <td class="text-center">+<?= $total_poin_prestasi ?></td>
                                        </tr>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-3">
                                                <i class="fas fa-trophy fa-2x mb-1 d-block text-warning opacity-50"></i>
                                                Siswa ini belum memiliki prestasi
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- MOBILE VIEW -->
                    <div class="d-block d-md-none">
                        <?php if (!empty($prestasi)): ?>
                            <?php $no = 1; foreach ($prestasi as $p): ?>
                                <div class="card mobile-card">
                                    <div class="card-body p-2">
                                        <div class="row g-1">
                                            <div class="col-8">
                                                <span class="badge bg-secondary" style="font-size:0.6rem;">#<?= $no++ ?></span>
                                                <span class="badge bg-primary" style="font-size:0.55rem;"><?= $p['no_prestasi'] ?? 'PRS' ?></span>
                                                <span class="fw-bold" style="font-size:0.7rem;"><?= htmlspecialchars($p['nama_prestasi']) ?></span>
                                            </div>
                                            <div class="col-4 text-end">
                                                <span class="badge bg-success" style="font-size:0.65rem;">+<?= $p['poin_prestasi'] ?></span>
                                            </div>
                                        </div>
                                        <div class="row g-1 mt-1">
                                            <div class="col-6">
                                                <small class="text-muted" style="font-size:0.55rem;">Jenis</small>
                                                <div>
                                                    <span class="badge bg-<?= $p['jenis_prestasi'] == 'akademik' ? 'success' : 'info' ?>" style="font-size:0.5rem;">
                                                        <?= ucfirst(str_replace('_', ' ', $p['jenis_prestasi'])) ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted" style="font-size:0.55rem;">Tingkat</small>
                                                <div>
                                                    <span class="badge bg-<?= 
                                                        $p['tingkat'] == 'internasional' ? 'danger' : 
                                                        ($p['tingkat'] == 'nasional' ? 'warning' : 
                                                        ($p['tingkat'] == 'provinsi' ? 'info' : 
                                                        ($p['tingkat'] == 'kota' ? 'success' : 
                                                        ($p['tingkat'] == 'kecamatan' ? 'primary' : 'secondary')))) ?>" style="font-size:0.5rem;">
                                                        <?= ucfirst($p['tingkat']) ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <?php if (!empty($p['juara']) && $p['juara'] != '-') : ?>
                                            <div class="row g-1 mt-1">
                                                <div class="col-6">
                                                    <small class="text-muted" style="font-size:0.55rem;">Juara</small>
                                                    <div style="font-size:0.65rem;"><?= htmlspecialchars($p['juara']) ?></div>
                                                </div>
                                                <div class="col-6">
                                                    <small class="text-muted" style="font-size:0.55rem;">Tahun</small>
                                                    <div style="font-size:0.65rem;"><?= $p['tahun'] ?></div>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="row g-1 mt-1">
                                                <div class="col-12">
                                                    <small class="text-muted" style="font-size:0.55rem;">Tahun</small>
                                                    <div style="font-size:0.65rem;"><?= $p['tahun'] ?></div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <div class="card" style="background:#f0fff4; border:1px solid #b7eb8f;">
                                <div class="card-body p-2 text-center">
                                    <span class="text-muted" style="font-size:0.6rem;">Total Poin Prestasi</span>
                                    <div class="fw-bold text-success" style="font-size:1rem;">+<?= $total_poin_prestasi ?></div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="card">
                                <div class="card-body p-3 text-center text-muted">
                                    <i class="fas fa-trophy fa-2x mb-1 d-block text-warning opacity-50"></i>
                                    <span style="font-size:0.7rem;">Siswa ini belum memiliki prestasi</span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($prestasi)): ?>
                        <div class="card-footer bg-transparent text-muted py-1 d-none d-md-block" style="font-size:0.65rem;">
                            <i class="fas fa-info-circle me-1"></i>
                            Menampilkan <?= count($prestasi) ?> prestasi | Total Poin: +<?= $total_poin_prestasi ?> poin
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Tombol Aksi -->
    <div class="mt-3 d-flex flex-wrap gap-1">
        <a href="<?= APP_URL ?>guru/data_siswa_guru.php" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Kembali
        </a>
        <a href="<?= APP_URL ?>guru/cetak_bukti.php?siswa=<?= $siswa['id'] ?>" class="btn btn-primary btn-sm">
            <i class="fas fa-print me-1"></i> Cetak Bukti
        </a>
    </div>
</div>

<style>
/* ============================================
   MOBILE FRIENDLY STYLES
   ============================================ */

.dashboard-container {
    padding: 6px !important;
    max-width: 100%;
    overflow-x: hidden;
}

.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    margin-bottom: 10px;
    gap: 4px;
}

.dashboard-title {
    font-size: 1rem;
    margin-bottom: 0;
}

.dashboard-subtitle {
    font-size: 0.65rem;
}

.header-right {
    display: flex;
    gap: 4px;
    flex-wrap: wrap;
}

/* Card */
.card {
    border: none;
    border-radius: 10px;
    box-shadow: 0 1px 6px rgba(0,0,0,0.06);
    margin-bottom: 10px;
    background: #fff;
}

.card-header {
    background: transparent;
    border-bottom: 1px solid #e9ecef;
    padding: 8px 12px;
}

.card-body {
    padding: 12px;
}

.card-footer {
    background: transparent;
    border-top: 1px solid #e9ecef;
    padding: 6px 12px;
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

/* Profile Image */
.profile-img {
    width: 100px !important;
    height: 100px !important;
}

/* Table Styles */
.table-bordered {
    border: 1px solid #dee2e6;
}

.table-bordered th,
.table-bordered td {
    border: 1px solid #dee2e6;
    padding: 4px 6px;
    vertical-align: middle;
}

.table thead th {
    background: #f8f9fa;
    font-weight: 600;
    border-bottom: 2px solid #dee2e6;
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.table tbody td {
    font-size: 0.75rem;
}

.table tbody tr:hover {
    background-color: rgba(108, 99, 255, 0.04);
}

.table-sm th,
.table-sm td {
    padding: 4px 6px !important;
}

/* Badge */
.badge {
    padding: 2px 6px !important;
}

/* Nav Tabs */
.nav-tabs .nav-link {
    border: none;
    border-bottom: 2px solid transparent;
    color: #6c757d;
    font-size: 0.75rem;
    padding: 4px 8px;
}

.nav-tabs .nav-link:hover {
    border-color: transparent;
    color: #495057;
}

.nav-tabs .nav-link.active {
    color: #4F46E5;
    background: transparent;
    border-bottom: 2px solid #4F46E5;
}

.nav-tabs .nav-link .badge {
    font-size: 0.55rem !important;
    padding: 1px 4px !important;
}

/* Buttons */
.btn-sm {
    padding: 3px 8px !important;
    font-size: 0.75rem !important;
    border-radius: 6px !important;
}

/* Mobile Card */
.mobile-card {
    border: 1px solid #e9ecef !important;
    border-radius: 8px !important;
    margin-bottom: 6px !important;
}

.mobile-card .card-body {
    padding: 8px !important;
}

/* ============================================
   RESPONSIVE
   ============================================ */

/* Mobile */
@media (max-width: 576px) {
    .dashboard-container {
        padding: 4px !important;
    }
    
    .dashboard-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 4px;
    }
    
    .dashboard-title {
        font-size: 0.85rem;
    }
    
    .dashboard-subtitle {
        font-size: 0.6rem;
    }
    
    .header-right {
        width: 100%;
    }
    
    .header-right .btn {
        flex: 1;
        text-align: center;
        font-size: 0.7rem !important;
        padding: 2px 6px !important;
    }
    
    .card-body {
        padding: 8px;
    }
    
    .profile-img {
        width: 70px !important;
        height: 70px !important;
    }
    
    .avatar-placeholder {
        width: 70px !important;
        height: 70px !important;
        font-size: 28px !important;
    }
    
    .table th,
    .table td {
        font-size: 0.6rem !important;
        padding: 2px 4px !important;
    }
    
    .badge {
        font-size: 0.5rem !important;
        padding: 1px 4px !important;
    }
    
    .btn-sm {
        font-size: 0.65rem !important;
        padding: 2px 6px !important;
    }
    
    .nav-tabs .nav-link {
        font-size: 0.65rem !important;
        padding: 3px 6px !important;
    }
    
    .mt-3 {
        margin-top: 8px !important;
    }
    
    .g-2 {
        gap: 4px !important;
    }
    
    .mobile-card .card-body {
        padding: 6px !important;
    }
}

/* Tablet */
@media (min-width: 577px) and (max-width: 768px) {
    .dashboard-container {
        padding: 8px !important;
    }
    
    .dashboard-title {
        font-size: 0.95rem;
    }
    
    .profile-img {
        width: 85px !important;
        height: 85px !important;
    }
    
    .avatar-placeholder {
        width: 85px !important;
        height: 85px !important;
        font-size: 34px !important;
    }
    
    .table th,
    .table td {
        font-size: 0.65rem !important;
        padding: 3px 5px !important;
    }
    
    .badge {
        font-size: 0.6rem !important;
    }
}

/* Desktop */
@media (min-width: 769px) {
    .dashboard-container {
        padding: 12px !important;
    }
    
    .profile-img {
        width: 120px !important;
        height: 120px !important;
    }
    
    .avatar-placeholder {
        width: 120px !important;
        height: 120px !important;
        font-size: 48px !important;
    }
    
    .table th {
        font-size: 0.7rem !important;
    }
    
    .table td {
        font-size: 0.75rem !important;
    }
}
</style>

<?php include '../includes/footer.php'; ?>