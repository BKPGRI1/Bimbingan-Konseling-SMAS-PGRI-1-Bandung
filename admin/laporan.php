<?php
// admin/laporan.php
include '../includes/config.php';
checkLogin();

$page_title = 'Laporan';
$conn = getDB();

// Handle export requests
if (isset($_GET['export'])) {
    $export_type = $_GET['export'];
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
    $kelas_id = isset($_GET['kelas']) ? (int)$_GET['kelas'] : null;
    $siswa_id = isset($_GET['siswa']) ? (int)$_GET['siswa'] : null;
    $report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'pelanggaran';
    
    if ($report_type == 'pelanggaran') {
        $data = getReportPelanggaranWithPrestasi($start_date, $end_date, $kelas_id, $siswa_id);
        $title = 'Laporan Pelanggaran';
    } elseif ($report_type == 'siswa') {
        $data = getReportSiswaWithPoin($kelas_id);
        $title = 'Laporan Data Siswa';
    } elseif ($report_type == 'kelas') {
        $data = getReportKelas();
        $title = 'Laporan Data Kelas';
    } elseif ($report_type == 'prestasi') {
        $data = getReportPrestasi($start_date, $end_date, $kelas_id, $siswa_id);
        $title = 'Laporan Prestasi';
    } else {
        $data = getReportPelanggaranWithPrestasi($start_date, $end_date, $kelas_id, $siswa_id);
        $title = 'Laporan Pelanggaran';
    }
    
    if ($export_type == 'pdf') {
        exportLaporanPDF($data, $title, $start_date, $end_date, $report_type);
    } elseif ($export_type == 'excel') {
        exportLaporanExcel($data, $title, $start_date, $end_date, $report_type);
    }
    exit;
}

// Get data for filters
$kelas_list = getKelasOptions();
$siswa_list = getSiswa();

// Filter values
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$kelas_filter = isset($_GET['kelas']) ? (int)$_GET['kelas'] : null;
$siswa_filter = isset($_GET['siswa']) ? (int)$_GET['siswa'] : null;
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'pelanggaran';

// Get data based on report type
if ($report_type == 'pelanggaran') {
    $data = getReportPelanggaranWithPrestasi($start_date, $end_date, $kelas_filter, $siswa_filter);
    $total = count($data);
    $total_poin_pelanggaran = array_sum(array_column($data, 'poin'));
    $total_poin_prestasi = array_sum(array_column($data, 'poin_prestasi'));
    $total_poin_akhir = $total_poin_pelanggaran - $total_poin_prestasi;
    if ($total_poin_akhir < 0) $total_poin_akhir = 0;
} elseif ($report_type == 'siswa') {
    $data = getReportSiswaWithPoin($kelas_filter);
    $total = count($data);
    $total_poin_akhir = array_sum(array_column($data, 'poin_akhir'));
} elseif ($report_type == 'kelas') {
    $data = getReportKelas();
    $total = count($data);
    $total_siswa = array_sum(array_column($data, 'jumlah_siswa'));
    $total_pelanggaran = array_sum(array_column($data, 'total_pelanggaran'));
} elseif ($report_type == 'prestasi') {
    $data = getReportPrestasi($start_date, $end_date, $kelas_filter, $siswa_filter);
    $total = count($data);
    $total_poin = array_sum(array_column($data, 'poin_prestasi'));
} else {
    $data = getReportPelanggaranWithPrestasi($start_date, $end_date, $kelas_filter, $siswa_filter);
    $total = count($data);
    $total_poin_pelanggaran = array_sum(array_column($data, 'poin'));
    $total_poin_prestasi = array_sum(array_column($data, 'poin_prestasi'));
    $total_poin_akhir = $total_poin_pelanggaran - $total_poin_prestasi;
    if ($total_poin_akhir < 0) $total_poin_akhir = 0;
}

include '../includes/header.php';
?>

