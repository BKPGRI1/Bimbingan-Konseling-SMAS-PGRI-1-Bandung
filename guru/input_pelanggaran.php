<?php
// guru/input_pelanggaran.php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Cek login
if (!isLoggedIn()) {
    header("Location: ../login.php");
    exit;
}

// Cek role (guru, bk, walikelas, admin)
if (!hasRole(['admin', 'guru', 'bk', 'walikelas'])) {
    header("Location: ../dashboard.php");
    exit;
}

$page_title = 'Input Pelanggaran';

// Proses simpan data
$toast_message = '';
$toast_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $siswa_id = isset($_POST['siswa_id']) ? (int)$_POST['siswa_id'] : 0;
    $jenis_pelanggaran_id = isset($_POST['jenis_pelanggaran_id']) ? (int)$_POST['jenis_pelanggaran_id'] : 0;
    $tanggal_pelanggaran = isset($_POST['tanggal_pelanggaran']) ? $_POST['tanggal_pelanggaran'] : date('Y-m-d');
    $jam_pelanggaran = isset($_POST['jam_pelanggaran']) ? $_POST['jam_pelanggaran'] : date('H:i:s');
    $lokasi = isset($_POST['lokasi']) ? cleanString($_POST['lokasi']) : '';
    $keterangan = isset($_POST['keterangan']) ? cleanString($_POST['keterangan']) : '';
    $guru_id = $_SESSION['user_id'];
    $status = isset($_POST['status']) ? $_POST['status'] : 'pending';
    $send_wa = isset($_POST['send_wa']) ? 1 : 0;
    
    // Validasi
    if ($siswa_id == 0 || $jenis_pelanggaran_id == 0) {
        $toast_message = "Silakan pilih siswa dan jenis pelanggaran!";
        $toast_type = "danger";
    } else {
        // Upload bukti foto
        $bukti_file = '';
        if (isset($_FILES['bukti']) && $_FILES['bukti']['error'] == UPLOAD_ERR_OK) {
            $upload_dir = APP_PATH . 'uploads/bukti_pelanggaran/';
            $bukti_file = uploadBuktiPelanggaran($_FILES['bukti'], $upload_dir);
            if (!$bukti_file) {
                $toast_message = "Gagal upload bukti foto. Pastikan file berformat JPG, PNG, atau PDF dan ukuran maksimal 5MB.";
                $toast_type = "danger";
            }
        }
        
        if (empty($toast_message)) {
            $data = [
                'siswa_id' => $siswa_id,
                'jenis_pelanggaran_id' => $jenis_pelanggaran_id,
                'guru_id' => $guru_id,
                'tanggal_pelanggaran' => $tanggal_pelanggaran,
                'jam_pelanggaran' => $jam_pelanggaran,
                'lokasi' => $lokasi,
                'keterangan' => $keterangan,
                'bukti' => $bukti_file,
                'status' => $status
            ];
            
            $result = tambahPelanggaran($data);
            
            if ($result) {
                // Ambil data siswa dan jenis pelanggaran untuk WhatsApp
                $siswa = getSiswaById($siswa_id);
                $jenis = getJenisPelanggaranById($jenis_pelanggaran_id);
                
                // Kirim WhatsApp
                $wa_sent = false;
                $wa_message = '';
                $api_config = getApiWaConfig();
                $wa_configured = !empty($api_config['url']) && !empty($api_config['token']);
                
                if ($send_wa && $wa_configured) {
                    if (!empty($siswa['no_hp'])) {
                        $wa_sent = sendWhatsAppPelanggaran($siswa, $jenis, $jenis['poin'], $tanggal_pelanggaran, $keterangan);
                        if ($wa_sent) {
                            $wa_message = " WhatsApp berhasil dikirim.";
                            $total_poin = getTotalPoinSiswa($siswa_id);
                            $batas = getBatasPoin();
                            if ($total_poin >= $batas['peringatan']) {
                                $siswa['nama_kelas'] = $siswa['nama_kelas'] ?? '';
                                sendWhatsAppPeringatanPoin($siswa, $total_poin);
                                $wa_message .= " Peringatan poin juga dikirim.";
                            }
                        } else {
                            $wa_message = " Gagal mengirim WhatsApp.";
                        }
                    } else {
                        $wa_message = " Nomor HP tidak tersedia.";
                    }
                } elseif ($send_wa && !$wa_configured) {
                    $wa_message = " API WA belum dikonfigurasi.";
                }
                
                $toast_message = "Data pelanggaran berhasil disimpan!" . $wa_message;
                $toast_type = "success";
                if ($bukti_file) {
                    $toast_message .= " Bukti foto berhasil diupload.";
                }
            } else {
                $toast_message = "Gagal menyimpan data pelanggaran!";
                $toast_type = "danger";
            }
        }
    }
}

