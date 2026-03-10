<?php
ob_start();
require_once '../include/header.php';

require_once '../../database/config.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login/login.php");
    exit();
}

// Cek apakah ada parameter id
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "ID siswa tidak ditemukan";
    header("Location: index.php");
    exit();
}

$id = mysqli_real_escape_string($conn, $_GET['id']);

// Query untuk mendapatkan data siswa beserta nama kelas
$siswa_query = "SELECT s.*, k.nama_kelas 
                FROM siswa s 
                LEFT JOIN kelas k ON s.kelas_id = k.id 
                WHERE s.id = '$id'";
$siswa_result = mysqli_query($conn, $siswa_query);

// Cek apakah data siswa ditemukan
if (mysqli_num_rows($siswa_result) == 0) {
    $_SESSION['error'] = "Data siswa tidak ditemukan";
    header("Location: index.php");
    exit();
}

$siswa = mysqli_fetch_assoc($siswa_result);

// Query untuk mendapatkan nilai dan mata pelajaran
$nilai_query = "SELECT n.*, mp.nama 
                FROM nilai n 
                JOIN mata_pelajaran mp ON n.mapel_id = mp.id 
                WHERE n.siswa_id = '$id' 
                ORDER BY mp.nama";
$nilai_result = mysqli_query($conn, $nilai_query);

// Query untuk mendapatkan minat siswa
$minat_query = "SELECT * FROM minat_siswa WHERE siswa_id = '$id' ORDER BY id DESC LIMIT 1";
$minat_result = mysqli_query($conn, $minat_query);
$minat = mysqli_fetch_assoc($minat_result);

// Query untuk mendapatkan hasil klasifikasi
$hasil_query = "SELECT * FROM hasil_klasifikasi WHERE siswa_id = '$id' ORDER BY id DESC LIMIT 1";
$hasil_result = mysqli_query($conn, $hasil_query);
$hasil = mysqli_fetch_assoc($hasil_result);

// Log aktivitas melihat detail siswa
$user_id = $_SESSION['user_id'];
$log_query = "INSERT INTO log_aktivitas (user_id, aktivitas, keterangan, ip_address) 
             VALUES ('$user_id', 'Melihat detail siswa', 'Nama: ".$siswa['nama_lengkap'].", NIS: ".$siswa['nis']."', '".$_SERVER['REMOTE_ADDR']."')";
