<?php
ob_start();
require_once '../../database/config.php';
require_once '../include/header.php';



// Cek apakah user memiliki akses
if ($_SESSION['role'] != 'staff_bk') {
    $_SESSION['message'] = 'Anda tidak memiliki akses untuk mengedit data kelas!';
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

// Ambil data kelas
$query = "SELECT * FROM kelas WHERE id = '$id'";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
    $_SESSION['message'] = 'Data kelas tidak ditemukan!';
    $_SESSION['message_type'] = 'danger';
    header('Location: index.php');
    exit();
}

$kelas = mysqli_fetch_assoc($result);

// Proses form jika disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_kelas = mysqli_real_escape_string($conn, $_POST['nama_kelas']);
    $tahun_ajaran = mysqli_real_escape_string($conn, $_POST['tahun_ajaran']);
    $wali_kelas = mysqli_real_escape_string($conn, $_POST['wali_kelas']);
    
    // Validasi input
    $errors = [];
    
    if (empty($nama_kelas)) {
        $errors[] = "Nama kelas tidak boleh kosong";
    }
    
    if (empty($tahun_ajaran)) {
        $errors[] = "Tahun ajaran tidak boleh kosong";
    }
    
    // Cek apakah kelas dengan nama dan tahun ajaran yang sama sudah ada (selain kelas ini)
    $check_query = "SELECT * FROM kelas WHERE nama_kelas = '$nama_kelas' AND tahun_ajaran = '$tahun_ajaran' AND id != '$id'";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        $errors[] = "Kelas dengan nama dan tahun ajaran yang sama sudah ada";
    }
    
    // Jika tidak ada error, update data
    if (empty($errors)) {
        $query = "UPDATE kelas SET nama_kelas = '$nama_kelas', tahun_ajaran = '$tahun_ajaran', wali_kelas = '$wali_kelas' WHERE id = '$id'";
        
        if (mysqli_query($conn, $query)) {
            // Log aktivitas
            $user_id = $_SESSION['user_id'];
            $aktivitas = "Mengedit data kelas: $nama_kelas ($tahun_ajaran)";
            mysqli_query($conn, "INSERT INTO log_aktivitas (user_id, aktivitas) VALUES ('$user_id', '$aktivitas')");
            
            $_SESSION['message'] = 'Data kelas berhasil diperbarui';
            $_SESSION['message_type'] = 'success';
            header('Location: index.php');
            exit();
        } else {
            $errors[] = "Error: " . mysqli_error($conn);
        }
    }
}
?>
<!-- CSS untuk perbaikan responsif content -->
<style>
    /* Base styling untuk content-wrapper */
    .content-wrapper {
        transition: all 0.3s ease;
        width: calc(100% - 250px);
        margin-left: 250px;
    }
    
    /* Saat sidebar collapsed */
    .content-wrapper.expanded {
        width: 100%;
        margin-left: 0;
    }
    
    /* Untuk perangkat mobile */
    @media (max-width: 768px) {
        .content-wrapper {
            width: 100%;
            margin-left: 0;
        }
    }
    
    /* Pastikan main content selalu mengikuti lebar yang tersedia */
    main {
        width: 100%;
        transition: all 0.3s ease;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <?php require_once '../include/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4 content-wrapper" id="content">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h2 class="h3">
                    <i class="fas fa-edit me-2"></i>Edit Kelas
                </h2>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="index.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Kembali
                        </a>
                    </div>
                </div>
            </div>
            
            <?php
            // Display errors if any
            if (!empty($errors)) {
                echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Error!</strong> 
                    <ul class="mb-0">';
                foreach ($errors as $error) {
                    echo '<li>' . $error . '</li>';
                }
                echo '</ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>';
            }
            ?>
            
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Form Edit Kelas</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="mb-3 row">
                            <label for="nama_kelas" class="col-sm-3 col-form-label">Nama Kelas <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" id="nama_kelas" name="nama_kelas" value="<?php echo htmlspecialchars($kelas['nama_kelas']); ?>" required>
                                <small class="text-muted">Contoh: X-1, XI IPA 2, XII IPS 1</small>
                            </div>
                        </div>
                        
                        <div class="mb-3 row">
                            <label for="tahun_ajaran" class="col-sm-3 col-form-label">Tahun Ajaran <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" id="tahun_ajaran" name="tahun_ajaran" value="<?php echo htmlspecialchars($kelas['tahun_ajaran']); ?>" required>
                                <small class="text-muted">Contoh: 2024/2025</small>
                            </div>
                        </div>
                        
                        <div class="mb-3 row">
                            <label for="wali_kelas" class="col-sm-3 col-form-label">Wali Kelas</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" id="wali_kelas" name="wali_kelas" value="<?php echo htmlspecialchars($kelas['wali_kelas']); ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-sm-9 offset-sm-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Simpan Perubahan
                                </button>
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-times me-1"></i> Batal
                                </a>
                            </div>
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

