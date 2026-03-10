<?php
ob_start();
require_once '../../database/config.php';
require_once '../include/header.php';

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$where = '';
if (!empty($search)) {
    $where = "WHERE s.nama_lengkap LIKE '%$search%' OR s.nis LIKE '%$search%' OR mp.nama LIKE '%$search%'";
}

// Filter by kelas
$kelas_filter = isset($_GET['kelas_id']) ? (int)$_GET['kelas_id'] : 0;
if ($kelas_filter > 0) {
    $where = $where ? $where . " AND s.kelas_id = $kelas_filter" : "WHERE s.kelas_id = $kelas_filter";
}

// Filter by mata pelajaran
$mapel_filter = isset($_GET['mapel_id']) ? (int)$_GET['mapel_id'] : 0;
if ($mapel_filter > 0) {
    $where = $where ? $where . " AND n.mapel_id = $mapel_filter" : "WHERE n.mapel_id = $mapel_filter";
}

// Filter by tahun ajaran
$tahun_filter = isset($_GET['tahun_ajaran']) ? $_GET['tahun_ajaran'] : '';
if (!empty($tahun_filter)) {
    $where = $where ? $where . " AND n.tahun_ajaran = '$tahun_filter'" : "WHERE n.tahun_ajaran = '$tahun_filter'";
}

// Get data for filtering
$query_kelas = "SELECT id, nama_kelas, tahun_ajaran FROM kelas ORDER BY nama_kelas ASC";
$result_kelas = mysqli_query($conn, $query_kelas);

$query_mapel = "SELECT id, nama, kode FROM mata_pelajaran ORDER BY nama ASC";
$result_mapel = mysqli_query($conn, $query_mapel);

$query_tahun = "SELECT DISTINCT tahun_ajaran FROM nilai ORDER BY tahun_ajaran DESC";
$result_tahun = mysqli_query($conn, $query_tahun);

// Query for data with pagination
$query = "SELECT n.id, s.id as siswa_id, s.nis, s.nama_lengkap, k.nama_kelas, mp.nama as mata_pelajaran, 
          mp.kategori, n.nilai, n.tahun_ajaran, n.created_at
          FROM nilai n
          JOIN siswa s ON n.siswa_id = s.id
          JOIN mata_pelajaran mp ON n.mapel_id = mp.id
          LEFT JOIN kelas k ON s.kelas_id = k.id
          $where
          ORDER BY s.nama_lengkap ASC, n.created_at DESC
          LIMIT $start, $limit";
$result = mysqli_query($conn, $query);

// Count total records for pagination
$query_count = "SELECT COUNT(*) as total FROM nilai n
                JOIN siswa s ON n.siswa_id = s.id
                JOIN mata_pelajaran mp ON n.mapel_id = mp.id
                LEFT JOIN kelas k ON s.kelas_id = k.id
                $where";
$result_count = mysqli_query($conn, $query_count);
$total_records = mysqli_fetch_assoc($result_count)['total'];
$total_pages = ceil($total_records / $limit);

// Get mata pelajaran for modal
$query_all_mapel = "SELECT id, kode, nama, kategori FROM mata_pelajaran ORDER BY nama ASC";
$result_all_mapel = mysqli_query($conn, $query_all_mapel);

