<?php
ob_start();
require_once '../include/header.php';

require_once '../../database/config.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login/login.php");
    exit();
}

// Cek apakah ada ID siswa yang diberikan
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "ID siswa tidak valid";
    header("Location: index.php");
    exit();
}

$id = mysqli_real_escape_string($conn, $_GET['id']);

// Cek apakah siswa ada di database
$check_query = "SELECT * FROM siswa WHERE id = '$id'";
$check_result = mysqli_query($conn, $check_query);

if (mysqli_num_rows($check_result) == 0) {
    $_SESSION['error'] = "Siswa tidak ditemukan";
    header("Location: index.php");
    exit();
}

$siswa = mysqli_fetch_assoc($check_result);

// Jika form konfirmasi disubmit
if (isset($_POST['confirm_delete'])) {
    // Hapus data siswa dari database
    // Check foreign key constraints dulu
    
    // Cek apakah ada data di tabel nilai
    $check_nilai = mysqli_query($conn, "SELECT COUNT(*) as total FROM nilai WHERE siswa_id = '$id'");
    $nilai_count = mysqli_fetch_assoc($check_nilai)['total'];
    
    // Cek apakah ada data di tabel test_iq
    $check_iq = mysqli_query($conn, "SELECT COUNT(*) as total FROM test_iq WHERE siswa_id = '$id'");
    $iq_count = mysqli_fetch_assoc($check_iq)['total'];
    
    // Cek apakah ada data di tabel minat_siswa
    $check_minat = mysqli_query($conn, "SELECT COUNT(*) as total FROM minat_siswa WHERE siswa_id = '$id'");
    $minat_count = mysqli_fetch_assoc($check_minat)['total'];
    
    // Cek apakah ada data di tabel penjurusan
    $check_penjurusan = mysqli_query($conn, "SELECT COUNT(*) as total FROM penjurusan WHERE siswa_id = '$id'");
    $penjurusan_count = mysqli_fetch_assoc($check_penjurusan)['total'];
    
    // Jika ada data terkait, tampilkan pesan
    if ($nilai_count > 0 || $iq_count > 0 || $minat_count > 0 || $penjurusan_count > 0) {
        $_SESSION['error'] = "Tidak dapat menghapus siswa karena masih memiliki data terkait. Hapus data nilai, test IQ, minat, dan penjurusan terlebih dahulu.";
        header("Location: index.php");
        exit();
    }
    
    // Jika tidak ada data terkait, lakukan penghapusan
    $delete_query = "DELETE FROM siswa WHERE id = '$id'";
    $delete_result = mysqli_query($conn, $delete_query);
    
    if ($delete_result) {
        // Log aktivitas penghapusan
        $user_id = $_SESSION['user_id'];
        $log_query = "INSERT INTO log_aktivitas (user_id, aktivitas, keterangan, ip_address) 
                     VALUES ('$user_id', 'Menghapus data siswa', 'Nama: ".$siswa['nama_lengkap'].", NIS: ".$siswa['nis']."', '".$_SERVER['REMOTE_ADDR']."')";
        mysqli_query($conn, $log_query);
        
        $_SESSION['success'] = "Data siswa berhasil dihapus";
    } else {
        $_SESSION['error'] = "Gagal menghapus data siswa: " . mysqli_error($conn);
    }
    
    header("Location: index.php");
    exit();
}

// Jika user cancel
if (isset($_POST['cancel'])) {
    header("Location: index.php");
    exit();
}
?>


<div class="container-fluid">
    <div class="row">
        <?php require_once '../include/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4 content-wrapper">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h2 class="h3">
                    <i class="fas fa-user-minus me-2"></i>Hapus Data Siswa
                </h2>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="index.php" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            </div>
            
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">Konfirmasi Penghapusan</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Perhatian!</strong> Data yang sudah dihapus tidak dapat dikembalikan.
                    </div>
                    
                    <p>Anda akan menghapus data siswa dengan informasi sebagai berikut:</p>
                    
                    <table class="table table-bordered mt-3">
                        <tr>
                            <th width="30%">NIS</th>
                            <td><?php echo $siswa['nis']; ?></td>
                        </tr>
                        <tr>
                            <th>NISN</th>
                            <td><?php echo $siswa['nisn']; ?></td>
                        </tr>
                        <tr>
                            <th>Nama Lengkap</th>
                            <td><?php echo $siswa['nama_lengkap']; ?></td>
                        </tr>
                        <tr>
                            <th>Jenis Kelamin</th>
                            <td><?php echo ($siswa['jenis_kelamin'] == 'L') ? 'Laki-laki' : 'Perempuan'; ?></td>
                        </tr>
                    </table>
                    
                    <form action="" method="POST" class="mt-4">
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" name="cancel" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i> Batal
                            </button>
                            <button type="submit" name="confirm_delete" class="btn btn-danger">
                                <i class="fas fa-trash me-1"></i> Hapus Data
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>


<?php require_once '../include/footer.php'; 
ob_end_flush();
?>