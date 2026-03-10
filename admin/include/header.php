<?php
// Check if session is already started before calling session_start()
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../../database/config.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'staff_bk') {
    header("Location: ../../login/login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Penjurusan - SMAN 1 Ciwaru</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../assets/css/style.css">

    <!-- Di file header.php, tambahkan sebelum tag </head> -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <!-- Di file header.php, tambahkan setelah link CSS Select2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            padding-top: 56px; /* Ruang untuk fixed navbar */
        }
        
        /* Navbar styling */
        .navbar {
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            z-index: 1030;
        }

        
        .content-wrapper.expanded {
            margin-left: 0;
        }
        
        /* Navbar toggler */
        .sidebar-toggler {
            cursor: pointer;
            color: white;
            border: none;
            background: transparent;
            padding: 8px;
        }
        
        /* Mobile responsiveness */
        @media (max-width: 768px) {
            .content-wrapper {
                margin-left: 0;
            }
        }
        
        /* Dropdown menu improvements */
        .dropdown-menu {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        
        .dropdown-item {
            padding: 0.5rem 1.5rem;
        }
        
        .dropdown-item:hover {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
<!-- Navbar - Terintegrasi dalam header.php -->
<nav class="navbar navbar-expand-lg navbar-dark fixed-top" style="background: linear-gradient(90deg, #0d6efd 0%, #0a58ca 100%); box-shadow: 0 2px 5px rgba(0,0,0,0.1);">

    <div class="container-fluid">
        <button class="sidebar-toggler d-inline-block me-2" id="sidebarToggle">
            <i class="fas fa-bars fa-lg"></i>
        </button>
        <a class="navbar-brand" href="../dashboard/index.php">
            <i class="fas fa-school me-2"></i>SMA Ciwaru - Guru BK
        </a>
        
        <!-- Tombol navbar toggle - hanya muncul di mobile -->
        <div class="ms-auto d-flex align-items-center">
            <div class="dropdown user-dropdown">
                <a class="nav-link dropdown-toggle text-white" href="#" id="userDropdown" role="button" 
                    data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-user-circle me-1"></i>
                    <?php echo $_SESSION['nama_lengkap']; ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                    <li><a class="dropdown-item" href="../pengaturan/profil.php"><i class="fas fa-user-cog me-2"></i>Profil</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="../../login/proses_logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<!-- Tambahkan CSS ini di bagian <style> dalam header -->
<style>
    /* Pastikan dropdown user selalu tampil di kanan */
    .user-dropdown {
        position: relative;
    }
    
    /* Pada tampilan mobile */
    @media (max-width: 768px) {
        .navbar-brand {
            max-width: 60%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    }
</style>

<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>

<!-- Tambahkan fungsi helper untuk SweetAlert -->
<script>
// Fungsi untuk menampilkan alert sukses
function showSuccessAlert(title, message) {
    Swal.fire({
        icon: 'success',
        title: title,
        text: message,
        confirmButtonText: 'OK',
        confirmButtonColor: '#28a745'
    });
}

// Fungsi untuk menampilkan alert error
function showErrorAlert(title, message) {
    Swal.fire({
        icon: 'error',
        title: title,
        text: message,
        confirmButtonText: 'OK',
        confirmButtonColor: '#dc3545'
    });
}

// Fungsi untuk menampilkan alert warning
function showWarningAlert(title, message) {
    Swal.fire({
        icon: 'warning',
        title: title,
        text: message,
        confirmButtonText: 'OK',
        confirmButtonColor: '#ffc107'
    });
}

// Fungsi untuk menampilkan alert info
function showInfoAlert(title, message) {
    Swal.fire({
        icon: 'info',
        title: title,
        text: message,
        confirmButtonText: 'OK',
        confirmButtonColor: '#17a2b8'
    });
}

// Fungsi untuk konfirmasi
function showConfirmAlert(title, message, confirmCallback) {
    Swal.fire({
        icon: 'question',
        title: title,
        text: message,
        showCancelButton: true,
        confirmButtonText: 'Ya',
        cancelButtonText: 'Tidak',
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#dc3545'
    }).then((result) => {
        if (result.isConfirmed) {
            confirmCallback();
        }
    });
}

// Fungsi untuk menampilkan loading
function showLoadingAlert(title = 'Memproses...') {
    Swal.fire({
        title: title,
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
}

// Fungsi untuk menutup loading
function closeLoadingAlert() {
    Swal.close();
}
</script>