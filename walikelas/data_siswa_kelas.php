<?php
// walikelas/data_siswa_kelas.php
include '../includes/config.php';
checkLogin();

// Cek role
if (!hasRole('walikelas')) {
    $_SESSION['toast'] = [
        'message' => 'Anda tidak memiliki akses ke halaman ini!',
        'type' => 'danger'
    ];
    header("Location: " . APP_URL . "login.php");
    exit();
}

$page_title = 'Data Siswa Kelas';

// Get data wali kelas
$user = getCurrentUser();
$kelas_wali = getRow("SELECT * FROM kelas WHERE wali_kelas_id = " . $user['id']);
$kelas_id = $kelas_wali ? $kelas_wali['id'] : 0;
$nama_kelas = $kelas_wali ? $kelas_wali['nama_kelas'] : 'Belum ditentukan';

// Get siswa
$siswa_list = $kelas_id > 0 ? getSiswaByKelas($kelas_id) : [];

// Filter search
$search = isset($_GET['search']) ? $_GET['search'] : '';
if (!empty($search)) {
    $search = strtolower($search);
    $siswa_list = array_filter($siswa_list, function($item) use ($search) {
        return strpos(strtolower($item['nama_siswa']), $search) !== false ||
               strpos(strtolower($item['nisn'] ?? ''), $search) !== false;
    });
}

// Sort
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'nama_siswa';
$order = isset($_GET['order']) ? $_GET['order'] : 'asc';

usort($siswa_list, function($a, $b) use ($sort, $order) {
    $val_a = $a[$sort] ?? '';
    $val_b = $b[$sort] ?? '';
    if ($sort == 'poin') {
        $val_a = (int)$val_a;
        $val_b = (int)$val_b;
    }
    if ($order == 'asc') {
        return $val_a > $val_b ? 1 : -1;
    } else {
        return $val_a < $val_b ? 1 : -1;
    }
});

include '../includes/header.php';
include '../includes/sidebar_walikelas.php';
?>

<div class="container-fluid px-2 px-md-3 px-lg-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
        <div>
            <h4 class="mb-0">
                <i class="fas fa-users text-primary me-2"></i> Data Siswa Kelas
            </h4>
            <small class="text-muted">
                <i class="fas fa-school me-1"></i> Kelas: <strong><?= htmlspecialchars($nama_kelas) ?></strong>
                <?php if ($kelas_id > 0): ?>
                    <span class="badge bg-primary ms-1">ID: <?= $kelas_id ?></span>
                <?php endif; ?>
                <span class="badge bg-secondary ms-1">
                    <i class="fas fa-user-graduate me-1"></i> <?= count($siswa_list) ?> Siswa
                </span>
            </small>
        </div>
        <div class="text-end">
            <span class="text-muted d-block">
                <i class="fas fa-user me-1"></i> <?= htmlspecialchars($user['nama_lengkap'] ?? 'Wali Kelas') ?>
            </span>
            <span class="text-muted" style="font-size: 12px;">
                <i class="fas fa-calendar-alt me-1"></i> <?= date('d F Y') ?>
            </span>
        </div>
    </div>

    <?php if ($kelas_id == 0): ?>
        <div class="alert alert-warning alert-dismissible fade show">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Anda belum ditugaskan sebagai wali kelas. Silahkan hubungi administrator.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif (empty($siswa_list)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            Belum ada siswa di kelas <?= htmlspecialchars($nama_kelas) ?>.
        </div>
    <?php else: ?>

    <!-- Search & Filter -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="" class="row g-2">
                <div class="col-md-9">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" name="search" 
                               placeholder="Cari NISN atau Nama Siswa..." 
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-1"></i> Cari
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Siswa -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="50" class="text-center">No</th>
                            <th width="60" class="text-center">Foto</th>
                            <th>
                                <a href="?sort=nisn&order=<?= $sort == 'nisn' && $order == 'asc' ? 'desc' : 'asc' ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
                                   class="text-decoration-none text-dark">
                                    NISN
                                    <?php if ($sort == 'nisn'): ?>
                                        <i class="fas fa-sort-<?= $order == 'asc' ? 'up' : 'down' ?> ms-1"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>
                                <a href="?sort=nama_siswa&order=<?= $sort == 'nama_siswa' && $order == 'asc' ? 'desc' : 'asc' ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
                                   class="text-decoration-none text-dark">
                                    Nama Siswa
                                    <?php if ($sort == 'nama_siswa'): ?>
                                        <i class="fas fa-sort-<?= $order == 'asc' ? 'up' : 'down' ?> ms-1"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th class="text-center">JK</th>
                            <th class="text-center">
                                <a href="?sort=poin&order=<?= $sort == 'poin' && $order == 'asc' ? 'desc' : 'asc' ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
                                   class="text-decoration-none text-dark">
                                    Poin
                                    <?php if ($sort == 'poin'): ?>
                                        <i class="fas fa-sort-<?= $order == 'asc' ? 'up' : 'down' ?> ms-1"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th class="text-center" width="120">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($siswa_list)): ?>
                            <?php $no = 1; foreach ($siswa_list as $siswa): ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?></td>
                                    <td class="text-center">
                                        <?php if (!empty($siswa['foto']) && file_exists(APP_PATH . 'uploads/siswa/' . $siswa['foto'])): ?>
                                            <img src="<?= APP_URL ?>uploads/siswa/<?= $siswa['foto'] ?>" 
                                                 alt="Foto" width="35" height="35" 
                                                 style="object-fit: cover; border-radius: 50%;">
                                        <?php else: ?>
                                            <div class="avatar-placeholder" style="width:35px;height:35px;border-radius:50%;background:#e9ecef;display:inline-flex;align-items:center;justify-content:center;color:#6c757d;font-weight:600;font-size:14px;">
                                                <?= strtoupper(substr($siswa['nama_siswa'], 0, 1)) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge bg-light text-dark"><?= htmlspecialchars($siswa['nisn'] ?? '-') ?></span></td>
                                    <td><strong><?= htmlspecialchars($siswa['nama_siswa']) ?></strong></td>
                                    <td class="text-center"><?= $siswa['jenis_kelamin'] == 'L' ? 'L' : 'P' ?></td>
                                    <td class="text-center">
                                        <?php 
                                        $poin = (int)$siswa['poin'];
                                        $badge = $poin >= 300 ? 'danger' : ($poin >= 200 ? 'warning' : ($poin >= 100 ? 'info' : 'success'));
                                        ?>
                                        <span class="badge bg-<?= $badge ?>"><?= $poin ?></span>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="<?= APP_URL ?>walikelas/siswa_detail_walikelas.php?id=<?= $siswa['id'] ?>" 
                                               class="btn btn-outline-info" title="Detail">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="fas fa-user-slash fa-2x mb-2 d-block opacity-25"></i>
                                    Tidak ada siswa
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if (!empty($siswa_list)): ?>
            <div class="card-footer bg-transparent text-muted">
                <i class="fas fa-info-circle me-1"></i>
                Menampilkan <?= count($siswa_list) ?> siswa
            </div>
        <?php endif; ?>
    </div>

    <?php endif; ?>
