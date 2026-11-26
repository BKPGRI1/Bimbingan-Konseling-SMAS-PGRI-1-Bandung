<?php
// includes/functions.php - Full Functions

// ============================================
// DASHBOARD FUNCTIONS
// ============================================

function getTotalSiswa() {
    $result = query("SELECT COUNT(*) as total FROM siswa");
    return $result->fetch_assoc()['total'];
}

function getTotalGuru() {
    $result = query("SELECT COUNT(*) as total FROM users WHERE role != 'admin'");
    return $result->fetch_assoc()['total'];
}

function getTotalKelas() {
    $result = query("SELECT COUNT(*) as total FROM kelas");
    return $result->fetch_assoc()['total'];
}

function getTotalPelanggaran() {
    $result = query("SELECT COUNT(*) as total FROM pelanggaran");
    return $result->fetch_assoc()['total'];
}

function getTotalPrestasi() {
    $conn = getDB();
    $result = $conn->query("SELECT COUNT(*) as total FROM prestasi_siswa");
    if ($result) {
        return (int)$result->fetch_assoc()['total'];
    }
    return 0;
}

function getTotalPoinPrestasi() {
    $conn = getDB();
    $result = $conn->query("SELECT SUM(poin_prestasi) as total FROM prestasi_siswa");
    if ($result) {
        return (int)$result->fetch_assoc()['total'];
    }
    return 0;
}

function getPrestasiBySiswa($siswa_id) {
    $query = "SELECT * FROM prestasi_siswa WHERE siswa_id = $siswa_id ORDER BY tahun DESC, created_at DESC";
    return getRows($query);
}

function getTotalPoinPrestasiBySiswa($siswa_id) {
    $result = query("SELECT SUM(poin_prestasi) as total FROM prestasi_siswa WHERE siswa_id = $siswa_id");
    if ($result) {
        return (int)$result->fetch_assoc()['total'];
    }
    return 0;
}

function getPoinSiswaSetelahPrestasi($siswa_id) {
    $result = query("SELECT poin FROM siswa WHERE id = $siswa_id");
    if ($result) {
        return (int)$result->fetch_assoc()['poin'];
    }
    return 0;
}

function getPelanggaranBulanIni() {
    $bulan = date('Y-m');
    $result = query("SELECT COUNT(*) as total FROM pelanggaran WHERE DATE_FORMAT(tanggal_pelanggaran, '%Y-%m') = '$bulan'");
    return $result->fetch_assoc()['total'];
}

function getPelanggaranHariIni() {
    $hari = date('Y-m-d');
    $result = query("SELECT COUNT(*) as total FROM pelanggaran WHERE tanggal_pelanggaran = '$hari'");
    return $result->fetch_assoc()['total'];
}

function getSiswaPoinTertinggi() {
    $query = "SELECT s.id, s.nama_siswa, s.poin, k.nama_kelas 
              FROM siswa s 
              LEFT JOIN kelas k ON s.kelas_id = k.id 
              ORDER BY s.poin DESC 
              LIMIT 5";
    return getRows($query);
}

function getGrafikPelanggaran() {
    $tahun = date('Y');
    $data = [];
    for ($i = 1; $i <= 12; $i++) {
        $query = "SELECT COUNT(*) as total FROM pelanggaran 
                  WHERE MONTH(tanggal_pelanggaran) = $i 
                  AND YEAR(tanggal_pelanggaran) = $tahun";
        $result = query($query);
        $data[] = (int)$result->fetch_assoc()['total'];
    }
    return $data;
}

function getPelanggaranTerbaru($limit = 5) {
    $query = "SELECT p.*, s.nama_siswa, s.nisn, jp.nama_pelanggaran, jp.poin as poin_diperoleh
              FROM pelanggaran p
              LEFT JOIN siswa s ON p.siswa_id = s.id
              LEFT JOIN jenis_pelanggaran jp ON p.jenis_pelanggaran_id = jp.id
              ORDER BY p.created_at DESC
              LIMIT $limit";
    return getRows($query);
}

function getStatistikKelas() {
    $query = "SELECT k.id, k.nama_kelas, COUNT(s.id) as jumlah
              FROM kelas k
              LEFT JOIN siswa s ON s.kelas_id = k.id
              GROUP BY k.id
              ORDER BY k.nama_kelas";
    return getRows($query);
}

// ============================================
// KELAS FUNCTIONS
// ============================================

function getKelas() {
    $query = "SELECT k.*, u.nama_lengkap as wali_kelas, 
              (SELECT COUNT(*) FROM siswa WHERE kelas_id = k.id) as jumlah_siswa
              FROM kelas k 
              LEFT JOIN users u ON k.wali_kelas_id = u.id 
              ORDER BY k.nama_kelas";
    return getRows($query);
}

function getKelasById($id) {
    $query = "SELECT * FROM kelas WHERE id = $id";
    return getRow($query);
}

function getKelasOptions() {
    $query = "SELECT id, nama_kelas FROM kelas ORDER BY nama_kelas";
    return getRows($query);
}

function getJumlahSiswaKelas($kelas_id) {
    $result = query("SELECT COUNT(*) as total FROM siswa WHERE kelas_id = $kelas_id");
    return $result->fetch_assoc()['total'];
}

function getDetailSiswaByKelas($kelas_id) {
    $query = "SELECT s.*, k.nama_kelas 
              FROM siswa s 
              LEFT JOIN kelas k ON s.kelas_id = k.id 
              WHERE s.kelas_id = $kelas_id 
              ORDER BY s.nama_siswa";
    return getRows($query);
}

function naikkanKelas($kelas_id) {
    $kelas = getKelasById($kelas_id);
    if (!$kelas) return false;
    
    $tingkat_map = ['X' => 1, 'XI' => 2, 'XII' => 3];
    $tingkat_reverse = [1 => 'X', 2 => 'XI', 3 => 'XII'];
    
    $current_level = $tingkat_map[$kelas['tingkat']] ?? 0;
    $new_level = $current_level + 1;
    
    if ($new_level > 3) {
        return false;
    }
    
    $new_tingkat = $tingkat_reverse[$new_level];
    $jurusan = $kelas['jurusan'];
    
    $data = [
        'nama_kelas' => $new_tingkat . ' ' . $jurusan,
        'tingkat' => $new_tingkat,
        'jurusan' => $jurusan,
        'tahun_ajaran' => date('Y') . '/' . (date('Y') + 1)
    ];
    
    $new_id = insert('kelas', $data);
    if ($new_id) {
        query("UPDATE siswa SET kelas_id = $new_id WHERE kelas_id = $kelas_id");
        return true;
    }
    return false;
}

function deleteKelas($id) {
    $count = getJumlahSiswaKelas($id);
    if ($count > 0) {
        return false;
    }
    return delete('kelas', "id = $id");
}

// ============================================
// SISWA FUNCTIONS
// ============================================

function getSiswa() {
    $query = "SELECT s.*, k.nama_kelas 
              FROM siswa s 
              LEFT JOIN kelas k ON s.kelas_id = k.id 
              ORDER BY s.nama_siswa";
    return getRows($query);
}

function getSiswaById($id) {
    $query = "SELECT s.*, k.nama_kelas 
              FROM siswa s 
              LEFT JOIN kelas k ON s.kelas_id = k.id 
              WHERE s.id = $id";
    return getRow($query);
}

function getSiswaByKelas($kelas_id) {
    $query = "SELECT * FROM siswa WHERE kelas_id = $kelas_id ORDER BY nama_siswa";
    return getRows($query);
}