// Ambil data untuk form
$siswa_list = getSiswa();
$jenis_pelanggaran_list = getJenisPelanggaranAll();

function getKelasByWaliKelas($user_id) {
    $query = "SELECT * FROM kelas WHERE wali_kelas_id = $user_id LIMIT 1";
    return getRow($query);
}

function uploadBuktiPelanggaran($file, $target_dir) {
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
    $max_size = 5 * 1024 * 1024;
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $file_type = mime_content_type($file['tmp_name']);
    if (!in_array($file_ext, $allowed_ext) || !in_array($file_type, $allowed_types) || $file['size'] > $max_size) {
        return false;
    }
    $new_filename = 'bukti_' . date('Ymd_His') . '_' . uniqid() . '.' . $file_ext;
    $target_path = $target_dir . $new_filename;
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        return $new_filename;
    }
    return false;
}

include __DIR__ . '/../includes/header_guru.php';
?>

<div class="container-fluid dashboard-container">
    <!-- Header -->
    <div class="dashboard-header">
        <div>
            <h4 class="dashboard-title">
                <i class="fas fa-plus-circle text-primary me-2"></i> Input Pelanggaran
            </h4>
            <small class="text-muted dashboard-subtitle">
                <i class="fas fa-search me-1"></i> Klik tombol cari untuk mencari siswa
            </small>
        </div>
    </div>

    <!-- Stats -->
    <div class="quick-stats-grid mb-3">
        <div class="quick-stat-card">
            <div class="quick-stat-icon bg-primary">
                <i class="fas fa-users"></i>
            </div>
            <div class="quick-stat-info">
                <span class="quick-stat-label">Total Siswa</span>
                <span class="quick-stat-number"><?= count($siswa_list) ?></span>
            </div>
        </div>
        <div class="quick-stat-card">
            <div class="quick-stat-icon bg-success">
                <i class="fas fa-tags"></i>
            </div>
            <div class="quick-stat-info">
                <span class="quick-stat-label">Jenis Pelanggaran</span>
                <span class="quick-stat-number"><?= count($jenis_pelanggaran_list) ?></span>
            </div>
        </div>
    </div>

    <!-- Form Input Pelanggaran -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-file-alt me-2"></i> Form Data Pelanggaran
            <span class="info-badge">
                <i class="fas fa-info-circle"></i> 
                <?php if ($user['role'] == 'walikelas'): ?>
                Hanya untuk siswa di kelas Anda
                <?php else: ?>
                Untuk semua siswa
                <?php endif; ?>
            </span>
        </div>
        <div class="card-body">
            <form method="POST" action="" id="formPelanggaran" enctype="multipart/form-data">
                <!-- Siswa - Dengan Tombol Cari -->
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-user-graduate me-1"></i> Siswa <span class="required">*</span>
                    </label>
                    <div class="siswa-search-group">
                        <div class="input-group">
                            <input type="text" id="siswaNamaDisplay" class="form-control" placeholder="Belum pilih siswa" readonly>
                            <input type="hidden" name="siswa_id" id="siswaIdHidden" value="">
                            <button type="button" class="btn btn-primary" id="btnCariSiswa" data-bs-toggle="modal" data-bs-target="#modalCariSiswa">
                                <i class="fas fa-search"></i> Cari
                            </button>
                            <button type="button" class="btn btn-secondary" id="btnClearSiswa" onclick="clearSiswa()">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Info Siswa -->
                    <div id="siswaInfo" class="siswa-info-card mt-2">
                        <div class="info-card">
                            <div class="info-grid">
                                <div class="info-item">
                                    <span class="info-label">Nama</span>
                                    <span class="info-value" id="infoNama">-</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">NISN</span>
                                    <span class="info-value" id="infoNISN">-</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Kelas</span>
                                    <span class="info-value" id="infoKelas">-</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">No HP</span>
                                    <span class="info-value" id="infoNoHP">-</span>
                                </div>
                                <div class="info-item full-width">
                                    <span class="info-label">Poin Saat Ini</span>
                                    <span id="infoPoin" class="badge-poin">0</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Jenis Pelanggaran -->
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-tag me-1"></i> Jenis Pelanggaran <span class="required">*</span>
                    </label>
                    <select name="jenis_pelanggaran_id" id="jenisPelanggaranSelect" class="form-select" required>
                        <option value="">-- Pilih Jenis Pelanggaran --</option>
                        <?php foreach ($jenis_pelanggaran_list as $jp): ?>
                        <option value="<?php echo $jp['id']; ?>" 
                                data-poin="<?php echo $jp['poin']; ?>"
                                data-kategori="<?php echo $jp['kategori']; ?>">
                            <?php echo htmlspecialchars($jp['nama_pelanggaran']); ?> (<?php echo $jp['poin']; ?> poin)
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div id="jpInfo" class="siswa-info-card mt-2" style="display:none;">
                        <div class="info-card">
                            <div class="info-grid">
                                <div class="info-item">
                                    <span class="info-label">Poin</span>
                                    <span class="info-value" id="infoPoinJP">-</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Kategori</span>
                                    <span class="info-value" id="infoKategoriJP">-</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tanggal & Jam -->
                <div class="row g-2">
                    <div class="col-6 mb-3">
                        <label class="form-label">
                            <i class="fas fa-calendar me-1"></i> Tanggal <span class="required">*</span>
                        </label>
                        <input type="date" name="tanggal_pelanggaran" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label">
                            <i class="fas fa-clock me-1"></i> Jam
                        </label>
                        <input type="time" name="jam_pelanggaran" class="form-control" value="<?php echo date('H:i'); ?>">
                    </div>
                </div>

                <!-- Lokasi -->
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-map-marker-alt me-1"></i> Lokasi
                    </label>
                    <input type="text" name="lokasi" class="form-control" placeholder="Contoh: Ruang Kelas, Lapangan, Kantin">
                </div>

                <!-- Keterangan -->
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-comment me-1"></i> Keterangan
                    </label>
                    <input type="text" name="keterangan" class="form-control" placeholder="Deskripsi pelanggaran">
                </div>

                <!-- Status -->
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-info-circle me-1"></i> Status
                    </label>
                    <select name="status" class="form-select">
                        <option value="pending">⏳ Pending (Menunggu)</option>
                        <option value="disetujui">✅ Disetujui</option>
                        <option value="ditolak">❌ Ditolak</option>
                    </select>
                </div>

                <!-- Bukti Foto -->
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-camera me-1"></i> Bukti Foto <small class="text-muted">(Opsional)</small>
                    </label>
                    <div class="upload-area" id="uploadArea">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p class="upload-text"><strong>Ketuk untuk upload</strong></p>
                        <p class="file-info">JPG, PNG, GIF, PDF (Max 5MB)</p>
                        <input type="file" name="bukti" id="buktiInput" class="d-none" accept=".jpg,.jpeg,.png,.gif,.pdf">
                        <button type="button" class="btn-upload" onclick="document.getElementById('buktiInput').click()">
                            <i class="fas fa-upload me-1"></i> Pilih File
                        </button>
                    </div>
                    <div id="previewContainer" class="preview-container" style="display: none;">
                        <img id="previewImage" src="" alt="Preview">
                        <button type="button" class="remove-preview" onclick="removePreview()">
                            <i class="fas fa-times"></i>
                        </button>
                        <span id="fileName" class="file-name-display"></span>
                    </div>
                </div>

                <!-- WhatsApp -->
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fab fa-whatsapp me-1" style="color: #25D366;"></i> WhatsApp
                    </label>
                    <div class="wa-card">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="sendWA" name="send_wa" value="1" checked>
                            <label class="form-check-label" for="sendWA">
                                Kirim notifikasi ke orang tua/wali
                                <span class="wa-badge"><i class="fab fa-whatsapp me-1"></i> WA</span>
                            </label>
                        </div>
                        <small class="text-muted d-block mt-1">Jika nomor HP siswa tersedia</small>
                        <?php 
                        $api_config = getApiWaConfig();
                        if (empty($api_config['url']) || empty($api_config['token'])): 
                        ?>
                            <span class="badge bg-warning text-dark mt-1">⚠️ API WA belum dikonfigurasi</span>
                            <br>
                            <small class="text-muted">
                                <a href="<?= APP_URL ?>admin/pengaturan/api_wa.php" target="_blank">Konfigurasi</a>
                            </small>
                        <?php else: ?>
                            <span class="badge bg-success mt-1">✅ WA terkonfigurasi</span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Tombol -->
                <div class="btn-group-mobile">
                    <a href="<?= APP_URL ?>guru/data_siswa_guru.php" class="btn btn-secondary btn-block">
                        <i class="fas fa-arrow-left me-1"></i> Kembali
                    </a>
                    <button type="reset" class="btn btn-secondary btn-block">
                        <i class="fas fa-undo me-1"></i> Reset
                    </button>
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-save me-1"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Informasi -->
    <div class="card">
        <div class="card-header" style="cursor:pointer;" onclick="toggleInfo()">
            <i class="fas fa-info-circle me-2"></i> Informasi 
            <span class="info-badge"><i class="fas fa-chevron-down" id="infoArrow"></i></span>
        </div>
        <div class="card-body" id="infoContent">
            <div class="row g-2">
                <div class="col-4">
                    <div class="info-badge-item">
                        <i class="fas fa-circle text-success"></i>
                        <span>Ringan</span>
                        <small>1-25 poin</small>
                    </div>
                </div>
                <div class="col-4">
                    <div class="info-badge-item">
                        <i class="fas fa-circle text-warning"></i>
                        <span>Sedang</span>
                        <small>26-50 poin</small>
                    </div>
                </div>
                <div class="col-4">
                    <div class="info-badge-item">
                        <i class="fas fa-circle text-danger"></i>
                        <span>Berat</span>
                        <small>&gt;50 poin</small>
                    </div>
                </div>
            </div>
            <hr>
            <div class="row g-2">
                <div class="col-6">
                    <span class="text-muted">Batas Poin:</span>
                </div>
                <div class="col-6">
                    <?php $batas = getBatasPoin(); ?>
                    <span class="badge bg-info">Teguran <?= $batas['peringatan']; ?></span>
                    <span class="badge bg-warning">Panggil <?= $batas['pemanggilan']; ?></span>
                    <span class="badge bg-danger">DO <?= $batas['do']; ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL CARI SISWA -->
