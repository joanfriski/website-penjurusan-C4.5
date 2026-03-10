<?php
ob_start();
require_once '../../database/config.php';
require_once '../include/header.php';


// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login/login.php");
    exit;
}

// Periksa apakah ID nilai ada di parameter URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "ID nilai tidak valid";
    header("Location: index.php");
    exit;
}

$nilai_id = mysqli_real_escape_string($conn, $_GET['id']);

// Ambil data nilai yang akan dihapus untuk log aktivitas
$query_nilai = "SELECT n.*, s.nama_lengkap, mp.nama as mapel_nama 
               FROM nilai n 
               JOIN siswa s ON n.siswa_id = s.id 
               JOIN mata_pelajaran mp ON n.mapel_id = mp.id 
               WHERE n.id = '$nilai_id'";
$result_nilai = mysqli_query($conn, $query_nilai);

if (mysqli_num_rows($result_nilai) == 0) {
    $_SESSION['error'] = "Data nilai tidak ditemukan";
    header("Location: index.php");
    exit;
}

$data_nilai = mysqli_fetch_assoc($result_nilai);

// Proses konfirmasi hapus
if (isset($_POST['konfirmasi_hapus']) && $_POST['konfirmasi_hapus'] == 'ya') {
    $user_id = $_SESSION['user_id'];
    
    // Hapus data nilai
    $query_delete = "DELETE FROM nilai WHERE id = '$nilai_id'";
    
    if (mysqli_query($conn, $query_delete)) {
        // Log aktivitas
        $aktivitas = "Menghapus nilai siswa: {$data_nilai['nama_lengkap']} untuk mata pelajaran {$data_nilai['mapel_nama']} (tahun ajaran: {$data_nilai['tahun_ajaran']})";
        $query_log = "INSERT INTO log_aktivitas (user_id, aktivitas, ip_address) VALUES ('$user_id', '$aktivitas', '{$_SERVER['REMOTE_ADDR']}')";
        mysqli_query($conn, $query_log);
        
        $_SESSION['success'] = "Data nilai berhasil dihapus";
    } else {
        $_SESSION['error'] = "Gagal menghapus data nilai: " . mysqli_error($conn);
    }
    
    header("Location: index.php");
    exit;
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
                    <i class="fas fa-trash me-2"></i>Hapus Data Nilai
                </h2>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="index.php" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            </div>
            
            <div class="alert alert-danger mb-4">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Perhatian!</strong> Data yang sudah dihapus tidak dapat dikembalikan.
            </div>
            
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-4">Konfirmasi Penghapusan Data</h5>
                    
                    <p>Anda akan menghapus data nilai dengan detail sebagai berikut:</p>
                    
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered">
                            <tr>
                                <th width="30%">Siswa</th>
                                <td><?php echo $data_nilai['nama_lengkap']; ?></td>
                            </tr>
                            <tr>
                                <th>Mata Pelajaran</th>
                                <td><?php echo $data_nilai['mapel_nama']; ?></td>
                            </tr>
                            <tr>
                                <th>Nilai</th>
                                <td><?php echo $data_nilai['nilai']; ?></td>
                            </tr>
                            <tr>
                                <th>Tahun Ajaran</th>
                                <td><?php echo $data_nilai['tahun_ajaran']; ?></td>
                            </tr>
                        </table>
                    </div>
                    
                    <form action="" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data nilai ini?');">
                        <input type="hidden" name="konfirmasi_hapus" value="ya">
                        
                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash me-1"></i> Hapus Data
                            </button>
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i> Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="mt-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Petunjuk</h5>
                    </div>
                    <div class="card-body">
                        <ul class="mb-0">
                            <li>Tindakan ini akan menghapus data nilai secara permanen dari database</li>
                            <li>Penghapusan data nilai tidak akan mempengaruhi data siswa atau mata pelajaran</li>
                            <li>Sistem akan menyimpan log aktivitas penghapusan data</li>
                            <li>Jika Anda hanya ingin mengubah nilai, gunakan fitur Edit Nilai</li>
                        </ul>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>


<?php require_once '../include/footer.php'; 
ob_end_flush();
?>