function getSiswaByNISN($nisn) {
    $query = "SELECT * FROM siswa WHERE nisn = '$nisn'";
    return getRow($query);
}

function searchSiswa($keyword) {
    $keyword = sanitize($keyword);
    $query = "SELECT s.*, k.nama_kelas 
              FROM siswa s 
              LEFT JOIN kelas k ON s.kelas_id = k.id 
              WHERE s.nama_siswa LIKE '%$keyword%' 
              OR s.nisn LIKE '%$keyword%' 
              ORDER BY s.nama_siswa";
    return getRows($query);
}

function tambahSiswa($data) {
    return insert('siswa', $data);
}

function updateSiswa($data, $id) {
    return update('siswa', $data, "id = $id");
}

function hapusSiswa($id) {
    $result = query("SELECT COUNT(*) as total FROM pelanggaran WHERE siswa_id = $id");
    $count = $result->fetch_assoc()['total'];
    if ($count > 0) {
        return false;
    }
    return delete('siswa', "id = $id");
}

function importSiswaCSV($file) {
    $handle = fopen($file['tmp_name'], 'r');
    if (!$handle) {
        return ['success' => 0, 'failed' => 0, 'error' => 'Gagal membuka file'];
    }
    
    $success = 0;
    $failed = 0;
    $row_number = 0;
    $errors = [];
    
    while (($row = fgetcsv($handle, 0, ';')) !== FALSE) {
        $row_number++;
        
        if (empty($row) || (count($row) == 1 && empty(trim($row[0])))) {
            continue;
        }
        
        if ($row_number == 1) {
            $first_col = strtolower(trim($row[0] ?? ''));
            if ($first_col == 'nisn' || $first_col == 'nisn;' || $first_col == 'nisn') {
                continue;
            }
        }
        
        if (count($row) < 8) {
            $failed++;
            $errors[] = "Baris $row_number: Jumlah kolom kurang dari 8";
            continue;
        }
        
        $data = [
            'nisn' => isset($row[0]) ? trim($row[0]) : '',
            'nama_siswa' => isset($row[1]) ? trim($row[1]) : '',
            'kelas_id' => isset($row[2]) && !empty(trim($row[2])) ? (int)trim($row[2]) : null,
            'jenis_kelamin' => isset($row[3]) ? strtoupper(trim($row[3])) : 'L',
            'tempat_lahir' => isset($row[4]) ? trim($row[4]) : '',
            'tanggal_lahir' => isset($row[5]) && !empty(trim($row[5])) ? trim($row[5]) : null,
            'alamat' => isset($row[6]) ? trim($row[6]) : '',
            'no_hp' => isset($row[7]) ? trim($row[7]) : ''
        ];
        
        if (empty($data['nisn']) || empty($data['nama_siswa'])) {
            $failed++;
            $errors[] = "Baris $row_number: NISN atau Nama Siswa kosong";
            continue;
        }
        
        if (!in_array($data['jenis_kelamin'], ['L', 'P'])) {
            $data['jenis_kelamin'] = 'L';
        }
        
        $conn = getDB();
        $check = query("SELECT id FROM siswa WHERE nisn = '" . $conn->real_escape_string($data['nisn']) . "'");
        if ($check->num_rows > 0) {
            $failed++;
            $errors[] = "Baris $row_number: NISN " . $data['nisn'] . " sudah terdaftar";
            continue;
        }
        
        if (!empty($data['kelas_id'])) {
            $cek_kelas = query("SELECT id FROM kelas WHERE id = " . $data['kelas_id']);
            if ($cek_kelas->num_rows == 0) {
                $failed++;
                $errors[] = "Baris $row_number: Kelas ID " . $data['kelas_id'] . " tidak ditemukan";
                continue;
            }
        }
        
        if (insert('siswa', $data)) {
            $success++;
        } else {
            $failed++;
            $errors[] = "Baris $row_number: Gagal menyimpan data";
        }
    }
    fclose($handle);
    
    return [
        'success' => $success, 
        'failed' => $failed,
        'errors' => $errors
    ];
}

// ============================================
// GURU FUNCTIONS
// ============================================

function getGuru() {
    $query = "SELECT * FROM users WHERE role != 'admin' ORDER BY nama_lengkap";
    return getRows($query);
}

function getGuruById($id) {
    $query = "SELECT * FROM users WHERE id = $id";
    return getRow($query);
}

function getGuruPiket() {
    $query = "SELECT * FROM users WHERE is_piket = 1 ORDER BY nama_lengkap";
    return getRows($query);
}

function getGuruBK() {
    $query = "SELECT * FROM users WHERE is_bk = 1 ORDER BY nama_lengkap";
    return getRows($query);
}

function getWaliKelas() {
    $query = "SELECT * FROM users WHERE is_walikelas = 1 ORDER BY nama_lengkap";
    return getRows($query);
}

function getGuruByRole($role) {
    $query = "SELECT * FROM users WHERE role = '$role' ORDER BY nama_lengkap";
    return getRows($query);
}

function tambahGuru($data) {
    if (isset($data['password'])) {
        $data['password'] = md5($data['password']);
    }
    
    if (isset($data['role']) && $data['role'] == 'walikelas') {
        $data['is_walikelas'] = 1;
    }
    
    return insert('users', $data);
}

function updateGuru($data, $id) {
    if (isset($data['password']) && !empty($data['password'])) {
        $data['password'] = md5($data['password']);
    } else {
        unset($data['password']);
    }
    
    if (isset($data['role']) && $data['role'] == 'walikelas') {
        $data['is_walikelas'] = 1;
    }
    
    return update('users', $data, "id = $id");
}

function resetPasswordGuru($id) {
    $new_password = generateRandomPassword(8);
    $hashed = md5($new_password);
    if (update('users', ['password' => $hashed], "id = $id")) {
        return $new_password;
    }
    return false;
}

function hapusGuru($id) {
    return delete('users', "id = $id");
}

// ============================================
// JENIS PELANGGARAN FUNCTIONS
// ============================================

function getJenisPelanggaran() {
    $query = "SELECT * FROM jenis_pelanggaran WHERE is_active = 1 ORDER BY poin DESC";
    return getRows($query);
}

function getJenisPelanggaranAll() {
    $query = "SELECT * FROM jenis_pelanggaran ORDER BY poin DESC";
    return getRows($query);
}

function getJenisPelanggaranById($id) {
    $query = "SELECT * FROM jenis_pelanggaran WHERE id = $id";
    return getRow($query);
}

function getJenisPelanggaranByKategori($kategori) {
    $query = "SELECT * FROM jenis_pelanggaran WHERE kategori = '$kategori' AND is_active = 1 ORDER BY poin DESC";
    return getRows($query);
}

function tambahJenisPelanggaran($data) {
    return insert('jenis_pelanggaran', $data);
}

function updateJenisPelanggaran($data, $id) {
    return update('jenis_pelanggaran', $data, "id = $id");
}

function hapusJenisPelanggaran($id) {
    $result = query("SELECT COUNT(*) as total FROM pelanggaran WHERE jenis_pelanggaran_id = $id");
    $count = $result->fetch_assoc()['total'];
    if ($count > 0) {
        return false;
    }
    return delete('jenis_pelanggaran', "id = $id");
}

function toggleJenisPelanggaran($id) {
    $jp = getJenisPelanggaranById($id);
    if ($jp) {
        $new_status = $jp['is_active'] ? 0 : 1;
        return update('jenis_pelanggaran', ['is_active' => $new_status], "id = $id");
    }
    return false;
}