</div>

<style>
/* Table Styles */
.table-bordered {
    border: 1px solid #dee2e6;
}

.table-bordered th,
.table-bordered td {
    border: 1px solid #dee2e6;
    padding: 10px 12px;
    vertical-align: middle;
}

.table thead th {
    background: #f8f9fa;
    font-weight: 600;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    color: #495057;
    border-bottom: 2px solid #dee2e6;
}

.table tbody tr:hover {
    background-color: rgba(108, 99, 255, 0.04);
}

.table tbody td {
    font-size: 14px;
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

/* Badge */
.badge {
    font-size: 10px !important;
    padding: 3px 8px !important;
}

/* Button */
.btn-group-sm .btn {
    padding: 2px 6px !important;
    font-size: 12px !important;
}

/* Sort link */
.table thead th a {
    color: #495057;
    text-decoration: none;
}

.table thead th a:hover {
    color: #6C63FF;
}

.table thead th a i {
    font-size: 10px;
}

/* Responsive - Mobile */
@media (max-width: 768px) {
    .table thead th {
        font-size: 10px;
        padding: 6px 8px;
    }
    
    .table tbody td {
        font-size: 12px;
        padding: 6px 8px;
    }
    
    .btn-group-sm .btn {
        padding: 2px 6px;
        font-size: 10px;
    }
    
    .badge {
        font-size: 9px !important;
        padding: 2px 6px !important;
    }
}

@media (max-width: 576px) {
    .table thead th {
        font-size: 9px;
        padding: 4px 6px;
    }
    
    .table tbody td {
        font-size: 10px;
        padding: 4px 6px;
    }
    
    .table tbody td .badge {
        font-size: 8px !important;
        padding: 2px 5px !important;
    }
    
    .btn-group-sm .btn {
        padding: 1px 4px;
        font-size: 9px;
    }
    
    /* Sembunyikan foto di mobile */
    .table th:nth-child(2),
    .table td:nth-child(2) {
        display: none !important;
    }
}
</style>

<?php include '../includes/footer.php'; ?>