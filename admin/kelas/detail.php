<?php
ob_start();
require_once '../../database/config.php';
require_once '../include/header.php';


// Periksa apakah parameter ID ada
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['message'] = "ID kelas tidak ditemukan!";
    $_SESSION['message_type'] = "danger";
    header("Location: index.php");
    exit();
}

$id = mysqli_real_escape_string($conn, $_GET['id']);

// Ambil data kelas berdasarkan ID
$sql = "SELECT * FROM kelas WHERE id = '$id'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
    $_SESSION['message'] = "Data kelas tidak ditemukan!";
    $_SESSION['message_type'] = "danger";
    header("Location: index.php");
    exit();
}

$kelas = mysqli_fetch_assoc($result);

// Ambil data siswa dalam kelas ini
$siswa_sql = "SELECT * FROM siswa WHERE kelas_id = '$id' AND status = 'aktif' ORDER BY nama_lengkap ASC";
$siswa_result = mysqli_query($conn, $siswa_sql);
$jumlah_siswa = mysqli_num_rows($siswa_result);

// Log aktivitas
$user_id = $_SESSION['user_id'];
$aktivitas = "Melihat detail kelas: " . $kelas['nama_kelas'];
mysqli_query($conn, "INSERT INTO log_aktivitas (user_id, aktivitas, keterangan, ip_address) 
                     VALUES ('$user_id', '$aktivitas', 'ID Kelas: $id', '".$_SERVER['REMOTE_ADDR']."')");
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
                    <li class="breadcrumb-item"><a href="index.php">Data Kelas</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Detail Kelas</li>
                </ol>
            </nav>
            
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h2 class="h3">
                    <i class="fas fa-school me-2 text-primary"></i>Detail Kelas
                </h2>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="edit.php?id=<?php echo $id; ?>" class="btn btn-sm btn-warning text-white">
                            <i class="fas fa-edit me-1"></i> Edit
                        </a>
                        <a href="index.php" class="btn btn-sm btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Kembali
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2 text-primary"></i>
                                Informasi Kelas
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-center mb-3">
                                <div class="avatar bg-light text-primary rounded-circle p-4 mb-3" style="width: 100px; height: 100px;">
                                    <i class="fas fa-school" style="font-size: 2.5rem;"></i>
                                </div>
                            </div>
                            
                            <h4 class="text-center mb-3"><?php echo htmlspecialchars($kelas['nama_kelas']); ?></h4>
                            
                            <table class="table table-borderless">
                                <tr>
                                    <td width="40%"><strong>Tahun Ajaran</strong></td>
                                    <td width="60%">: <?php echo htmlspecialchars($kelas['tahun_ajaran']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Wali Kelas</strong></td>
                                    <td>: <?php echo htmlspecialchars($kelas['wali_kelas']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Jumlah Siswa</strong></td>
                                    <td>: <span class="badge bg-info"><?php echo $jumlah_siswa; ?> siswa</span></td>
                                </tr>
                                <tr>
                                    <td><strong>Tanggal Dibuat</strong></td>
                                    <td>: <?php echo date('d-m-Y H:i', strtotime($kelas['created_at'])); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Terakhir Diperbarui</strong></td>
                                    <td>: <?php echo date('d-m-Y H:i', strtotime($kelas['updated_at'])); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-8 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-user-graduate me-2 text-primary"></i>
                                    Daftar Siswa
                                    <span class="badge bg-primary rounded-pill ms-2"><?php echo $jumlah_siswa; ?></span>
                                </h5>
                                <?php if ($jumlah_siswa > 0): ?>
                                <a href="../siswa/index.php?kelas=<?php echo $id; ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-list me-1"></i> Lihat Semua
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="5%">No</th>
                                            <th width="15%">NIS</th>
                                            <th width="40%">Nama Lengkap</th>
                                            <th width="20%">Jenis Kelamin</th>
                                            <th width="20%">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        if ($jumlah_siswa > 0): 
                                            $no = 1;
                                            while ($siswa = mysqli_fetch_assoc($siswa_result)):
                                        ?>
                                        <tr>
                                            <td><?php echo $no++; ?></td>
                                            <td><?php echo htmlspecialchars($siswa['nis']); ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar bg-light text-primary rounded-circle p-2 me-2">
                                                        <i class="fas <?php echo ($siswa['jenis_kelamin'] == 'L') ? 'fa-male' : 'fa-female'; ?>"></i>
                                                    </div>
                                                    <div>
                                                        <?php echo htmlspecialchars($siswa['nama_lengkap']); ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo ($siswa['jenis_kelamin'] == 'L') ? 'Laki-laki' : 'Perempuan'; ?></td>
                                            <td>
                                                <a href="../siswa/detail.php?id=<?php echo $siswa['id']; ?>" class="btn btn-sm btn-info text-white">
                                                    <i class="fas fa-eye"></i> Detail
                                                </a>
                                            </td>
                                        </tr>
                                        <?php 
                                            endwhile;
                                        else:
                                        ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-4">
                                                <div class="d-flex flex-column align-items-center">
                                                    <i class="fas fa-user-slash text-secondary mb-2" style="font-size: 2rem;"></i>
                                                    <p class="mb-0">Belum ada siswa di kelas ini</p>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <?php if ($jumlah_siswa > 10): ?>
                        <div class="card-footer bg-white text-center">
                            <a href="../siswa/index.php?kelas=<?php echo $id; ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-list me-1"></i> Lihat Semua Siswa
                            </a>
                        </div>
                        <?php endif; ?>
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

