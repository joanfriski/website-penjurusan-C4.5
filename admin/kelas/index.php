<?php
ob_start();
require_once '../../database/config.php';
require_once '../include/header.php';


// Fungsi untuk menghapus data kelas
if (isset($_GET['action']) && $_GET['action'] == 'hapus' && isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Log aktivitas sebelum menghapus
    $user_id = $_SESSION['user_id'];
    $log_query = "INSERT INTO log_aktivitas (user_id, aktivitas, keterangan, ip_address) 
                 VALUES ('$user_id', 'Menghapus data kelas', 'ID Kelas: $id', '".$_SERVER['REMOTE_ADDR']."')";
    mysqli_query($conn, $log_query);
    
    // Hapus data kelas
    $delete_query = "DELETE FROM kelas WHERE id = '$id'";
    $delete_result = mysqli_query($conn, $delete_query);
    
    if ($delete_result) {
        $_SESSION['success'] = "Data kelas berhasil dihapus";
    } else {
        $_SESSION['error'] = "Gagal menghapus data kelas: " . mysqli_error($conn);
    }
    
    // Redirect ke halaman kelas
    header("Location: index.php");
    exit();
}

// Pagination
$limit = 10; // Jumlah data per halaman
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page > 1) ? ($page * $limit) - $limit : 0;

// Query untuk mendapatkan total data
$total_query = "SELECT COUNT(*) as total FROM kelas";
$total_result = mysqli_query($conn, $total_query);
$total_data = mysqli_fetch_assoc($total_result)['total'];
$total_page = ceil($total_data / $limit);

// Query untuk mendapatkan data dengan pagination
$query = "SELECT * FROM kelas ORDER BY tahun_ajaran DESC, nama_kelas ASC LIMIT $start, $limit";
$result = mysqli_query($conn, $query);

// Filter data berdasarkan pencarian
$search = isset($_GET['search']) ? $_GET['search'] : '';
if (!empty($search)) {
    $query = "SELECT * FROM kelas 
              WHERE nama_kelas LIKE '%$search%' OR tahun_ajaran LIKE '%$search%' OR wali_kelas LIKE '%$search%'
              ORDER BY tahun_ajaran DESC, nama_kelas ASC LIMIT $start, $limit";
    $result = mysqli_query($conn, $query);
    
    // Update total data untuk pagination
    $total_query = "SELECT COUNT(*) as total FROM kelas 
                   WHERE nama_kelas LIKE '%$search%' OR tahun_ajaran LIKE '%$search%' OR wali_kelas LIKE '%$search%'";
    $total_result = mysqli_query($conn, $total_query);
    $total_data = mysqli_fetch_assoc($total_result)['total'];
    $total_page = ceil($total_data / $limit);
}

// Filter data berdasarkan tahun ajaran
$filter_tahun = isset($_GET['tahun_ajaran']) ? $_GET['tahun_ajaran'] : '';
if (!empty($filter_tahun)) {
    $query = "SELECT * FROM kelas 
              WHERE tahun_ajaran = '$filter_tahun'
              ORDER BY nama_kelas ASC LIMIT $start, $limit";
    $result = mysqli_query($conn, $query);
    
    // Update total data untuk pagination
    $total_query = "SELECT COUNT(*) as total FROM kelas WHERE tahun_ajaran = '$filter_tahun'";
    $total_result = mysqli_query($conn, $total_query);
    $total_data = mysqli_fetch_assoc($total_result)['total'];
    $total_page = ceil($total_data / $limit);
}

// Query untuk mendapatkan daftar tahun ajaran untuk filter
$tahun_query = "SELECT DISTINCT tahun_ajaran FROM kelas ORDER BY tahun_ajaran DESC";
$tahun_result = mysqli_query($conn, $tahun_query);

// Log aktivitas
$user_id = $_SESSION['user_id'];
$aktivitas = "Mengakses halaman data kelas";
mysqli_query($conn, "INSERT INTO log_aktivitas (user_id, aktivitas, ip_address) VALUES ('$user_id', '$aktivitas', '".$_SERVER['REMOTE_ADDR']."')");

