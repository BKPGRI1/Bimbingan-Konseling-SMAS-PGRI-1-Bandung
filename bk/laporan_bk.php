<?php
// bk/laporan_bk.php - PERBAIKAN AKUMULASI SISWA
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

$page_title = 'Laporan BK';
$conn = getDB();

// ============================================
// HANDLE FILTER
// ============================================
$bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : date('m');
$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : date('Y');
$jenis_laporan = isset($_GET['jenis']) ? $_GET['jenis'] : 'pelanggaran';

// ============================================
// AMBIL DATA
// ============================================

// Detail Pelanggaran Bulan Ini
$query_detail = "SELECT p.*, s.nama_siswa, s.nisn, k.nama_kelas, 
                 jp.nama_pelanggaran, jp.kategori, jp.poin
                 FROM pelanggaran p
                 LEFT JOIN siswa s ON p.siswa_id = s.id
                 LEFT JOIN kelas k ON s.kelas_id = k.id
                 LEFT JOIN jenis_pelanggaran jp ON p.jenis_pelanggaran_id = jp.id
                 WHERE MONTH(p.tanggal_pelanggaran) = $bulan 
                 AND YEAR(p.tanggal_pelanggaran) = $tahun
                 AND p.status = 'disetujui'
                 ORDER BY p.tanggal_pelanggaran DESC";
$detail_data = getRows($query_detail);

// ============================================
// HITUNG AKUMULASI PER SISWA DENGAN BENAR (TANPA DUPLIKAT)
// ============================================
$siswa_akumulasi = [];

// Ambil semua siswa yang punya pelanggaran di bulan ini
$siswa_ids = array_unique(array_column($detail_data, 'siswa_id'));

foreach ($siswa_ids as $siswa_id) {
    // Ambil data siswa
    $siswa = getSiswaById($siswa_id);
    if (!$siswa) continue;
    
    // Hitung total pelanggaran
    $query_pel = "SELECT COUNT(*) as total, SUM(poin_diperoleh) as total_poin 
                  FROM pelanggaran 
                  WHERE siswa_id = $siswa_id 
                  AND status = 'disetujui'
                  AND MONTH(tanggal_pelanggaran) = $bulan 
                  AND YEAR(tanggal_pelanggaran) = $tahun";
    $result_pel = $conn->query($query_pel);
    $pel_data = $result_pel->fetch_assoc();
    
    // Hitung total prestasi
    $query_pres = "SELECT SUM(poin_prestasi) as total_poin 
                   FROM prestasi_siswa 
                   WHERE siswa_id = $siswa_id";
    $result_pres = $conn->query($query_pres);
    $pres_data = $result_pres->fetch_assoc();
    
    $poin_pelanggaran = (int)$pel_data['total_poin'];
    $total_pelanggaran = (int)$pel_data['total'];
    $poin_prestasi = (int)$pres_data['total_poin'];
    $poin_akhir = $poin_pelanggaran - $poin_prestasi;
    if ($poin_akhir < 0) $poin_akhir = 0;
    
    $siswa_akumulasi[] = [
        'id' => $siswa_id,
        'nisn' => $siswa['nisn'],
        'nama' => $siswa['nama_siswa'],
        'kelas' => $siswa['nama_kelas'] ?? '-',
        'total_pelanggaran' => $total_pelanggaran,
        'poin_pelanggaran' => $poin_pelanggaran,
        'poin_prestasi' => $poin_prestasi,
        'poin_akhir' => $poin_akhir
    ];
}

// Urutkan berdasarkan poin akhir tertinggi
usort($siswa_akumulasi, function($a, $b) {
    return $b['poin_akhir'] - $a['poin_akhir'];
});

// Hitung total
$total_poin_pelanggaran = array_sum(array_column($siswa_akumulasi, 'poin_pelanggaran'));
$total_poin_prestasi = array_sum(array_column($siswa_akumulasi, 'poin_prestasi'));
$total_poin_akhir = array_sum(array_column($siswa_akumulasi, 'poin_akhir'));

// Prestasi Bulan Ini
$query_prestasi_bulan = "SELECT p.*, s.nama_siswa, s.nisn, k.nama_kelas
                         FROM prestasi_siswa p
                         LEFT JOIN siswa s ON p.siswa_id = s.id
                         LEFT JOIN kelas k ON s.kelas_id = k.id
                         WHERE MONTH(p.created_at) = $bulan 
                         AND YEAR(p.created_at) = $tahun
                         ORDER BY p.created_at DESC";