// Log aktivitas
$user_id = $_SESSION['user_id'];
$aktivitas = "Mengakses halaman data nilai";
$query_log = "INSERT INTO log_aktivitas (user_id, aktivitas, ip_address) VALUES ('$user_id', '$aktivitas', '{$_SERVER['REMOTE_ADDR']}')";
mysqli_query($conn, $query_log);
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
                    <i class="fas fa-chart-line me-2"></i>Data Nilai Siswa
                </h2>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="tambah.php" class="btn btn-sm btn-primary me-2">
                        <i class="fas fa-plus me-1"></i> Tambah Nilai
                    </a>
                    <button type="button" class="btn btn-sm btn-success me-2" data-bs-toggle="modal" data-bs-target="#mapelModal">
                        <i class="fas fa-book me-1"></i> Kelola Mata Pelajaran
                    </button>
                   
                </div>
            </div>
            
            <!-- Filter and Search -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="" class="row g-3">
                        <div class="col-md-4">
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="Cari nama siswa/NIS" name="search" value="<?php echo $search; ?>">
                                <button class="btn btn-outline-secondary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <select class="form-select" name="kelas_id">
                                <option value="0">Semua Kelas</option>
                                <?php while ($kelas = mysqli_fetch_assoc($result_kelas)): ?>
                                    <option value="<?php echo $kelas['id']; ?>" <?php echo $kelas_filter == $kelas['id'] ? 'selected' : ''; ?>>
                                        <?php echo $kelas['nama_kelas'] . ' (' . $kelas['tahun_ajaran'] . ')'; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <select class="form-select" name="mapel_id">
                                <option value="0">Semua Mapel</option>
                                <?php while ($mapel = mysqli_fetch_assoc($result_mapel)): ?>
                                    <option value="<?php echo $mapel['id']; ?>" <?php echo $mapel_filter == $mapel['id'] ? 'selected' : ''; ?>>
                                        <?php echo $mapel['nama'] . ' (' . $mapel['kode'] . ')'; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <select class="form-select" name="tahun_ajaran">
                                <option value="">Semua Tahun</option>
                                <?php while ($tahun = mysqli_fetch_assoc($result_tahun)): ?>
                                    <option value="<?php echo $tahun['tahun_ajaran']; ?>" <?php echo $tahun_filter == $tahun['tahun_ajaran'] ? 'selected' : ''; ?>>
                                        <?php echo $tahun['tahun_ajaran']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-1">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Display alerts if any -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php 
                    echo $_SESSION['success']; 
                    unset($_SESSION['success']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php 
                    echo $_SESSION['error']; 
                    unset($_SESSION['error']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <!-- Data Table -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">No</th>
                                    <th width="10%">NIS</th>
                                    <th width="20%">Nama Siswa</th>
                                    <th width="10%">Kelas</th>
                                    <th width="15%">Mata Pelajaran</th>
                                    <th width="10%">Kategori</th>
                                    <th width="10%">Nilai</th>
                                    <th width="10%">Tahun</th>
                                    <th width="10%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = $start + 1;
                                if (mysqli_num_rows($result) > 0):
                                    while ($row = mysqli_fetch_assoc($result)):
                                ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo $row['nis']; ?></td>
                                    <td><?php echo $row['nama_lengkap']; ?></td>
                                    <td><?php echo $row['nama_kelas'] ?: '-'; ?></td>
                                    <td><?php echo $row['mata_pelajaran']; ?></td>
                                    <td>
                                        <span class="badge <?php echo $row['kategori'] == 'IPA' ? 'bg-info' : ($row['kategori'] == 'IPS' ? 'bg-warning' : 'bg-secondary'); ?>">
                                            <?php echo $row['kategori']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $row['nilai']; ?></td>
                                    <td><?php echo $row['tahun_ajaran']; ?></td>
                                    <td>
    <div class="btn-group">
        <a href="detail.php?siswa_id=<?php echo $row['siswa_id']; ?>" class="btn btn-sm btn-info">
            <i class="fas fa-eye"></i>
        </a>
        <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">
            <i class="fas fa-edit"></i>
        </a>
        <a href="hapus.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?')">
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
                                    <td colspan="9" class="text-center">Tidak ada data yang ditemukan</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                  <!-- Pagination -->
<?php if ($total_pages > 1): ?>
<nav aria-label="Page navigation" class="mt-4">
    <ul class="pagination justify-content-center">
        <?php if ($page > 1): ?>
            <li class="page-item">
                <a class="page-link" href="?page=<?php echo $page-1; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?><?php echo !empty($kelas_filter) ? '&kelas_id='.$kelas_filter : ''; ?><?php echo !empty($mapel_filter) ? '&mapel_id='.$mapel_filter : ''; ?><?php echo !empty($tahun_filter) ? '&tahun_ajaran='.$tahun_filter : ''; ?>">
                    <i class="fas fa-chevron-left"></i>
                </a>
            </li>
        <?php endif; ?>

        <?php
        $start_page = max(1, $page - 2);
        $end_page = min($total_pages, $page + 2);

        if ($start_page > 1) {
            echo '<li class="page-item"><a class="page-link" href="?page=1'.(!empty($search) ? '&search='.urlencode($search) : '').(!empty($kelas_filter) ? '&kelas_id='.$kelas_filter : '').(!empty($mapel_filter) ? '&mapel_id='.$mapel_filter : '').(!empty($tahun_filter) ? '&tahun_ajaran='.$tahun_filter : '').'">1</a></li>';
            if ($start_page > 2) {
                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }

        for ($i = $start_page; $i <= $end_page; $i++): ?>
            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?><?php echo !empty($kelas_filter) ? '&kelas_id='.$kelas_filter : ''; ?><?php echo !empty($mapel_filter) ? '&mapel_id='.$mapel_filter : ''; ?><?php echo !empty($tahun_filter) ? '&tahun_ajaran='.$tahun_filter : ''; ?>">
                    <?php echo $i; ?>
                </a>
            </li>
        <?php endfor;

        if ($end_page < $total_pages) {
            if ($end_page < $total_pages - 1) {
                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
            echo '<li class="page-item"><a class="page-link" href="?page='.$total_pages.(!empty($search) ? '&search='.urlencode($search) : '').(!empty($kelas_filter) ? '&kelas_id='.$kelas_filter : '').(!empty($mapel_filter) ? '&mapel_id='.$mapel_filter : '').(!empty($tahun_filter) ? '&tahun_ajaran='.$tahun_filter : '').'">'.$total_pages.'</a></li>';
        }
        ?>

        <?php if ($page < $total_pages): ?>
            <li class="page-item">
                <a class="page-link" href="?page=<?php echo $page+1; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?><?php echo !empty($kelas_filter) ? '&kelas_id='.$kelas_filter : ''; ?><?php echo !empty($mapel_filter) ? '&mapel_id='.$mapel_filter : ''; ?><?php echo !empty($tahun_filter) ? '&tahun_ajaran='.$tahun_filter : ''; ?>">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </li>
        <?php endif; ?>
    </ul>
</nav>
<?php endif; ?>
                
                <div class="card-footer bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">Total <?php echo $total_records; ?> data nilai</span>
                        <?php if($total_records > 0): ?>
                        <span class="text-muted small">Menampilkan <?php echo $start + 1; ?> - <?php echo min($start + $limit, $total_records); ?> dari <?php echo $total_records; ?> data</span>
                        <?php endif; ?>
                    </div>
                </div>
                </div>
            </div>
            
            <div class="mt-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Informasi Penilaian</h5>
                    </div>
                    <div class="card-body">
                        <ul class="mb-0">
                            <li>Nilai siswa digunakan sebagai salah satu atribut dalam algoritma C4.5 untuk penjurusan.</li>
                            <li>Kategori mata pelajaran (IPA/IPS) menentukan bobot dalam perhitungan penjurusan.</li>
                            <li>Pastikan data nilai lengkap sebelum melakukan proses penjurusan.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal Mata Pelajaran -->
<div class="modal fade" id="mapelModal" tabindex="-1" aria-labelledby="mapelModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="mapelModalLabel">Kelola Mata Pelajaran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Button Add Mapel -->
                <div class="d-flex justify-content-end mb-3">
                    <button class="btn btn-sm btn-primary" id="btnAddMapel">
                        <i class="fas fa-plus me-1"></i> Tambah Mata Pelajaran
                    </button>
                </div>
                
                <!-- Mapel Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th width="5%">No</th>
                                <th width="15%">Kode</th>
                                <th width="45%">Nama Mata Pelajaran</th>
                                <th width="15%">Kategori</th>
                                <th width="20%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no_mapel = 1;
                            if (mysqli_num_rows($result_all_mapel) > 0):
                                while ($mapel = mysqli_fetch_assoc($result_all_mapel)):
                            ?>
                            <tr>
                                <td><?php echo $no_mapel++; ?></td>
                                <td><?php echo $mapel['kode']; ?></td>
                                <td><?php echo $mapel['nama']; ?></td>
                                <td>
                                    <span class="badge <?php echo $mapel['kategori'] == 'IPA' ? 'bg-info' : 'bg-warning'; ?>">
                                        <?php echo $mapel['kategori']; ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-warning btn-edit-mapel" 
                                            data-id="<?php echo $mapel['id']; ?>"
                                            data-kode="<?php echo $mapel['kode']; ?>"
                                            data-nama="<?php echo $mapel['nama']; ?>"
                                            data-kategori="<?php echo $mapel['kategori']; ?>">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="btn btn-sm btn-danger btn-delete-mapel" 
                                            data-id="<?php echo $mapel['id']; ?>"
                                            data-nama="<?php echo $mapel['nama']; ?>">
                                        <i class="fas fa-trash"></i> Hapus
                                    </button>
                                </td>
                            </tr>
                            <?php 
                                endwhile;
                            else:
                            ?>
                            <tr>
                                <td colspan="5" class="text-center">Tidak ada data mata pelajaran</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Form Mapel -->
<div class="modal fade" id="formMapelModal" tabindex="-1" aria-labelledby="formMapelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="formMapelModalLabel">Tambah Mata Pelajaran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formMapel" action="mapel_proses.php" method="POST">
                <!-- Ini perlu diletakkan di modal form mapel -->
<div class="modal-body">
    <input type="hidden" name="id" id="mapel_id">
    <input type="hidden" name="action" id="mapel_action" value="add">
    
    <!-- Kode tidak ditampilkan untuk tambah baru, hanya untuk edit -->
    <div class="mb-3" id="kode_field" style="display: none;">
        <label for="kode" class="form-label">Kode Mata Pelajaran</label>
        <input type="text" class="form-control" id="kode" name="kode" readonly>
        <small class="form-text text-muted">Kode dibuat otomatis berdasarkan kategori mata pelajaran</small>
    </div>
    
    <div class="mb-3">
        <label for="nama" class="form-label">Nama Mata Pelajaran</label>
        <input type="text" class="form-control" id="nama" name="nama" required>
    </div>
    
    <div class="mb-3">
        <label for="kategori" class="form-label">Kategori</label>
        <select class="form-select" id="kategori" name="kategori" required>
            <option value="IPA">IPA</option>
            <option value="IPS">IPS</option>
        </select>
    </div>
</div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Delete Confirmation -->
<div class="modal fade" id="deleteMapelModal" tabindex="-1" aria-labelledby="deleteMapelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteMapelModalLabel">Konfirmasi Hapus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus mata pelajaran <strong id="delete-mapel-name"></strong>?</p>
                <p class="text-danger">Perhatian: Menghapus mata pelajaran akan menghapus semua nilai terkait!</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <form action="mapel_proses.php" method="POST">
                    <input type="hidden" name="id" id="delete_mapel_id">
                    <input type="hidden" name="action" value="delete">
                    <button type="submit" class="btn btn-danger">Hapus</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto close alerts after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
    
    // Add Mapel Button
    document.getElementById('btnAddMapel').addEventListener('click', function() {
        document.getElementById('formMapelModalLabel').textContent = 'Tambah Mata Pelajaran';
        document.getElementById('formMapel').reset();
        document.getElementById('mapel_action').value = 'add';
        document.getElementById('mapel_id').value = '';
        
        const formMapelModal = new bootstrap.Modal(document.getElementById('formMapelModal'));
        formMapelModal.show();
    });
    
   // Tampilkan field kode hanya saat edit
   document.getElementById('btnAddMapel').addEventListener('click', function() {
        document.getElementById('formMapelModalLabel').textContent = 'Tambah Mata Pelajaran';
        document.getElementById('formMapel').reset();
        document.getElementById('mapel_action').value = 'add';
        document.getElementById('mapel_id').value = '';
        document.getElementById('kode_field').style.display = 'none'; // Sembunyikan field kode
        
        const formMapelModal = new bootstrap.Modal(document.getElementById('formMapelModal'));
        formMapelModal.show();
    });
    
    // Edit Mapel Buttons
    const editButtons = document.querySelectorAll('.btn-edit-mapel');
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const kode = this.getAttribute('data-kode');
            const nama = this.getAttribute('data-nama');
            const kategori = this.getAttribute('data-kategori');
            
            document.getElementById('formMapelModalLabel').textContent = 'Edit Mata Pelajaran';
            document.getElementById('mapel_id').value = id;
            document.getElementById('kode').value = kode;
            document.getElementById('nama').value = nama;
            document.getElementById('kategori').value = kategori;
            document.getElementById('mapel_action').value = 'edit';
            document.getElementById('kode_field').style.display = 'block'; // Tampilkan field kode untuk edit
            
            const formMapelModal = new bootstrap.Modal(document.getElementById('formMapelModal'));
            formMapelModal.show();
        });
    });
    // Delete Mapel Buttons
    const deleteButtons = document.querySelectorAll('.btn-delete-mapel');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const nama = this.getAttribute('data-nama');
            
            document.getElementById('delete_mapel_id').value = id;
            document.getElementById('delete-mapel-name').textContent = nama;
            
            const deleteMapelModal = new bootstrap.Modal(document.getElementById('deleteMapelModal'));
            deleteMapelModal.show();
        });
    });
});
</script>


<?php require_once '../include/footer.php'; 
ob_end_flush();
?>

