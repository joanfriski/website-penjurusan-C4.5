<?php
// Aktifkan output buffering di awal sekali
ob_start();

// Mencegah caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Memuat file header dari parent directory
require_once '../include/header.php';

// Pastikan pengguna sudah login dan memiliki hak akses
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'staff_bk') {
    // Redirect ke halaman login jika belum login atau tidak memiliki hak akses
    header("Location: ../../login/login.php");
    exit();
}

// Ambil daftar siswa yang belum memiliki data test IQ
$query_siswa = "SELECT s.id, s.nis, s.nama_lengkap, k.nama_kelas 
                FROM siswa s 
                LEFT JOIN kelas k ON s.kelas_id = k.id 
                WHERE s.id NOT IN (SELECT siswa_id FROM test_iq) 
                AND s.status = 'aktif'
                ORDER BY s.nama_lengkap ASC";
$result_siswa = mysqli_query($conn, $query_siswa);

// Ambil juga daftar semua siswa untuk dropdown
$query_all_siswa = "SELECT s.id, s.nis, s.nama_lengkap, k.nama_kelas 
                    FROM siswa s 
                    LEFT JOIN kelas k ON s.kelas_id = k.id 
                    WHERE s.status = 'aktif'
                    ORDER BY s.nama_lengkap ASC";
$result_all_siswa = mysqli_query($conn, $query_all_siswa);

// Inisialisasi variabel
$error = '';
$success = '';

