<?php
// login.php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// Jika sudah login, redirect berdasarkan role
if (isLoggedIn()) {
    $role = $_SESSION['role'] ?? '';
    
    // Jika role kosong, cek di database
    if (empty($role)) {
        $user = getCurrentUser();
        if ($user && !empty($user['role'])) {
            $role = $user['role'];
            $_SESSION['role'] = $role;
        } else {
            $role = 'siswa';
            $_SESSION['role'] = $role;
        }
    }
    
    switch ($role) {
        case 'admin':
            header("Location: " . APP_URL . "admin/dashboard.php");
            break;
        case 'walikelas':
            header("Location: " . APP_URL . "walikelas/dashboard_walikelas.php");
            break;
        case 'bk':
            header("Location: " . APP_URL . "bk/dashboard_bk.php");
            break;
        case 'guru':
            header("Location: " . APP_URL . "guru/dashboard_guru.php");
            break;
        case 'siswa':
        default:
            header("Location: " . APP_URL . "siswa/dashboard_siswa.php");
            break;
    }
    exit();
}

// Get pengaturan dari database
$pengaturan = getPengaturan();
$nama_sekolah = $pengaturan['nama_sekolah'] ?? 'Tatib Sekolah';
$logo = $pengaturan['logo'] ?? '';

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $conn = getDB();
    $username_escaped = $conn->real_escape_string($username);
    
    // ============================================
    // CEK DI TABEL USERS
    // ============================================
    $query = "SELECT * FROM users WHERE username = '$username_escaped'";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows == 1) {
        $user = $result->fetch_assoc();
        $stored_password = $user['password'];
        
        // Cek password
        $valid = false;
        
        // Cek dengan MD5
        if (md5($password) === $stored_password) {
            $valid = true;
        }
        // Cek dengan password_hash (bcrypt)
        elseif (password_verify($password, $stored_password)) {
            $valid = true;
        }
        
        if ($valid) {
            // ============================================
            // CEK DAN UPDATE ROLE JIKA KOSONG
            // ============================================
            $role = $user['role'];
            
            // Jika role kosong, set otomatis
            if (empty($role) || $role == '' || $role == NULL) {
                // Cek apakah ini siswa (username adalah NISN)
                $cek_siswa = $conn->query("SELECT id FROM siswa WHERE nisn = '$username_escaped'");
                if ($cek_siswa && $cek_siswa->num_rows > 0) {
                    $role = 'siswa';
                } 
                // Cek apakah admin
                elseif ($username_escaped == 'admin') {
                    $role = 'admin';
                }
                // Default ke siswa
                else {
                    $role = 'siswa';
                }
                
                // Update role di database
                $conn->query("UPDATE users SET role = '$role' WHERE id = " . $user['id']);
            }
            
            // ============================================
            // SET SESSION
            // ============================================
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $role;
            $_SESSION['nama'] = $user['nama_lengkap'];
            $_SESSION['logged_in'] = true;
            
            // ============================================
            // REDIRECT
            // ============================================
            switch ($role) {
                case 'admin':
                    header("Location: " . APP_URL . "admin/dashboard.php");
                    break;
                case 'walikelas':
                    header("Location: " . APP_URL . "walikelas/dashboard_walikelas.php");
                    break;
                case 'bk':
                    header("Location: " . APP_URL . "bk/dashboard_bk.php");
                    break;
                case 'guru':
                    header("Location: " . APP_URL . "guru/dashboard_guru.php");
                    break;
                case 'siswa':
                default:
                    header("Location: " . APP_URL . "siswa/dashboard_siswa.php");
                    break;
            }
            exit();
        } else {
            $error = 'Username atau password salah!';
        }
    } else {
        // ============================================
        // CEK DI TABEL SISWA (Login dengan NISN)
        // ============================================
        $query_siswa = "SELECT * FROM siswa WHERE nisn = '$username_escaped'";
        $result_siswa = $conn->query($query_siswa);
        
        if ($result_siswa && $result_siswa->num_rows == 1) {
            $siswa = $result_siswa->fetch_assoc();
            
            // Password default = NISN
            if ($password === $siswa['nisn']) {
                // Cek apakah sudah ada user
                $cek_user = $conn->query("SELECT id FROM users WHERE username = '{$siswa['nisn']}'");
                
                if ($cek_user && $cek_user->num_rows > 0) {
                    // Update user yang sudah ada
                    $user_data = $cek_user->fetch_assoc();
                    $conn->query("UPDATE users SET role = 'siswa', nama_lengkap = '{$siswa['nama_siswa']}' WHERE id = " . $user_data['id']);
                    $user_id = $user_data['id'];
                } else {
                    // Buat user baru
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $insert = "INSERT INTO users (username, password, nama_lengkap, role, nip) 
                               VALUES ('{$siswa['nisn']}', '$hashed_password', '{$siswa['nama_siswa']}', 'siswa', '{$siswa['nisn']}')";
                    $conn->query($insert);
                    $user_id = $conn->insert_id;
                }
                
                // Login siswa
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $siswa['nisn'];
                $_SESSION['role'] = 'siswa';
                $_SESSION['nama'] = $siswa['nama_siswa'];
                $_SESSION['logged_in'] = true;
                
                header("Location: " . APP_URL . "siswa/dashboard_siswa.php");
                exit();
            } else {
                $error = 'Username atau password salah! (Gunakan NISN sebagai password)';
            }
        } else {
            $error = 'Username atau password salah!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Login - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: url('<?= APP_URL ?>assets/images/bkg.png') no-repeat center center fixed;
            background-size: cover;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            padding: 16px;
        }
        
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.45);
            z-index: 0;
        }
        
        .login-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 320px;
            margin: 0 auto;
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border-radius: 14px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.18);
            padding: 20px 18px 18px;
            width: 100%;
        }
        
        .login-logo {
            text-align: center;
            margin-bottom: 14px;
        }
        
        .login-logo .logo-circle {
            width: 56px;
            height: 56px;
            margin: 0 auto 8px;
            border-radius: 50%;
            background: #f0f2f5;
            border: 3px solid #e8ecf1;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .login-logo .logo-circle img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .login-logo .logo-circle .default-logo {
            font-size: 24px;
            color: #667eea;
        }
        
        .login-logo h3 {
            color: #1a1a2e;
            font-weight: 700;
            font-size: 15px;
            margin-bottom: 1px;
        }
        
        .login-logo p {
            color: #888;
            font-size: 10px;
            margin-bottom: 0;
        }
        
        .form-label {
            font-size: 11px;
            font-weight: 600;
            color: #333;
            margin-bottom: 3px;
        }
        
        .form-control {
            border-radius: 8px;
            padding: 6px 10px;
            border: 2px solid #e8ecf1;
            font-size: 12px;
            background: #f8f9fa;
            height: 34px;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.12);
            background: #fff;
        }
        
        .input-group-text {
            border-radius: 8px 0 0 8px;
            border: 2px solid #e8ecf1;
            border-right: none;
            background: #f8f9fa;
            color: #888;
            padding: 6px 10px;
            font-size: 12px;
        }
        
        .input-group .form-control {
            border-radius: 0 8px 8px 0;
            border-left: none;
        }
        
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 8px;
            padding: 8px;
            font-weight: 600;
            font-size: 13px;
            color: #fff;
            width: 100%;
            cursor: pointer;
        }
        
        .btn-login:hover { opacity: 0.9; }
        .btn-login:active { transform: scale(0.98); }
        
        .alert-custom {
            border-radius: 8px;
            padding: 6px 10px;
            font-size: 11px;
            border: none;
            margin-bottom: 10px;
        }
        
        .form-check-label {
            font-size: 11px;
            color: #666;
        }
        
        .form-check-input:checked {
            background-color: #667eea;
            border-color: #667eea;
        }
        
        .text-link {
            color: #667eea;
            font-size: 11px;
            text-decoration: none;
        }
        
        .text-link:hover { text-decoration: underline; }
        
        .text-muted {
            font-size: 9px;
        }
        
        .download-app {
            text-align: center;
            margin-top: 12px;
            padding-top: 10px;
            border-top: 1px solid #e8ecf1;
        }
        
        .download-app a {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #555;
            text-decoration: none;
            font-size: 10px;
            font-weight: 500;
            padding: 4px 12px;
            border-radius: 16px;
            background: #f0f2f5;
        }
        
        .download-app a:hover {
            background: #667eea;
            color: #fff;
        }
        
        .download-app a i {
            font-size: 12px;
        }
        
        .download-app a .badge-new {
            background: #ef4444;
            color: #fff;
            font-size: 6px;
            padding: 1px 6px;
            border-radius: 10px;
            font-weight: 700;
        }
        
        .download-app a:hover .badge-new {
            background: #fff;
            color: #ef4444;
        }
        
        .footer-text {
            text-align: center;
            margin-top: 10px;
            color: rgba(255, 255, 255, 0.5);
            font-size: 9px;
        }
        
        @media (max-width: 480px) {
            .login-container { max-width: 300px; }
            .login-card { padding: 16px 14px 14px; border-radius: 12px; }
            .login-logo .logo-circle { width: 48px; height: 48px; }
            .login-logo .logo-circle .default-logo { font-size: 20px; }
            .login-logo h3 { font-size: 13px; }
            .login-logo p { font-size: 9px; }
            .form-control { padding: 5px 8px; font-size: 11px; height: 30px; }
            .input-group-text { padding: 5px 8px; font-size: 11px; }
            .btn-login { padding: 6px; font-size: 12px; }
            .download-app a { font-size: 9px; padding: 3px 10px; }
            .download-app a i { font-size: 10px; }
        }
        
        @media (max-width: 360px) {
            .login-container { max-width: 270px; }
            .login-card { padding: 12px 10px 10px; border-radius: 10px; }
            .login-logo .logo-circle { width: 40px; height: 40px; }
            .login-logo .logo-circle .default-logo { font-size: 16px; }
            .login-logo h3 { font-size: 12px; }
            .login-logo p { font-size: 8px; }
            .form-control { padding: 4px 6px; font-size: 10px; height: 28px; }
            .input-group-text { padding: 4px 6px; font-size: 10px; }
            .btn-login { padding: 5px; font-size: 11px; }
            .form-label { font-size: 10px; }
            .text-muted { font-size: 8px; }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-logo">
                <div class="logo-circle">
                    <?php if (!empty($logo) && file_exists(APP_PATH . 'uploads/logo/' . $logo)): ?>
                        <img src="<?= APP_URL ?>uploads/logo/<?= $logo ?>" alt="Logo">
                    <?php else: ?>
                        <i class="fas fa-school default-logo"></i>
                    <?php endif; ?>
                </div>
                <h3><?= htmlspecialchars($nama_sekolah) ?></h3>
                <p>Sistem Informasi Tata Tertib Sekolah</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-custom alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i> <?= $error ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" style="font-size: 8px;"></button>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-2">
                    <label class="form-label">Username / NISN</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" name="username" class="form-control" 
                               placeholder="Masukkan username atau NISN" 
                               value="<?= htmlspecialchars($username) ?>" 
                               required autofocus>
                    </div>
                    <small class="text-muted">Siswa menggunakan <strong>NISN</strong> sebagai username</small>
                </div>
                
                <div class="mb-2">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
                    </div>
                    <small class="text-muted">Siswa password default = <strong>NISN</strong></small>
                </div>
                
                <div class="mb-2 d-flex justify-content-between align-items-center">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="remember">
                        <label class="form-check-label" for="remember">Ingat saya</label>
                    </div>
                    <a href="#" class="text-link">Lupa password?</a>
                </div>
                
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i> Login
                </button>
            </form>
            
            <div class="download-app">
                <a href="https://shopee.co.id/viralytics_shop" target="_blank">
                    <i class="fas fa-download"></i>
                    <span>Download Aplikasi</span>
                    <span class="badge-new">NEW</span>
                </a>
            </div>
        </div>
        
        <div class="footer-text">
            &copy; <?= date('Y') ?> <?= htmlspecialchars($nama_sekolah) ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>