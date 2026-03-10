<?php
// Pastikan tidak ada whitespace sebelum tag PHP pembuka
// Pastikan session_start() dipanggil sebelum output apapun
session_start();
require_once '../../database/config.php';

// Cek autentikasi user
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login/login.php");
    exit;
}

// Cek hak akses user
if ($_SESSION['role'] != 'staff_bk') {
    $_SESSION['message'] = "Anda tidak memiliki akses untuk mengubah data minat siswa";
    header("Location: index.php");
    exit;
}

// Cek parameter id
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['message'] = "ID minat siswa tidak valid";
    header("Location: index.php");
    exit;
}

$id = mysqli_real_escape_string($conn, $_GET['id']);

// Ambil data minat siswa
$query = "SELECT ms.*, s.nis, s.nama_lengkap, k.nama_kelas 
          FROM minat_siswa ms 
          JOIN siswa s ON ms.siswa_id = s.id 
          LEFT JOIN kelas k ON s.kelas_id = k.id 
          WHERE ms.id = '$id'";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
    $_SESSION['message'] = "Data minat siswa tidak ditemukan";
    header("Location: index.php");
    exit;
}

$data = mysqli_fetch_assoc($result);

// Proses form jika disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $minat = mysqli_real_escape_string($conn, $_POST['minat']);
    
    // Validasi input
    $errors = [];
    
    if (empty($minat)) {
        $errors[] = "Minat jurusan harus dipilih";
    }
    
    // Jika tidak ada error, update data
    if (empty($errors)) {
        $query = "UPDATE minat_siswa 
                  SET minat = '$minat', 
                      updated_at = CURRENT_TIMESTAMP 
                  WHERE id = '$id'";
        
        if (mysqli_query($conn, $query)) {
            // Log aktivitas
            $user_id = $_SESSION['user_id'];
            $aktivitas = "Mengubah data minat siswa " . $data['nama_lengkap'];
            $log_query = "INSERT INTO log_aktivitas (user_id, aktivitas, ip_address) 
                         VALUES ('$user_id', '$aktivitas', '".$_SERVER['REMOTE_ADDR']."')";
            mysqli_query($conn, $log_query);
            
            $_SESSION['message'] = "Data minat siswa berhasil diperbarui";
            header("Location: index.php");
            exit;
        } else {
            $errors[] = "Gagal memperbarui data: " . mysqli_error($conn);
        }
    }
}

// Sekarang kita bisa memasukkan header HTML
require_once '../include/header.php';
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
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../dashboard/index.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="index.php">Minat Siswa</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Edit Data</li>
                </ol>
            </nav>
            
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h2 class="h3">
                    <i class="fas fa-edit me-2"></i>Edit Data Minat Siswa
                </h2>
            </div>
            
            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?= $error ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-lg-8">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Form Edit Minat Siswa</h5>
                        </div>
                        <div class="card-body">
                            <form action="" method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Siswa</label>
                                    <input type="text" class="form-control" value="<?= $data['nis'] ?> - <?= htmlspecialchars($data['nama_lengkap']) ?> (<?= htmlspecialchars($data['nama_kelas'] ?? 'Belum ada kelas') ?>)" disabled>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="minat" class="form-label">Minat Jurusan <span class="text-danger">*</span></label>
                                    <div class="mt-2">
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="minat" id="minat_ipa" value="IPA" <?= (isset($_POST['minat']) ? $_POST['minat'] : $data['minat']) == 'IPA' ? 'checked' : '' ?> required>
                                            <label class="form-check-label" for="minat_ipa">IPA</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="minat" id="minat_ips" value="IPS" <?= (isset($_POST['minat']) ? $_POST['minat'] : $data['minat']) == 'IPS' ? 'checked' : '' ?> required>
                                            <label class="form-check-label" for="minat_ips">IPS</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <a href="index.php" class="btn btn-secondary me-md-2">
                                        <i class="fas fa-arrow-left me-1"></i>Batal
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i>Simpan Perubahan
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="fas fa-history me-2"></i>Riwayat
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="mb-1"><strong>Tanggal Input:</strong></p>
                            <p><?= date('d F Y H:i', strtotime($data['created_at'])) ?></p>
                            
                            <p class="mb-1"><strong>Terakhir Diperbarui:</strong></p>
                            <p><?= date('d F Y H:i', strtotime($data['updated_at'])) ?></p>
                        </div>
                    </div>
                    
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="fas fa-info-circle me-2"></i>Bantuan
                            </h5>
                        </div>
                        <div class="card-body">
                            <p>Data minat siswa digunakan sebagai salah satu atribut penting dalam algoritma C4.5 untuk menentukan penjurusan siswa. Pilihlah jurusan yang paling diminati oleh siswa berdasarkan hasil angket atau wawancara.</p>
                            
                            <p class="mb-0 small text-muted">Perubahan data minat siswa akan dicatat dalam log aktivitas sistem.</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once '../include/footer.php'; ?>