<div class="modal fade" id="modalCariSiswa" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-search text-primary me-2"></i> Cari Siswa
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <input type="text" id="searchSiswaInput" class="form-control form-control-lg" 
                           placeholder="🔍 Ketik nama atau NISN..." autofocus>
                    <small class="text-muted">Ketik minimal 1 karakter untuk mencari</small>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-striped" id="tableSiswaSearch">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama</th>
                                <th>NISN</th>
                                <th>Kelas</th>
                                <th>Poin</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="siswaSearchResult">
                            <tr>
                                <td colspan="6" class="text-center text-muted py-3">
                                    <i class="fas fa-search me-2"></i> Ketik nama atau NISN untuk mencari
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div class="toast-container position-fixed bottom-0 start-50 translate-middle-x p-3" style="z-index: 9999; max-width: 95%;">
    <div id="liveToast" class="toast align-items-center border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body" id="toastMessage"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script>
// ============================================
// DATA SISWA
// ============================================
const siswaData = <?php 
$batas = getBatasPoin();
$data = [];
foreach ($siswa_list as $siswa) {
    $show = true;
    if ($user['role'] == 'walikelas') {
        $kelas = getKelasByWaliKelas($_SESSION['user_id']);
        if ($kelas && $siswa['kelas_id'] != $kelas['id']) {
            $show = false;
        }
    }
    if ($show) {
        $poin = (int)$siswa['poin'];
        $poin_class = $poin >= $batas['do'] ? 'danger' : ($poin >= $batas['peringatan'] ? 'warning' : 'success');
        $data[] = [
            'id' => $siswa['id'],
            'nama' => $siswa['nama_siswa'],
            'nisn' => $siswa['nisn'],
            'kelas' => $siswa['nama_kelas'] ?? '-',
            'nohp' => $siswa['no_hp'] ?? '-',
            'poin' => $poin,
            'poin_class' => $poin_class
        ];
    }
}
echo json_encode($data);
?>;