mysqli_query($conn, $log_query);
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
    
    /* Detail list */
    .detail-list {
        list-style-type: none;
        padding-left: 0;
    }
    
    .detail-list li {
        padding: 10px 0;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .detail-list li:last-child {
        border-bottom: none;
    }
    
    .detail-label {
        font-weight: 600;
        color: #495057;
    }
    
    .badge-status {
        font-size: 0.85rem;
        padding: 0.35em 0.65em;
    }
    
    /* Status badges */
    .badge-aktif {
        background-color: #28a745;
        color: white;
    }
    
    .badge-tidak_aktif {
        background-color: #dc3545;
        color: white;
    }
    
    /* Section headers */
    .section-header {
        font-size: 1.1rem;
        color: #495057;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #dee2e6;
    }
    
    /* Avatar placeholder */
    .avatar-placeholder {
        width: 150px;
        height: 150px;
        background-color: #f8f9fa;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem auto;
        font-size: 3rem;
        color: #adb5bd;
        border: 1px solid #dee2e6;
    }
    
    /* Styling untuk tabel nilai */
    .nilai-table {
        width: 100%;
        margin-bottom: 1rem;
    }
    
    .nilai-table th {
        background-color: #f8f9fa;
        font-weight: 600;
    }
    
    .nilai-table td, .nilai-table th {
        padding: 0.75rem;
        vertical-align: middle;
    }
    
    /* Badge untuk hasil klasifikasi */
    .badge-ipa {
        background-color: #4361ee;
        color: white;
    }
    
    .badge-ips {
        background-color: #ef476f;
        color: white;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <?php require_once '../include/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4 content-wrapper" id="content">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h2 class="h3">
                    <i class="fas fa-user me-2 text-primary"></i>Detail Siswa
                </h2>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="edit.php?id=<?php echo $siswa['id']; ?>" class="btn btn-sm btn-warning me-2">
                        <i class="fas fa-edit me-1"></i> Edit
                    </a>
                    <a href="index.php" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            </div>
            
            <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo $_SESSION['success']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo $_SESSION['error']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <div class="row">
                <!-- Kolom Kiri - Informasi Utama -->
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white text-center">
                            <h5 class="mb-0">Profil Siswa</h5>
                        </div>
                        <div class="card-body text-center">
                            <div class="avatar-placeholder">
                                <?php echo strtoupper(substr($siswa['nama_lengkap'], 0, 1)); ?>
                            </div>
                            <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($siswa['nama_lengkap']); ?></h5>
                            <p class="mb-1">NIS: <?php echo htmlspecialchars($siswa['nis']); ?></p>
                            <p class="mb-1">NISN: <?php echo htmlspecialchars($siswa['nisn']); ?></p>
                            <p class="mb-3">Kelas: <?php echo htmlspecialchars($siswa['nama_kelas']); ?></p>
                            
                            <div class="d-grid gap-2">
                                <span class="badge <?php echo $siswa['status'] == 'aktif' ? 'badge-aktif' : 'badge-tidak_aktif'; ?> rounded-pill badge-status">
                                    <?php echo $siswa['status'] == 'aktif' ? 'Aktif' : 'Tidak Aktif'; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Kolom Kanan - Detail Informasi -->
                <div class="col-md-8">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="fas fa-info-circle me-2 text-primary"></i>Informasi Detail
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- Data Identitas -->
                            <h6 class="section-header">
                                <i class="fas fa-id-card me-2"></i>Data Identitas
                            </h6>
                            <ul class="detail-list">
                                <li class="row">
                                    <div class="col-md-4 detail-label">NIS</div>
                                    <div class="col-md-8"><?php echo htmlspecialchars($siswa['nis']); ?></div>
                                </li>
                                <li class="row">
                                    <div class="col-md-4 detail-label">NISN</div>
                                    <div class="col-md-8"><?php echo htmlspecialchars($siswa['nisn']); ?></div>
                                </li>
                                <li class="row">
                                    <div class="col-md-4 detail-label">Nama Lengkap</div>
                                    <div class="col-md-8"><?php echo htmlspecialchars($siswa['nama_lengkap']); ?></div>
                                </li>
                                <li class="row">
                                    <div class="col-md-4 detail-label">Kelas</div>
                                    <div class="col-md-8"><?php echo htmlspecialchars($siswa['nama_kelas']); ?></div>
                                </li>
                            </ul>
                            
                            <!-- Data Pribadi -->
                            <h6 class="section-header mt-4">
                                <i class="fas fa-user me-2"></i>Data Pribadi
                            </h6>
                            <ul class="detail-list">
                                <li class="row">
                                    <div class="col-md-4 detail-label">Jenis Kelamin</div>
                                    <div class="col-md-8">
                                        <?php echo $siswa['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan'; ?>
                                    </div>
                                </li>
                                <li class="row">
                                    <div class="col-md-4 detail-label">Tempat Lahir</div>
                                    <div class="col-md-8">
                                        <?php echo !empty($siswa['tempat_lahir']) ? htmlspecialchars($siswa['tempat_lahir']) : '<em class="text-muted">Tidak ada data</em>'; ?>
                                    </div>
                                </li>
                                <li class="row">
                                    <div class="col-md-4 detail-label">Tanggal Lahir</div>
                                    <div class="col-md-8">
                                        <?php 
                                        if (!empty($siswa['tanggal_lahir'])) {
                                            $tanggal_lahir = new DateTime($siswa['tanggal_lahir']);
                                            echo $tanggal_lahir->format('d F Y');
                                        } else {
                                            echo '<em class="text-muted">Tidak ada data</em>';
                                        }
                                        ?>
                                    </div>
                                </li>
                            </ul>
                            
                            <!-- Data Kontak -->
                            <h6 class="section-header mt-4">
                                <i class="fas fa-address-book me-2"></i>Data Kontak
                            </h6>
                            <ul class="detail-list">
                                <li class="row">
                                    <div class="col-md-4 detail-label">Alamat</div>
                                    <div class="col-md-8">
                                        <?php echo !empty($siswa['alamat']) ? htmlspecialchars($siswa['alamat']) : '<em class="text-muted">Tidak ada data</em>'; ?>
                                    </div>
                                </li>
                                <li class="row">
                                    <div class="col-md-4 detail-label">Nomor Telepon</div>
                                    <div class="col-md-8">
                                        <?php 
                                        if (!empty($siswa['no_telp'])) {
                                            echo '<i class="fas fa-phone me-1 text-success"></i> ' . htmlspecialchars($siswa['no_telp']);
                                        } else {
                                            echo '<em class="text-muted">Tidak ada data</em>';
                                        }
                                        ?>
                                    </div>
                                </li>
                            </ul>
                            
                            <!-- Data Sistem -->
                            <h6 class="section-header mt-4">
                                <i class="fas fa-cog me-2"></i>Data Sistem
                            </h6>
                            <ul class="detail-list">
                                <li class="row">
                                    <div class="col-md-4 detail-label">Status</div>
                                    <div class="col-md-8">
                                        <span class="badge <?php echo $siswa['status'] == 'aktif' ? 'badge-aktif' : 'badge-tidak_aktif'; ?> rounded-pill badge-status">
                                            <?php echo $siswa['status'] == 'aktif' ? 'Aktif' : 'Tidak Aktif'; ?>
                                        </span>
                                    </div>
                                </li>
                                <li class="row">
                                    <div class="col-md-4 detail-label">Dibuat pada</div>
                                    <div class="col-md-8">
                                        <?php 
                                        $created_at = new DateTime($siswa['created_at']);
                                        echo $created_at->format('d F Y, H:i:s'); 
                                        ?>
                                    </div>
                                </li>
                                <li class="row">
                                    <div class="col-md-4 detail-label">Diperbarui pada</div>
                                    <div class="col-md-8">
                                        <?php 
                                        $updated_at = new DateTime($siswa['updated_at']);
                                        echo $updated_at->format('d F Y, H:i:s'); 
                                        ?>
                                    </div>
                                </li>
                            </ul>

                            <!-- Data Nilai -->
                            <h6 class="section-header mt-4">
                                <i class="fas fa-book me-2"></i>Data Nilai
                            </h6>
                            <?php if(mysqli_num_rows($nilai_result) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered nilai-table">
                                    <thead>
                                        <tr>
                                            <th>Mata Pelajaran</th>
                                            <th>Nilai</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($nilai = mysqli_fetch_assoc($nilai_result)): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($nilai['nama']); ?></td>
                                            <td><?php echo htmlspecialchars($nilai['nilai']); ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>Belum ada data nilai
                            </div>
                            <?php endif; ?>

                            <!-- Data Minat dan Hasil Klasifikasi -->
                            <h6 class="section-header mt-4">
                                <i class="fas fa-graduation-cap me-2"></i>Data Penjurusan
                            </h6>
                            <ul class="detail-list">
                                <li class="row">
                                    <div class="col-md-4 detail-label">Minat Siswa</div>
                                    <div class="col-md-8">
                                        <?php if($minat): ?>
                                            <span class="badge <?php echo $minat['minat'] == 'IPA' ? 'badge-ipa' : 'badge-ips'; ?> rounded-pill">
                                                <?php echo htmlspecialchars($minat['minat']); ?>
                                            </span>
                                        <?php else: ?>
                                            <em class="text-muted">Belum ada data minat</em>
                                        <?php endif; ?>
                                    </div>
                                </li>
                                <li class="row">
                                    <div class="col-md-4 detail-label">Hasil Klasifikasi</div>
                                    <div class="col-md-8">
                                        <?php if($hasil): ?>
                                            <span class="badge <?php echo $hasil['hasil_prediksi'] == 'IPA' ? 'badge-ipa' : 'badge-ips'; ?> rounded-pill">
                                                <?php echo htmlspecialchars($hasil['hasil_prediksi']); ?>
                                            </span>
                                            <?php if($minat && $minat['minat'] == $hasil['hasil_prediksi']): ?>
                                                <span class="badge bg-success ms-2">
                                                    <i class="fas fa-check-circle me-1"></i>Sesuai
                                                </span>
                                            <?php elseif($minat): ?>
                                                <span class="badge bg-danger ms-2">
                                                    <i class="fas fa-times-circle me-1"></i>Tidak Sesuai
                                                </span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <em class="text-muted">Belum ada hasil klasifikasi</em>
                                        <?php endif; ?>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
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