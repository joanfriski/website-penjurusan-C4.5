<?php
// Aktifkan output buffering di awal sekali
ob_start();

// Mencegah caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Memuat file header dari parent directory
require_once '../include/header.php';

// Pastikan pengguna sudah login dan memiliki hak akses
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'staff_bk') {
    // Redirect ke halaman login jika belum login atau tidak memiliki hak akses
    header("Location: ../../login/login.php");
    exit();
}

// Pastikan parameter id ada
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Redirect ke halaman index jika tidak ada id
    header("Location: index.php");
    exit();
}

$id = $_GET['id'];

// Ambil informasi siswa terlebih dahulu untuk log aktivitas
$query_info = "SELECT t.id, t.siswa_id, s.nama_lengkap, s.nis 
              FROM test_iq t 
              JOIN siswa s ON t.siswa_id = s.id 
              WHERE t.id = $id";
$result_info = mysqli_query($conn, $query_info);

if (mysqli_num_rows($result_info) == 0) {
    $_SESSION['notification'] = "Data test IQ tidak ditemukan!";
    // Gunakan JavaScript untuk redirect karena header mungkin sudah dikirim
    echo "<script>window.location.href = 'index.php?refresh=" . time() . "';</script>";
    exit();
}

$row_info = mysqli_fetch_assoc($result_info);
$nama_siswa = $row_info['nama_lengkap'];
$nis_siswa = $row_info['nis'];
$siswa_id = $row_info['siswa_id'];

// Hapus data test IQ
$delete_query = "DELETE FROM test_iq WHERE id = $id";
$delete_result = mysqli_query($conn, $delete_query);

if ($delete_result) {
    // Catat aktivitas
    $aktivitas = "Menghapus data test IQ";
    $log_query = "INSERT INTO log_aktivitas (user_id, aktivitas, keterangan, ip_address) 
                 VALUES ('{$_SESSION['user_id']}', '$aktivitas', 'Menghapus data test IQ untuk siswa: $nama_siswa ($nis_siswa)', '{$_SERVER['REMOTE_ADDR']}')";
    mysqli_query($conn, $log_query);
    
    // Cache siswa ID dalam session untuk penggunaan di tambah.php
    $_SESSION['recently_deleted_iq_student'] = $siswa_id;
    
    $_SESSION['notification'] = "Data test IQ untuk siswa $nama_siswa berhasil dihapus!";
} else {
    $_SESSION['notification'] = "Gagal menghapus data test IQ! Error: " . mysqli_error($conn);
}

// Redirect kembali ke halaman index menggunakan JavaScript dengan parameter refresh
echo "<script>window.location.href = 'index.php?refresh=" . time() . "';</script>";
exit();

// Aktifkan output buffering untuk menyelesaikan buffer
ob_end_flush();
?>