// ============================================
// FUNGSI CARI SISWA
// ============================================
function searchSiswa(keyword) {
    keyword = keyword.toLowerCase().trim();
    if (keyword.length === 0) {
        return [];
    }
    return siswaData.filter(function(item) {
        return item.nama.toLowerCase().indexOf(keyword) !== -1 || 
               item.nisn.indexOf(keyword) !== -1;
    });
}

// ============================================
// RENDER HASIL PENCARIAN
// ============================================
function renderSearchResult(results) {
    const tbody = document.getElementById('siswaSearchResult');
    if (results.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-3">Siswa tidak ditemukan</td></tr>';
        return;
    }
    let html = '';
    results.forEach(function(item, index) {
        const poinBadge = item.poin > 0 ? 
            '<span class="badge bg-' + item.poin_class + '">' + item.poin + ' poin</span>' : 
            '<span class="badge bg-secondary">0</span>';
        html += '<tr>' +
            '<td>' + (index + 1) + '</td>' +
            '<td><strong>' + item.nama + '</strong></td>' +
            '<td>' + item.nisn + '</td>' +
            '<td>' + item.kelas + '</td>' +
            '<td>' + poinBadge + '</td>' +
            '<td>' +
                '<button class="btn btn-sm btn-success" onclick="pilihSiswa(' + item.id + ')">' +
                    '<i class="fas fa-check me-1"></i> Pilih' +
                '</button>' +
            '</td>' +
            '</tr>';
    });
    tbody.innerHTML = html;
}