$prestasi_bulan_data = getRows($query_prestasi_bulan);
$prestasi_bulan = count($prestasi_bulan_data);
$poin_prestasi_bulan = array_sum(array_column($prestasi_bulan_data, 'poin_prestasi'));

// Semua Prestasi
$query_semua_prestasi = "SELECT p.*, s.nama_siswa, s.nisn, k.nama_kelas
                         FROM prestasi_siswa p
                         LEFT JOIN siswa s ON p.siswa_id = s.id
                         LEFT JOIN kelas k ON s.kelas_id = k.id
                         ORDER BY p.created_at DESC";
$semua_prestasi_data = getRows($query_semua_prestasi);
$total_prestasi = count($semua_prestasi_data);
$total_poin_prestasi_all = array_sum(array_column($semua_prestasi_data, 'poin_prestasi'));

// Semua Pelanggaran
$query_semua_pelanggaran = "SELECT p.*, s.nama_siswa, s.nisn, k.nama_kelas, 
                           jp.nama_pelanggaran, jp.kategori, jp.poin,
                           u.nama_lengkap as guru
                           FROM pelanggaran p
                           LEFT JOIN siswa s ON p.siswa_id = s.id
                           LEFT JOIN kelas k ON s.kelas_id = k.id
                           LEFT JOIN jenis_pelanggaran jp ON p.jenis_pelanggaran_id = jp.id
                           LEFT JOIN users u ON p.guru_id = u.id
                           WHERE p.status = 'disetujui'
                           ORDER BY p.tanggal_pelanggaran DESC, p.created_at DESC";
$semua_pelanggaran = getRows($query_semua_pelanggaran);

// Siswa Bermasalah (dengan poin akhir yang benar)
$batas = getBatasPoin();
$query_siswa = "SELECT s.*, k.nama_kelas
                FROM siswa s
                LEFT JOIN kelas k ON s.kelas_id = k.id";
$siswa_all = getRows($query_siswa);

$siswa_bermasalah_all = [];
foreach ($siswa_all as $row) {
    // Hitung poin pelanggaran
    $q_pel = $conn->query("SELECT SUM(poin_diperoleh) as total FROM pelanggaran WHERE siswa_id = {$row['id']} AND status = 'disetujui'");
    $pel = $q_pel->fetch_assoc();
    $poin_pel = (int)$pel['total'];
    
    // Hitung poin prestasi
    $q_pres = $conn->query("SELECT SUM(poin_prestasi) as total FROM prestasi_siswa WHERE siswa_id = {$row['id']}");
    $pres = $q_pres->fetch_assoc();
    $poin_pres = (int)$pres['total'];
    
    $poin_akhir = $poin_pel - $poin_pres;
    if ($poin_akhir < 0) $poin_akhir = 0;
    
    if ($poin_akhir >= $batas['peringatan']) {
        $row['poin_pelanggaran'] = $poin_pel;
        $row['poin_prestasi'] = $poin_pres;
        $row['poin_akhir'] = $poin_akhir;
        $siswa_bermasalah_all[] = $row;
    }
}
usort($siswa_bermasalah_all, function($a, $b) {
    return $b['poin_akhir'] - $a['poin_akhir'];
});
$siswa_bermasalah_count = count($siswa_bermasalah_all);

// Konseling
$query_konseling_terbaru = "SELECT c.*, s.nama_siswa 
                            FROM catatan_konseling c
                            LEFT JOIN siswa s ON c.siswa_id = s.id
                            ORDER BY c.tanggal DESC
                            LIMIT 20";
$konseling_terbaru = getRows($query_konseling_terbaru);
$jenis_data = getRows("SELECT jenis_konseling, COUNT(*) as total FROM catatan_konseling GROUP BY jenis_konseling");

$status_konseling = ['pending' => 0, 'proses' => 0, 'selesai' => 0];
$query_status = "SELECT status, COUNT(*) as total FROM catatan_konseling GROUP BY status";
$result_status = $conn->query($query_status);
while ($row = $result_status->fetch_assoc()) {
    $status_konseling[$row['status']] = $row['total'];
}

$nama_bulan = getBulanIndonesia($bulan);

include __DIR__ . '/../includes/header_bk.php';
?>

