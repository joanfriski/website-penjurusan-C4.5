<?php
// Check if session is already started before calling session_start()
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../../database/config.php';

// Cek autentikasi user
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login/login.php");
    exit;
}

// Cek hak akses user (contoh: hanya staff BK yang bisa tambah data)
if ($_SESSION['role'] != 'staff_bk') {
    $_SESSION['message'] = "Anda tidak memiliki akses untuk menambah data minat siswa";
    header("Location: index.php");
    exit;
}

// Mengambil daftar siswa yang belum memiliki data minat
$query_siswa = "SELECT s.id, s.nis, s.nama_lengkap, k.nama_kelas 
                FROM siswa s 
                LEFT JOIN kelas k ON s.kelas_id = k.id 
                WHERE s.id NOT IN (SELECT siswa_id FROM minat_siswa) 
                AND s.status = 'aktif'
                ORDER BY k.nama_kelas, s.nama_lengkap";
$result_siswa = mysqli_query($conn, $query_siswa);

// Proses form jika disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $siswa_id = mysqli_real_escape_string($conn, $_POST['siswa_id']);
    $minat = mysqli_real_escape_string($conn, $_POST['minat']);
    
    // Validasi input
    $errors = [];
    
    if (empty($siswa_id)) {
        $errors[] = "Siswa harus dipilih";
    }
    
    if (empty($minat)) {
        $errors[] = "Minat jurusan harus dipilih";
    }
    
    // Jika tidak ada error, simpan data
    if (empty($errors)) {
        $query = "INSERT INTO minat_siswa (siswa_id, minat) 
                  VALUES ('$siswa_id', '$minat')";
        
        if (mysqli_query($conn, $query)) {
            // Log aktivitas
            $user_id = $_SESSION['user_id'];
            $aktivitas = "Menambahkan data minat siswa";
            $log_query = "INSERT INTO log_aktivitas (user_id, aktivitas, ip_address) 
                         VALUES ('$user_id', '$aktivitas', '".$_SERVER['REMOTE_ADDR']."')";
            mysqli_query($conn, $log_query);
            
            $_SESSION['message'] = "Data minat siswa berhasil ditambahkan";
            header("Location: index.php");
            exit;
        } else {
            $errors[] = "Gagal menyimpan data: " . mysqli_error($conn);
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
                    <li class="breadcrumb-item active" aria-current="page">Tambah Data</li>
                </ol>
            </nav>
            
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h2 class="h3">
                    <i class="fas fa-plus-circle me-2"></i>Tambah Data Minat Siswa
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
                            <h5 class="mb-0">Form Tambah Minat Siswa</h5>
                        </div>
                        <div class="card-body">
                            <form action="" method="POST">
                                <div class="mb-3">
                                    <label for="siswa_id" class="form-label">Pilih Siswa <span class="text-danger">*</span></label>
                                    <select name="siswa_id" id="siswa_id" class="form-select" required>
                                        <option value="">-- Pilih Siswa --</option>
                                        <?php if (mysqli_num_rows($result_siswa) > 0): ?>
                                            <?php while($siswa = mysqli_fetch_assoc($result_siswa)): ?>
                                                <option value="<?= $siswa['id'] ?>" <?= isset($_POST['siswa_id']) && $_POST['siswa_id'] == $siswa['id'] ? 'selected' : '' ?>>
                                                    <?= $siswa['nis'] ?> - <?= htmlspecialchars($siswa['nama_lengkap']) ?> (<?= htmlspecialchars($siswa['nama_kelas'] ?? 'Belum ada kelas') ?>)
                                                </option>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <option disabled>Semua siswa sudah memiliki data minat</option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Minat Jurusan <span class="text-danger">*</span></label>
                                    <div class="mt-2">
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="minat" id="minat_ipa" value="IPA" <?= isset($_POST['minat']) && $_POST['minat'] == 'IPA' ? 'checked' : '' ?> required>
                                            <label class="form-check-label" for="minat_ipa">IPA</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="minat" id="minat_ips" value="IPS" <?= isset($_POST['minat']) && $_POST['minat'] == 'IPS' ? 'checked' : '' ?> required>
                                            <label class="form-check-label" for="minat_ips">IPS</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <a href="index.php" class="btn btn-secondary me-md-2">
                                        <i class="fas fa-arrow-left me-1"></i>Batal
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i>Simpan
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
                                <i class="fas fa-info-circle me-2"></i>Bantuan
                            </h5>
                        </div>
                        <div class="card-body">
                            <h6>Catatan:</h6>
                            <ul>
                                <li>Data minat siswa digunakan sebagai salah satu atribut penting dalam algoritma C4.5 untuk menentukan penjurusan siswa</li>
                                <li>Pastikan data minat diisi dengan benar sesuai hasil angket/wawancara dengan siswa</li>
                                <li>Setiap siswa hanya dapat memilih satu jurusan yang diminati (IPA atau IPS)</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once '../include/footer.php'; ?>