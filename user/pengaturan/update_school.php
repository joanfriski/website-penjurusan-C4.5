<?php
// Pastikan session sudah dimulai
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include koneksi database jika belum ter-include
if (!isset($conn)) {
    require_once '../../database/config.php';
}

// Inisialisasi variabel untuk pesan
$success_message = '';
$error_message = '';

// Fungsi untuk mengupload gambar
function uploadLogo($file) {
    // Update the target directory to the new location
    $target_dir = "./logo/"; // Relative path from the update_school.php file
    
    // Check if directory exists, if not create it
    if (!file_exists($target_dir)) {
        if (!mkdir($target_dir, 0777, true)) {
            return [false, "Gagal membuat direktori upload. Periksa izin folder."];
        }
    }
    
    // Make sure the directory is writable
    if (!is_writable($target_dir)) {
        chmod($target_dir, 0777);
        if (!is_writable($target_dir)) {
            return [false, "Direktori upload tidak dapat ditulis. Periksa izin folder."];
        }
    }
    
    $file_name = time() . '_' . basename($file["name"]);
    $target_file = $target_dir . $file_name;
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    
    // Cek apakah file adalah gambar
    $check = getimagesize($file["tmp_name"]);
    if($check === false) {
        return [false, "File bukan gambar yang valid."];
    }
    
    // Cek ukuran file
    if ($file["size"] > 5000000) { // 5MB
        return [false, "Maaf, ukuran file terlalu besar (maksimal 5MB)."];
    }
    
    // Izinkan format file tertentu
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
        return [false, "Maaf, hanya file JPG, JPEG, PNG & GIF yang diizinkan."];
    }
    
    // Upload file
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return [true, $file_name];
    } else {
        $error = error_get_last();
        return [false, "Maaf, terjadi kesalahan saat mengupload file: " . ($error ? $error['message'] : "Unknown error")];
    }
}