// ============================================
// PILIH SISWA
// ============================================
function pilihSiswa(id) {
    const siswa = siswaData.find(function(item) { return item.id == id; });
    if (!siswa) return;
    
    document.getElementById('siswaIdHidden').value = siswa.id;
    document.getElementById('siswaNamaDisplay').value = siswa.nama + ' (' + siswa.nisn + ')';
    
    document.getElementById('infoNama').textContent = siswa.nama;
    document.getElementById('infoNISN').textContent = siswa.nisn;
    document.getElementById('infoKelas').textContent = siswa.kelas;
    document.getElementById('infoNoHP').textContent = siswa.nohp || '-';
    document.getElementById('infoPoin').textContent = siswa.poin;
    
    const poinBadge = document.getElementById('infoPoin');
    const batas = <?php echo json_encode(getBatasPoin()); ?>;
    if (siswa.poin >= batas.do) {
        poinBadge.className = 'badge-poin poin-danger';
    } else if (siswa.poin >= batas.peringatan) {
        poinBadge.className = 'badge-poin poin-warning';
    } else {
        poinBadge.className = 'badge-poin poin-aman';
    }
    
    document.getElementById('siswaInfo').classList.add('show');
    
    // Tutup modal
    var modal = bootstrap.Modal.getInstance(document.getElementById('modalCariSiswa'));
    if (modal) modal.hide();
}

// ============================================
// CLEAR SISWA
// ============================================
function clearSiswa() {
    document.getElementById('siswaIdHidden').value = '';
    document.getElementById('siswaNamaDisplay').value = 'Belum pilih siswa';
    document.getElementById('siswaInfo').classList.remove('show');
}

// ============================================
// EVENT LISTENER SEARCH
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchSiswaInput');
    
    // Event setiap kali input berubah
    searchInput.addEventListener('input', function() {
        const keyword = this.value;
        const results = searchSiswa(keyword);
        renderSearchResult(results);
    });
    
    // Auto focus saat modal dibuka
    document.getElementById('modalCariSiswa').addEventListener('shown.bs.modal', function() {
        setTimeout(function() {
            searchInput.focus();
            searchInput.select();
        }, 300);
    });
    
    // Reset saat modal ditutup
    document.getElementById('modalCariSiswa').addEventListener('hidden.bs.modal', function() {
        searchInput.value = '';
        document.getElementById('siswaSearchResult').innerHTML = 
            '<tr><td colspan="6" class="text-center text-muted py-3"><i class="fas fa-search me-2"></i> Ketik nama atau NISN untuk mencari</td></tr>';
    });
    
    // Trigger default untuk jenis pelanggaran
    const jpSelect = document.querySelector('select[name="jenis_pelanggaran_id"]');
    if (jpSelect.value) {
        jpSelect.dispatchEvent(new Event('change'));
    }
    
    // Enter key untuk pilih siswa pertama
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            const firstButton = document.querySelector('#siswaSearchResult .btn-success');
            if (firstButton) {
                firstButton.click();
            }
        }
    });
});

// ============================================
// TOGGLE INFO
// ============================================
function toggleInfo() {
    const content = document.getElementById('infoContent');
    const arrow = document.getElementById('infoArrow');
    if (content.style.display === 'none') {
        content.style.display = 'block';
        arrow.className = 'fas fa-chevron-up';
    } else {
        content.style.display = 'none';
        arrow.className = 'fas fa-chevron-down';
    }
}

// ============================================
// SHOW INFO JENIS PELANGGARAN
// ============================================
document.querySelector('select[name="jenis_pelanggaran_id"]').addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    const infoDiv = document.getElementById('jpInfo');
    if (this.value) {
        document.getElementById('infoPoinJP').textContent = selected.dataset.poin || '-';
        document.getElementById('infoKategoriJP').textContent = selected.dataset.kategori || '-';
        infoDiv.style.display = 'block';
    } else {
        infoDiv.style.display = 'none';
    }
});

