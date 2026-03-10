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

// Ambil data nilai yang akan diedit
$query_nilai = "SELECT n.*, s.nama_lengkap, s.nis, mp.nama as mapel_nama, mp.kode as mapel_kode 
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

// Proses form jika ada data yang dikirim
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nilai_baru = $_POST['nilai'];
    $tahun_ajaran = $_POST['tahun_ajaran'];
    $user_id = $_SESSION['user_id'];
    
    // Validasi input
    $errors = [];
    
    if (!is_numeric($nilai_baru) || $nilai_baru < 0 || $nilai_baru > 100) {
        $errors[] = "Nilai harus berupa angka antara 0-100";
    }
    
    if (empty($tahun_ajaran)) {
        $errors[] = "Tahun ajaran harus diisi";
    }
    
    // Jika tidak ada error
    if (empty($errors)) {
        // Cek apakah terjadi perubahan data
        $is_changed = ($nilai_baru != $data_nilai['nilai'] || $tahun_ajaran != $data_nilai['tahun_ajaran']);
        
        if ($is_changed) {
            // Update data nilai
            $query = "UPDATE nilai SET nilai = '$nilai_baru', tahun_ajaran = '$tahun_ajaran' WHERE id = '$nilai_id'";
            
            if (mysqli_query($conn, $query)) {
                // Log aktivitas
                $aktivitas = "Mengubah data nilai siswa: {$data_nilai['nama_lengkap']} untuk mata pelajaran {$data_nilai['mapel_nama']}";
                $query_log = "INSERT INTO log_aktivitas (user_id, aktivitas, ip_address) VALUES ('$user_id', '$aktivitas', '{$_SERVER['REMOTE_ADDR']}')";
                mysqli_query($conn, $query_log);
                
                $_SESSION['success'] = "Data nilai berhasil diperbarui";
                header("Location: index.php");
                exit;
            } else {
                $_SESSION['error'] = "Gagal memperbarui data nilai: " . mysqli_error($conn);
            }
        } else {
            $_SESSION['info'] = "Tidak ada perubahan data";
            header("Location: index.php");
            exit;
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
                    <i class="fas fa-edit me-2"></i>Edit Data Nilai
                </h2>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="index.php" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <div class="card shadow-sm">
                <div class="card-body">
                    <form action="" method="POST">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Siswa</label>
                                <input type="text" class="form-control" value="<?php echo $data_nilai['nis'] . ' - ' . $data_nilai['nama_lengkap']; ?>" readonly>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Mata Pelajaran</label>
                                <input type="text" class="form-control" value="<?php echo $data_nilai['mapel_kode'] . ' - ' . $data_nilai['mapel_nama']; ?>" readonly>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="nilai" class="form-label">Nilai</label>
                                <input type="number" class="form-control" id="nilai" name="nilai" value="<?php echo $data_nilai['nilai']; ?>" min="0" max="100" step="0.01" required>
                                <div class="form-text">Masukkan nilai antara 0-100</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="tahun_ajaran" class="form-label">Tahun Ajaran</label>
                                <input type="text" class="form-control" id="tahun_ajaran" name="tahun_ajaran" value="<?php echo $data_nilai['tahun_ajaran']; ?>" required>
                                <div class="form-text">Format: YYYY/YYYY (contoh: 2024/2025)</div>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Simpan Perubahan
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
                        <h5 class="mb-0">Riwayat Perubahan</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        // Ambil riwayat perubahan nilai dari log aktivitas
                        $query_log = "SELECT la.*, u.nama_lengkap as user_nama 
                                     FROM log_aktivitas la 
                                     JOIN users u ON la.user_id = u.id 
                                     WHERE la.aktivitas LIKE '%{$data_nilai['nama_lengkap']}%' 
                                     AND la.aktivitas LIKE '%{$data_nilai['mapel_nama']}%' 
                                     ORDER BY la.created_at DESC 
                                     LIMIT 5";
                        $result_log = mysqli_query($conn, $query_log);
                        
                        if (mysqli_num_rows($result_log) > 0):
                        ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Waktu</th>
                                            <th>User</th>
                                            <th>Aktivitas</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($log = mysqli_fetch_assoc($result_log)): ?>
                                            <tr>
                                                <td><?php echo date('d/m/Y H:i', strtotime($log['created_at'])); ?></td>
                                                <td><?php echo $log['user_nama']; ?></td>
                                                <td><?php echo $log['aktivitas']; ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted mb-0">Belum ada riwayat perubahan untuk data nilai ini</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validasi format tahun ajaran
    document.querySelector('form').addEventListener('submit', function(event) {
        const tahunAjaran = document.getElementById('tahun_ajaran').value;
        const pattern = /^\d{4}\/\d{4}$/;
        
        if (!pattern.test(tahunAjaran)) {
            event.preventDefault();
            alert('Format tahun ajaran harus YYYY/YYYY (contoh: 2024/2025)');
        }
    });
});
</script>

<?php require_once '../include/footer.php'; 
ob_end_flush();
?>