<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
        <h4 class="mb-0">
            <i class="fas fa-file-alt text-primary me-2"></i> Laporan
        </h4>
        <div class="d-flex gap-2 flex-wrap">
            <button class="btn btn-danger btn-sm" onclick="exportPDF()">
                <i class="fas fa-file-pdf me-1"></i> PDF
            </button>
            <button class="btn btn-success btn-sm" onclick="exportExcel()">
                <i class="fas fa-file-excel me-1"></i> Excel
            </button>
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

    <!-- Filter -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="" id="filterForm">
                <div class="row g-2">
                    <div class="col-md-3">
                        <label class="form-label" style="font-size: 12px;">Jenis Laporan</label>
                        <select class="form-select" name="report_type" id="reportType" onchange="toggleFilter(this.value)">
                            <option value="pelanggaran" <?= $report_type == 'pelanggaran' ? 'selected' : '' ?>>Laporan Pelanggaran</option>
                            <option value="prestasi" <?= $report_type == 'prestasi' ? 'selected' : '' ?>>Laporan Prestasi</option>
                            <option value="siswa" <?= $report_type == 'siswa' ? 'selected' : '' ?>>Laporan Data Siswa</option>
                            <option value="kelas" <?= $report_type == 'kelas' ? 'selected' : '' ?>>Laporan Data Kelas</option>
                        </select>
                    </div>
                    <div class="col-md-3" id="dateRange">
                        <label class="form-label" style="font-size: 12px;">Tanggal Mulai</label>
                        <input type="date" class="form-control" name="start_date" value="<?= $start_date ?>">
                    </div>
                    <div class="col-md-3" id="dateRange2">
                        <label class="form-label" style="font-size: 12px;">Tanggal Selesai</label>
                        <input type="date" class="form-control" name="end_date" value="<?= $end_date ?>">
                    </div>
                    <div class="col-md-3" id="kelasFilter">
                        <label class="form-label" style="font-size: 12px;">Kelas</label>
                        <select class="form-select" name="kelas">
                            <option value="">Semua Kelas</option>
                            <?php foreach ($kelas_list as $kelas): ?>
                                <option value="<?= $kelas['id'] ?>" <?= $kelas_filter == $kelas['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($kelas['nama_kelas']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3" id="siswaFilter" style="display: none;">
                        <label class="form-label" style="font-size: 12px;">Siswa</label>
                        <select class="form-select" name="siswa">
                            <option value="">Semua Siswa</option>
                            <?php foreach ($siswa_list as $siswa): ?>
                                <option value="<?= $siswa['id'] ?>" <?= $siswa_filter == $siswa['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($siswa['nama_siswa']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-12 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter me-1"></i> Tampilkan
                        </button>
                        <a href="<?= APP_URL ?>admin/laporan.php" class="btn btn-secondary">
                            <i class="fas fa-undo me-1"></i> Reset
                        </a>
                        <span class="text-muted ms-auto" style="font-size: 13px;">
                            <i class="fas fa-file-alt me-1"></i> Total: <?= $total ?> data
                        </span>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-3">
        <div class="col-md-3 col-6">
            <div class="card bg-primary text-white">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-0" style="font-size: 10px; text-transform: uppercase;">Total Data</h6>
                            <h3 class="mb-0"><?= $total ?></h3>
                        </div>
                        <div>
                            <i class="fas fa-<?= $report_type == 'pelanggaran' ? 'exclamation-triangle' : ($report_type == 'prestasi' ? 'trophy' : ($report_type == 'siswa' ? 'users' : 'building')) ?> fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php if ($report_type == 'pelanggaran'): ?>
            <div class="col-md-3 col-6">
                <div class="card bg-danger text-white">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-white-50 mb-0" style="font-size: 10px; text-transform: uppercase;">Poin Pelanggaran</h6>
                                <h3 class="mb-0">+<?= $total_poin_pelanggaran ?? 0 ?></h3>
                            </div>
                            <div>
                                <i class="fas fa-exclamation-triangle fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card bg-success text-white">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-white-50 mb-0" style="font-size: 10px; text-transform: uppercase;">Poin Prestasi</h6>
                                <h3 class="mb-0">+<?= $total_poin_prestasi ?? 0 ?></h3>
                            </div>
                            <div>
                                <i class="fas fa-star fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card bg-warning text-white">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-white-50 mb-0" style="font-size: 10px; text-transform: uppercase;">Poin Akhir</h6>
                                <h3 class="mb-0"><?= $total_poin_akhir ?? 0 ?></h3>
                            </div>
                            <div>
                                <i class="fas fa-calculator fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php elseif ($report_type == 'prestasi'): ?>
            <div class="col-md-3 col-6">
                <div class="card bg-success text-white">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-white-50 mb-0" style="font-size: 10px; text-transform: uppercase;">Total Poin</h6>
                                <h3 class="mb-0">+<?= $total_poin ?? 0 ?></h3>
                            </div>
                            <div>
                                <i class="fas fa-plus-circle fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card bg-info text-white">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-white-50 mb-0" style="font-size: 10px; text-transform: uppercase;">Akademik</h6>
                                <h3 class="mb-0">
                                    <?php 
                                    $akademik = array_filter($data, function($item) {
                                        return $item['jenis_prestasi'] == 'akademik';
                                    });
                                    echo count($akademik);
                                    ?>
                                </h3>
                            </div>
                            <div>
                                <i class="fas fa-graduation-cap fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card bg-warning text-white">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-white-50 mb-0" style="font-size: 10px; text-transform: uppercase;">Non-Akademik</h6>
                                <h3 class="mb-0">
                                    <?php 
                                    $non_akademik = array_filter($data, function($item) {
                                        return $item['jenis_prestasi'] == 'non_akademik';
                                    });
                                    echo count($non_akademik);
                                    ?>
                                </h3>
                            </div>
                            <div>
                                <i class="fas fa-medal fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php elseif ($report_type == 'siswa'): ?>
            <div class="col-md-3 col-6">
                <div class="card bg-warning text-white">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-white-50 mb-0" style="font-size: 10px; text-transform: uppercase;">Total Poin</h6>
                                <h3 class="mb-0"><?= $total_poin_akhir ?? 0 ?></h3>
                            </div>
                            <div>
                                <i class="fas fa-star fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php elseif ($report_type == 'kelas'): ?>
            <div class="col-md-3 col-6">
                <div class="card bg-success text-white">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-white-50 mb-0" style="font-size: 10px; text-transform: uppercase;">Total Siswa</h6>
                                <h3 class="mb-0"><?= $total_siswa ?? 0 ?></h3>
                            </div>
                            <div>
                                <i class="fas fa-user-graduate fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card bg-warning text-white">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-white-50 mb-0" style="font-size: 10px; text-transform: uppercase;">Total Pelanggaran</h6>
                                <h3 class="mb-0"><?= $total_pelanggaran ?? 0 ?></h3>
                            </div>
                            <div>
                                <i class="fas fa-exclamation-triangle fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Tabel Laporan -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0" id="tableLaporan">
                    <thead class="table-light">
                        <tr>
                            <?php if ($report_type == 'pelanggaran'): ?>
                                <th width="35" class="text-center">No</th>
                                <th>Tanggal</th>
                                <th>NISN</th>
                                <th>Nama Siswa</th>
                                <th>Kelas</th>
                                <th>Pelanggaran</th>
                                <th class="text-center">Poin</th>
                                <th class="text-center">Prestasi</th>
                                <th class="text-center">Sisa Poin</th>
                                <th>Guru</th>
                                <th class="text-center">Status</th>
                            <?php elseif ($report_type == 'prestasi'): ?>
                                <th width="35" class="text-center">No</th>
                                <th>No. Prestasi</th>
                                <th>NISN</th>
                                <th>Nama Siswa</th>
                                <th>Kelas</th>
                                <th>Jenis</th>
                                <th>Prestasi</th>
                                <th>Tingkat</th>
                                <th>Tahun</th>
                                <th class="text-center">Poin</th>
                            <?php elseif ($report_type == 'siswa'): ?>
                                <th width="35" class="text-center">No</th>
                                <th>NISN</th>
                                <th>Nama Siswa</th>
                                <th>Kelas</th>
                                <th>JK</th>
                                <th class="text-center">Pelanggaran</th>
                                <th class="text-center">Prestasi</th>
                                <th class="text-center">Poin Akhir</th>
                                <th class="text-center">Level</th>
                            <?php elseif ($report_type == 'kelas'): ?>
                                <th width="35" class="text-center">No</th>
                                <th>Nama Kelas</th>
                                <th>Tingkat</th>
                                <th>Jurusan</th>
                                <th class="text-center">Jumlah Siswa</th>
                                <th class="text-center">Total Pelanggaran</th>
                                <th class="text-center">Rata-rata Poin</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($data)): ?>
                            <?php $no = 1; foreach ($data as $row): ?>
                                <tr>
                                    <?php if ($report_type == 'pelanggaran'): 
                                        $sisa_poin = $row['poin'] - $row['poin_prestasi'];
                                        if ($sisa_poin < 0) $sisa_poin = 0;
                                    ?>
                                        <td class="text-center"><?= $no++ ?></td>
                                        <td><?= formatTanggalIndo($row['tanggal_pelanggaran']) ?></td>
                                        <td><span class="badge bg-light text-dark" style="font-size: 9px;"><?= htmlspecialchars($row['nisn'] ?? '-') ?></span></td>
                                        <td><strong><?= htmlspecialchars($row['nama_siswa']) ?></strong></td>
                                        <td><?= htmlspecialchars($row['nama_kelas'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($row['nama_pelanggaran']) ?></td>
                                        <td class="text-center"><span class="badge bg-danger" style="font-size: 10px;">+<?= $row['poin'] ?></span></td>
                                        <td class="text-center">
                                            <?php if ($row['poin_prestasi'] > 0): ?>
                                                <span class="badge bg-success" style="font-size: 10px;">+<?= $row['poin_prestasi'] ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">0</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-<?= $sisa_poin >= 50 ? 'danger' : ($sisa_poin >= 20 ? 'warning' : 'success') ?>" style="font-size: 10px;">
                                                <?= $sisa_poin ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($row['guru'] ?? '-') ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-<?= getStatusBadge($row['status']) ?>" style="font-size: 9px;">
                                                <?= getStatusLabel($row['status']) ?>
                                            </span>
                                        </td>
                                    <?php elseif ($report_type == 'prestasi'): ?>
                                        <td class="text-center"><?= $no++ ?></td>
                                        <td><span class="badge bg-primary" style="font-size: 9px;"><?= $row['no_prestasi'] ?? 'PRS' ?></span></td>
                                        <td><span class="badge bg-light text-dark" style="font-size: 9px;"><?= htmlspecialchars($row['nisn'] ?? '-') ?></span></td>
                                        <td><strong><?= htmlspecialchars($row['nama_siswa']) ?></strong></td>
                                        <td><?= htmlspecialchars($row['nama_kelas'] ?? '-') ?></td>
                                        <td>
                                            <span class="badge bg-<?= $row['jenis_prestasi'] == 'akademik' ? 'success' : 'info' ?>" style="font-size: 9px;">
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
                                                ($row['tingkat'] == 'kecamatan' ? 'primary' : 'secondary')))) ?>" style="font-size: 9px;">
                                                <?= ucfirst($row['tingkat']) ?>
                                            </span>
                                        </td>
                                        <td><?= $row['tahun'] ?></td>
                                        <td class="text-center"><span class="badge bg-success" style="font-size: 10px;">+<?= $row['poin_prestasi'] ?></span></td>
                                    <?php elseif ($report_type == 'siswa'): 
                                        $level = getPoinLevel($row['poin_akhir'] ?? 0);
                                    ?>
                                        <td class="text-center"><?= $no++ ?></td>
                                        <td><span class="badge bg-light text-dark" style="font-size: 9px;"><?= htmlspecialchars($row['nisn'] ?? '-') ?></span></td>
                                        <td><strong><?= htmlspecialchars($row['nama_siswa']) ?></strong></td>
                                        <td><?= htmlspecialchars($row['nama_kelas'] ?? '-') ?></td>
                                        <td><?= $row['jenis_kelamin'] == 'L' ? 'L' : 'P' ?></td>
                                        <td class="text-center">
                                            <?php if ($row['total_pelanggaran'] > 0): ?>
                                                <span class="badge bg-danger"><?= $row['total_pelanggaran'] ?></span>
                                                <br><small style="font-size:7px;">(<?= $row['poin_pelanggaran'] ?? 0 ?>)</small>
                                            <?php else: ?>
                                                <span class="text-muted">0</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($row['poin_prestasi'] > 0): ?>
                                                <span class="badge bg-success"><?= $row['poin_prestasi'] ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">0</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center"><span class="badge bg-<?= $level['color'] ?>"><?= $row['poin_akhir'] ?? 0 ?></span></td>
                                        <td class="text-center">
                                            <span class="badge bg-<?= $level['color'] ?>">
                                                <?= $level['level'] ?>
                                            </span>
                                        </td>
                                    <?php elseif ($report_type == 'kelas'): ?>
                                        <td class="text-center"><?= $no++ ?></td>
                                        <td><strong><?= htmlspecialchars($row['nama_kelas']) ?></strong></td>
                                        <td><?= htmlspecialchars($row['tingkat'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($row['jurusan'] ?? '-') ?></td>
                                        <td class="text-center"><span class="badge bg-primary"><?= $row['jumlah_siswa'] ?? 0 ?></span></td>
                                        <td class="text-center"><span class="badge bg-warning"><?= $row['total_pelanggaran'] ?? 0 ?></span></td>
                                        <td class="text-center">
                                            <?php 
                                            $avg = ($row['jumlah_siswa'] ?? 0) > 0 ? round(($row['total_pelanggaran'] ?? 0) / ($row['jumlah_siswa'] ?? 1), 1) : 0;
                                            ?>
                                            <span class="badge bg-<?= $avg > 5 ? 'danger' : ($avg > 2 ? 'warning' : 'success') ?>">
                                                <?= $avg ?>
                                            </span>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                            <?php if ($report_type == 'pelanggaran'): ?>
                                <tr class="table-info fw-bold">
                                    <td colspan="6" class="text-end">Total</td>
                                    <td class="text-center">+<?= $total_poin_pelanggaran ?></td>
                                    <td class="text-center">+<?= $total_poin_prestasi ?></td>
                                    <td class="text-center"><?= $total_poin_akhir ?></td>
                                    <td colspan="2"></td>
                                </tr>
                            <?php elseif ($report_type == 'siswa'): ?>
                                <tr class="table-info fw-bold">
                                    <td colspan="5" class="text-end">Total</td>
                                    <td class="text-center"><?= array_sum(array_column($data, 'total_pelanggaran')) ?></td>
                                    <td class="text-center"><?= array_sum(array_column($data, 'poin_prestasi')) ?></td>
                                    <td class="text-center"><?= array_sum(array_column($data, 'poin_akhir')) ?></td>
                                    <td></td>
                                </tr>
                            <?php endif; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?= $report_type == 'pelanggaran' ? 11 : ($report_type == 'prestasi' ? 10 : ($report_type == 'siswa' ? 9 : 7)) ?>" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block opacity-25"></i>
                                    Tidak ada data
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle filter based on report type
function toggleFilter(value) {
    var dateRange = document.getElementById('dateRange');
    var dateRange2 = document.getElementById('dateRange2');
    var kelasFilter = document.getElementById('kelasFilter');
    var siswaFilter = document.getElementById('siswaFilter');
    
    if (value == 'pelanggaran' || value == 'prestasi') {
        dateRange.style.display = 'block';
        dateRange2.style.display = 'block';
        kelasFilter.style.display = 'block';
        siswaFilter.style.display = 'block';
    } else if (value == 'siswa') {
        dateRange.style.display = 'none';
        dateRange2.style.display = 'none';
        kelasFilter.style.display = 'block';
        siswaFilter.style.display = 'none';
    } else if (value == 'kelas') {
        dateRange.style.display = 'none';
        dateRange2.style.display = 'none';
        kelasFilter.style.display = 'none';
        siswaFilter.style.display = 'none';
    }
}

// Set initial state
document.addEventListener('DOMContentLoaded', function() {
    var reportType = document.getElementById('reportType').value;
    toggleFilter(reportType);
});

// Export PDF
function exportPDF() {
    var form = document.getElementById('filterForm');
    var action = form.action || window.location.pathname;
    var params = new URLSearchParams(new FormData(form));
    params.append('export', 'pdf');
    window.open(action + '?' + params.toString(), '_blank');
}

// Export Excel
function exportExcel() {
    var form = document.getElementById('filterForm');
    var action = form.action || window.location.pathname;
    var params = new URLSearchParams(new FormData(form));
    params.append('export', 'excel');
    window.open(action + '?' + params.toString(), '_blank');
}

// Auto close toast
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
/* Table Styles */
.table-bordered {
    border: 1px solid #dee2e6;
}

.table-bordered th,
.table-bordered td {
    border: 1px solid #dee2e6;
    padding: 6px 8px;
    vertical-align: middle;
}

.table thead th {
    background: #f8f9fa;
    font-weight: 600;
    font-size: 9px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    color: #495057;
    border-bottom: 2px solid #dee2e6;
    white-space: nowrap;
}

.table tbody tr:hover {
    background-color: rgba(108, 99, 255, 0.04);
}

.table tbody td {
    font-size: 11px;
}

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

/* Badge */
.badge {
    font-size: 8px !important;
    padding: 2px 6px !important;
}

/* Responsive */
@media (max-width: 768px) {
    .table thead th {
        font-size: 7px;
        padding: 3px 4px;
    }
    
    .table tbody td {
        font-size: 9px;
        padding: 3px 4px;
    }
    
    .badge {
        font-size: 7px !important;
        padding: 1px 4px !important;
    }
    
    .toast-container .toast {
        min-width: 200px !important;
        max-width: 280px !important;
    }
    
    .toast-container .toast .toast-body {
        font-size: 11px !important;
    }
}

@media (max-width: 576px) {
    .table thead th {
        font-size: 6px;
        padding: 2px 3px;
    }
    
    .table tbody td {
        font-size: 8px;
        padding: 2px 3px;
    }
    
    .toast-container .toast {
        min-width: 160px !important;
        max-width: 240px !important;
    }
    
    .toast-container .toast .toast-body {
        font-size: 10px !important;
    }
}
</style>

<?php include '../includes/footer.php'; ?>

<?php
// ============================================
// FUNGSI GET REPORT PELANGGARAN DENGAN PRESTASI
// ============================================

function getReportPelanggaranWithPrestasi($start_date, $end_date, $kelas_id = null, $siswa_id = null) {
    $conn = getDB();
    $where = [];
    
    if (!empty($start_date) && !empty($end_date)) {
        $where[] = "DATE(p.tanggal_pelanggaran) BETWEEN '$start_date' AND '$end_date'";
    }
    if ($kelas_id > 0) {
        $where[] = "s.kelas_id = $kelas_id";
    }
    if ($siswa_id > 0) {
        $where[] = "p.siswa_id = $siswa_id";
    }
    $where[] = "p.status = 'disetujui'";
    
    $where_sql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
    
    $query = "SELECT p.*, s.nama_siswa, s.nisn, k.nama_kelas, jp.nama_pelanggaran, jp.poin,
                     COALESCE((SELECT SUM(poin_prestasi) FROM prestasi_siswa WHERE siswa_id = p.siswa_id), 0) as poin_prestasi,
                     u.nama_lengkap as guru
              FROM pelanggaran p
              JOIN siswa s ON p.siswa_id = s.id
              LEFT JOIN kelas k ON s.kelas_id = k.id
              LEFT JOIN jenis_pelanggaran jp ON p.jenis_pelanggaran_id = jp.id
              LEFT JOIN users u ON p.guru_id = u.id
              $where_sql
              ORDER BY p.tanggal_pelanggaran DESC, p.created_at DESC";
    
    return getRows($query);
}

// ============================================
// FUNGSI GET REPORT SISWA DENGAN POIN AKHIR
// ============================================

function getReportSiswaWithPoin($kelas_id = null) {
    $conn = getDB();
    $where = $kelas_id ? "WHERE s.kelas_id = $kelas_id" : "";
    
    $query = "SELECT s.*, k.nama_kelas
              FROM siswa s
              LEFT JOIN kelas k ON s.kelas_id = k.id
              $where
              ORDER BY s.nama_siswa";
    $siswa = getRows($query);
    
    $result = [];
    foreach ($siswa as $row) {
        $q_pel = $conn->query("SELECT COUNT(*) as total, SUM(poin_diperoleh) as total_poin FROM pelanggaran WHERE siswa_id = {$row['id']} AND status = 'disetujui'");
        $pel = $q_pel->fetch_assoc();
        
        $q_pres = $conn->query("SELECT SUM(poin_prestasi) as total_poin FROM prestasi_siswa WHERE siswa_id = {$row['id']}");
        $pres = $q_pres->fetch_assoc();
        
        $poin_pel = (int)$pel['total_poin'];
        $total_pel = (int)$pel['total'];
        $poin_pres = (int)$pres['total_poin'];
        $poin_akhir = $poin_pel - $poin_pres;
        if ($poin_akhir < 0) $poin_akhir = 0;
        
        $row['total_pelanggaran'] = $total_pel;
        $row['poin_pelanggaran'] = $poin_pel;
        $row['poin_prestasi'] = $poin_pres;
        $row['poin_akhir'] = $poin_akhir;
        $result[] = $row;
    }
    
    return $result;
}

// ============================================
// FUNGSI GET REPORT PRESTASI
// ============================================

function getReportPrestasi($start_date, $end_date, $kelas_id = null, $siswa_id = null) {
    $conn = getDB();
    $where = [];
    
    if (!empty($start_date) && !empty($end_date)) {
        $where[] = "DATE(p.created_at) BETWEEN '$start_date' AND '$end_date'";
    }
    if ($kelas_id > 0) {
        $where[] = "s.kelas_id = $kelas_id";
    }
    if ($siswa_id > 0) {
        $where[] = "p.siswa_id = $siswa_id";
    }
    
    $where_sql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
    
    $query = "SELECT p.*, s.nama_siswa, s.nisn, k.nama_kelas
              FROM prestasi_siswa p
              JOIN siswa s ON p.siswa_id = s.id
              LEFT JOIN kelas k ON s.kelas_id = k.id
              $where_sql
              ORDER BY p.tahun DESC, p.created_at DESC";
    
    return getRows($query);
}

// ============================================
// EXPORT FUNCTIONS
// ============================================

function exportLaporanPDF($data, $title, $start_date, $end_date, $report_type) {
    require_once('../tcpdf/tcpdf.php');
    
    $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('Tatib Sekolah');
    $pdf->SetAuthor('Tatib Sekolah');
    $pdf->SetTitle($title);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->AddPage();
    
    $pengaturan = getPengaturan();
    
    // Title
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, $pengaturan['nama_sekolah'], 0, 1, 'C');
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, strtoupper($title), 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 10);
    if ($report_type == 'pelanggaran' || $report_type == 'prestasi') {
        $pdf->Cell(0, 7, 'Periode: ' . formatTanggalIndo($start_date) . ' s/d ' . formatTanggalIndo($end_date), 0, 1, 'C');
    }
    $pdf->Ln(5);
    
    // Table header
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetFillColor(240, 240, 240);
    
    if ($report_type == 'pelanggaran') {
        $headers = ['No', 'Tanggal', 'NISN', 'Nama Siswa', 'Kelas', 'Pelanggaran', 'Poin', 'Prestasi', 'Sisa Poin', 'Guru', 'Status'];
        $widths = [6, 20, 20, 28, 16, 30, 12, 12, 14, 25, 20];
    } elseif ($report_type == 'prestasi') {
        $headers = ['No', 'No. Prestasi', 'NISN', 'Nama Siswa', 'Kelas', 'Jenis', 'Prestasi', 'Tingkat', 'Tahun', 'Poin'];
        $widths = [6, 18, 20, 28, 18, 20, 30, 18, 12, 10];
    } elseif ($report_type == 'siswa') {
        $headers = ['No', 'NISN', 'Nama Siswa', 'Kelas', 'JK', 'Pelanggaran', 'Prestasi', 'Poin Akhir', 'Level'];
        $widths = [6, 20, 30, 20, 12, 20, 20, 20, 18];
    } elseif ($report_type == 'kelas') {
        $headers = ['No', 'Nama Kelas', 'Tingkat', 'Jurusan', 'Jumlah Siswa', 'Total Pelanggaran', 'Rata-rata Poin'];
        $widths = [10, 35, 20, 25, 25, 30, 25];
    }
    
    foreach ($headers as $i => $header) {
        $pdf->Cell($widths[$i], 8, $header, 1, 0, 'C', true);
    }
    $pdf->Ln();
    
    // Table data
    $pdf->SetFont('helvetica', '', 7);
    $no = 1;
    foreach ($data as $row) {
        if ($report_type == 'pelanggaran') {
            $sisa_poin = $row['poin'] - ($row['poin_prestasi'] ?? 0);
            if ($sisa_poin < 0) $sisa_poin = 0;
            $pdf->Cell($widths[0], 6, $no++, 1, 0, 'C');
            $pdf->Cell($widths[1], 6, formatTanggal($row['tanggal_pelanggaran']), 1, 0, 'C');
            $pdf->Cell($widths[2], 6, $row['nisn'] ?? '-', 1, 0, 'C');
            $pdf->Cell($widths[3], 6, substr($row['nama_siswa'], 0, 15), 1, 0, 'L');
            $pdf->Cell($widths[4], 6, $row['nama_kelas'] ?? '-', 1, 0, 'C');
            $pdf->Cell($widths[5], 6, substr($row['nama_pelanggaran'], 0, 15), 1, 0, 'L');
            $pdf->Cell($widths[6], 6, $row['poin'], 1, 0, 'C');
            $pdf->Cell($widths[7], 6, $row['poin_prestasi'] ?? 0, 1, 0, 'C');
            $pdf->Cell($widths[8], 6, $sisa_poin, 1, 0, 'C');
            $pdf->Cell($widths[9], 6, substr($row['guru'] ?? '-', 0, 12), 1, 0, 'L');
            $pdf->Cell($widths[10], 6, getStatusLabel($row['status']), 1, 0, 'C');
            $pdf->Ln();
        } elseif ($report_type == 'prestasi') {
            $pdf->Cell($widths[0], 6, $no++, 1, 0, 'C');
            $pdf->Cell($widths[1], 6, $row['no_prestasi'] ?? 'PRS', 1, 0, 'C');
            $pdf->Cell($widths[2], 6, $row['nisn'], 1, 0, 'C');
            $pdf->Cell($widths[3], 6, substr($row['nama_siswa'], 0, 15), 1, 0, 'L');
            $pdf->Cell($widths[4], 6, $row['nama_kelas'] ?? '-', 1, 0, 'C');
            $pdf->Cell($widths[5], 6, ucfirst(str_replace('_', ' ', $row['jenis_prestasi'])), 1, 0, 'C');
            $pdf->Cell($widths[6], 6, substr($row['nama_prestasi'], 0, 18), 1, 0, 'L');
            $pdf->Cell($widths[7], 6, ucfirst($row['tingkat']), 1, 0, 'C');
            $pdf->Cell($widths[8], 6, $row['tahun'], 1, 0, 'C');
            $pdf->Cell($widths[9], 6, $row['poin_prestasi'], 1, 0, 'C');
            $pdf->Ln();
        } elseif ($report_type == 'siswa') {
            $level = getPoinLevel($row['poin_akhir'] ?? 0);
            $pdf->Cell($widths[0], 6, $no++, 1, 0, 'C');
            $pdf->Cell($widths[1], 6, $row['nisn'] ?? '-', 1, 0, 'C');
            $pdf->Cell($widths[2], 6, substr($row['nama_siswa'], 0, 18), 1, 0, 'L');
            $pdf->Cell($widths[3], 6, $row['nama_kelas'] ?? '-', 1, 0, 'C');
            $pdf->Cell($widths[4], 6, $row['jenis_kelamin'], 1, 0, 'C');
            $pdf->Cell($widths[5], 6, $row['total_pelanggaran'] ?? 0, 1, 0, 'C');
            $pdf->Cell($widths[6], 6, $row['poin_prestasi'] ?? 0, 1, 0, 'C');
            $pdf->Cell($widths[7], 6, $row['poin_akhir'] ?? 0, 1, 0, 'C');
            $pdf->Cell($widths[8], 6, $level['level'], 1, 0, 'C');
            $pdf->Ln();
        } elseif ($report_type == 'kelas') {
            $avg = ($row['jumlah_siswa'] ?? 0) > 0 ? round(($row['total_pelanggaran'] ?? 0) / ($row['jumlah_siswa'] ?? 1), 1) : 0;
            $pdf->Cell($widths[0], 6, $no++, 1, 0, 'C');
            $pdf->Cell($widths[1], 6, $row['nama_kelas'], 1, 0, 'L');
            $pdf->Cell($widths[2], 6, $row['tingkat'] ?? '-', 1, 0, 'C');
            $pdf->Cell($widths[3], 6, $row['jurusan'] ?? '-', 1, 0, 'C');
            $pdf->Cell($widths[4], 6, $row['jumlah_siswa'] ?? 0, 1, 0, 'C');
            $pdf->Cell($widths[5], 6, $row['total_pelanggaran'] ?? 0, 1, 0, 'C');
            $pdf->Cell($widths[6], 6, $avg, 1, 0, 'C');
            $pdf->Ln();
        }
    }
    
    // Summary
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 7, 'Total Data: ' . count($data), 0, 1, 'R');
    
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->Cell(0, 7, 'Dicetak: ' . date('d-m-Y H:i:s'), 0, 1, 'L');
    
    $pdf->Output($title . '_' . date('Ymd') . '.pdf', 'I');
    exit;
}