// ============================================
// TOAST NOTIFICATION
// ============================================
function showToast(message, type = 'success') {
    const toastEl = document.getElementById('liveToast');
    const toastBody = document.getElementById('toastMessage');
    toastEl.className = 'toast align-items-center border-0';
    toastEl.classList.add('toast-' + type);
    toastBody.innerHTML = message;
    const toast = new bootstrap.Toast(toastEl, { delay: 5000, animation: true });
    toast.show();
}

<?php if ($toast_message): ?>
document.addEventListener('DOMContentLoaded', function() {
    showToast('<?php echo addslashes($toast_message); ?>', '<?php echo $toast_type; ?>');
});
<?php endif; ?>

// ============================================
// UPLOAD BUKTI FOTO
// ============================================
const buktiInput = document.getElementById('buktiInput');
const previewContainer = document.getElementById('previewContainer');
const previewImage = document.getElementById('previewImage');
const fileName = document.getElementById('fileName');
const uploadArea = document.getElementById('uploadArea');

buktiInput.addEventListener('change', function(e) {
    const file = this.files[0];
    if (file) previewFile(file);
});

uploadArea.addEventListener('click', function(e) {
    if (e.target.tagName !== 'BUTTON') buktiInput.click();
});

function previewFile(file) {
    const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
    const maxSize = 5 * 1024 * 1024;
    if (!validTypes.includes(file.type)) {
        showToast('Format file tidak didukung.', 'danger');
        buktiInput.value = '';
        return;
    }
    if (file.size > maxSize) {
        showToast('Ukuran file terlalu besar. Maksimal 5MB.', 'danger');
        buktiInput.value = '';
        return;
    }
    fileName.textContent = file.name + ' (' + formatFileSize(file.size) + ')';
    previewContainer.style.display = 'block';
    uploadArea.style.display = 'none';
    if (file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImage.src = e.target.result;
            previewImage.style.display = 'block';
        };
        reader.readAsDataURL(file);
    } else {
        previewImage.src = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI2NCIgaGVpZ2h0PSI2NCIgdmlld0JveD0iMCAwIDI0IDI0IiBmaWxsPSJub25lIiBzdHJva2U9IiNlZjQ0NDQiIHN0cm9rZS13aWR0aD0iMiIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiBzdHJva2UtbGluZWpvaW49InJvdW5kIj48cGF0aCBkPSJNMTQgMkg2YTIgMiAwIDAgMC0yIDJ2MTZhMiAyIDAgMCAwIDIgMmgxMmEyIDIgMCAwIDAgMi0yVjhsLTYtNnoiLz48cGF0aCBkPSJNMTQgMnY2aDZNMTAgMTJsMiAybDQtNCIvPjwvc3ZnPg==';
        previewImage.style.display = 'block';
        previewImage.style.maxWidth = '64px';
        previewImage.style.maxHeight = '64px';
    }
}

function removePreview() {
    buktiInput.value = '';
    previewContainer.style.display = 'none';
    uploadArea.style.display = 'block';
    previewImage.src = '';
    previewImage.style.display = 'none';
    fileName.textContent = '';
}

function formatFileSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1048576).toFixed(1) + ' MB';
}
</script>

<style>
/* ============================================
   STYLES
   ============================================ */

.siswa-search-group .input-group {
    gap: 6px;
}
.siswa-search-group .input-group .form-control {
    border-radius: 10px;
    border: 2px solid #e2e8f0;
    padding: 12px 14px;
    font-size: 16px;
    height: 52px;
    background: #f8fafc;
    flex: 1;
}
.siswa-search-group .input-group .btn {
    height: 52px;
    padding: 0 20px;
    border-radius: 10px;
    font-size: 16px;
    white-space: nowrap;
}
.siswa-search-group .input-group .btn-primary {
    background: #4F46E5;
    border: none;
    color: #fff;
}
.siswa-search-group .input-group .btn-primary:hover {
    background: #4338CA;
}
.siswa-search-group .input-group .btn-secondary {
    background: #e2e8f0;
    border: none;
    color: #4a5568;
}
.siswa-search-group .input-group .btn-secondary:hover {
    background: #cbd5e0;
}

/* Modal */
.modal-content {
    border-radius: 16px;
    border: none;
}
.modal-header {
    border-bottom: 1px solid #e9ecef;
    padding: 16px 20px;
}
.modal-body {
    padding: 20px;
}
.modal-footer {
    border-top: 1px solid #e9ecef;
    padding: 12px 20px;
}

#searchSiswaInput {
    border-radius: 12px;
    border: 2px solid #e2e8f0;
    padding: 14px 18px;
    font-size: 18px;
    height: 58px;
}
#searchSiswaInput:focus {
    border-color: #4F46E5;
    box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.15);
}

