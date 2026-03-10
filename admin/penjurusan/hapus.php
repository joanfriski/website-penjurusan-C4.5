<?php
require_once '../../database/config.php';

// Pastikan pengguna sudah login (jika ada sistem login)
// session_start();
// if (!isset($_SESSION['user_id'])) {
//     header("Location: ../../login.php");
//     exit;
// }

// Pastikan ada ID prediksi yang dikirim
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script>alert('ID Prediksi tidak valid!'); window.location.href='index.php';</script>";
    exit;
}

$prediksi_id = mysqli_real_escape_string($conn, $_GET['id']);

// Ambil data prediksi untuk log
$query_check = "SELECT pj.id, s.nama_lengkap 
                FROM prediksi_jurusan pj 
                JOIN siswa s ON pj.siswa_id = s.id 
                WHERE pj.id = '$prediksi_id'";
$result_check = mysqli_query($conn, $query_check);

if (!$result_check || mysqli_num_rows($result_check) == 0) {
    echo "<script>alert('Data prediksi tidak ditemukan!'); window.location.href='index.php';</script>";
    exit;
}

$data_prediksi = mysqli_fetch_assoc($result_check);
$nama_siswa = $data_prediksi['nama_lengkap'];

// Hapus data prediksi
$query_delete = "DELETE FROM prediksi_jurusan WHERE id = '$prediksi_id'";

if (mysqli_query($conn, $query_delete)) {
    // Log aktivitas (jika diperlukan)
    // $user_id = $_SESSION['user_id'];
    // $aktivitas = "Menghapus data prediksi jurusan untuk siswa: $nama_siswa";
    // mysqli_query($conn, "INSERT INTO log_aktivitas (user_id, aktivitas, created_at) VALUES ('$user_id', '$aktivitas', NOW())");
    
    echo "<script>alert('Data prediksi jurusan berhasil dihapus!'); window.location.href='index.php';</script>";
} else {
    echo "<script>alert('Gagal menghapus data prediksi: " . mysqli_error($conn) . "'); window.location.href='index.php';</script>";
}
?>