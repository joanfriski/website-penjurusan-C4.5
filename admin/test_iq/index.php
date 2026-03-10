<?php
ob_start();
require_once '../../database/config.php';
require_once '../include/header.php';


// Pastikan pengguna sudah login dan memiliki hak akses
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'staff_bk' && $_SESSION['role'] != 'kepsek')) {
    // Redirect ke halaman login jika belum login atau tidak memiliki hak akses
    header("Location: ../../login/login.php");
    exit();
}

// Inisialisasi variabel pencarian dan filter
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$kelas_filter = isset($_GET['kelas']) ? mysqli_real_escape_string($conn, $_GET['kelas']) : '';
$kategori_filter = isset($_GET['kategori']) ? mysqli_real_escape_string($conn, $_GET['kategori']) : '';

// Query untuk mendapatkan semua kelas untuk filter dropdown
$query_kelas = "SELECT id, nama_kelas FROM kelas ORDER BY nama_kelas ASC";
$result_kelas = mysqli_query($conn, $query_kelas);

// Membangun query dengan filter
$query_count = "SELECT COUNT(*) as total FROM test_iq t 
                JOIN siswa s ON t.siswa_id = s.id 
                LEFT JOIN kelas k ON s.kelas_id = k.id 
                WHERE 1=1";

$query = "SELECT t.*, s.nis, s.nama_lengkap, s.kelas_id, k.nama_kelas 
          FROM test_iq t 
          JOIN siswa s ON t.siswa_id = s.id 
          LEFT JOIN kelas k ON s.kelas_id = k.id 
          WHERE 1=1";

// Tambahkan kondisi pencarian jika ada
if (!empty($search)) {
    $search_condition = " AND (s.nis LIKE '%$search%' OR s.nama_lengkap LIKE '%$search%')";
    $query .= $search_condition;
    $query_count .= $search_condition;
}

// Tambahkan filter kelas jika ada
if (!empty($kelas_filter)) {
    $kelas_condition = " AND s.kelas_id = '$kelas_filter'";
    $query .= $kelas_condition;
    $query_count .= $kelas_condition;
}

// Tambahkan filter kategori jika ada
if (!empty($kategori_filter)) {
    $kategori_condition = " AND t.kategori = '$kategori_filter'";
    $query .= $kategori_condition;
    $query_count .= $kategori_condition;
}

// Konfigurasi pagination
$records_per_page = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $records_per_page;

// Tambahkan ORDER BY dan LIMIT untuk pagination
$query .= " ORDER BY t.tanggal_test DESC, s.nama_lengkap ASC LIMIT $offset, $records_per_page";

// Jalankan query data
$result = mysqli_query($conn, $query);

// Hitung total records untuk pagination
$result_count = mysqli_query($conn, $query_count);
$row_count = mysqli_fetch_assoc($result_count);
$total_records = $row_count['total'];
$total_pages = ceil($total_records / $records_per_page);