// Proses tambah data sekolah baru
if (isset($_POST['add_school'])) {
    $nama_sekolah = mysqli_real_escape_string($conn, $_POST['nama_sekolah']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    $telepon = mysqli_real_escape_string($conn, $_POST['telepon']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $website = mysqli_real_escape_string($conn, $_POST['website']);
    $kepala_sekolah = mysqli_real_escape_string($conn, $_POST['kepala_sekolah']);
    $nip_kepala_sekolah = mysqli_real_escape_string($conn, $_POST['nip_kepala_sekolah']);
    $tahun_ajaran = mysqli_real_escape_string($conn, $_POST['tahun_ajaran']);
    
    $logo = '';
    
    // Cek apakah ada upload logo
    if ($_FILES['logo']['name'] !== '') {
        $upload_result = uploadLogo($_FILES['logo']);
        if ($upload_result[0]) {
            $logo = $upload_result[1];
        } else {
            $error_message = $upload_result[1];
        }
    }
    
    if (empty($error_message)) {
        // Cek apakah sudah ada data sekolah
        $check_query = "SELECT id FROM settings LIMIT 1";
        $check_result = mysqli_query($conn, $check_query);
        
        if (mysqli_num_rows($check_result) > 0) {
            // Update data yang sudah ada
            $update_query = "UPDATE settings SET 
                nama_sekolah = '$nama_sekolah', 
                alamat = '$alamat', 
                telepon = '$telepon', 
                email = '$email', 
                website = '$website', 
                kepala_sekolah = '$kepala_sekolah', 
                nip_kepala_sekolah = '$nip_kepala_sekolah', 
                tahun_ajaran = '$tahun_ajaran'";
            
            if ($logo !== '') {
                $update_query .= ", logo = '$logo'";
            }
            
            $update_query .= ", updated_at = NOW() WHERE id = 1";
            
            if (mysqli_query($conn, $update_query)) {
                $success_message = "Data profil sekolah berhasil diperbarui!";
                
                // Catat aktivitas di log
                $aktivitas = "Menambahkan data profil sekolah";
                mysqli_query($conn, "INSERT INTO log_aktivitas (user_id, aktivitas) VALUES ('$user_id', '$aktivitas')");
                
                // Refresh data sekolah
                $query_school = "SELECT * FROM settings ORDER BY id DESC LIMIT 1";
                $result_school = mysqli_query($conn, $query_school);
                $school_data = mysqli_fetch_assoc($result_school);
            } else {
                $error_message = "Error: " . mysqli_error($conn);
            }
        } else {
            // Insert data baru
            $insert_query = "INSERT INTO settings 
                (nama_sekolah, alamat, telepon, email, website, kepala_sekolah, nip_kepala_sekolah, tahun_ajaran, logo, created_at, updated_at) 
                VALUES 
                ('$nama_sekolah', '$alamat', '$telepon', '$email', '$website', '$kepala_sekolah', '$nip_kepala_sekolah', '$tahun_ajaran', '$logo', NOW(), NOW())";
            
            if (mysqli_query($conn, $insert_query)) {
                $success_message = "Data profil sekolah berhasil ditambahkan!";
                
                // Catat aktivitas di log
                $aktivitas = "Menambahkan data profil sekolah";
                mysqli_query($conn, "INSERT INTO log_aktivitas (user_id, aktivitas) VALUES ('$user_id', '$aktivitas')");
                
                // Refresh data sekolah
                $query_school = "SELECT * FROM settings ORDER BY id DESC LIMIT 1";
                $result_school = mysqli_query($conn, $query_school);
                $school_data = mysqli_fetch_assoc($result_school);
            } else {
                $error_message = "Error: " . mysqli_error($conn);
            }
        }
    }
}

// Proses update data sekolah
if (isset($_POST['update_school'])) {
    $nama_sekolah = mysqli_real_escape_string($conn, $_POST['nama_sekolah']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    $telepon = mysqli_real_escape_string($conn, $_POST['telepon']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $website = mysqli_real_escape_string($conn, $_POST['website']);
    $kepala_sekolah = mysqli_real_escape_string($conn, $_POST['kepala_sekolah']);
    $nip_kepala_sekolah = mysqli_real_escape_string($conn, $_POST['nip_kepala_sekolah']);
    $tahun_ajaran = mysqli_real_escape_string($conn, $_POST['tahun_ajaran']);
    
    $update_query = "UPDATE settings SET 
        nama_sekolah = '$nama_sekolah', 
        alamat = '$alamat', 
        telepon = '$telepon', 
        email = '$email', 
        website = '$website', 
        kepala_sekolah = '$kepala_sekolah', 
        nip_kepala_sekolah = '$nip_kepala_sekolah', 
        tahun_ajaran = '$tahun_ajaran',
        updated_at = NOW()";
    
    // Cek apakah ada request untuk menghapus logo
    if (isset($_POST['remove_logo']) && $_POST['remove_logo'] == 'on') {
        // Hapus file logo jika ada
        if ($school_data['logo'] && file_exists("../../assets/img/" . $school_data['logo'])) {
            unlink("../../assets/img/" . $school_data['logo']);
        }
        $update_query .= ", logo = ''";
    } 
    // Cek apakah ada upload logo baru
    elseif ($_FILES['logo']['name'] !== '') {
        $upload_result = uploadLogo($_FILES['logo']);
        if ($upload_result[0]) {
            // Hapus file logo lama jika ada
            if ($school_data['logo'] && file_exists("../../assets/img/" . $school_data['logo'])) {
                unlink("../../assets/img/" . $school_data['logo']);
            }
            $update_query .= ", logo = '" . $upload_result[1] . "'";
        } else {
            $error_message = $upload_result[1];
        }
    }
    
    if (empty($error_message)) {
        $update_query .= " WHERE id = " . $school_data['id'];
        
        if (mysqli_query($conn, $update_query)) {
            $success_message = "Data profil sekolah berhasil diperbarui!";
            
            // Catat aktivitas di log
            $aktivitas = "Memperbarui data profil sekolah";
            mysqli_query($conn, "INSERT INTO log_aktivitas (user_id, aktivitas) VALUES ('$user_id', '$aktivitas')");
            
            // Refresh data sekolah
            $query_school = "SELECT * FROM settings ORDER BY id DESC LIMIT 1";
            $result_school = mysqli_query($conn, $query_school);
            $school_data = mysqli_fetch_assoc($result_school);
        } else {
            $error_message = "Error: " . mysqli_error($conn);
        }
    }
}
?>