// ============================================
// PELANGGARAN FUNCTIONS
// ============================================

function getPelanggaran() {
    $query = "SELECT p.*, s.nama_siswa, s.nisn, jp.nama_pelanggaran, jp.poin, u.nama_lengkap as guru
              FROM pelanggaran p
              LEFT JOIN siswa s ON p.siswa_id = s.id
              LEFT JOIN jenis_pelanggaran jp ON p.jenis_pelanggaran_id = jp.id
              LEFT JOIN users u ON p.guru_id = u.id
              ORDER BY p.tanggal_pelanggaran DESC, p.created_at DESC";
    return getRows($query);
}

function getPelanggaranById($id) {
    $query = "SELECT p.*, s.nama_siswa, s.nisn, jp.nama_pelanggaran, jp.poin, u.nama_lengkap as guru
              FROM pelanggaran p
              LEFT JOIN siswa s ON p.siswa_id = s.id
              LEFT JOIN jenis_pelanggaran jp ON p.jenis_pelanggaran_id = jp.id
              LEFT JOIN users u ON p.guru_id = u.id
              WHERE p.id = $id";
    return getRow($query);
}

function getPelanggaranBySiswa($siswa_id) {
    $query = "SELECT p.*, jp.nama_pelanggaran, jp.poin, u.nama_lengkap as guru
              FROM pelanggaran p
              LEFT JOIN jenis_pelanggaran jp ON p.jenis_pelanggaran_id = jp.id
              LEFT JOIN users u ON p.guru_id = u.id
              WHERE p.siswa_id = $siswa_id
              ORDER BY p.tanggal_pelanggaran DESC";
    return getRows($query);
}

function getPelanggaranByDate($start_date, $end_date) {
    $query = "SELECT p.*, s.nama_siswa, s.nisn, jp.nama_pelanggaran, jp.poin, u.nama_lengkap as guru
              FROM pelanggaran p
              LEFT JOIN siswa s ON p.siswa_id = s.id
              LEFT JOIN jenis_pelanggaran jp ON p.jenis_pelanggaran_id = jp.id
              LEFT JOIN users u ON p.guru_id = u.id
              WHERE p.tanggal_pelanggaran BETWEEN '$start_date' AND '$end_date'
              ORDER BY p.tanggal_pelanggaran DESC";
    return getRows($query);
}

function getPelanggaranByKelas($kelas_id) {
    $query = "SELECT p.*, s.nama_siswa, s.nisn, jp.nama_pelanggaran, jp.poin, u.nama_lengkap as guru
              FROM pelanggaran p
              LEFT JOIN siswa s ON p.siswa_id = s.id
              LEFT JOIN jenis_pelanggaran jp ON p.jenis_pelanggaran_id = jp.id
              LEFT JOIN users u ON p.guru_id = u.id
              WHERE s.kelas_id = $kelas_id
              ORDER BY p.tanggal_pelanggaran DESC";
    return getRows($query);
}

function getTotalPoinSiswa($siswa_id) {
    $result = query("SELECT SUM(poin_diperoleh) as total FROM pelanggaran WHERE siswa_id = $siswa_id AND status = 'disetujui'");
    return (int)$result->fetch_assoc()['total'];
}

function tambahPelanggaran($data) {
    $jp = getJenisPelanggaranById($data['jenis_pelanggaran_id']);
    if ($jp) {
        $data['poin_diperoleh'] = $jp['poin'];
    }
    
    $result = insert('pelanggaran', $data);
    
    if ($result) {
        $total_poin = getTotalPoinSiswa($data['siswa_id']);
        update('siswa', ['poin' => $total_poin], "id = " . $data['siswa_id']);
        return $result;
    }
    return false;
}

function updatePelanggaran($data, $id) {
    $result = update('pelanggaran', $data, "id = $id");
    if ($result) {
        $p = getPelanggaranById($id);
        if ($p) {
            $total_poin = getTotalPoinSiswa($p['siswa_id']);
            update('siswa', ['poin' => $total_poin], "id = " . $p['siswa_id']);
        }
        return true;
    }
    return false;
}

function hapusPelanggaran($id) {
    $p = getPelanggaranById($id);
    if ($p) {
        $result = delete('pelanggaran', "id = $id");
        if ($result) {
            $total_poin = getTotalPoinSiswa($p['siswa_id']);
            update('siswa', ['poin' => $total_poin], "id = " . $p['siswa_id']);
            return true;
        }
    }
    return false;
}

function approvePelanggaran($id) {
    return update('pelanggaran', ['status' => 'disetujui'], "id = $id");
}

function rejectPelanggaran($id) {
    return update('pelanggaran', ['status' => 'ditolak'], "id = $id");
}

// ============================================
// PENGATURAN FUNCTIONS
// ============================================

function getPengaturan() {
    $result = query("SELECT * FROM pengaturan LIMIT 1");
    if ($result->num_rows == 0) {
        insert('pengaturan', [
            'nama_sekolah' => 'SMA Negeri 1 Tatib',
            'alamat' => 'Jl. Pendidikan No. 1',
            'no_telp' => '021-1234567',
            'email' => 'info@smantatib.sch.id',
            'batas_poin_peringatan' => 100,
            'batas_poin_pemanggilan' => 200,
            'batas_poin_do' => 300
        ]);
        return getPengaturan();
    }
    return $result->fetch_assoc();
}

function updatePengaturan($data) {
    $set = [];
    $conn = getDB();
    foreach ($data as $key => $value) {
        $set[] = "$key = '" . $conn->real_escape_string($value) . "'";
    }
    $sql = "UPDATE pengaturan SET " . implode(', ', $set) . " WHERE id = 1";
    return query($sql);
}

function updateLogo($file) {
    $pengaturan = getPengaturan();
    if ($pengaturan && !empty($pengaturan['logo'])) {
        $old_file = APP_PATH . 'uploads/logo/' . $pengaturan['logo'];
        if (file_exists($old_file)) {
            unlink($old_file);
        }
    }
    
    $new_file = uploadFile($file, APP_PATH . 'uploads/logo/', ['jpg', 'jpeg', 'png', 'gif', 'svg']);
    if ($new_file) {
        updatePengaturan(['logo' => $new_file]);
        return true;
    }
    return false;
}

function getBatasPoin() {
    $pengaturan = getPengaturan();
    return [
        'peringatan' => $pengaturan['batas_poin_peringatan'],
        'pemanggilan' => $pengaturan['batas_poin_pemanggilan'],
        'do' => $pengaturan['batas_poin_do']
    ];
}

// ============================================
// API WHATSAPP FUNCTIONS
// ============================================

function getApiWaConfig() {
    $pengaturan = getPengaturan();
    return [
        'url' => $pengaturan['api_wa_url'] ?? '',
        'token' => $pengaturan['api_wa_token'] ?? ''
    ];
}

function updateApiWa($url, $token) {
    return updatePengaturan([
        'api_wa_url' => $url,
        'api_wa_token' => $token
    ]);
}

