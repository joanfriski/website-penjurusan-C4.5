<?php
session_start();
require_once '../database/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];

    // Hardcoded login for admin (username: admin, password: admin123)
    if ($username === 'admin' && $password === 'admin123') {
         $_SESSION['logged_in'] = true;
         $_SESSION['user_id'] = 0; // dummy id
         $_SESSION['username'] = 'admin';
         $_SESSION['nama_lengkap'] = 'Administrator';
         $_SESSION['role'] = 'admin';
         header("Location: ../admin/dashboard/index.php");
         exit;
    }
    // Hardcoded login for kepsesk (username: kepsesk, password: 123)
    if ($username === 'kepsesk' && $password === '123') {
         $_SESSION['logged_in'] = true;
         $_SESSION['user_id'] = 0; // dummy id
         $_SESSION['username'] = 'kepsesk';
         $_SESSION['nama_lengkap'] = 'Kepala Sekolah';
         $_SESSION['role'] = 'kepsesk';
         header("Location: ../user/dashboard/index.php");
         exit;
    }

    // Existing database login query
    $sql = "SELECT * FROM users WHERE username = '$username'";
    $result = $conn->query($sql);

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['logged_in'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['role'] = $user['role'];
            if ($user['role'] === 'staff_bk') {
                header("Location: ../admin/dashboard/index.php");
            } else if ($user['role'] === 'kepsek') {
                header("Location: ../user/dashboard/index.php");
            } else {
                header("Location: ../user/index.php");
            }
            exit;
        }
    }
    
    // Jika login gagal
    header("Location: login.php?error=1");
    exit;
} else {
    header("Location: login.php");
    exit;
}
?>