<?php
ob_start();
require_once '../include/header.php';

require_once '../../database/config.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login/login.php");
    exit();
}

// Ambil ID siswa dari parameter URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Jika tidak ada ID valid, redirect ke halaman index
if ($id <= 0) {
    $_SESSION['error'] = "ID siswa tidak valid";
    header("Location: index.php");
    exit();
}

// Ambil data siswa berdasarkan ID
$siswa_query = "SELECT * FROM siswa WHERE id = $id";
$siswa_result = mysqli_query($conn, $siswa_query);

// Jika siswa tidak ditemukan, redirect ke halaman index
if (mysqli_num_rows($siswa_result) == 0) {
    $_SESSION['error'] = "Data siswa tidak ditemukan";
    header("Location: index.php");
    exit();
}

$siswa = mysqli_fetch_assoc($siswa_result);

// Proses form jika ada submit
if (isset($_POST['submit'])) {
    // Ambil data dari form
    $nis = mysqli_real_escape_string($conn, $_POST['nis']);
    $nisn = mysqli_real_escape_string($conn, $_POST['nisn']);
    $nama_lengkap = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $jenis_kelamin = mysqli_real_escape_string($conn, $_POST['jenis_kelamin']);
    $tempat_lahir = mysqli_real_escape_string($conn, $_POST['tempat_lahir']);
    $tanggal_lahir = mysqli_real_escape_string($conn, $_POST['tanggal_lahir']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    $no_telp = mysqli_real_escape_string($conn, $_POST['no_telp']);
    $kelas_id = mysqli_real_escape_string($conn, $_POST['kelas_id']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    // Validasi data
    $errors = [];
    
    // Cek NIS unik (kecuali jika NIS tidak berubah)
    if ($nis != $siswa['nis']) {
        $check_nis = mysqli_query($conn, "SELECT * FROM siswa WHERE nis = '$nis' AND id != $id");
        if (mysqli_num_rows($check_nis) > 0) {
            $errors[] = "NIS sudah digunakan, silakan gunakan NIS lain.";
        }
    }
    
    // Cek NISN unik (kecuali jika NISN tidak berubah)
    if ($nisn != $siswa['nisn']) {
        $check_nisn = mysqli_query($conn, "SELECT * FROM siswa WHERE nisn = '$nisn' AND id != $id");
        if (mysqli_num_rows($check_nisn) > 0) {
            $errors[] = "NISN sudah digunakan, silakan gunakan NISN lain.";
        }
    }
    
    // Jika tidak ada error, proses update data
    if (empty($errors)) {
        $update_query = "UPDATE siswa SET 
                        nis = '$nis',
                        nisn = '$nisn',
                        nama_lengkap = '$nama_lengkap',
                        jenis_kelamin = '$jenis_kelamin',
                        tempat_lahir = '$tempat_lahir',
                        tanggal_lahir = '$tanggal_lahir',
                        alamat = '$alamat',
                        no_telp = '$no_telp',
                        kelas_id = '$kelas_id',
                        status = '$status'
                        WHERE id = $id";
        
        $result = mysqli_query($conn, $update_query);
        
        if ($result) {
            // Log aktivitas perubahan data siswa
            $user_id = $_SESSION['user_id'];
            $log_query = "INSERT INTO log_aktivitas (user_id, aktivitas, keterangan, ip_address) 
                         VALUES ('$user_id', 'Mengubah data siswa', 'Nama: $nama_lengkap, NIS: $nis', '".$_SERVER['REMOTE_ADDR']."')";
            mysqli_query($conn, $log_query);
            
            $_SESSION['success'] = "Data siswa berhasil diperbarui";
            header("Location: index.php");
            exit();
        } else {
            $_SESSION['error'] = "Gagal memperbarui data siswa: " . mysqli_error($conn);
        }
    }
}

// Query untuk mendapatkan daftar kelas
$kelas_query = "SELECT * FROM kelas ORDER BY nama_kelas ASC";
$kelas_result = mysqli_query($conn, $kelas_query);
?>

<!-- CSS untuk perbaikan tampilan -->
<style>
    /* Responsif content-wrapper */
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
    
    /* Styling untuk form */
    .form-control:focus, .form-select:focus {
        border-color: #86b7fe;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }
    
    .form-label {
        font-weight: 500;
        margin-bottom: 0.5rem;
    }
    
    /* Styling untuk kartu */
    .card {
        border-radius: 0.5rem;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }
    
    .card-header {
        border-bottom: 1px solid #ebedf2;
        padding: 1rem 1.25rem;
        background-color: #f8f9fa;
    }
    
    /* Animasi pada tombol */
    .btn {
        transition: all 0.3s;
    }
    
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    /* Styling untuk alert */
    .alert {
        border-radius: 0.5rem;
    }
    
    /* Form groups spacing */
    .row + .row, .mb-3 + .mb-3 {
        margin-top: 1.5rem;
    }
    
    /* Required field marker */
    .required-field::after {
        content: " *";
        color: #dc3545;
    }
    
    /* Form validation styling */
    .was-validated .form-control:invalid {
        border-color: #dc3545;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right calc(0.375em + 0.1875rem) center;
        background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
    }
    
    .was-validated .form-control:valid {
        border-color: #198754;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%23198754' d='M2.3 6.73L.6 4.53c-.4-1.04.46-1.4 1.1-.8l1.1 1.4 3.4-3.8c.6-.63 1.6-.27 1.2.7l-4 4.6c-.43.5-.8.4-1.1.1z'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right calc(0.375em + 0.1875rem) center;
        background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
    }
</style>

<div class="container-fluid">
    <div class="row">
        <?php require_once '../include/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4 content-wrapper" id="content">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h2 class="h3">
                    <i class="fas fa-user-edit me-2 text-primary"></i>Edit Siswa
                </h2>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="index.php" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            </div>
            
            <?php if(isset($errors) && !empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Error!</strong> Ada beberapa masalah dengan input Anda:
                <ul class="mb-0 mt-2">
                    <?php foreach($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-wpforms me-2 text-primary"></i>Form Edit Siswa
                    </h5>
                    <small class="text-muted">Silakan ubah data siswa dengan lengkap dan benar</small>
                </div>
                <div class="card-body">
                    <form action="" method="POST" class="needs-validation" novalidate>
                        <!-- Bagian Data Identitas -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="border-bottom pb-2 mb-3">
                                    <i class="fas fa-id-card me-2"></i>Data Identitas
                                </h6>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nis" class="form-label required-field">NIS</label>
                                    <input type="text" class="form-control" id="nis" name="nis" required 
                                           placeholder="Masukkan NIS" value="<?php echo isset($_POST['nis']) ? $_POST['nis'] : $siswa['nis']; ?>">
                                    <div class="form-text">NIS harus unik dan tidak boleh sama dengan siswa lain.</div>
                                    <div class="invalid-feedback">NIS wajib diisi!</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nisn" class="form-label required-field">NISN</label>
                                    <input type="text" class="form-control" id="nisn" name="nisn" required 
                                           placeholder="Masukkan NISN" value="<?php echo isset($_POST['nisn']) ? $_POST['nisn'] : $siswa['nisn']; ?>">
                                    <div class="form-text">NISN harus unik dan tidak boleh sama dengan siswa lain.</div>
                                    <div class="invalid-feedback">NISN wajib diisi!</div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="nama_lengkap" class="form-label required-field">Nama Lengkap</label>
                                    <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" required 
                                           placeholder="Masukkan nama lengkap" value="<?php echo isset($_POST['nama_lengkap']) ? $_POST['nama_lengkap'] : $siswa['nama_lengkap']; ?>">
                                    <div class="invalid-feedback">Nama lengkap wajib diisi!</div>
                                </div>
                            </div>
                        </div>

                        <!-- Bagian Data Pribadi -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="border-bottom pb-2 mb-3">
                                    <i class="fas fa-user me-2"></i>Data Pribadi
                                </h6>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="jenis_kelamin" class="form-label required-field">Jenis Kelamin</label>
                                    <select class="form-select" id="jenis_kelamin" name="jenis_kelamin" required>
                                        <option value="" disabled>-- Pilih Jenis Kelamin --</option>
                                        <option value="L" <?php echo (isset($_POST['jenis_kelamin']) ? $_POST['jenis_kelamin'] : $siswa['jenis_kelamin']) == 'L' ? 'selected' : ''; ?>>Laki-laki</option>
                                        <option value="P" <?php echo (isset($_POST['jenis_kelamin']) ? $_POST['jenis_kelamin'] : $siswa['jenis_kelamin']) == 'P' ? 'selected' : ''; ?>>Perempuan</option>
                                    </select>
                                    <div class="invalid-feedback">Jenis kelamin wajib dipilih!</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="kelas_id" class="form-label required-field">Kelas</label>
                                    <select class="form-select" id="kelas_id" name="kelas_id" required>
                                        <option value="" disabled>-- Pilih Kelas --</option>
                                        <?php mysqli_data_seek($kelas_result, 0); ?>
                                        <?php while($kelas = mysqli_fetch_assoc($kelas_result)): ?>
                                        <option value="<?php echo $kelas['id']; ?>" <?php echo (isset($_POST['kelas_id']) ? $_POST['kelas_id'] : $siswa['kelas_id']) == $kelas['id'] ? 'selected' : ''; ?>>
                                            <?php echo $kelas['nama_kelas']; ?>
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                    <div class="invalid-feedback">Kelas wajib dipilih!</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="tempat_lahir" class="form-label">Tempat Lahir</label>
                                    <input type="text" class="form-control" id="tempat_lahir" name="tempat_lahir" 
                                           placeholder="Masukkan tempat lahir" value="<?php echo isset($_POST['tempat_lahir']) ? $_POST['tempat_lahir'] : $siswa['tempat_lahir']; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="tanggal_lahir" class="form-label">Tanggal Lahir</label>
                                    <input type="date" class="form-control" id="tanggal_lahir" name="tanggal_lahir" 
                                           value="<?php echo isset($_POST['tanggal_lahir']) ? $_POST['tanggal_lahir'] : $siswa['tanggal_lahir']; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Bagian Data Kontak -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="border-bottom pb-2 mb-3">
                                    <i class="fas fa-address-book me-2"></i>Data Kontak
                                </h6>
                            </div>
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="alamat" class="form-label">Alamat</label>
                                    <textarea class="form-control" id="alamat" name="alamat" rows="3" 
                                              placeholder="Masukkan alamat lengkap"><?php echo isset($_POST['alamat']) ? $_POST['alamat'] : $siswa['alamat']; ?></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="no_telp" class="form-label">Nomor Telepon</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                        <input type="text" class="form-control" id="no_telp" name="no_telp" 
                                               placeholder="Masukkan nomor telepon" value="<?php echo isset($_POST['no_telp']) ? $_POST['no_telp'] : $siswa['no_telp']; ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="status" class="form-label required-field">Status</label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="aktif" <?php echo (isset($_POST['status']) ? $_POST['status'] : $siswa['status']) == 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                                        <option value="tidak_aktif" <?php echo (isset($_POST['status']) ? $_POST['status'] : $siswa['status']) == 'tidak_aktif' ? 'selected' : ''; ?>>Tidak Aktif</option>
                                    </select>
                                    <div class="invalid-feedback">Status wajib dipilih!</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                            <button type="reset" class="btn btn-secondary me-2">
                                <i class="fas fa-undo me-1"></i> Reset
                            </button>
                            <button type="submit" name="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
    // Script untuk validasi form
    (function() {
        'use strict';
        
        // Fetch all the forms we want to apply custom Bootstrap validation styles to
        var forms = document.querySelectorAll('.needs-validation');
        
        // Loop over them and prevent submission
        Array.prototype.slice.call(forms)
            .forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    
                    form.classList.add('was-validated');
                }, false);
            });
    })();
    
    // Script untuk mengatur toggle sidebar dan responsif content
    $(document).ready(function() {
        // Fungsi untuk menyesuaikan content saat sidebar toggle
        function adjustContent() {
            if ($("#sidebar").hasClass("collapsed") || $("#sidebar").css("margin-left") === "-250px") {
                $("#content").addClass("expanded");
            } else {
                $("#content").removeClass("expanded");
            }
        }

        // Jalankan saat halaman dimuat
        adjustContent();

        // Listen untuk event sidebar toggle dari header
        $("#sidebarToggle").on("click", function() {
            setTimeout(function() {
                adjustContent();
            }, 10);
        });

        // Juga check saat window resize
        $(window).resize(function() {
            adjustContent();
            
            // Pada mobile, content selalu expanded
            if ($(window).width() <= 768) {
                $("#content").addClass("expanded");
            }
        });
    });
</script>

<?php require_once '../include/footer.php'; 
ob_end_flush();
?>