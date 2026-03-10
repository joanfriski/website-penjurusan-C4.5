<?php
require_once '../../database/config.php';
require_once '../include/header.php';

// Pagination setup
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Search functionality
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$where = "";
if (!empty($search)) {
    $where = "WHERE s.nama_lengkap LIKE '%$search%' OR s.nis LIKE '%$search%'";
}

// Count total records for pagination
$query_count = "SELECT COUNT(*) as total FROM minat_siswa ms 
                JOIN siswa s ON ms.siswa_id = s.id 
                $where";
$result_count = mysqli_query($conn, $query_count);
$total_records = mysqli_fetch_assoc($result_count)['total'];
$total_pages = ceil($total_records / $limit);

// Fetch minat siswa data with pagination
$query = "SELECT ms.*, s.nama_lengkap, s.nis, k.nama_kelas 
          FROM minat_siswa ms 
          JOIN siswa s ON ms.siswa_id = s.id 
          LEFT JOIN kelas k ON s.kelas_id = k.id 
          $where
          ORDER BY s.nama_lengkap ASC, ms.created_at DESC 
          LIMIT $start, $limit";
$result = mysqli_query($conn, $query);

// Handle success/error messages
$message = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
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
                    <i class="fas fa-brain me-2"></i>Data Minat Siswa
                </h2>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="tambah.php" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus me-1"></i>Tambah Data Minat
                    </a>
                </div>
            </div>
            
            <?php if(!empty($message)): ?>
            <div class="alert alert-<?= strpos($message, 'berhasil') !== false ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
                <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <div class="row justify-content-between align-items-center">
                        <div class="col-auto">
                            <h5 class="mb-0">Daftar Minat Siswa</h5>
                        </div>
                        <div class="col-md-4">
                            <form action="" method="GET" class="d-flex">
                                <input type="text" name="search" class="form-control form-control-sm me-2" 
                                       placeholder="Cari nama/NIS siswa..." value="<?= $search ?>">
                                <button type="submit" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-search"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col" width="5%">No</th>
                                    <th scope="col">NIS</th>
                                    <th scope="col">Nama Siswa</th>
                                    <th scope="col">Kelas</th>
                                    <th scope="col">Minat Jurusan</th>
                                    <th scope="col">Tanggal Input</th>
                                    <th scope="col" width="15%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if (mysqli_num_rows($result) > 0):
                                    $no = $start + 1;
                                    while($row = mysqli_fetch_assoc($result)): 
                                ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= htmlspecialchars($row['nis']) ?></td>
                                    <td><?= htmlspecialchars($row['nama_lengkap']) ?></td>
                                    <td><?= htmlspecialchars($row['nama_kelas'] ?? '-') ?></td>
                                    <td>
                                        <span class="badge bg-<?= $row['minat'] == 'IPA' ? 'info' : 'success' ?>">
                                            <?= $row['minat'] ?>
                                        </span>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($row['created_at'])) ?></td>
                                    <td>
                                        <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="#" class="btn btn-sm btn-danger" 
                                           data-bs-toggle="modal" 
                                           data-bs-target="#deleteModal<?= $row['id'] ?>">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        
                                        <!-- Modal Konfirmasi Hapus -->
                                        <div class="modal fade" id="deleteModal<?= $row['id'] ?>" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="deleteModalLabel">Konfirmasi Hapus</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        Anda yakin ingin menghapus data minat siswa <strong><?= htmlspecialchars($row['nama_lengkap']) ?></strong>?
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                        <a href="hapus.php?id=<?= $row['id'] ?>" class="btn btn-danger">Hapus</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php 
                                    endwhile; 
                                else: 
                                ?>
                                <tr>
                                    <td colspan="7" class="text-center py-3">Tidak ada data minat siswa</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">Total <?php echo $total_records; ?> data minat</span>
                        <?php if($total_records > 0): ?>
                        <span class="text-muted small">Menampilkan <?php echo $start + 1; ?> - <?php echo min($start + $limit, $total_records); ?> dari <?php echo $total_records; ?> data</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Pagination -->
            <?php if($total_pages > 1): ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page-1; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);

                    if ($start_page > 1) {
                        echo '<li class="page-item"><a class="page-link" href="?page=1'.(!empty($search) ? '&search='.urlencode($search) : '').'">1</a></li>';
                        if ($start_page > 2) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                    }

                    for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor;

                    if ($end_page < $total_pages) {
                        if ($end_page < $total_pages - 1) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                        echo '<li class="page-item"><a class="page-link" href="?page='.$total_pages.(!empty($search) ? '&search='.urlencode($search) : '').'">'.$total_pages.'</a></li>';
                    }
                    ?>

                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page+1; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
            
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>Informasi
                    </h5>
                </div>
                <div class="card-body">
                    <p>Data minat siswa digunakan sebagai salah satu atribut penting dalam algoritma C4.5 untuk menentukan penjurusan siswa.</p>
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Minat Jurusan:</h6>
                            <ul>
                                <li><span class="badge bg-info">IPA</span> - Siswa berminat pada jurusan IPA</li>
                                <li><span class="badge bg-success">IPS</span> - Siswa berminat pada jurusan IPS</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>Catatan:</h6>
                            <ul>
                                <li>Data minat diambil berdasarkan angket yang diisi siswa</li>
                                <li>Pastikan setiap siswa memiliki data minat sebelum memproses penjurusan</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once '../include/footer.php'; ?>