<?php
ob_start();
require_once '../../database/config.php';
require_once '../include/header.php';


// Pastikan pengguna sudah login dan memiliki hak akses
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'staff_bk') {
    // Redirect ke halaman login jika belum login atau tidak memiliki hak akses
    header("Location: ../../login/login.php");
    exit();
}

// Pastikan parameter id ada
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Redirect ke halaman index jika tidak ada id
    header("Location: index.php");
    exit();
}

$id = $_GET['id'];

// Ambil data test IQ berdasarkan id
$query = "SELECT t.*, s.nis, s.nama_lengkap, k.nama_kelas 
          FROM test_iq t 
          JOIN siswa s ON t.siswa_id = s.id 
          LEFT JOIN kelas k ON s.kelas_id = k.id 
          WHERE t.id = $id";
$result = mysqli_query($conn, $query);

// Jika data tidak ditemukan
if (mysqli_num_rows($result) == 0) {
    $_SESSION['notification'] = "Data test IQ tidak ditemukan!";
    header("Location: index.php");
    exit();
}

$row = mysqli_fetch_assoc($result);

// Inisialisasi variabel
$error = '';
$success = '';

// Jika form disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil data dari form
    $skor = $_POST['skor'];
    $tanggal_test = $_POST['tanggal_test'];
    $keterangan = $_POST['keterangan'];
    
    // Validasi data
    if (empty($skor) || empty($tanggal_test)) {
        $error = "Semua field wajib diisi kecuali keterangan!";
    } else {
        // Tentukan kategori berdasarkan skor IQ
        if ($skor >= 115) {
            $kategori = "Tinggi";
        } elseif ($skor >= 90) {
            $kategori = "Sedang";
        } else {
            $kategori = "Rendah";
        }
        
        // Update data test IQ
        $update_query = "UPDATE test_iq 
                        SET skor = $skor, 
                            kategori = '$kategori', 
                            tanggal_test = '$tanggal_test', 
                            keterangan = '$keterangan', 
                            updated_at = NOW() 
                        WHERE id = $id";
        $update_result = mysqli_query($conn, $update_query);
        
        if ($update_result) {
            // Catat aktivitas
            $aktivitas = "Mengedit data test IQ siswa";
            $log_query = "INSERT INTO log_aktivitas (user_id, aktivitas, keterangan, ip_address) 
                         VALUES ('{$_SESSION['user_id']}', '$aktivitas', 'Mengedit data test IQ untuk siswa: {$row['nama_lengkap']}', '{$_SERVER['REMOTE_ADDR']}')";
            mysqli_query($conn, $log_query);
            
            $success = "Data test IQ berhasil diperbarui!";
            
            // Set notifikasi untuk halaman index
            $_SESSION['notification'] = "Data test IQ berhasil diperbarui!";
            header("Location: index.php");
            exit();
        } else {
            $error = "Gagal memperbarui data test IQ! Error: " . mysqli_error($conn);
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
                    <i class="fas fa-brain me-2"></i>Edit Data Test IQ
                </h2>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="index.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Kembali
                        </a>
                    </div>
                </div>
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-user me-2"></i>Informasi Siswa
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="150">NIS</th>
                                    <td>: <?php echo $row['nis']; ?></td>
                                </tr>
                                <tr>
                                    <th>Nama Lengkap</th>
                                    <td>: <?php echo $row['nama_lengkap']; ?></td>
                                </tr>
                                <tr>
                                    <th>Kelas</th>
                                    <td>: <?php echo $row['nama_kelas'] ?? 'Belum ditentukan'; ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card shadow-sm">
                <div class="card-body">
                    <form action="edit.php?id=<?php echo $id; ?>" method="post">
                        <div class="mb-3">
                            <label for="skor" class="form-label">Skor IQ</label>
                            <input type="number" class="form-control" id="skor" name="skor" min="0" max="200" value="<?php echo $row['skor']; ?>" required>
                            <div class="form-text">Masukkan skor IQ antara 0-200</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="tanggal_test" class="form-label">Tanggal Test</label>
                            <input type="date" class="form-control" id="tanggal_test" name="tanggal_test" value="<?php echo $row['tanggal_test']; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="keterangan" class="form-label">Keterangan</label>
                            <textarea class="form-control" id="keterangan" name="keterangan" rows="3"><?php echo $row['keterangan']; ?></textarea>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>Informasi
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="fas fa-list-ol me-2"></i>Kategori IQ:</h6>
                            <ul>
                                <li><strong>Tinggi</strong>: Skor IQ >= 115</li>
                                <li><strong>Sedang</strong>: Skor IQ 90-114</li>
                                <li><strong>Rendah</strong>: Skor IQ < 90</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-lightbulb me-2"></i>Petunjuk Pengisian:</h6>
                            <ol>
                                <li>Perbarui skor IQ (hasil test)</li>
                                <li>Perbarui tanggal pelaksanaan test jika diperlukan</li>
                                <li>Berikan keterangan jika diperlukan</li>
                                <li>Klik tombol 'Simpan Perubahan'</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>


<?php require_once '../include/footer.php'; 
ob_end_flush();
?>