function sendWhatsApp($no_hp, $message) {
    if (empty($no_hp) || $no_hp === '0' || $no_hp === '62') {
        return false;
    }

    $no_hp = preg_replace('/[^0-9]/', '', $no_hp);
    if (strlen($no_hp) < 10) {
        return false;
    }

    if (substr($no_hp, 0, 1) === "0") {
        $no_hp = "+62" . substr($no_hp, 1);
    } elseif (substr($no_hp, 0, 2) !== "62") {
        $no_hp = "+62" . $no_hp;
    } elseif (substr($no_hp, 0, 3) !== "+62") {
        $no_hp = "+" . $no_hp;
    }

    $config = getApiWaConfig();
    
    if (!empty($config['url']) && strpos($config['url'], 'sidobe.com') !== false) {
        return sendWhatsAppSidobe($no_hp, $message, $config['token']);
    } else {
        return sendWhatsAppCustom($no_hp, $message, $config);
    }
}

function sendWhatsAppSidobe($no_hp, $message, $secret_key) {
    if (empty($secret_key)) {
        return false;
    }

    $data = ['phone' => $no_hp, 'message' => $message];
    $max_retries = 2;
    $retry_count = 0;
    $response = false;
    
    do {
        $ch = curl_init('https://api.sidobe.com/wa/v1/send-message');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-Secret-Key: ' . $secret_key
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        
        if (file_exists('cacert.pem')) {
            curl_setopt($ch, CURLOPT_CAINFO, 'cacert.pem');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error_msg = curl_error($ch);
        curl_close($ch);
        
        if ($response !== false) {
            break;
        }
        
        $retry_count++;
        if ($retry_count < $max_retries) {
            usleep(500000);
        }
    } while ($retry_count < $max_retries);

    if ($response === false) {
        error_log("Gagal kirim WA Sidobe: " . $error_msg);
        return false;
    }

    $res = json_decode($response, true);
    if ($res && isset($res['is_success']) && $res['is_success']) {
        return true;
    } else {
        $error_info = isset($res['message']) ? $res['message'] : 'Tidak ada info error';
        error_log("Gagal kirim WA Sidobe. HTTP Code: $httpCode, Response: " . $response);
        return false;
    }
}

function sendWhatsAppCustom($no_hp, $message, $config) {
    if (empty($config['url']) || empty($config['token'])) {
        return false;
    }
    
    $data = [
        'phone' => $no_hp,
        'message' => $message,
        'token' => $config['token']
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $config['url']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $httpCode == 200;
}

function sendWhatsAppPelanggaran($siswa, $jenis_pelanggaran, $poin, $tanggal, $keterangan = '') {
    $no_hp = $siswa['no_hp'] ?? '';
    if (empty($no_hp)) {
        return false;
    }
    
    $nama_kelas = $siswa['nama_kelas'] ?? '';

    $pesan = "📋 *PEMBERITAHUAN PELANGGARAN*\n\n";
    $pesan .= "Kepada Yth. Orang Tua/Wali dari:\n";
    $pesan .= "Nama: {$siswa['nama_siswa']}\n";
    $pesan .= "NISN: {$siswa['nisn']}\n";
    if (!empty($nama_kelas)) {
        $pesan .= "Kelas: {$nama_kelas}\n";
    }
    $pesan .= "\nDengan ini kami sampaikan bahwa ananda telah melakukan pelanggaran:\n";
    $pesan .= "----------------------------------------\n";
    $pesan .= "Jenis Pelanggaran: {$jenis_pelanggaran['nama_pelanggaran']}\n";
    $pesan .= "Poin: {$poin}\n";
    $pesan .= "Tanggal: " . formatTanggalIndo($tanggal) . "\n";
    if (!empty($keterangan)) {
        $pesan .= "Keterangan: {$keterangan}\n";
    }
    $pesan .= "----------------------------------------\n\n";
    $pesan .= "Demikian pemberitahuan ini disampaikan, atas perhatiannya terima kasih.\n\n";
    $pesan .= "Salam,\n*" . getPengaturan()['nama_sekolah'] . "*";
    
    return sendWhatsApp($no_hp, $pesan);
}

function sendWhatsAppPeringatanPoin($siswa, $total_poin) {
    $no_hp = $siswa['no_hp'] ?? '';
    if (empty($no_hp)) {
        return false;
    }
    
    $batas = getBatasPoin();
    $nama_kelas = $siswa['nama_kelas'] ?? '';
    
    $pesan = "⚠️ *PERINGATAN POIN*\n\n";
    $pesan .= "Kepada Yth. Orang Tua/Wali dari:\n";
    $pesan .= "Nama: {$siswa['nama_siswa']}\n";
    $pesan .= "NISN: {$siswa['nisn']}\n";
    if (!empty($nama_kelas)) {
        $pesan .= "Kelas: {$nama_kelas}\n";
    }
    $pesan .= "\nTotal poin pelanggaran ananda saat ini: *{$total_poin} poin*\n\n";
    
    if ($total_poin >= $batas['do']) {
        $pesan .= "🚨 *PERHATIAN!* Poin telah mencapai batas DO ({$batas['do']} poin)\n";
        $pesan .= "Mohon segera menghubungi pihak sekolah.\n";
    } elseif ($total_poin >= $batas['pemanggilan']) {
        $pesan .= "📞 Poin telah mencapai batas pemanggilan orang tua ({$batas['pemanggilan']} poin)\n";
        $pesan .= "Mohon bersiap untuk dipanggil ke sekolah.\n";
    } elseif ($total_poin >= $batas['peringatan']) {
        $pesan .= "📝 Poin telah mencapai batas peringatan ({$batas['peringatan']} poin)\n";
        $pesan .= "Mohon perhatikan perilaku ananda.\n";
    }
    
    $pesan .= "\nSalam,\n*" . getPengaturan()['nama_sekolah'] . "*";
    
    return sendWhatsApp($no_hp, $pesan);
}

// ============================================
// BK FUNCTIONS
// ============================================

function countSiswaBermasalah() {
    $batas = getBatasPoin();
    $result = query("SELECT COUNT(*) as total FROM siswa WHERE poin >= " . $batas['peringatan']);
    if ($result) {
        return (int)$result->fetch_assoc()['total'];
    }
    return 0;
}

function countKonselingPending() {
    $conn = getDB();
    $check = $conn->query("SHOW TABLES LIKE 'catatan_konseling'");
    if ($check && $check->num_rows == 0) {
        createTableCatatanKonseling();
        return 0;
    }
    
    $result = query("SELECT COUNT(*) as total FROM catatan_konseling WHERE status = 'pending'");
    if ($result) {
        return (int)$result->fetch_assoc()['total'];
    }
    return 0;
}

function countKonselingSelesai() {
    $conn = getDB();
    $check = $conn->query("SHOW TABLES LIKE 'catatan_konseling'");
    if ($check && $check->num_rows == 0) {
        return 0;
    }
    
    $result = query("SELECT COUNT(*) as total FROM catatan_konseling WHERE status = 'selesai'");
    if ($result) {
        return (int)$result->fetch_assoc()['total'];
    }
    return 0;
}

function getSiswaBermasalah($limit = null) {
    $batas = getBatasPoin();
    $query = "SELECT s.*, k.nama_kelas 
              FROM siswa s 
              LEFT JOIN kelas k ON s.kelas_id = k.id 
              WHERE s.poin >= " . $batas['peringatan'] . " 
              ORDER BY s.poin DESC";
    if ($limit) {
        $query .= " LIMIT $limit";
    }
    return getRows($query);
}

function getCatatanKonseling($siswa_id = null, $limit = null) {
    $conn = getDB();
    $check = $conn->query("SHOW TABLES LIKE 'catatan_konseling'");
    if ($check && $check->num_rows == 0) {
        createTableCatatanKonseling();
        return [];
    }
    
    $query = "SELECT c.*, s.nama_siswa, s.nisn, k.nama_kelas 
              FROM catatan_konseling c
              LEFT JOIN siswa s ON c.siswa_id = s.id
              LEFT JOIN kelas k ON s.kelas_id = k.id";
    if ($siswa_id) {
        $query .= " WHERE c.siswa_id = $siswa_id";
    }
    $query .= " ORDER BY c.tanggal DESC, c.created_at DESC";
    if ($limit) {
        $query .= " LIMIT $limit";
    }
    return getRows($query);
}

function getCatatanKonselingById($id) {
    $conn = getDB();
    $check = $conn->query("SHOW TABLES LIKE 'catatan_konseling'");
    if ($check && $check->num_rows == 0) {
        createTableCatatanKonseling();
        return null;
    }
    
    $query = "SELECT c.*, s.nama_siswa, s.nisn, k.nama_kelas 
              FROM catatan_konseling c
              LEFT JOIN siswa s ON c.siswa_id = s.id
              LEFT JOIN kelas k ON s.kelas_id = k.id
              WHERE c.id = $id";
    return getRow($query);
}

function tambahCatatanKonseling($data) {
    $conn = getDB();
    $check = $conn->query("SHOW TABLES LIKE 'catatan_konseling'");
    if ($check && $check->num_rows == 0) {
        createTableCatatanKonseling();
    }
    return insert('catatan_konseling', $data);
}

function updateCatatanKonseling($data, $id) {
    $conn = getDB();
    $check = $conn->query("SHOW TABLES LIKE 'catatan_konseling'");
    if ($check && $check->num_rows == 0) {
        createTableCatatanKonseling();
        return false;
    }
    return update('catatan_konseling', $data, "id = $id");
}

function hapusCatatanKonseling($id) {
    $conn = getDB();
    $check = $conn->query("SHOW TABLES LIKE 'catatan_konseling'");
    if ($check && $check->num_rows == 0) {
        return false;
    }
    return delete('catatan_konseling', "id = $id");
}

function getRiwayatPelanggaranBK($siswa_id = null, $limit = null) {
    $query = "SELECT p.*, s.nama_siswa, s.nisn, k.nama_kelas, 
              jp.nama_pelanggaran, jp.poin, u.nama_lengkap as guru
              FROM pelanggaran p
              LEFT JOIN siswa s ON p.siswa_id = s.id
              LEFT JOIN kelas k ON s.kelas_id = k.id
              LEFT JOIN jenis_pelanggaran jp ON p.jenis_pelanggaran_id = jp.id
              LEFT JOIN users u ON p.guru_id = u.id";
    if ($siswa_id) {
        $query .= " WHERE p.siswa_id = $siswa_id";
    }
    $query .= " ORDER BY p.tanggal_pelanggaran DESC, p.created_at DESC";
    if ($limit) {
        $query .= " LIMIT $limit";
    }
    return getRows($query);
}

function getMonitoringPoin($kelas_id = null) {
    $query = "SELECT s.*, k.nama_kelas,
              (SELECT COUNT(*) FROM pelanggaran WHERE siswa_id = s.id AND status = 'disetujui') as total_pelanggaran,
              (SELECT SUM(poin_diperoleh) FROM pelanggaran WHERE siswa_id = s.id AND status = 'disetujui') as total_poin
              FROM siswa s
              LEFT JOIN kelas k ON s.kelas_id = k.id";
    if ($kelas_id) {
        $query .= " WHERE s.kelas_id = $kelas_id";
    }
    $query .= " ORDER BY s.poin DESC";
    return getRows($query);
}

function createTableCatatanKonseling() {
    $conn = getDB();
    $sql = "CREATE TABLE IF NOT EXISTS `catatan_konseling` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `siswa_id` int(11) NOT NULL,
        `bk_id` int(11) DEFAULT NULL,
        `tanggal` date NOT NULL,
        `jenis_konseling` enum('individu','kelompok','kelas') DEFAULT 'individu',
        `topik` varchar(255) DEFAULT NULL,
        `catatan` text NOT NULL,
        `tindak_lanjut` text,
        `status` enum('pending','proses','selesai') DEFAULT 'pending',
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `siswa_id` (`siswa_id`),
        KEY `bk_id` (`bk_id`),
        CONSTRAINT `catatan_konseling_ibfk_1` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE,
        CONSTRAINT `catatan_konseling_ibfk_2` FOREIGN KEY (`bk_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;";
    
    $conn->query($sql);
    
    $check = $conn->query("SHOW TABLES LIKE 'catatan_konseling'");
    if ($check && $check->num_rows > 0) {
        return true;
    }
    return false;
}

// ============================================
// FUNGSI TAMBAHAN UNTUK BK CHART
// ============================================

function getPelanggaranBulan($bulan) {
    $tahun = date('Y');
    $result = query("SELECT COUNT(*) as total FROM pelanggaran 
                     WHERE MONTH(tanggal_pelanggaran) = $bulan 
                     AND YEAR(tanggal_pelanggaran) = $tahun");
    if ($result) {
        return (int)$result->fetch_assoc()['total'];
    }
    return 0;
}

function getPelanggaranPerBulan() {
    $data = [];
    for ($i = 1; $i <= 12; $i++) {
        $data[] = getPelanggaranBulan($i);
    }
    return $data;
}

function getKategoriPelanggaran() {
    $kategori = [
        'ringan' => 0,
        'sedang' => 0,
        'berat' => 0
    ];
    
    $result = query("SELECT jp.kategori, COUNT(*) as total 
                     FROM pelanggaran p 
                     LEFT JOIN jenis_pelanggaran jp ON p.jenis_pelanggaran_id = jp.id 
                     GROUP BY jp.kategori");
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $kat = $row['kategori'] ?? 'ringan';
            if (isset($kategori[$kat])) {
                $kategori[$kat] = (int)$row['total'];
            }
        }
    }
    return $kategori;
}

function getStatusKonseling() {
    $status = [
        'pending' => 0,
        'proses' => 0,
        'selesai' => 0
    ];
    
    $conn = getDB();
    $check = $conn->query("SHOW TABLES LIKE 'catatan_konseling'");
    if ($check && $check->num_rows > 0) {
        $result = query("SELECT status, COUNT(*) as total FROM catatan_konseling GROUP BY status");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $st = $row['status'] ?? 'pending';
                if (isset($status[$st])) {
                    $status[$st] = (int)$row['total'];
                }
            }
        }
    }
    return $status;
}

// ============================================
// FUNGSI SURAT MENEG - DIPERBAIKI TOTAL
// ============================================

function getPengaturanSurat() {
    $conn = getDB();
    
    // Cek apakah tabel pengaturan_surat ada
    $check = $conn->query("SHOW TABLES LIKE 'pengaturan_surat'");
    if ($check && $check->num_rows == 0) {
        createTablePengaturanSurat();
    }
    
    // CEK DAN TAMBAHKAN SEMUA KOLOM YANG HILANG
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
    
    $result = query("SELECT * FROM pengaturan_surat LIMIT 1");
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    // Jika tidak ada data, insert default
    $pengaturan = getPengaturan();
    $sql = "INSERT INTO `pengaturan_surat` (
        `nama_sekolah`, `alamat_sekolah`, `no_telp_sekolah`, `email_sekolah`, `website_sekolah`,
        `kementrian`, `kepala_sekolah`, `nip_kepala_sekolah`, `nomor_surat`,
        `pembukaan_teguran`, `isi_teguran`, `penutup_teguran`,
        `pembukaan_pemanggilan`, `isi_pemanggilan`, `penutup_pemanggilan`
    ) VALUES (
        '" . $conn->real_escape_string($pengaturan['nama_sekolah']) . "',
        '" . $conn->real_escape_string($pengaturan['alamat']) . "',
        '" . $conn->real_escape_string($pengaturan['no_telp']) . "',
        '" . $conn->real_escape_string($pengaturan['email']) . "',
        '',
        'Kementerian Pendidikan dan Kebudayaan',
        'Kepala Sekolah',
        '',
        '001/TEG/2024',
        'Kepada Yth. Bapak/Ibu Orang Tua/Wali dari siswa {nama_siswa} di tempat.',
        'Berdasarkan data pelanggaran yang tercatat di sekolah, {nama_siswa} dengan NISN {nisn} dari kelas {kelas} telah melakukan pelanggaran dengan total poin {poin}.\n\nKami menghimbau Bapak/Ibu untuk memberikan perhatian dan pembinaan kepada ananda agar tidak mengulangi pelanggaran tersebut.',
        'Demikian surat teguran ini kami sampaikan. Atas perhatian dan kerjasama Bapak/Ibu, kami ucapkan terima kasih.',
        'Kepada Yth. Bapak/Ibu Orang Tua/Wali dari siswa {nama_siswa} di tempat.',
        'Dengan ini kami mengundang Bapak/Ibu untuk hadir di sekolah pada hari {tanggal_pemanggilan} pukul {jam_pemanggilan} untuk membahas masalah pelanggaran yang dilakukan oleh {nama_siswa} dengan NISN {nisn} dari kelas {kelas}.\n\nKehadiran Bapak/Ibu sangat kami harapkan untuk menyelesaikan permasalahan ini.',
        'Demikian surat pemanggilan ini kami sampaikan. Atas perhatian dan kehadiran Bapak/Ibu, kami ucapkan terima kasih.'
    )";
    $conn->query($sql);
    
    return getPengaturanSurat();
}

// ============================================
// FUNGSI UPDATE PENGATURAN SURAT - DIPERBAIKI
// ============================================

function updatePengaturanSurat($data) {
    $conn = getDB();
    
    // Cek apakah ada data dengan id=1
    $check = $conn->query("SELECT id FROM pengaturan_surat WHERE id = 1");
    
    // Buat query UPDATE langsung
    $sets = array();
    foreach ($data as $key => $value) {
        if ($key != 'id') {
            $sets[] = "`$key` = '" . $conn->real_escape_string($value) . "'";
        }
    }
    
    if (empty($sets)) {
        return true;
    }
    
    if ($check && $check->num_rows > 0) {
        // UPDATE
        $sql = "UPDATE pengaturan_surat SET " . implode(', ', $sets) . " WHERE id = 1";
    } else {
        // INSERT
        $sql = "INSERT INTO pengaturan_surat SET " . implode(', ', $sets);
    }
    
    // Eksekusi query
    $result = $conn->query($sql);
    
    return $result;
}

function createTablePengaturanSurat() {
    $conn = getDB();
    $sql = "CREATE TABLE IF NOT EXISTS `pengaturan_surat` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;";
    $conn->query($sql);
}

function getSuratMeneg() {
    $conn = getDB();
    $check = $conn->query("SHOW TABLES LIKE 'surat_meneg'");
    if ($check && $check->num_rows == 0) {
        createTableSuratMeneg();
        return [];
    }
    
    $query = "SELECT s.*, siswa.nama_siswa, siswa.nisn, kelas.nama_kelas 
              FROM surat_meneg s
              LEFT JOIN siswa ON s.siswa_id = siswa.id
              LEFT JOIN kelas ON siswa.kelas_id = kelas.id
              ORDER BY s.created_at DESC";
    return getRows($query);
}

function getSuratMenegById($id) {
    $conn = getDB();
    $check = $conn->query("SHOW TABLES LIKE 'surat_meneg'");
    if ($check && $check->num_rows == 0) {
        createTableSuratMeneg();
        return null;
    }
    
    $query = "SELECT s.*, siswa.nama_siswa, siswa.nisn, kelas.nama_kelas
              FROM surat_meneg s
              LEFT JOIN siswa ON s.siswa_id = siswa.id
              LEFT JOIN kelas ON siswa.kelas_id = kelas.id
              WHERE s.id = $id";
    return getRow($query);
}

function tambahSuratMeneg($data) {
    $conn = getDB();
    $check = $conn->query("SHOW TABLES LIKE 'surat_meneg'");
    if ($check && $check->num_rows == 0) {
        createTableSuratMeneg();
    }
    return insert('surat_meneg', $data);
}

function updateSuratMeneg($data, $id) {
    return update('surat_meneg', $data, "id = $id");
}

function hapusSuratMeneg($id) {
    return delete('surat_meneg', "id = $id");
}

function createTableSuratMeneg() {
    $conn = getDB();
    $sql = "CREATE TABLE IF NOT EXISTS `surat_meneg` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `siswa_id` int(11) NOT NULL,
        `jenis_surat` enum('teguran','pemanggilan','peringatan','do') DEFAULT 'teguran',
        `nomor_surat` varchar(50) NOT NULL,
        `tanggal_surat` date NOT NULL,
        `isi_surat` text NOT NULL,
        `status` enum('draft','terkirim','selesai') DEFAULT 'draft',
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `siswa_id` (`siswa_id`),
        CONSTRAINT `surat_meneg_ibfk_1` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;";
    $conn->query($sql);
}

// ============================================
// SISTEM FUNCTIONS
// ============================================

function ubahPassword($user_id, $old_password, $new_password) {
    $user = getGuruById($user_id);
    if (!$user || $user['password'] != md5($old_password)) {
        return false;
    }
    return update('users', ['password' => md5($new_password)], "id = $user_id");
}

function resetPassword($user_id) {
    $new_password = generateRandomPassword(8);
    $hashed = md5($new_password);
    if (update('users', ['password' => $hashed], "id = $user_id")) {
        return $new_password;
    }
    return false;
}

function backupDatabase() {
    $conn = getDB();
    $tables = [];
    $result = $conn->query("SHOW TABLES");
    while ($row = $result->fetch_array()) {
        $tables[] = $row[0];
    }
    
    $backup = "-- Database Backup: " . date('Y-m-d H:i:s') . "\n\n";
    
    foreach ($tables as $table) {
        $backup .= "-- Table: $table\n";
        $result = $conn->query("SELECT * FROM $table");
        $num_fields = $result->field_count;
        
        $backup .= "DROP TABLE IF EXISTS $table;\n";
        $create = $conn->query("SHOW CREATE TABLE $table")->fetch_assoc();
        $backup .= $create['Create Table'] . ";\n\n";
        
        while ($row = $result->fetch_row()) {
            $backup .= "INSERT INTO $table VALUES(";
            for ($i = 0; $i < $num_fields; $i++) {
                $row[$i] = addslashes($row[$i]);
                $backup .= $i == 0 ? "'" . $row[$i] . "'" : ", '" . $row[$i] . "'";
            }
            $backup .= ");\n";
        }
        $backup .= "\n\n";
    }
    
    $filename = APP_PATH . 'exports/backup_' . date('Y-m-d_H-i-s') . '.sql';
    file_put_contents($filename, $backup);
    return $filename;
}

function getSystemInfo() {
    return [
        'php_version' => phpversion(),
        'mysql_version' => getDB()->server_info,
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'server_name' => $_SERVER['SERVER_NAME'] ?? 'Unknown',
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time'),
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size')
    ];
}

// ============================================
// AUTH FUNCTIONS (HANYA login DAN logout)
// ============================================

function login($username, $password) {
    $username = sanitize($username);
    $password = md5($password);
    $query = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
    $result = query($query);
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['nama'] = $user['nama_lengkap'];
        return true;
    }
    return false;
}

function logout() {
    session_destroy();
    return true;
}

// ============================================
// HELPER FUNCTIONS
// ============================================

function formatTanggal($tgl, $format = 'd-m-Y') {
    if (empty($tgl) || $tgl == '0000-00-00') return '-';
    return date($format, strtotime($tgl));
}

function formatTanggalIndo($tgl) {
    if (empty($tgl) || $tgl == '0000-00-00') return '-';
    $bulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    $date = date_create($tgl);
    return date_format($date, 'd ') . $bulan[(int)date_format($date, 'm')] . date_format($date, ' Y');
}

function formatTanggalWaktu($tgl) {
    if (empty($tgl)) return '-';
    return date('d-m-Y H:i', strtotime($tgl));
}

function formatRupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

function getStatusBadge($status) {
    $badges = [
        'pending' => 'warning',
        'disetujui' => 'success',
        'ditolak' => 'danger'
    ];
    return $badges[$status] ?? 'secondary';
}

function getStatusLabel($status) {
    $labels = [
        'pending' => 'Pending',
        'disetujui' => 'Disetujui',
        'ditolak' => 'Ditolak'
    ];
    return $labels[$status] ?? $status;
}

function getKategoriBadge($kategori) {
    $badges = [
        'ringan' => 'success',
        'sedang' => 'warning',
        'berat' => 'danger'
    ];
    return $badges[$kategori] ?? 'secondary';
}

function getKategoriLabel($kategori) {
    $labels = [
        'ringan' => 'Ringan',
        'sedang' => 'Sedang',
        'berat' => 'Berat'
    ];
    return $labels[$kategori] ?? $kategori;
}

function uploadFile($file, $target_dir, $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'pdf']) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_ext, $allowed_types)) {
        return false;
    }
    
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $new_filename = uniqid() . '.' . $file_ext;
    $target_path = $target_dir . $new_filename;
    
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        return $new_filename;
    }
    return false;
}

function generateRandomPassword($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $password;
}

function getPoinLevel($poin) {
    $pengaturan = getPengaturan();
    if ($poin >= $pengaturan['batas_poin_do']) {
        return ['level' => 'DO', 'color' => 'danger', 'icon' => 'fa-times-circle'];
    } elseif ($poin >= $pengaturan['batas_poin_pemanggilan']) {
        return ['level' => 'Pemanggilan', 'color' => 'warning', 'icon' => 'fa-phone'];
    } elseif ($poin >= $pengaturan['batas_poin_peringatan']) {
        return ['level' => 'Peringatan', 'color' => 'info', 'icon' => 'fa-exclamation-circle'];
    }
    return ['level' => 'Aman', 'color' => 'success', 'icon' => 'fa-check-circle'];
}

function getPoinProgress($poin) {
    $batas = getBatasPoin();
    $max = $batas['do'];
    if ($poin >= $max) return 100;
    return ($poin / $max) * 100;
}

function getJenisKelaminLabel($jk) {
    return $jk == 'L' ? 'Laki-laki' : 'Perempuan';
}

function getRoleLabel($role) {
    $labels = [
        'admin' => 'Administrator',
        'guru' => 'Guru',
        'bk' => 'BK',
        'walikelas' => 'Wali Kelas'
    ];
    return $labels[$role] ?? $role;
}

function cleanString($str) {
    return htmlspecialchars(strip_tags(trim($str)));
}

function generateSlug($str) {
    $str = strtolower($str);
    $str = preg_replace('/[^a-z0-9-]/', '-', $str);
    $str = preg_replace('/-+/', '-', $str);
    return trim($str, '-');
}

function getYearOptions($start = 2020) {
    $current = date('Y');
    $options = [];
    for ($i = $start; $i <= $current + 1; $i++) {
        $options[] = $i . '/' . ($i + 1);
    }
    return $options;
}

function getMonthOptions() {
    return [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
}

function getBulanIndonesia($month) {
    $months = getMonthOptions();
    return $months[(int)$month] ?? $month;
}

// ============================================
// REPORT FUNCTIONS
// ============================================

function getReportPelanggaran($start_date, $end_date, $kelas_id = null, $siswa_id = null) {
    $where = "p.tanggal_pelanggaran BETWEEN '$start_date' AND '$end_date'";
    if ($kelas_id) {
        $where .= " AND s.kelas_id = $kelas_id";
    }
    if ($siswa_id) {
        $where .= " AND p.siswa_id = $siswa_id";
    }
    
    $query = "SELECT p.*, s.nama_siswa, s.nisn, s.kelas_id, k.nama_kelas, 
              jp.nama_pelanggaran, jp.poin, u.nama_lengkap as guru
              FROM pelanggaran p
              LEFT JOIN siswa s ON p.siswa_id = s.id
              LEFT JOIN kelas k ON s.kelas_id = k.id
              LEFT JOIN jenis_pelanggaran jp ON p.jenis_pelanggaran_id = jp.id
              LEFT JOIN users u ON p.guru_id = u.id
              WHERE $where
              ORDER BY p.tanggal_pelanggaran DESC";
    return getRows($query);
}

function getReportSiswa($kelas_id = null) {
    $where = $kelas_id ? "WHERE s.kelas_id = $kelas_id" : "";
    $query = "SELECT s.*, k.nama_kelas,
              (SELECT COUNT(*) FROM pelanggaran WHERE siswa_id = s.id) as total_pelanggaran,
              (SELECT SUM(poin_diperoleh) FROM pelanggaran WHERE siswa_id = s.id AND status = 'disetujui') as total_poin
              FROM siswa s
              LEFT JOIN kelas k ON s.kelas_id = k.id
              $where
              ORDER BY s.nama_siswa";
    return getRows($query);
}

function getReportKelas() {
    $query = "SELECT k.*,
              COUNT(DISTINCT s.id) as jumlah_siswa,
              COUNT(p.id) as total_pelanggaran
              FROM kelas k
              LEFT JOIN siswa s ON s.kelas_id = k.id
              LEFT JOIN pelanggaran p ON p.siswa_id = s.id
              GROUP BY k.id
              ORDER BY k.nama_kelas";
    return getRows($query);
}

function getExportData($start_date, $end_date, $kelas_id = null) {
    $where = "p.tanggal_pelanggaran BETWEEN '$start_date' AND '$end_date'";
    if ($kelas_id) {
        $where .= " AND s.kelas_id = $kelas_id";
    }
    
    $query = "SELECT s.nisn, s.nama_siswa, k.nama_kelas, 
              jp.nama_pelanggaran, jp.kategori, jp.poin,
              p.tanggal_pelanggaran, p.lokasi, p.keterangan, p.status,
              u.nama_lengkap as guru
              FROM pelanggaran p
              LEFT JOIN siswa s ON p.siswa_id = s.id
              LEFT JOIN kelas k ON s.kelas_id = k.id
              LEFT JOIN jenis_pelanggaran jp ON p.jenis_pelanggaran_id = jp.id
              LEFT JOIN users u ON p.guru_id = u.id
              WHERE $where
              ORDER BY p.tanggal_pelanggaran DESC";
    return getRows($query);
}

// ============================================
// FUNGSI KHUSUS WALI KELAS
// ============================================

/**
 * Mendapatkan data kelas yang diampu oleh wali kelas yang sedang login
 * @return array|null Data kelas atau null jika bukan wali kelas
 */
function getWalikelasKelas() {
    if (!isWalikelas()) return null;
    
    $user = getCurrentUser();
    if (!$user) return null;
    
    $query = "SELECT * FROM kelas WHERE wali_kelas_id = " . $user['id'];
    $result = query($query);
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return null;
}

/**
 * Mendapatkan daftar siswa di kelas wali
 * @return array Daftar siswa
 */
function getSiswaByWalikelas() {
    $kelas = getWalikelasKelas();
    if (!$kelas) return [];
    
    $query = "SELECT s.*, k.nama_kelas 
              FROM siswa s 
              LEFT JOIN kelas k ON s.kelas_id = k.id 
              WHERE s.kelas_id = " . $kelas['id'] . " 
              ORDER BY s.nama_siswa";
    return getRows($query);
}

/**
 * Mendapatkan jumlah siswa di kelas wali
 * @return int Jumlah siswa
 */
function getJumlahSiswaWalikelas() {
    $kelas = getWalikelasKelas();
    if (!$kelas) return 0;
    
    $result = query("SELECT COUNT(*) as total FROM siswa WHERE kelas_id = " . $kelas['id']);
    if ($result) {
        return (int)$result->fetch_assoc()['total'];
    }
    return 0;
}

/**
 * Mendapatkan daftar pelanggaran siswa di kelas wali
 * @param int|null $limit Batas data
 * @return array Daftar pelanggaran
 */
function getPelanggaranByWalikelas($limit = null) {
    $kelas = getWalikelasKelas();
    if (!$kelas) return [];
    
    $query = "SELECT p.*, s.nama_siswa, s.nisn, jp.nama_pelanggaran, jp.poin, u.nama_lengkap as guru
              FROM pelanggaran p
              LEFT JOIN siswa s ON p.siswa_id = s.id
              LEFT JOIN jenis_pelanggaran jp ON p.jenis_pelanggaran_id = jp.id
              LEFT JOIN users u ON p.guru_id = u.id
              WHERE s.kelas_id = " . $kelas['id'] . "
              ORDER BY p.tanggal_pelanggaran DESC, p.created_at DESC";
    
    if ($limit) {
        $query .= " LIMIT $limit";
    }
    return getRows($query);
}

/**
 * Mendapatkan statistik untuk dashboard wali kelas
 * @return array|null Data statistik
 */
function getStatistikWalikelas() {
    $kelas = getWalikelasKelas();
    if (!$kelas) return null;
    
    $data = [
        'total_siswa' => 0,
        'total_poin' => 0,
        'total_pelanggaran' => 0,
        'pelanggaran_hari_ini' => 0,
        'pelanggaran_bulan_ini' => 0,
        'siswa_tertinggi' => null
    ];
    
    // Total siswa
    $result = query("SELECT COUNT(*) as total FROM siswa WHERE kelas_id = " . $kelas['id']);
    if ($result) {
        $data['total_siswa'] = (int)$result->fetch_assoc()['total'];
    }
    
    // Total poin
    $result = query("SELECT SUM(poin) as total FROM siswa WHERE kelas_id = " . $kelas['id']);
    if ($result) {
        $data['total_poin'] = (int)$result->fetch_assoc()['total'];
    }
    
    // Total pelanggaran
    $result = query("SELECT COUNT(*) as total FROM pelanggaran p 
                     LEFT JOIN siswa s ON p.siswa_id = s.id 
                     WHERE s.kelas_id = " . $kelas['id']);
    if ($result) {
        $data['total_pelanggaran'] = (int)$result->fetch_assoc()['total'];
    }
    
    // Pelanggaran hari ini
    $hari = date('Y-m-d');
    $result = query("SELECT COUNT(*) as total FROM pelanggaran p 
                     LEFT JOIN siswa s ON p.siswa_id = s.id 
                     WHERE s.kelas_id = " . $kelas['id'] . " 
                     AND p.tanggal_pelanggaran = '$hari'");
    if ($result) {
        $data['pelanggaran_hari_ini'] = (int)$result->fetch_assoc()['total'];
    }
    
    // Pelanggaran bulan ini
    $bulan = date('Y-m');
    $result = query("SELECT COUNT(*) as total FROM pelanggaran p 
                     LEFT JOIN siswa s ON p.siswa_id = s.id 
                     WHERE s.kelas_id = " . $kelas['id'] . " 
                     AND DATE_FORMAT(p.tanggal_pelanggaran, '%Y-%m') = '$bulan'");
    if ($result) {
        $data['pelanggaran_bulan_ini'] = (int)$result->fetch_assoc()['total'];
    }
    
    // Siswa dengan poin tertinggi
    $result = query("SELECT id, nama_siswa, poin FROM siswa 
                     WHERE kelas_id = " . $kelas['id'] . " 
                     ORDER BY poin DESC LIMIT 1");
    if ($result && $result->num_rows > 0) {
        $data['siswa_tertinggi'] = $result->fetch_assoc();
    }
    
    return $data;
}

/**
 * Mendapatkan data grafik pelanggaran per bulan untuk wali kelas
 * @return array Data grafik 12 bulan
 */
function getGrafikPelanggaranWalikelas() {
    $kelas = getWalikelasKelas();
    if (!$kelas) return array_fill(0, 12, 0);
    
    $data = [];
    $tahun = date('Y');
    for ($i = 1; $i <= 12; $i++) {
        $query = "SELECT COUNT(*) as total FROM pelanggaran p 
                  LEFT JOIN siswa s ON p.siswa_id = s.id 
                  WHERE s.kelas_id = " . $kelas['id'] . " 
                  AND MONTH(p.tanggal_pelanggaran) = $i 
                  AND YEAR(p.tanggal_pelanggaran) = $tahun";
        $result = query($query);
        $data[] = (int)$result->fetch_assoc()['total'];
    }
    return $data;
}

/**
 * Mendapatkan 5 siswa dengan poin tertinggi di kelas wali
 * @return array Daftar siswa
 */
function getTop5SiswaWalikelas() {
    $kelas = getWalikelasKelas();
    if (!$kelas) return [];
    
    $query = "SELECT id, nama_siswa, poin FROM siswa 
              WHERE kelas_id = " . $kelas['id'] . " 
              ORDER BY poin DESC LIMIT 5";
    return getRows($query);
}

/**
 * Mendapatkan pelanggaran terbaru di kelas wali
 * @param int $limit Jumlah data
 * @return array Daftar pelanggaran
 */
function getPelanggaranTerbaruWalikelas($limit = 5) {
    $kelas = getWalikelasKelas();
    if (!$kelas) return [];
    
    $query = "SELECT p.*, s.nama_siswa, s.nisn, jp.nama_pelanggaran, jp.poin
              FROM pelanggaran p
              LEFT JOIN siswa s ON p.siswa_id = s.id
              LEFT JOIN jenis_pelanggaran jp ON p.jenis_pelanggaran_id = jp.id
              WHERE s.kelas_id = " . $kelas['id'] . "
              ORDER BY p.created_at DESC
              LIMIT $limit";
    return getRows($query);
}

?>