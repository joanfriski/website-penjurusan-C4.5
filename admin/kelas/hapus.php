<?php
ob_start();
require_once '../../database/config.php';
require_once '../include/header.php';


// Cek apakah user memiliki akses
if ($_SESSION['role'] != 'staff_bk') {
    $_SESSION['message'] = 'Anda tidak memiliki akses untuk menghapus data kelas!';
    $_SESSION['message_type'] = 'danger';
    header('Location: index.php');
    exit();
}

// Cek ID kelas
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['message'] = 'ID kelas tidak valid!';
    $_SESSION['message_type'] = 'danger';
    header('Location: index.php');
    exit();
}

$id = mysqli_real_escape_string($conn, $_GET['id']);

// Ambil data kelas sebelum dihapus (untuk log)
$query_kelas = "SELECT * FROM kelas WHERE id = '$id'";
$result_kelas = mysqli_query($conn, $query_kelas);

if (mysqli_num_rows($result_kelas) == 0) {
    $_SESSION['message'] = 'Data kelas tidak ditemukan!';
    $_SESSION['message_type'] = 'danger';
    header('Location: index.php');
    exit();
}

$kelas = mysqli_fetch_assoc($result_kelas);
$nama_kelas = $kelas['nama_kelas'];
$tahun_ajaran = $kelas['tahun_ajaran'];

// Mulai transaction
mysqli_begin_transaction($conn);

try {
    // Check for students in this class
    $check_siswa = "SELECT COUNT(*) as total FROM siswa WHERE kelas_id = '$id'";
    $result_siswa = mysqli_query($conn, $check_siswa);
    $siswa_count = mysqli_fetch_assoc($result_siswa)['total'];
    
    if ($siswa_count > 0) {
        // Update students to have no class
        $update_siswa = "UPDATE siswa SET kelas_id = NULL WHERE kelas_id = '$id'";
        if (!mysqli_query($conn, $update_siswa)) {
            throw new Exception("Gagal mengupdate data siswa terkait");
        }
    }
    
    // Delete class
    $delete_query = "DELETE FROM kelas WHERE id = '$id'";
    if (!mysqli_query($conn, $delete_query)) {
        throw new Exception("Gagal menghapus data kelas");
    }
    
    // Log aktivitas
    $user_id = $_SESSION['user_id'];
    $aktivitas = "Menghapus kelas: $nama_kelas ($tahun_ajaran)";
    $log_query = "INSERT INTO log_aktivitas (user_id, aktivitas) VALUES ('$user_id', '$aktivitas')";
    if (!mysqli_query($conn, $log_query)) {
        throw new Exception("Gagal mencatat log aktivitas");
    }
    
    // Commit transaction
    mysqli_commit($conn);
    
    $_SESSION['message'] = 'Data kelas berhasil dihapus';
    $_SESSION['message_type'] = 'success';
    
} catch (Exception $e) {
    // Rollback transaction
    mysqli_rollback($conn);
    
    $_SESSION['message'] = 'Gagal menghapus data kelas: ' . $e->getMessage();
    $_SESSION['message_type'] = 'danger';
}

// Redirect back to index
header('Location: index.php');
exit();
ob_end_flush();
?>