// Jika form disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil data dari form
    $siswa_id = $_POST['siswa_id'];
    $skor = $_POST['skor'];
    $tanggal_test = $_POST['tanggal_test'];
    $keterangan = $_POST['keterangan'];
    
    // Validasi data
    if (empty($siswa_id) || empty($skor) || empty($tanggal_test)) {
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
        
        // Cek apakah siswa sudah memiliki data test IQ
        $check_query = "SELECT id FROM test_iq WHERE siswa_id = $siswa_id";
        $check_result = mysqli_query($conn, $check_query);
        
        if (mysqli_num_rows($check_result) > 0) {
            // Update data yang sudah ada
            $update_query = "UPDATE test_iq 
                            SET skor = $skor, 
                                kategori = '$kategori', 
                                tanggal_test = '$tanggal_test', 
                                keterangan = '$keterangan', 
                                updated_at = NOW() 
                            WHERE siswa_id = $siswa_id";
            $update_result = mysqli_query($conn, $update_query);
            
            if ($update_result) {
                // Catat aktivitas
                $aktivitas = "Memperbarui data test IQ siswa";
                $log_query = "INSERT INTO log_aktivitas (user_id, aktivitas, keterangan, ip_address) 
                             VALUES ('{$_SESSION['user_id']}', '$aktivitas', 'Memperbarui data test IQ untuk siswa dengan ID: $siswa_id', '{$_SERVER['REMOTE_ADDR']}')";
                mysqli_query($conn, $log_query);
                
                $success = "Data test IQ berhasil diperbarui!";
                
                // Set notifikasi untuk halaman index
                $_SESSION['notification'] = "Data test IQ berhasil diperbarui!";
                
                // Gunakan javascript untuk redirect karena header sudah dikirim
                echo "<script>window.location.href = 'index.php?refresh=" . time() . "';</script>";
                exit();
            } else {
                $error = "Gagal memperbarui data test IQ! Error: " . mysqli_error($conn);
            }
        } else {
            // Insert data baru
            $insert_query = "INSERT INTO test_iq (siswa_id, skor, kategori, tanggal_test, keterangan, created_at, updated_at) 
                           VALUES ($siswa_id, $skor, '$kategori', '$tanggal_test', '$keterangan', NOW(), NOW())";
            $insert_result = mysqli_query($conn, $insert_query);
            
            if ($insert_result) {
                // Catat aktivitas
                $aktivitas = "Menambahkan data test IQ siswa";
                $log_query = "INSERT INTO log_aktivitas (user_id, aktivitas, keterangan, ip_address) 
                             VALUES ('{$_SESSION['user_id']}', '$aktivitas', 'Menambahkan data test IQ untuk siswa dengan ID: $siswa_id', '{$_SERVER['REMOTE_ADDR']}')";
                mysqli_query($conn, $log_query);
                
                $success = "Data test IQ berhasil ditambahkan!";
                
                // Set notifikasi untuk halaman index
                $_SESSION['notification'] = "Data test IQ berhasil ditambahkan!";
                
                // Gunakan javascript untuk redirect karena header sudah dikirim
                echo "<script>window.location.href = 'index.php?refresh=" . time() . "';</script>";
                exit();
            } else {
                $error = "Gagal menambahkan data test IQ! Error: " . mysqli_error($conn);
            }
        }
    }
    
    // Refresh query setelah operasi database
    $result_siswa = mysqli_query($conn, $query_siswa);
    $result_all_siswa = mysqli_query($conn, $query_all_siswa);
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
                    <i class="fas fa-brain me-2"></i>Tambah Data Test IQ
                </h2>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="index.php?refresh=<?php echo time(); ?>" class="btn btn-sm btn-outline-secondary">
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
            
            <div class="card shadow-sm">
                <div class="card-body">
                    <form action="tambah.php?refresh=<?php echo time(); ?>" method="post">
                        <div class="mb-3">
                            <label for="siswa_id" class="form-label">Siswa</label>
                            <select name="siswa_id" id="siswa_id" class="form-select" required>
                                <option value="">-- Pilih Siswa --</option>
                                <?php
                                // Refresh query tepat sebelum menampilkan dropdown
                                $result_siswa = mysqli_query($conn, $query_siswa);
                                
                                // Tampilkan siswa yang belum memiliki data test IQ
                                if (mysqli_num_rows($result_siswa) > 0) {
                                    echo "<optgroup label='Siswa Belum Memiliki Data Test IQ'>";
                                    while ($row = mysqli_fetch_assoc($result_siswa)) {
                                        echo "<option value='" . $row['id'] . "'>" . $row['nama_lengkap'] . " (" . $row['nis'] . ") - " . ($row['nama_kelas'] ?? 'Belum ditentukan') . "</option>";
                                    }
                                    echo "</optgroup>";
                                }
                                
                                // Refresh query untuk semua siswa
                                $result_all_siswa = mysqli_query($conn, $query_all_siswa);
                                
                                // Tampilkan juga daftar semua siswa
                                if (mysqli_num_rows($result_all_siswa) > 0) {
                                    echo "<optgroup label='Semua Siswa'>";
                                    while ($row = mysqli_fetch_assoc($result_all_siswa)) {
                                        echo "<option value='" . $row['id'] . "'>" . $row['nama_lengkap'] . " (" . $row['nis'] . ") - " . ($row['nama_kelas'] ?? 'Belum ditentukan') . "</option>";
                                    }
                                    echo "</optgroup>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="skor" class="form-label">Skor IQ</label>
                            <input type="number" class="form-control" id="skor" name="skor" min="0" max="200" required>
                            <div class="form-text">Masukkan skor IQ antara 0-200</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="tanggal_test" class="form-label">Tanggal Test</label>
                            <input type="date" class="form-control" id="tanggal_test" name="tanggal_test" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="keterangan" class="form-label">Keterangan</label>
                            <textarea class="form-control" id="keterangan" name="keterangan" rows="3"></textarea>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Simpan Data
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
                                <li>Pilih siswa dari dropdown</li>
                                <li>Masukkan skor IQ (hasil test)</li>
                                <li>Masukkan tanggal pelaksanaan test</li>
                                <li>Berikan keterangan jika diperlukan</li>
                                <li>Klik tombol 'Simpan Data'</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
    // Inisialisasi Select2 untuk dropdown siswa
    $(document).ready(function() {
        $('#siswa_id').select2({
            theme: 'bootstrap-5',
            placeholder: 'Pilih Siswa',
            allowClear: true
        });
        
        // Jika ada parameter refresh di URL, refresh Select2
        if(window.location.href.indexOf('refresh') > -1) {
            $('#siswa_id').trigger('change');
        }
    });
</script>

<?php 
// Flush output buffer and send content to browser
ob_end_flush();
require_once '../include/footer.php'; 
?>