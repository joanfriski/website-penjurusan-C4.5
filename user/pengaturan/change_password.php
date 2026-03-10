<?php
// Proses ganti password
if (!isset($_SESSION)) {
    session_start();
}

if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validasi panjang password
    if (strlen($new_password) < 8) {
        $_SESSION['password_error'] = "Password minimal harus 8 karakter!";
    } 
    // Validasi kombinasi huruf dan angka
    elseif (!preg_match('/[A-Za-z]/', $new_password) || !preg_match('/[0-9]/', $new_password)) {
        $_SESSION['password_error'] = "Password harus mengandung kombinasi huruf dan angka!";
    }
    // Verifikasi password lama
    elseif (password_verify($current_password, $user['password'])) {
        // Cek konfirmasi password baru
        if ($new_password === $confirm_password) {
            // Hash password baru
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            
            $query_password = "UPDATE users SET password = '$password_hash' WHERE id = $user_id";
            
            if (mysqli_query($conn, $query_password)) {
                // Catat aktivitas di log
                $aktivitas = "Mengubah password";
                mysqli_query($conn, "INSERT INTO log_aktivitas (user_id, aktivitas, created_at) VALUES ('$user_id', '$aktivitas', NOW())");
                
                $_SESSION['password_success'] = "Password berhasil diubah!";
                
                // Redirect untuk mencegah resubmit form saat refresh
                header("Location: " . $_SERVER['PHP_SELF'] . "?tab=password");
                exit;
            } else {
                $_SESSION['password_error'] = "Gagal mengubah password: " . mysqli_error($conn);
            }
        } else {
            $_SESSION['password_error'] = "Konfirmasi password tidak cocok!";
        }
    } else {
        $_SESSION['password_error'] = "Password lama tidak valid!";
    }
    
    // Jika ada error, redirect dengan parameter tab
    if (isset($_SESSION['password_error'])) {
        header("Location: " . $_SERVER['PHP_SELF'] . "?tab=password");
        exit;
    }
}

// Menampilkan pesan sukses jika ada
if (isset($_SESSION['password_success'])) {
    $success_password = $_SESSION['password_success'];
    unset($_SESSION['password_success']);
}

// Menampilkan pesan error jika ada
if (isset($_SESSION['password_error'])) {
    $error_password = $_SESSION['password_error'];
    unset($_SESSION['password_error']);
}

// Set tab aktif berdasarkan parameter
if (isset($_GET['tab']) && $_GET['tab'] == 'password') {
    echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            document.getElementById("v-pills-password-tab").click();
        });
    </script>';
}
?>