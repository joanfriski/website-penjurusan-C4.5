<?php
// File ini untuk setup awal sistem
echo "<h1>Setup Awal SMA Ciwaru</h1>";

require_once 'database/config.php';

// Cek apakah tabel users sudah ada
$check_table = $conn->query("SHOW TABLES LIKE 'users'");

if ($check_table->num_rows == 0) {
    // Buat tabel users jika belum ada
    $create_table = "
    CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        nama_lengkap VARCHAR(100) NOT NULL,
        role ENUM('staff_bk', 'kepsek') NOT NULL,
        email VARCHAR(100),
        no_telp VARCHAR(15),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($create_table)) {

        echo "<p>Tabel users berhasil dibuat.</p>";
    } else {
        die("<p>Gagal membuat tabel users: " . $conn->error . "</p>");
    }
} else {
    echo "<p>Tabel users sudah ada.</p>";
}

// Cek apakah user admin sudah ada
$check_admin = $conn->query("SELECT id FROM users WHERE username = 'admin'");

if ($check_admin->num_rows == 0) {
    // Tambahkan user admin
    $password_admin = password_hash('password123', PASSWORD_DEFAULT);
    $insert_admin = "
    INSERT INTO users (username, password, nama_lengkap, role, email, no_telp) 
    VALUES ('admin', '$password_admin', 'Admin BK', 'staff_bk', 'admin@smaciwaru.sch.id', '081234567890')
    ";
    
    if ($conn->query($insert_admin)) {
        echo "<p>User admin berhasil ditambahkan.</p>";
        echo "<p>Username: admin</p>";
        echo "<p>Password: password123</p>";
    } else {
        echo "<p>Gagal menambahkan user admin: " . $conn->error . "</p>";
    }
} else {
    echo "<p>User admin sudah ada.</p>";
}

// Cek apakah user kepsek sudah ada
$check_kepsek = $conn->query("SELECT id FROM users WHERE username = 'kepsek'");

if ($check_kepsek->num_rows == 0) {
    // Tambahkan user kepala sekolah
    $password_kepsek = password_hash('password123', PASSWORD_DEFAULT);
    $insert_kepsek = "
    INSERT INTO users (username, password, nama_lengkap, role, email, no_telp) 
    VALUES ('kepsek', '$password_kepsek', 'Kepala Sekolah', 'kepsek', 'kepsek@smaciwaru.sch.id', '081234567891')
    ";
    
    if ($conn->query($insert_kepsek)) {
        echo "<p>User kepala sekolah berhasil ditambahkan.</p>";
        echo "<p>Username: kepsek</p>";
        echo "<p>Password: password123</p>";
    } else {
        echo "<p>Gagal menambahkan user kepala sekolah: " . $conn->error . "</p>";
    }
} else {
    echo "<p>User kepala sekolah sudah ada.</p>";
}

echo "<h2>Instalasi Selesai</h2>";
echo "<p>Sistem sekarang siap digunakan. Silahkan:</p>";
echo "<ul>
        <li><a href='index.php'>Kembali ke halaman utama</a></li>
        <li><a href='login/login.php'>Login ke sistem</a></li>
      </ul>";
echo "<p><strong>PERINGATAN:</strong> Hapus file install.php setelah selesai untuk keamanan sistem.</p>";

$conn->close();
?>