<div class="container-fluid dashboard-container">
    <!-- Header -->
    <div class="dashboard-header">
        <div>
            <h4 class="dashboard-title">
                <i class="fas fa-file-alt text-primary me-1"></i> Laporan BK
            </h4>
            <small class="text-muted dashboard-subtitle">
                <i class="fas fa-chart-bar me-1"></i> Laporan dan statistik Bimbingan Konseling
            </small>
        </div>
        <div class="d-flex gap-1">
            <a href="export_laporan.php?action=excel&bulan=<?= $bulan ?>&tahun=<?= $tahun ?>&jenis=<?= $jenis_laporan ?>" class="btn btn-success btn-sm">
                <i class="fas fa-file-excel"></i>
            </a>
            <a href="export_laporan.php?action=pdf&bulan=<?= $bulan ?>&tahun=<?= $tahun ?>&jenis=<?= $jenis_laporan ?>" class="btn btn-danger btn-sm" target="_blank">
                <i class="fas fa-file-pdf"></i>
            </a>
        </div>
    </div>

    <!-- Filter -->
    <div class="card filter-card mb-2">
        <div class="card-body p-2">
            <form method="GET" action="" class="filter-form">
                <div class="row g-1 align-items-end">
                    <div class="col-4 col-md-2">
                        <label class="form-label">Bulan</label>
                        <select class="form-select" name="bulan">
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                <option value="<?= $i ?>" <?= $bulan == $i ? 'selected' : '' ?>>
                                    <?= getBulanIndonesia($i) ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-4 col-md-2">
                        <label class="form-label">Tahun</label>
                        <select class="form-select" name="tahun">
                            <?php for ($i = date('Y') - 2; $i <= date('Y'); $i++): ?>
                                <option value="<?= $i ?>" <?= $tahun == $i ? 'selected' : '' ?>><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-4 col-md-3">
                        <label class="form-label">Jenis</label>
                        <select class="form-select" name="jenis">
                            <option value="pelanggaran" <?= $jenis_laporan == 'pelanggaran' ? 'selected' : '' ?>>Pelanggaran</option>
                            <option value="semua_pelanggaran" <?= $jenis_laporan == 'semua_pelanggaran' ? 'selected' : '' ?>>Semua Pelanggaran</option>
                            <option value="prestasi" <?= $jenis_laporan == 'prestasi' ? 'selected' : '' ?>>Prestasi</option>
                            <option value="semua_prestasi" <?= $jenis_laporan == 'semua_prestasi' ? 'selected' : '' ?>>Semua Prestasi</option>
                            <option value="konseling" <?= $jenis_laporan == 'konseling' ? 'selected' : '' ?>>Konseling</option>
                            <option value="siswa" <?= $jenis_laporan == 'siswa' ? 'selected' : '' ?>>Siswa Bermasalah</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label d-none d-md-block">&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label d-none d-md-block">&nbsp;</label>
                        <a href="?bulan=<?= date('m') ?>&tahun=<?= date('Y') ?>&jenis=pelanggaran" class="btn btn-secondary btn-sm w-100">
                            <i class="fas fa-undo"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- LAPORAN PELANGGARAN BULAN INI -->
    <!-- ========================================== -->
    <?php if ($jenis_laporan == 'pelanggaran'): ?>
        <!-- RINGKASAN POIN -->
        <div class="row g-1 mb-2">
            <div class="col-4">
                <div class="card bg-danger text-white">
                    <div class="card-body p-1 text-center">
                        <small style="font-size:8px;">Total Pelanggaran</small>
                        <div class="fw-bold" style="font-size:14px;">+<?= $total_poin_pelanggaran ?></div>
                    </div>
                </div>
            </div>
            <div class="col-4">
                <div class="card bg-success text-white">
                    <div class="card-body p-1 text-center">
                        <small style="font-size:8px;">Total Prestasi</small>
                        <div class="fw-bold" style="font-size:14px;">-<?= $total_poin_prestasi ?></div>
                    </div>
                </div>
            </div>
            <div class="col-4">
                <div class="card bg-primary text-white">
                    <div class="card-body p-1 text-center">
                        <small style="font-size:8px;">Sisa Poin</small>
                        <div class="fw-bold" style="font-size:14px;"><?= $total_poin_akhir ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detail Pelanggaran -->
        <div class="card">
            <div class="card-header py-1 px-2">
                <i class="fas fa-list me-1"></i> Detail Pelanggaran Bulan <?= $nama_bulan ?> <?= $tahun ?>
                <span class="badge bg-primary ms-1"><?= count($detail_data) ?></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-striped mb-0" style="font-size:10px;width:100%;">
                        <thead>
                            <tr>
                                <th class="text-center" style="width:4%;">No</th>
                                <th style="width:10%;">Tanggal</th>
                                <th style="width:10%;">NISN</th>
                                <th style="width:17%;">Nama</th>
                                <th style="width:7%;">Kelas</th>
                                <th style="width:16%;">Pelanggaran</th>
                                <th style="width:9%;">Kategori</th>
                                <th style="width:7%;">Poin</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($detail_data) > 0): ?>
                                <?php $no = 1; ?>
                                <?php foreach ($detail_data as $row): ?>
                                    <tr>
                                        <td class="text-center"><?= $no++ ?></td>
                                        <td><?= date('d-m-Y', strtotime($row['tanggal_pelanggaran'])) ?></td>
                                        <td><?= $row['nisn'] ?></td>
                                        <td><?= htmlspecialchars($row['nama_siswa']) ?></td>
                                        <td><?= $row['nama_kelas'] ?? '-' ?></td>
                                        <td><?= htmlspecialchars($row['nama_pelanggaran']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $row['kategori'] == 'berat' ? 'danger' : ($row['kategori'] == 'sedang' ? 'warning' : 'success') ?>">
                                                <?= ucfirst($row['kategori']) ?>
                                            </span>
                                        </td>
                                        <td class="text-center"><?= $row['poin'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="table-danger fw-bold">
                                    <td colspan="7" class="text-end">Total Poin Pelanggaran</td>
                                    <td class="text-center">+<?= $total_poin_pelanggaran ?></td>
                                </tr>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-2 text-muted">
                                        <i class="fas fa-inbox me-1"></i> Tidak ada pelanggaran
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- AKUMULASI POIN PER SISWA (1 BARIS PER SISWA) -->
        <div class="card mt-2">
            <div class="card-header py-1 px-2">
                <i class="fas fa-calculator me-1"></i> Akumulasi Poin Siswa (Pelanggaran - Prestasi)
                <span class="badge bg-info ms-1">Total: <?= count($siswa_akumulasi) ?> siswa</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-striped mb-0" style="font-size:10px;width:100%;">
                        <thead>
                            <tr>
                                <th class="text-center" style="width:5%;">No</th>
                                <th style="width:12%;">NISN</th>
                                <th style="width:20%;">Nama</th>
                                <th style="width:10%;">Kelas</th>
                                <th class="text-center" style="width:12%;">Pelanggaran</th>
                                <th class="text-center" style="width:12%;">Prestasi</th>
                                <th class="text-center" style="width:12%;">Sisa Poin</th>
                                <th style="width:17%;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($siswa_akumulasi) > 0): ?>
                                <?php $no = 1; ?>
                                <?php foreach ($siswa_akumulasi as $data): 
                                    $level = getPoinLevel($data['poin_akhir']);
                                ?>
                                    <tr>
                                        <td class="text-center"><?= $no++ ?></td>
                                        <td><?= $data['nisn'] ?></td>
                                        <td><?= htmlspecialchars($data['nama']) ?></td>
                                        <td><?= $data['kelas'] ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-danger"><?= $data['total_pelanggaran'] ?></span>
                                            <br><small style="font-size:7px;">(<?= $data['poin_pelanggaran'] ?>)</small>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($data['poin_prestasi'] > 0): ?>
                                                <span class="badge bg-success"><?= $data['poin_prestasi'] ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">0</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center fw-bold text-<?= $level['color'] ?>">
                                            <?= $data['poin_akhir'] ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $level['color'] ?>">
                                                <i class="fas <?= $level['icon'] ?> me-1"></i>
                                                <?= $level['level'] ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="table-info fw-bold">
                                    <td colspan="4" class="text-end">Total</td>
                                    <td class="text-center"><?= $total_poin_pelanggaran ?></td>
                                    <td class="text-center"><?= $total_poin_prestasi ?></td>
                                    <td class="text-center"><?= $total_poin_akhir ?></td>
                                    <td></td>
                                </tr>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-2 text-muted">
                                        <i class="fas fa-inbox me-1"></i> Tidak ada data
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <!-- ========================================== -->
    <!-- LAPORAN PRESTASI (TANPA MINUS) -->
    <!-- ========================================== -->
    <?php elseif ($jenis_laporan == 'prestasi'): ?>
        <div class="card">
            <div class="card-header py-1 px-2">
                <i class="fas fa-trophy me-1"></i> Detail Prestasi Bulan <?= $nama_bulan ?> <?= $tahun ?>
                <span class="badge bg-success ms-1"><?= $prestasi_bulan ?></span>
                <span class="badge bg-info ms-1">+<?= $poin_prestasi_bulan ?></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-striped mb-0" style="font-size:10px;width:100%;">
                        <thead>
                            <tr>
                                <th class="text-center" style="width:4%;">No</th>
                                <th style="width:10%;">No. Prestasi</th>
                                <th style="width:10%;">NISN</th>
                                <th style="width:18%;">Nama</th>
                                <th style="width:8%;">Kelas</th>
                                <th style="width:10%;">Jenis</th>
                                <th style="width:18%;">Prestasi</th>
                                <th style="width:10%;">Tingkat</th>
                                <th style="width:6%;">Tahun</th>
                                <th style="width:6%;">Poin</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($prestasi_bulan_data) > 0): ?>
                                <?php $no = 1; ?>
                                <?php foreach ($prestasi_bulan_data as $row): ?>
                                    <tr>
                                        <td class="text-center"><?= $no++ ?></td>
                                        <td><span class="badge bg-primary"><?= $row['no_prestasi'] ?? 'PRS' ?></span></td>
                                        <td><?= $row['nisn'] ?></td>
                                        <td><?= htmlspecialchars($row['nama_siswa']) ?></td>
                                        <td><?= $row['nama_kelas'] ?? '-' ?></td>
                                        <td>
                                            <span class="badge bg-<?= $row['jenis_prestasi'] == 'akademik' ? 'success' : 'info' ?>">
                                                <?= ucfirst(str_replace('_', ' ', $row['jenis_prestasi'])) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($row['nama_prestasi']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= 
                                                $row['tingkat'] == 'internasional' ? 'danger' : 
                                                ($row['tingkat'] == 'nasional' ? 'warning' : 
                                                ($row['tingkat'] == 'provinsi' ? 'info' : 
                                                ($row['tingkat'] == 'kota' ? 'success' : 
                                                ($row['tingkat'] == 'kecamatan' ? 'primary' : 'secondary')))) ?>">
                                                <?= ucfirst($row['tingkat']) ?>
                                            </span>
                                        </td>
                                        <td><?= $row['tahun'] ?></td>
                                        <td class="text-center"><span class="badge bg-success">+<?= $row['poin_prestasi'] ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="table-success fw-bold">
                                    <td colspan="9" class="text-end">Total Poin Prestasi</td>
                                    <td class="text-center">+<?= $poin_prestasi_bulan ?></td>
                                </tr>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" class="text-center py-2 text-muted">
                                        <i class="fas fa-inbox me-1"></i> Tidak ada prestasi
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <!-- ========================================== -->
    <!-- SEMUA PRESTASI (TANPA MINUS) -->
    <!-- ========================================== -->
    <?php elseif ($jenis_laporan == 'semua_prestasi'): ?>
        <div class="card">
            <div class="card-header py-1 px-2">
                <i class="fas fa-trophy me-1"></i> Semua Prestasi Siswa
                <span class="badge bg-success ms-1"><?= $total_prestasi ?></span>
                <span class="badge bg-info ms-1">+<?= $total_poin_prestasi_all ?></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-striped mb-0" style="font-size:10px;width:100%;">
                        <thead>
                            <tr>
                                <th class="text-center" style="width:4%;">No</th>
                                <th style="width:10%;">No. Prestasi</th>
                                <th style="width:10%;">NISN</th>
                                <th style="width:18%;">Nama</th>
                                <th style="width:8%;">Kelas</th>
                                <th style="width:10%;">Jenis</th>
                                <th style="width:18%;">Prestasi</th>
                                <th style="width:10%;">Tingkat</th>
                                <th style="width:6%;">Tahun</th>
                                <th style="width:6%;">Poin</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($semua_prestasi_data) > 0): ?>
                                <?php $no = 1; ?>
                                <?php foreach ($semua_prestasi_data as $row): ?>
                                    <tr>
                                        <td class="text-center"><?= $no++ ?></td>
                                        <td><span class="badge bg-primary"><?= $row['no_prestasi'] ?? 'PRS' ?></span></td>
                                        <td><?= $row['nisn'] ?></td>
                                        <td><?= htmlspecialchars($row['nama_siswa']) ?></td>
                                        <td><?= $row['nama_kelas'] ?? '-' ?></td>
                                        <td>
                                            <span class="badge bg-<?= $row['jenis_prestasi'] == 'akademik' ? 'success' : 'info' ?>">
                                                <?= ucfirst(str_replace('_', ' ', $row['jenis_prestasi'])) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($row['nama_prestasi']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= 
                                                $row['tingkat'] == 'internasional' ? 'danger' : 
                                                ($row['tingkat'] == 'nasional' ? 'warning' : 
                                                ($row['tingkat'] == 'provinsi' ? 'info' : 
                                                ($row['tingkat'] == 'kota' ? 'success' : 
                                                ($row['tingkat'] == 'kecamatan' ? 'primary' : 'secondary')))) ?>">
                                                <?= ucfirst($row['tingkat']) ?>
                                            </span>
                                        </td>
                                        <td><?= $row['tahun'] ?></td>
                                        <td class="text-center"><span class="badge bg-success">+<?= $row['poin_prestasi'] ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="table-success fw-bold">
                                    <td colspan="9" class="text-end">Total Poin Prestasi</td>
                                    <td class="text-center">+<?= $total_poin_prestasi_all ?></td>
                                </tr>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" class="text-center py-2 text-muted">
                                        <i class="fas fa-inbox me-1"></i> Tidak ada prestasi
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <!-- ========================================== -->
    <!-- SEMUA PELANGGARAN -->
    <!-- ========================================== -->
    <?php elseif ($jenis_laporan == 'semua_pelanggaran'): ?>
        <div class="card">
            <div class="card-header py-1 px-2">
                <i class="fas fa-list me-1"></i> Semua Riwayat Pelanggaran
                <span class="badge bg-primary ms-1"><?= count($semua_pelanggaran) ?></span>
                <span class="badge bg-danger ms-1">+<?= array_sum(array_column($semua_pelanggaran, 'poin_diperoleh')) ?></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-striped mb-0" style="font-size:10px;width:100%;">
                        <thead>
                            <tr>
                                <th class="text-center" style="width:4%;">No</th>
                                <th style="width:10%;">Tanggal</th>
                                <th style="width:10%;">NISN</th>
                                <th style="width:18%;">Nama</th>
                                <th style="width:8%;">Kelas</th>
                                <th style="width:14%;">Pelanggaran</th>
                                <th style="width:9%;">Kategori</th>
                                <th style="width:7%;">Poin</th>
                                <th style="width:10%;">Guru</th>
                                <th style="width:10%;">Ket.</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($semua_pelanggaran) > 0): ?>
                                <?php $no = 1; ?>
                                <?php foreach ($semua_pelanggaran as $row): ?>
                                    <tr>
                                        <td class="text-center"><?= $no++ ?></td>
                                        <td><?= date('d-m-Y', strtotime($row['tanggal_pelanggaran'])) ?></td>
                                        <td><?= $row['nisn'] ?></td>
                                        <td><?= htmlspecialchars($row['nama_siswa']) ?></td>
                                        <td><?= $row['nama_kelas'] ?? '-' ?></td>
                                        <td><?= htmlspecialchars($row['nama_pelanggaran']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $row['kategori'] == 'berat' ? 'danger' : ($row['kategori'] == 'sedang' ? 'warning' : 'success') ?>">
                                                <?= ucfirst($row['kategori']) ?>
                                            </span>
                                        </td>
                                        <td class="text-center fw-bold"><?= $row['poin_diperoleh'] ?></td>
                                        <td><?= $row['guru'] ?? '-' ?></td>
                                        <td><?= htmlspecialchars(substr($row['keterangan'] ?? '-', 0, 10)) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="table-danger fw-bold">
                                    <td colspan="7" class="text-end">Total Poin Pelanggaran</td>
                                    <td class="text-center">+<?= array_sum(array_column($semua_pelanggaran, 'poin_diperoleh')) ?></td>
                                    <td colspan="2"></td>
                                </tr>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" class="text-center py-2 text-muted">
                                        <i class="fas fa-inbox me-1"></i> Tidak ada pelanggaran
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <!-- ========================================== -->
    <!-- LAPORAN KONSELING -->
    <!-- ========================================== -->
    <?php elseif ($jenis_laporan == 'konseling'): ?>
        <div class="row g-1">
            <div class="col-12 col-md-6">
                <div class="card">
                    <div class="card-header py-1 px-2">
                        <i class="fas fa-chart-pie me-1"></i> Status Konseling
                    </div>
                    <div class="card-body p-2">
                        <div style="max-height: 130px;">
                            <canvas id="chartKonseling"></canvas>
                        </div>
                        <div class="row mt-1 text-center">
                            <div class="col-4">
                                <span class="badge bg-secondary">Pending</span>
                                <div class="fw-bold"><?= $status_konseling['pending'] ?? 0 ?></div>
                            </div>
                            <div class="col-4">
                                <span class="badge bg-warning">Proses</span>
                                <div class="fw-bold"><?= $status_konseling['proses'] ?? 0 ?></div>
                            </div>
                            <div class="col-4">
                                <span class="badge bg-success">Selesai</span>
                                <div class="fw-bold"><?= $status_konseling['selesai'] ?? 0 ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6">
                <div class="card">
                    <div class="card-header py-1 px-2">
                        <i class="fas fa-list me-1"></i> Rekap Konseling
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-bordered table-striped mb-0" style="font-size:10px;width:100%;">
                            <thead>
                                <tr>
                                    <th>Jenis Konseling</th>
                                    <th>Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($jenis_data) > 0): ?>
                                    <?php $total_jenis = 0; ?>
                                    <?php foreach ($jenis_data as $row): ?>
                                        <?php $total_jenis += $row['total']; ?>
                                        <tr>
                                            <td><?= ucfirst($row['jenis_konseling']) ?></td>
                                            <td class="text-center fw-bold"><?= $row['total'] ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr class="table-info fw-bold">
                                        <td>Total</td>
                                        <td class="text-center"><?= $total_jenis ?></td>
                                    </tr>
                                <?php else: ?>
                                    <tr><td colspan="2" class="text-center py-2 text-muted">Tidak ada data</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Catatan Konseling -->
        <div class="card mt-2">
            <div class="card-header py-1 px-2">
                <i class="fas fa-notes-medical me-1"></i> Catatan Konseling Terbaru
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-striped mb-0" style="font-size:10px;width:100%;">
                        <thead>
                            <tr>
                                <th style="width:15%;">Tanggal</th>
                                <th style="width:20%;">Siswa</th>
                                <th style="width:25%;">Topik</th>
                                <th style="width:20%;">Jenis</th>
                                <th style="width:20%;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($konseling_terbaru) > 0): ?>
                                <?php foreach ($konseling_terbaru as $row): ?>
                                    <tr>
                                        <td><?= date('d-m-Y', strtotime($row['tanggal'])) ?></td>
                                        <td><?= htmlspecialchars($row['nama_siswa'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($row['topik'] ?? '-') ?></td>
                                        <td><?= ucfirst($row['jenis_konseling']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $row['status'] == 'selesai' ? 'success' : ($row['status'] == 'proses' ? 'warning' : 'secondary') ?>">
                                                <?= ucfirst($row['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-center py-2 text-muted">Tidak ada catatan</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <!-- ========================================== -->
    <!-- LAPORAN SISWA BERMASALAH -->
    <!-- ========================================== -->
    <?php elseif ($jenis_laporan == 'siswa'): ?>
        <div class="card">
            <div class="card-header py-1 px-2">
                <i class="fas fa-user-graduate me-1"></i> Siswa Bermasalah
                <span class="badge bg-danger ms-1"><?= $siswa_bermasalah_count ?></span>
                <span class="badge bg-info ms-1">Batas: <?= $batas['peringatan'] ?></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-striped mb-0" style="font-size:10px;width:100%;">
                        <thead>
                            <tr>
                                <th class="text-center" style="width:5%;">No</th>
                                <th style="width:12%;">NISN</th>
                                <th style="width:20%;">Nama</th>
                                <th style="width:10%;">Kelas</th>
                                <th class="text-center" style="width:12%;">Pelanggaran</th>
                                <th class="text-center" style="width:12%;">Prestasi</th>
                                <th class="text-center" style="width:12%;">Sisa Poin</th>
                                <th style="width:17%;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($siswa_bermasalah_all) > 0): ?>
                                <?php $no = 1; ?>
                                <?php foreach ($siswa_bermasalah_all as $row): 
                                    $level = getPoinLevel($row['poin_akhir']);
                                ?>
                                    <tr>
                                        <td class="text-center"><?= $no++ ?></td>
                                        <td><?= $row['nisn'] ?></td>
                                        <td><?= htmlspecialchars($row['nama_siswa']) ?></td>
                                        <td><?= $row['nama_kelas'] ?? '-' ?></td>
                                        <td class="text-center"><span class="badge bg-danger"><?= $row['poin_pelanggaran'] ?></span></td>
                                        <td class="text-center">
                                            <?php if ($row['poin_prestasi'] > 0): ?>
                                                <span class="badge bg-success"><?= $row['poin_prestasi'] ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">0</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center fw-bold text-<?= $level['color'] ?>"><?= $row['poin_akhir'] ?></td>
                                        <td>
                                            <span class="badge bg-<?= $level['color'] ?>">
                                                <i class="fas <?= $level['icon'] ?> me-1"></i>
                                                <?= $level['level'] ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="table-info fw-bold">
                                    <td colspan="4" class="text-end">Total</td>
                                    <td class="text-center"><?= array_sum(array_column($siswa_bermasalah_all, 'poin_pelanggaran')) ?></td>
                                    <td class="text-center"><?= array_sum(array_column($siswa_bermasalah_all, 'poin_prestasi')) ?></td>
                                    <td class="text-center"><?= array_sum(array_column($siswa_bermasalah_all, 'poin_akhir')) ?></td>
                                    <td></td>
                                </tr>
                            <?php else: ?>
                                <tr><td colspan="8" class="text-center py-2 text-muted">
                                    <i class="fas fa-check-circle text-success me-1"></i> Tidak ada siswa bermasalah
                                </td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($jenis_laporan == 'konseling'): ?>
    var ctx = document.getElementById('chartKonseling').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Pending', 'Proses', 'Selesai'],
            datasets: [{
                data: [
                    <?= $status_konseling['pending'] ?? 0 ?>,
                    <?= $status_konseling['proses'] ?? 0 ?>,
                    <?= $status_konseling['selesai'] ?? 0 ?>
                ],
                backgroundColor: ['#6c757d', '#F59E0B', '#10B981'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: { legend: { display: false } }
        }
    });
    <?php endif; ?>
});
</script>

<style>
.dashboard-container {
    padding: 4px !important;
    max-width: 100%;
    overflow-x: hidden;
}

.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    margin-bottom: 6px;
    gap: 4px;
}

.dashboard-title {
    font-size: 0.9rem;
    margin-bottom: 0;
}

.dashboard-subtitle {
    font-size: 0.55rem;
}

.card {
    border: none;
    border-radius: 8px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.05);
    margin-bottom: 6px;
    background: #fff;
}

.card-header {
    background: transparent;
    border-bottom: 1px solid #e9ecef;
    padding: 4px 8px !important;
    font-weight: 600;
    font-size: 10px;
}

.card-body {
    padding: 6px !important;
}

.card-footer {
    background: transparent;
    border-top: 1px solid #e9ecef;
    padding: 4px 8px !important;
    font-size: 9px;
}

.filter-card .card-body {
    padding: 6px !important;
}

.form-label {
    font-weight: 600;
    font-size: 9px;
    color: #4a5568;
    margin-bottom: 1px;
}

.form-control, .form-select {
    border-radius: 4px;
    border: 1px solid #e2e8f0;
    padding: 2px 6px;
    font-size: 10px;
    height: 26px;
}

.btn-sm {
    padding: 2px 6px !important;
    font-size: 9px !important;
    border-radius: 4px !important;
}

.table {
    font-size: 9px !important;
    margin-bottom: 0;
    width: 100% !important;
}

.table-bordered {
    border: 1px solid #dee2e6;
}

.table-bordered th,
.table-bordered td {
    border: 1px solid #dee2e6;
    padding: 2px 4px !important;
    vertical-align: middle;
}

.table th {
    font-weight: 600;
    font-size: 8px !important;
    text-transform: uppercase;
    letter-spacing: 0.2px;
    white-space: nowrap;
    background-color: #f8f9fa;
}

.table td {
    vertical-align: middle;
    font-size: 9px !important;
}

.table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    margin: 0;
}

.badge {
    font-size: 7px !important;
    padding: 1px 4px !important;
    border-radius: 3px;
}

canvas {
    max-height: 130px !important;
    max-width: 100% !important;
}

@media (max-width: 576px) {
    .dashboard-title {
        font-size: 0.8rem;
    }
    .dashboard-subtitle {
        font-size: 0.5rem;
    }
    .table {
        font-size: 8px !important;
    }
    .table th {
        font-size: 7px !important;
        padding: 1px 3px !important;
    }
    .table td {
        font-size: 8px !important;
        padding: 1px 3px !important;
    }
    .badge {
        font-size: 6px !important;
        padding: 1px 3px !important;
    }
    .card-header {
        font-size: 9px;
        padding: 3px 6px !important;
    }
    .btn-sm {
        font-size: 8px !important;
        padding: 1px 4px !important;
    }
    .form-label {
        font-size: 8px;
    }
    .form-control, .form-select {
        font-size: 9px;
        height: 24px;
        padding: 1px 4px;
    }
}
</style>

<?php include '../includes/footer.php'; ?>