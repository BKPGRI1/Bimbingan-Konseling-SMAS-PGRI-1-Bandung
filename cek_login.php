<?php
// cek_login.php - CEK MANUAL TANPA SESSION
require_once 'includes/config.php'; // <-- INI YANG PENTING
require_once 'includes/db.php';

$username = '1234567893';
$password = '1234567893';

echo "<h2>CEK LOGIN MANUAL</h2>";
echo "Username: " . $username . "<br>";
echo "Password: " . $password . "<br>";
echo "MD5 Password: " . md5($password) . "<br><br>";

$conn = getDB();

// 1. CEK DI TABEL USERS
echo "<h3>1. CEK TABEL USERS</h3>";
$query = "SELECT * FROM users WHERE username = '$username'";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo "✅ User ditemukan!<br>";
    echo "ID: " . $user['id'] . "<br>";
    echo "Username: " . $user['username'] . "<br>";
    echo "Password di DB: " . $user['password'] . "<br>";
    echo "Role: '" . $user['role'] . "'<br><br>";
    
    if ($user['password'] == md5($password)) {
        echo "<span style='color:green;font-weight:bold;'>✅ PASSWORD COCOK! Bisa login.</span><br>";
    } else {
        echo "<span style='color:red;font-weight:bold;'>❌ PASSWORD TIDAK COCOK!</span><br>";
        echo "Password DB: " . $user['password'] . "<br>";
        echo "MD5('" . $password . "'): " . md5($password);
    }
} else {
    echo "❌ User tidak ditemukan di tabel users!<br>";
    
    // 2. CEK DI TABEL SISWA
    echo "<h3>2. CEK TABEL SISWA</h3>";
    $query_siswa = "SELECT * FROM siswa WHERE nisn = '$username'";
    $result_siswa = $conn->query($query_siswa);
    
    if ($result_siswa && $result_siswa->num_rows > 0) {
        $siswa = $result_siswa->fetch_assoc();
        echo "✅ Siswa ditemukan!<br>";
        echo "NISN: " . $siswa['nisn'] . "<br>";
        echo "Nama: " . $siswa['nama_siswa'] . "<br>";
        echo "Password default: " . $siswa['nisn'] . "<br><br>";
        
        echo "💡 Solusi: Buat user baru dengan query:<br>";
        echo "<pre style='background:#f0f0f0;padding:10px;border-radius:5px;'>
INSERT INTO users (username, password, nama_lengkap, nip, role) 
VALUES ('{$siswa['nisn']}', MD5('{$siswa['nisn']}'), '{$siswa['nama_siswa']}', '{$siswa['nisn']}', 'siswa');
        </pre>";
    } else {
        echo "❌ Siswa tidak ditemukan!";
    }
}

// 3. TAMPILKAN SEMUA USER
echo "<h3>3. SEMUA USER DI DATABASE</h3>";
$all = $conn->query("SELECT id, username, role, password FROM users");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Username</th><th>Role</th><th>Password</th></tr>";
while ($row = $all->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . $row['username'] . "</td>";
    echo "<td>'" . $row['role'] . "'</td>";
    echo "<td>" . $row['password'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// 4. CEK APAKAH ADA SISWA YANG BELUM PUNYA USER
echo "<h3>4. SISWA YANG BELUM PUNYA USER</h3>";
$sql = "SELECT s.* FROM siswa s 
        LEFT JOIN users u ON s.nisn = u.username 
        WHERE u.id IS NULL";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>NISN</th><th>Nama</th><th>Kelas</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['nisn'] . "</td>";
        echo "<td>" . $row['nama_siswa'] . "</td>";
        echo "<td>" . $row['kelas_id'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<br>💡 Jalankan query ini untuk membuat user:<br>";
    echo "<pre style='background:#f0f0f0;padding:10px;border-radius:5px;'>
INSERT INTO users (username, password, nama_lengkap, nip, role) 
SELECT nisn, MD5(nisn), nama_siswa, nisn, 'siswa' 
FROM siswa 
WHERE nisn NOT IN (SELECT username FROM users);
    </pre>";
} else {
    echo "✅ Semua siswa sudah punya user!";
}
?>