function exportLaporanExcel($data, $title, $start_date, $end_date, $report_type) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="' . $title . '_' . date('Ymd') . '.xls"');
    header('Cache-Control: max-age=0');
    
    $pengaturan = getPengaturan();
    
    echo '<html><head><meta charset="UTF-8"><title>' . $title . '</title>';
    echo '<style>';
    echo 'th { background-color: #4F46E5; color: white; font-weight: bold; padding: 8px; border: 1px solid #ddd; text-align: center; }';
    echo 'td { padding: 6px; border: 1px solid #ddd; }';
    echo '.header { font-size: 16px; font-weight: bold; text-align: center; }';
    echo '.sub-header { font-size: 12px; text-align: center; margin-bottom: 10px; }';
    echo '.total { font-weight: bold; text-align: right; }';
    echo '</style></head><body>';
    
    echo '<h2 class="header">' . $pengaturan['nama_sekolah'] . '</h2>';
    echo '<h3 class="header">' . strtoupper($title) . '</h3>';
    if ($report_type == 'pelanggaran' || $report_type == 'prestasi') {
        echo '<p class="sub-header">Periode: ' . formatTanggalIndo($start_date) . ' s/d ' . formatTanggalIndo($end_date) . '</p>';
    }
    
    echo '<table cellpadding="0" cellspacing="0" border="1" style="width:100%;border-collapse:collapse;">';
    echo '<thead><tr>';
    
    if ($report_type == 'pelanggaran') {
        echo '<th>No</th><th>Tanggal</th><th>NISN</th><th>Nama Siswa</th><th>Kelas</th><th>Pelanggaran</th><th>Poin</th><th>Prestasi</th><th>Sisa Poin</th><th>Guru</th><th>Status</th>';
    } elseif ($report_type == 'prestasi') {
        echo '<th>No</th><th>No. Prestasi</th><th>NISN</th><th>Nama Siswa</th><th>Kelas</th><th>Jenis</th><th>Prestasi</th><th>Tingkat</th><th>Tahun</th><th>Poin</th>';
    } elseif ($report_type == 'siswa') {
        echo '<th>No</th><th>NISN</th><th>Nama Siswa</th><th>Kelas</th><th>JK</th><th>Pelanggaran</th><th>Prestasi</th><th>Poin Akhir</th><th>Level</th>';
    } elseif ($report_type == 'kelas') {
        echo '<th>No</th><th>Nama Kelas</th><th>Tingkat</th><th>Jurusan</th><th>Jumlah Siswa</th><th>Total Pelanggaran</th><th>Rata-rata Poin</th>';
    }
    
    echo '</tr></thead><tbody>';
    
    $no = 1;
    foreach ($data as $row) {
        echo '<tr>';
        if ($report_type == 'pelanggaran') {
            $sisa_poin = $row['poin'] - ($row['poin_prestasi'] ?? 0);
            if ($sisa_poin < 0) $sisa_poin = 0;
            echo '<td style="text-align:center;">' . $no++ . '</td>';
            echo '<td style="text-align:center;">' . formatTanggal($row['tanggal_pelanggaran']) . '</td>';
            echo '<td style="text-align:center;">' . ($row['nisn'] ?? '-') . '</td>';
            echo '<td>' . $row['nama_siswa'] . '</td>';
            echo '<td>' . ($row['nama_kelas'] ?? '-') . '</td>';
            echo '<td>' . $row['nama_pelanggaran'] . '</td>';
            echo '<td style="text-align:center;">' . $row['poin'] . '</td>';
            echo '<td style="text-align:center;">' . ($row['poin_prestasi'] ?? 0) . '</td>';
            echo '<td style="text-align:center;">' . $sisa_poin . '</td>';
            echo '<td>' . ($row['guru'] ?? '-') . '</td>';
            echo '<td style="text-align:center;">' . getStatusLabel($row['status']) . '</td>';
        } elseif ($report_type == 'prestasi') {
            echo '<td style="text-align:center;">' . $no++ . '</td>';
            echo '<td style="text-align:center;">' . ($row['no_prestasi'] ?? 'PRS') . '</td>';
            echo '<td style="text-align:center;">' . $row['nisn'] . '</td>';
            echo '<td>' . $row['nama_siswa'] . '</td>';
            echo '<td>' . ($row['nama_kelas'] ?? '-') . '</td>';
            echo '<td>' . ucfirst(str_replace('_', ' ', $row['jenis_prestasi'])) . '</td>';
            echo '<td>' . $row['nama_prestasi'] . '</td>';
            echo '<td>' . ucfirst($row['tingkat']) . '</td>';
            echo '<td style="text-align:center;">' . $row['tahun'] . '</td>';
            echo '<td style="text-align:center;">' . $row['poin_prestasi'] . '</td>';
        } elseif ($report_type == 'siswa') {
            $level = getPoinLevel($row['poin_akhir'] ?? 0);
            echo '<td style="text-align:center;">' . $no++ . '</td>';
            echo '<td style="text-align:center;">' . ($row['nisn'] ?? '-') . '</td>';
            echo '<td>' . $row['nama_siswa'] . '</td>';
            echo '<td>' . ($row['nama_kelas'] ?? '-') . '</td>';
            echo '<td style="text-align:center;">' . $row['jenis_kelamin'] . '</td>';
            echo '<td style="text-align:center;">' . ($row['total_pelanggaran'] ?? 0) . '</td>';
            echo '<td style="text-align:center;">' . ($row['poin_prestasi'] ?? 0) . '</td>';
            echo '<td style="text-align:center;">' . ($row['poin_akhir'] ?? 0) . '</td>';
            echo '<td style="text-align:center;">' . $level['level'] . '</td>';
        } elseif ($report_type == 'kelas') {
            $avg = ($row['jumlah_siswa'] ?? 0) > 0 ? round(($row['total_pelanggaran'] ?? 0) / ($row['jumlah_siswa'] ?? 1), 1) : 0;
            echo '<td style="text-align:center;">' . $no++ . '</td>';
            echo '<td>' . $row['nama_kelas'] . '</td>';
            echo '<td style="text-align:center;">' . ($row['tingkat'] ?? '-') . '</td>';
            echo '<td style="text-align:center;">' . ($row['jurusan'] ?? '-') . '</td>';
            echo '<td style="text-align:center;">' . ($row['jumlah_siswa'] ?? 0) . '</td>';
            echo '<td style="text-align:center;">' . ($row['total_pelanggaran'] ?? 0) . '</td>';
            echo '<td style="text-align:center;">' . $avg . '</td>';
        }
        echo '</tr>';
    }
    
    echo '</tbody></table>';
    echo '<p class="total">Total Data: ' . count($data) . '</p>';
    echo '<p style="font-size:10px;color:#999;">Dicetak: ' . date('d-m-Y H:i:s') . '</p>';
    echo '</body></html>';
    exit;
}
?>