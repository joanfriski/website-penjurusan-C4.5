<?php
require_once '../../database/config.php';
session_start();

// Cek autentikasi user
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login/login.php");
    exit;
}

// Cek hak akses user
if ($_SESSION['role'] != 'staff_bk') {
    $_SESSION['message'] = "Anda tidak memiliki akses untuk menghapus data minat siswa";
    header("Location: index.php");
    exit;
}

// Cek parameter id
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['message'] = "ID minat siswa tidak valid";
    header("Location: index.php");
    exit;
}

$id = mysqli_real_escape_string($conn, $_GET['id']);

// Ambil data minat siswa untuk log
$query_data = "SELECT ms.*, s.nama_lengkap 
               FROM minat_siswa ms 
               JOIN siswa s ON ms.siswa_id = s.id 
               WHERE ms.id = '$id'";
$result_data = mysqli_query($conn, $query_data);

if (mysqli_num_rows($result_data) == 0) {
    $_SESSION['message'] = "Data minat siswa tidak ditemukan";
    header("Location: index.php");
    exit;
}

$data = mysqli_fetch_assoc($result_data);
$nama_siswa = $data['nama_lengkap'];

// Hapus data minat
$query = "DELETE FROM minat_siswa WHERE id = '$id'";

if (mysqli_query($conn, $query)) {
    // Log aktivitas
    $user_id = $_SESSION['user_id'];
    $aktivitas = "Menghapus data minat siswa " . $nama_siswa;
    $log_query = "INSERT INTO log_aktivitas (user_id, aktivitas, ip_address) 
                 VALUES ('$user_id', '$aktivitas', '".$_SERVER['REMOTE_ADDR']."')";
    mysqli_query($conn, $log_query);
    
    $_SESSION['message'] = "Data minat siswa berhasil dihapus";
} else {
    $_SESSION['message'] = "Gagal menghapus data: " . mysqli_error($conn);
}

// Redirect kembali ke halaman index
header("Location: index.php");
exit;
?>