// Pesan notifikasi (jika ada)
$notification = '';
if (isset($_SESSION['notification'])) {
    $notification = $_SESSION['notification'];
    unset($_SESSION['notification']);
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
    
    /* Styling untuk pagination */
    .pagination {
        margin-bottom: 0;
    }
    
    /* Styling untuk filter form */
    .filter-form {
        background-color: #f8f9fa;
        border-radius: 5px;
        padding: 15px;
        margin-bottom: 20px;
    }
    
    .filter-form .form-select,
    .filter-form .form-control {
        border-radius: 4px;
    }
    
    .filter-form .btn {
        border-radius: 4px;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <?php require_once '../include/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4 content-wrapper" id="content">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h2 class="h3">
                    <i class="fas fa-brain me-2"></i>Data Test IQ Siswa
                </h2>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="tambah.php" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-plus me-1"></i> Tambah Data
                        </a>
                    </div>
                </div>
            </div>
            
            <?php if ($notification): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $notification; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <!-- Filter Form -->
            <div class="card shadow-sm mb-3 filter-form">
                <div class="card-body">
                    <form method="GET" action="">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label for="search" class="form-label">Cari Siswa</label>
                                <input type="text" class="form-control" id="search" name="search" placeholder="NIS / Nama" value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="kelas" class="form-label">Filter Kelas</label>
                                <select class="form-select" id="kelas" name="kelas">
                                    <option value="">Semua Kelas</option>
                                    <?php while ($kelas = mysqli_fetch_assoc($result_kelas)): ?>
                                        <option value="<?php echo $kelas['id']; ?>" <?php echo ($kelas_filter == $kelas['id']) ? 'selected' : ''; ?>>
                                            <?php echo $kelas['nama_kelas']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="kategori" class="form-label">Filter Kategori</label>
                                <select class="form-select" id="kategori" name="kategori">
                                    <option value="">Semua Kategori</option>
                                    <option value="Tinggi" <?php echo ($kategori_filter == 'Tinggi') ? 'selected' : ''; ?>>Tinggi</option>
                                    <option value="Sedang" <?php echo ($kategori_filter == 'Sedang') ? 'selected' : ''; ?>>Sedang</option>
                                    <option value="Rendah" <?php echo ($kategori_filter == 'Rendah') ? 'selected' : ''; ?>>Rendah</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-filter me-1"></i> Filter
                                    </button>
                                    <a href="index.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-redo me-1"></i> Reset
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="dataTable">
                            <thead class="table-light">
                                <tr>
                                    <th width="50">No</th>
                                    <th>NIS</th>
                                    <th>Nama Siswa</th>
                                    <th>Kelas</th>
                                    <th>Skor IQ</th>
                                    <th>Kategori</th>
                                    <th>Tanggal Test</th>
                                    <th width="150">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if (mysqli_num_rows($result) > 0) {
                                    $no = $offset + 1;
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        // Tentukan class warna berdasarkan kategori IQ
                                        $badge_class = "";
                                        switch ($row['kategori']) {
                                            case 'Tinggi':
                                                $badge_class = "bg-success";
                                                break;
                                            case 'Sedang':
                                                $badge_class = "bg-info";
                                                break;
                                            case 'Rendah':
                                                $badge_class = "bg-warning";
                                                break;
                                            default:
                                                $badge_class = "bg-secondary";
                                        }
                                ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo $row['nis']; ?></td>
                                    <td><?php echo $row['nama_lengkap']; ?></td>
                                    <td><?php echo $row['nama_kelas'] ?? 'Belum ditentukan'; ?></td>
                                    <td><?php echo $row['skor']; ?></td>
                                    <td><span class="badge rounded-pill <?php echo $badge_class; ?>"><?php echo $row['kategori']; ?></span></td>
                                    <td><?php echo date('d M Y', strtotime($row['tanggal_test'])); ?></td>
                                    <td>
                                        <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="hapus.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Yakin ingin menghapus data ini?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php
                                    }
                                } else {
                                ?>
                                <tr>
                                    <td colspan="8" class="text-center">Tidak ada data test IQ yang ditemukan</td>
                                </tr>
                                <?php
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page-1; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?><?php echo !empty($kelas_filter) ? '&kelas='.$kelas_filter : ''; ?><?php echo !empty($kategori_filter) ? '&kategori='.$kategori_filter : ''; ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);

                            if ($start_page > 1) {
                                echo '<li class="page-item"><a class="page-link" href="?page=1'.(!empty($search) ? '&search='.urlencode($search) : '').(!empty($kelas_filter) ? '&kelas='.$kelas_filter : '').(!empty($kategori_filter) ? '&kategori='.$kategori_filter : '').'">1</a></li>';
                                if ($start_page > 2) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                            }

                            for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?><?php echo !empty($kelas_filter) ? '&kelas='.$kelas_filter : ''; ?><?php echo !empty($kategori_filter) ? '&kategori='.$kategori_filter : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor;

                            if ($end_page < $total_pages) {
                                if ($end_page < $total_pages - 1) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                                echo '<li class="page-item"><a class="page-link" href="?page='.$total_pages.(!empty($search) ? '&search='.urlencode($search) : '').(!empty($kelas_filter) ? '&kelas='.$kelas_filter : '').(!empty($kategori_filter) ? '&kategori='.$kategori_filter : '').'">'.$total_pages.'</a></li>';
                            }
                            ?>

                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page+1; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?><?php echo !empty($kelas_filter) ? '&kelas='.$kelas_filter : ''; ?><?php echo !empty($kategori_filter) ? '&kategori='.$kategori_filter : ''; ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                    <?php endif; ?>
                    
                    <div class="card-footer bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Total <?php echo $total_records; ?> data test IQ</span>
                            <?php if($total_records > 0): ?>
                            <span class="text-muted small">Menampilkan <?php echo $offset + 1; ?> - <?php echo min($offset + $records_per_page, $total_records); ?> dari <?php echo $total_records; ?> data</span>
                            <?php endif; ?>
                        </div>
                    </div>
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
                            <h6><i class="fas fa-lightbulb me-2"></i>Pentingnya Data Test IQ:</h6>
                            <p>
                                Data test IQ merupakan salah satu atribut penting yang digunakan dalam algoritma C4.5 
                                untuk menentukan penjurusan siswa. Pastikan data test IQ selalu diperbarui.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
    // Kita tidak perlu lagi menggunakan DataTables karena sudah mengimplementasikan
    // fitur search, filter, dan pagination secara native
    
    // JavaScript untuk mempertahankan filter saat paginasi
    $(document).ready(function() {
        // Fungsi untuk melakukan reset form filter
        $('#resetFilter').click(function(e) {
            e.preventDefault();
            window.location.href = 'index.php';
        });
    });
</script>


<?php require_once '../include/footer.php'; 
ob_end_flush();
?>