// Log aktivitas
$user_id = $_SESSION['user_id'];
$aktivitas = "Mengakses halaman data kelas";
mysqli_query($conn, "INSERT INTO log_aktivitas (user_id, aktivitas, ip_address) VALUES ('$user_id', '$aktivitas', '".$_SERVER['REMOTE_ADDR']."')");
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
                    <i class="fas fa-school me-2 text-primary"></i>Data Kelas
                </h2>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="tambah.php" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus me-1"></i> Tambah Kelas
                        </a>
                    </div>
                </div>
            </div>
            
            <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php 
                echo $_SESSION['success']; 
                unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php 
                echo $_SESSION['error']; 
                unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0 d-flex align-items-center">
                        <i class="fas fa-list me-2 text-primary"></i>
                        Daftar Kelas
                        <span class="badge bg-primary rounded-pill ms-2"><?php echo $total_data; ?></span>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3 filter-row">
                        <div class="col-md-6 mb-2 mb-md-0">
                            <form action="" method="GET" class="d-flex">
                                <div class="input-group">
                                    <input type="text" name="search" class="form-control" placeholder="Cari berdasarkan nama kelas/tahun ajaran/wali kelas" value="<?php echo $search; ?>">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i>
                                    </button>
                                    <?php if(!empty($search)): ?>
                                    <a href="index.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                        <div class="col-md-6">
                            <form action="" method="GET" class="d-flex justify-content-md-end">
                                <div class="input-group" style="max-width: 300px;">
                                    <select name="tahun_ajaran" class="form-select">
                                        <option value="">-- Pilih Tahun Ajaran --</option>
                                        <?php mysqli_data_seek($tahun_result, 0); ?>
                                        <?php while($tahun = mysqli_fetch_assoc($tahun_result)): ?>
                                        <option value="<?php echo $tahun['tahun_ajaran']; ?>" <?php echo ($filter_tahun == $tahun['tahun_ajaran']) ? 'selected' : ''; ?>>
                                            <?php echo $tahun['tahun_ajaran']; ?>
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                    <button type="submit" class="btn btn-secondary">
                                        <i class="fas fa-filter me-1"></i> Filter
                                    </button>
                                    <?php if(!empty($filter_tahun)): ?>
                                    <a href="index.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">No</th>
                                    <th width="20%">Nama Kelas</th>
                                    <th width="15%">Tahun Ajaran</th>
                                    <th width="25%">Wali Kelas</th>
                                    <th width="15%">Jumlah Siswa</th>
                                    <th width="15%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = $start + 1;
                                if(mysqli_num_rows($result) > 0):
                                    while($row = mysqli_fetch_assoc($result)):
                                        // Menghitung jumlah siswa di setiap kelas
                                        $kelas_id = $row['id'];
                                        $sql_siswa = "SELECT COUNT(*) as total FROM siswa WHERE kelas_id = '$kelas_id' AND status = 'aktif'";
                                        $result_siswa = mysqli_query($conn, $sql_siswa);
                                        $siswa_count = mysqli_fetch_assoc($result_siswa)['total'];
                                ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar bg-light text-primary rounded-circle p-2 me-2">
                                                <i class="fas fa-graduation-cap"></i>
                                            </div>
                                            <div>
                                                <span class="fw-bold"><?php echo $row['nama_kelas']; ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo $row['tahun_ajaran']; ?></td>
                                    <td><?php echo $row['wali_kelas']; ?></td>
                                    <td>
                                        <span class="badge bg-info text-white">
                                            <?php echo $siswa_count; ?> siswa
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="detail.php?id=<?php echo $row['id']; ?>" class="btn btn-info text-white" title="Detail">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn btn-warning text-white" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="javascript:void(0);" class="btn btn-danger" title="Hapus" onclick="konfirmasiHapus(<?php echo $row['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php 
                                    endwhile;
                                else:
                                ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <div class="d-flex flex-column align-items-center">
                                            <i class="fas fa-exclamation-circle text-secondary mb-2" style="font-size: 2rem;"></i>
                                            <p class="mb-0">Tidak ada data kelas</p>
                                            <?php if(!empty($search) || !empty($filter_tahun)): ?>
                                            <a href="index.php" class="btn btn-sm btn-outline-primary mt-2">
                                                <i class="fas fa-redo me-1"></i> Reset Filter
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if($total_page > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo ($page <= 1) ? '#' : '?page='.($page-1).((!empty($search)) ? '&search='.$search : '').((!empty($filter_tahun)) ? '&tahun_ajaran='.$filter_tahun : ''); ?>">
                                    <i class="fas fa-chevron-left small"></i> Previous
                                </a>
                            </li>
                            <?php for($i = 1; $i <= $total_page; $i++): ?>
                            <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo (!empty($search)) ? '&search='.$search : ''; ?><?php echo (!empty($filter_tahun)) ? '&tahun_ajaran='.$filter_tahun : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo ($page >= $total_page) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo ($page >= $total_page) ? '#' : '?page='.($page+1).((!empty($search)) ? '&search='.$search : '').((!empty($filter_tahun)) ? '&tahun_ajaran='.$filter_tahun : ''); ?>">
                                    Next <i class="fas fa-chevron-right small"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>
                </div>
                <div class="card-footer bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">Total <?php echo $total_data; ?> data kelas</span>
                        <?php if($total_data > 0): ?>
                        <span class="text-muted small">Menampilkan <?php echo $start + 1; ?> - <?php echo min($start + $limit, $total_data); ?> dari <?php echo $total_data; ?> data</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal Konfirmasi Hapus -->
<div class="modal fade" id="hapusModal" tabindex="-1" aria-labelledby="hapusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="hapusModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>Konfirmasi Hapus
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus data kelas ini?</p>
                <div class="alert alert-warning mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    <small>Perhatian: Tindakan ini akan mempengaruhi data siswa yang terkait dengan kelas ini.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Batal
                </button>
                <a href="" id="hapusLink" class="btn btn-danger">
                    <i class="fas fa-trash me-1"></i> Hapus
                </a>
            </div>
        </div>
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

    function konfirmasiHapus(id) {
        // Set the href attribute for the delete link
        document.getElementById('hapusLink').setAttribute('href', 'index.php?action=hapus&id=' + id);
        
        // Show the modal
        var myModal = new bootstrap.Modal(document.getElementById('hapusModal'));
        myModal.show();
    }
</script>


<?php require_once '../include/footer.php'; 
ob_end_flush();
?>

