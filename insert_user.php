<?php
// (Removed session check so that non-logged-in users can insert a new user)
require_once 'database/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi input wajib
    if (empty($_POST['username']) || empty($_POST['password']) || empty($_POST['nama_lengkap']) || empty($_POST['role'])) {
        header("Location: create_user.php?error=1");
        exit;
    }

    // Escape dan ambil nilai input
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];
    $nama_lengkap = $conn->real_escape_string($_POST['nama_lengkap']);
    $role = $conn->real_escape_string($_POST['role']);
    $email = (isset($_POST['email']) && !empty($_POST['email'])) ? $conn->real_escape_string($_POST['email']) : null;
    $no_telp = (isset($_POST['no_telp']) && !empty($_POST['no_telp'])) ? $conn->real_escape_string($_POST['no_telp']) : null;

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Siapkan query insert
    $sql = "INSERT INTO users (username, password, nama_lengkap, role, email, no_telp) VALUES ('$username', '$hashed_password', '$nama_lengkap', '$role', " . ($email ? "'$email'" : "NULL") . ", " . ($no_telp ? "'$no_telp'" : "NULL") . ")";

    if ($conn->query($sql) === TRUE) {
        header("Location: create_user.php?success=1");
    } else {
        header("Location: create_user.php?error=2");
    }
    exit;
} else {
    header("Location: create_user.php");
    exit;
} 