<?php
session_start();
require_once '../../database/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
// Add new mata pelajaran
if ($action == 'add') {
    // Generate kode otomatis berdasarkan kategori
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori']);
    $prefix = substr($kategori, 0, 3); // Ambil 3 huruf pertama dari kategori
    
    // Cari kode terakhir dengan prefix yang sama
    $check_code_query = "SELECT MAX(SUBSTRING(kode, 4)) as last_num 
                         FROM mata_pelajaran 
                         WHERE kode LIKE '$prefix%'";
    $check_code_result = mysqli_query($conn, $check_code_query);
    $row = mysqli_fetch_assoc($check_code_result);
    
    $last_num = intval($row['last_num']);
    $new_num = $last_num + 1;
    
    // Format nomor dengan leading zeros (3 digit)
    $formatted_num = sprintf('%03d', $new_num);
    
    // Buat kode baru
    $kode = $prefix . $formatted_num;
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    
    // Kemudian lanjutkan dengan query INSERT
    $query = "INSERT INTO mata_pelajaran (kode, nama, kategori) VALUES ('$kode', '$nama', '$kategori')";
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['success'] = "Mata pelajaran berhasil ditambahkan dengan kode: $kode!";
        
        // Log activity
        $user_id = $_SESSION['user_id'];
        $aktivitas = "Menambahkan mata pelajaran baru: $nama ($kode)";
        $query_log = "INSERT INTO log_aktivitas (user_id, aktivitas, ip_address) VALUES ('$user_id', '$aktivitas', '{$_SERVER['REMOTE_ADDR']}')";
        mysqli_query($conn, $query_log);
    } else {
        $_SESSION['error'] = "Gagal menambahkan mata pelajaran: " . mysqli_error($conn);
    }
}
    
    // Edit mata pelajaran
    elseif ($action == 'edit') {
        $id = (int)$_POST['id'];
        $kode = mysqli_real_escape_string($conn, $_POST['kode']);
        $nama = mysqli_real_escape_string($conn, $_POST['nama']);
        $kategori = mysqli_real_escape_string($conn, $_POST['kategori']);
        
        // Check if code already exists (excluding current record)
        $check_query = "SELECT id FROM mata_pelajaran WHERE kode = '$kode' AND id != $id";
        $check_result = mysqli_query($conn, $check_query);
        
        if (mysqli_num_rows($check_result) > 0) {
            $_SESSION['error'] = "Kode mata pelajaran '$kode' sudah digunakan!";
        } else {
            $query = "UPDATE mata_pelajaran SET kode = '$kode', nama = '$nama', kategori = '$kategori' WHERE id = $id";
            
            if (mysqli_query($conn, $query)) {
                $_SESSION['success'] = "Mata pelajaran berhasil diperbarui!";
                
                // Log activity
                $user_id = $_SESSION['user_id'];
                $aktivitas = "Memperbarui mata pelajaran: $nama ($kode)";
                $query_log = "INSERT INTO log_aktivitas (user_id, aktivitas, ip_address) VALUES ('$user_id', '$aktivitas', '{$_SERVER['REMOTE_ADDR']}')";
                mysqli_query($conn, $query_log);
            } else {
                $_SESSION['error'] = "Gagal memperbarui mata pelajaran: " . mysqli_error($conn);
            }
        }
    }
    
    // Delete mata pelajaran
    elseif ($action == 'delete') {
        $id = (int)$_POST['id'];
        
        // Get mata pelajaran details before deletion
        $get_query = "SELECT nama, kode FROM mata_pelajaran WHERE id = $id";
        $get_result = mysqli_query($conn, $get_query);
        $mapel = mysqli_fetch_assoc($get_result);
        
        $query = "DELETE FROM mata_pelajaran WHERE id = $id";
        
        if (mysqli_query($conn, $query)) {
            $_SESSION['success'] = "Mata pelajaran berhasil dihapus!";
            
            // Log activity
            $user_id = $_SESSION['user_id'];
            $aktivitas = "Menghapus mata pelajaran: {$mapel['nama']} ({$mapel['kode']})";
            $query_log = "INSERT INTO log_aktivitas (user_id, aktivitas, ip_address) VALUES ('$user_id', '$aktivitas', '{$_SERVER['REMOTE_ADDR']}')";
            mysqli_query($conn, $query_log);
        } else {
            $_SESSION['error'] = "Gagal menghapus mata pelajaran: " . mysqli_error($conn);
        }
    }
    
    else {
        $_SESSION['error'] = "Aksi tidak valid!";
    }
    
    // Redirect back to the nilai page
    header('Location: index.php');
    exit;
} else {
    // If not POST request, redirect to index
    header('Location: index.php');
    exit;
}
?>