.table {
    font-size: 14px;
}
.table th {
    background: #f8fafc;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    color: #4a5568;
    white-space: nowrap;
}
.table td {
    vertical-align: middle;
    padding: 10px 12px;
}
.table .btn-sm {
    padding: 4px 12px;
    font-size: 13px;
    border-radius: 6px;
}

/* Toast */
.toast {
    background: #1a202c;
    color: #fff;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    min-width: auto;
    max-width: 95%;
    margin: 0 auto;
}
.toast .toast-body {
    padding: 14px 18px;
    font-size: 15px;
}
.toast-success .toast-body { background: #10B981; color: #fff; }
.toast-danger .toast-body { background: #EF4444; color: #fff; }
.toast-warning .toast-body { background: #F59E0B; color: #fff; }
.toast-info .toast-body { background: #3B82F6; color: #fff; }
.toast-success .btn-close,
.toast-danger .btn-close,
.toast-warning .btn-close,
.toast-info .btn-close {
    filter: brightness(0) invert(1);
}

/* Card */
.card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
    margin-bottom: 12px;
    background: #fff;
}
.card-header {
    background: transparent;
    border-bottom: 1px solid #e9ecef;
    padding: 12px 14px;
    font-weight: 600;
    font-size: 14px;
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    align-items: center;
}
.card-body {
    padding: 14px;
}

/* Info Card */
.info-card {
    background: #f8fafc;
    border-radius: 10px;
    padding: 12px 14px;
    border: 1px solid #e9ecef;
}
.info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 6px 12px;
}
.info-item {
    display: flex;
    flex-direction: column;
}
.info-item.full-width {
    grid-column: 1 / -1;
}
.info-label {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    color: #6B7280;
}
.info-value {
    font-size: 14px;
    font-weight: 600;
    color: #1a202c;
}
.badge-poin {
    display: inline-block;
    padding: 2px 12px;
    border-radius: 16px;
    font-size: 14px;
    font-weight: 700;
    text-align: center;
    margin-top: 2px;
}
.poin-aman { background: #d1fae5; color: #065f46; }
.poin-warning { background: #fef3c7; color: #92400e; }
.poin-danger { background: #fecaca; color: #991b1b; }

/* Form */
.form-control, .form-select {
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    padding: 12px 14px;
    font-size: 16px !important;
    height: 52px;
}
.form-control:focus, .form-select:focus {
    border-color: #4F46E5;
    box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.15);
}
.form-label {
    font-weight: 600;
    color: #4a5568;
    font-size: 14px;
    margin-bottom: 4px;
    display: block;
}
.required {
    color: #e53e3e;
    margin-left: 2px;
}
.info-badge {
    font-size: 11px;
    color: #718096;
    margin-left: auto;
}

/* Quick Stats */
.quick-stats-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    margin-bottom: 12px;
}
.quick-stat-card {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    min-height: 56px;
}
.quick-stat-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 14px;
    flex-shrink: 0;
}
.quick-stat-icon.bg-primary { background: #4F46E5; }
.quick-stat-icon.bg-success { background: #10B981; }
.quick-stat-info {
    flex: 1;
    min-width: 0;
}
.quick-stat-label {
    font-size: 0.55rem;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    color: #6B7280;
    display: block;
}
.quick-stat-number {
    font-size: 1.1rem;
    font-weight: 700;
    display: block;
}

/* WA Card */
.wa-card {
    background: #f8fafc;
    border-radius: 10px;
    padding: 12px 14px;
    border: 1px solid #e9ecef;
}
.wa-badge {
    background: #25D366;
    color: #fff;
    padding: 2px 10px;
    border-radius: 20px;
    font-size: 11px;
    display: inline-block;
}

/* Upload Area */
.upload-area {
    border: 2px dashed #d1d5db;
    border-radius: 12px;
    padding: 20px 15px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
    background: #f9fafb;
}
.upload-area:hover {
    border-color: #4F46E5;
    background: rgba(79, 70, 229, 0.05);
}
.upload-area i {
    font-size: 32px;
    color: #9ca3af;
    margin-bottom: 6px;
}
.upload-area .upload-text {
    font-size: 14px;
    font-weight: 500;
    margin: 0;
    color: #4a5568;
}
.upload-area .file-info {
    font-size: 12px;
    color: #9ca3af;
    margin: 4px 0 8px 0;
}
.btn-upload {
    background: #4F46E5;
    color: #fff;
    border: none;
    padding: 8px 20px;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 14px;
}
.btn-upload:hover {
    background: #4338CA;
    color: #fff;
}

/* Preview */
.preview-container {
    position: relative;
    display: inline-block;
    margin-top: 10px;
}
.preview-container img {
    max-width: 160px;
    max-height: 120px;
    border-radius: 8px;
    border: 2px solid #e2e8f0;
    padding: 4px;
    background: #fff;
    object-fit: cover;
}
.preview-container .remove-preview {
    position: absolute;
    top: -8px;
    right: -8px;
    width: 26px;
    height: 26px;
    border-radius: 50%;
    background: #ef4444;
    color: #fff;
    border: none;
    font-size: 13px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}
.file-name-display {
    display: inline-block;
    padding: 4px 12px;
    background: #e5e7eb;
    border-radius: 6px;
    font-size: 13px;
    margin-top: 6px;
}

/* Button Group */
.btn-group-mobile {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    margin-top: 12px;
}
.btn-group-mobile .btn-block {
    width: 100%;
    padding: 12px;
    font-size: 15px;
    border-radius: 10px;
    border: none;
    font-weight: 600;
    text-align: center;
}
.btn-group-mobile .btn-primary {
    grid-column: 1 / -1;
    background: linear-gradient(135deg, #4F46E5 0%, #4338CA 100%);
    color: #fff;
}
.btn-group-mobile .btn-secondary {
    background: #e2e8f0;
    color: #4a5568;
}

/* Info Badge Items */
.info-badge-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 6px;
    background: #f8fafc;
    border-radius: 8px;
}
.info-badge-item span {
    font-size: 12px;
    font-weight: 500;
}
.info-badge-item small {
    font-size: 10px;
    color: #6c757d;
}

/* Dashboard */
.dashboard-container {
    padding: 8px !important;
    max-width: 100%;
}
.dashboard-header {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 4px;
    margin-bottom: 12px;
}
.dashboard-title {
    font-size: 1.1rem;
    margin-bottom: 0;
    font-weight: 700;
}
.dashboard-subtitle {
    font-size: 0.7rem;
    color: #6c757d;
}

/* Mobile Info Collapsible */
#infoContent {
    display: block;
}
@media (max-width: 576px) {
    #infoContent {
        display: none;
    }
}

/* Responsive */
@media (max-width: 768px) {
    .dashboard-container { padding: 6px !important; }
    .dashboard-title { font-size: 1rem; }
    .form-control, .form-select { font-size: 16px !important; padding: 10px 12px; height: 48px; }
    .card-header { font-size: 13px; padding: 10px 12px; }
    .card-body { padding: 12px; }
    .btn-group-mobile .btn-block { padding: 10px; font-size: 14px; }
    .siswa-search-group .input-group .form-control { height: 48px; font-size: 15px; }
    .siswa-search-group .input-group .btn { height: 48px; font-size: 14px; padding: 0 14px; }
    .modal-content { border-radius: 12px; }
    #searchSiswaInput { font-size: 16px; height: 50px; padding: 12px 14px; }
}

@media (max-width: 576px) {
    .dashboard-container { padding: 4px !important; }
    .dashboard-title { font-size: 0.9rem; }
    .quick-stats-grid { gap: 4px; }
    .quick-stat-card { padding: 6px 10px; min-height: 44px; gap: 6px; }
    .quick-stat-icon { width: 28px; height: 28px; font-size: 11px; }
    .quick-stat-number { font-size: 0.9rem; }
    .quick-stat-label { font-size: 0.45rem; }
    .form-control, .form-select { font-size: 16px !important; padding: 8px 10px; height: 44px; border-radius: 8px; }
    .form-label { font-size: 13px; }
    .card-header { font-size: 12px; padding: 8px 10px; }
    .card-body { padding: 10px; }
    .btn-group-mobile { gap: 6px; }
    .btn-group-mobile .btn-block { padding: 8px; font-size: 13px; border-radius: 8px; }
    .upload-area { padding: 14px 10px; }
    .upload-area i { font-size: 24px; }
    .btn-upload { padding: 6px 16px; font-size: 13px; }
    .toast .toast-body { font-size: 14px; padding: 12px 14px; }
    .siswa-search-group .input-group .form-control { height: 44px; font-size: 15px; padding: 8px 12px; }
    .siswa-search-group .input-group .btn { height: 44px; font-size: 13px; padding: 0 12px; }
    .modal-body { padding: 12px; }
    #searchSiswaInput { font-size: 16px; height: 44px; padding: 10px 12px; }
    .table { font-size: 13px; }
    .table td { padding: 6px 8px; }
}
</style>

<?php include '../includes/footer.php'; ?>