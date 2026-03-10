<?php
// Start session first before any output
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if form is submitted
if (isset($_POST['update_profile'])) {
    // Get form data
    $nama_lengkap = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $no_telp = mysqli_real_escape_string($conn, $_POST['no_telp']);
    
    // Update user data
    $query_update = "UPDATE users SET 
                    nama_lengkap = '$nama_lengkap',
                    email = '$email',
                    no_telp = '$no_telp'
                    WHERE id = $user_id";
    
    if (mysqli_query($conn, $query_update)) {
        // Log activity
        $aktivitas = "Memperbarui data profil";
        mysqli_query($conn, "INSERT INTO log_aktivitas (user_id, aktivitas, created_at) VALUES ('$user_id', '$aktivitas', NOW())");
        
        // Store success message in session
        $_SESSION['profile_success'] = "Profil berhasil diperbarui!";
    } else {
        // Store error message in session
        $_SESSION['profile_error'] = "Gagal memperbarui profil: " . mysqli_error($conn);
    }
    
    // Use JavaScript for redirection instead of header()
    echo "<script>window.location.href = '" . $_SERVER['PHP_SELF'] . "';</script>";
    exit;
}

// Initialize message variables
$success_message = '';
$error_message = '';

// Get success message from session if exists
if (isset($_SESSION['profile_success'])) {
    $success_message = $_SESSION['profile_success'];
    unset($_SESSION['profile_success']);
}

// Get error message from session if exists
if (isset($_SESSION['profile_error'])) {
    $error_message = $_SESSION['profile_error'];
    unset($_SESSION['profile_error']);
}
?>