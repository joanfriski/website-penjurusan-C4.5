<?php
require_once '../include/header.php';
require_once '../../database/config.php';

// Filter
$kelas_id = isset($_GET['kelas_id']) ? $_GET['kelas_id'] : '';
$jenis_kelamin = isset($_GET['jenis_kelamin']) ? $_GET['jenis_kelamin'] : '';

// Query untuk data kelas (untuk filter)
$query_kelas_filter = "SELECT id, nama_kelas FROM kelas ORDER BY nama_kelas";
$result_kelas_filter = mysqli_query($conn, $query_kelas_filter);

// Base query
$query = "SELECT s.id, s.nis, s.nisn, s.nama_lengkap, s.jenis_kelamin, 
          s.tempat_lahir, s.tanggal_lahir, k.nama_kelas, 
          (SELECT COUNT(*) FROM hasil_klasifikasi WHERE siswa_id = s.id) as sudah_jurusan,
          (SELECT hasil_prediksi FROM hasil_klasifikasi WHERE siswa_id = s.id LIMIT 1) as hasil_prediksi
          FROM siswa s
          LEFT JOIN kelas k ON s.kelas_id = k.id
          WHERE s.status = 'aktif'";

// Apply filters
if (!empty($kelas_id)) {
    $query .= " AND s.kelas_id = " . mysqli_real_escape_string($conn, $kelas_id);
}
if (!empty($jenis_kelamin)) {
    $query .= " AND s.jenis_kelamin = '" . mysqli_real_escape_string($conn, $jenis_kelamin) . "'";
}

$query .= " ORDER BY k.nama_kelas, s.nama_lengkap";
$result = mysqli_query($conn, $query);

// Statistik untuk ringkasan
$query_summary = "SELECT 
                COUNT(CASE WHEN s.jenis_kelamin = 'L' THEN 1 END) as total_laki,
                COUNT(CASE WHEN s.jenis_kelamin = 'P' THEN 1 END) as total_perempuan,
                COUNT(CASE WHEN hk.id IS NOT NULL THEN 1 END) as total_terjuruskan,
                COUNT(CASE WHEN hk.id IS NULL THEN 1 END) as total_belum_jurusan
                FROM siswa s
                LEFT JOIN hasil_klasifikasi hk ON s.id = hk.siswa_id
                WHERE s.status = 'aktif'";

$result_summary = mysqli_query($conn, $query_summary);
$summary = mysqli_fetch_assoc($result_summary);

// Query untuk statistik jurusan
$query_jurusan = "SELECT 
                 COUNT(CASE WHEN hasil_prediksi = 'IPA' THEN 1 END) as total_ipa,
                 COUNT(CASE WHEN hasil_prediksi = 'IPS' THEN 1 END) as total_ips
                 FROM hasil_klasifikasi";
$result_jurusan = mysqli_query($conn, $query_jurusan);
$jurusan = mysqli_fetch_assoc($result_jurusan);
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
        justify-content: center;
        margin-top: 15px;
    }
    
    .search-form {
        margin-bottom: 15px;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <?php require_once '../include/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4 content-wrapper" id="content">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h2 class="h3">
                    <i class="fas fa-users me-2"></i>Laporan Data Siswa
                </h2>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="cetak.php?type=siswa<?php echo !empty($kelas_id) ? '&kelas_id='.$kelas_id : ''; ?><?php echo !empty($jenis_kelamin) ? '&jenis_kelamin='.$jenis_kelamin : ''; ?>" class="btn btn-sm btn-outline-secondary" target="_blank">
                            <i class="fas fa-print me-1"></i>Cetak
                        </a>
                        <a href="index.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Kembali
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Filter Laporan</h5>
                </div>
                <div class="card-body">
                    <form action="" method="GET" class="row g-3">
                        <div class="col-md-5">
                            <label for="kelas_id" class="form-label">Kelas</label>
                            <select name="kelas_id" id="kelas_id" class="form-select">
                                <option value="">Semua Kelas</option>
                                <?php while($kelas = mysqli_fetch_assoc($result_kelas_filter)): ?>
                                <option value="<?php echo $kelas['id']; ?>" <?php echo ($kelas_id == $kelas['id']) ? 'selected' : ''; ?>>
                                    <?php echo $kelas['nama_kelas']; ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label for="jenis_kelamin" class="form-label">Jenis Kelamin</label>
                            <select name="jenis_kelamin" id="jenis_kelamin" class="form-select">
                                <option value="">Semua</option>
                                <option value="L" <?php echo ($jenis_kelamin == 'L') ? 'selected' : ''; ?>>Laki-laki</option>
                                <option value="P" <?php echo ($jenis_kelamin == 'P') ? 'selected' : ''; ?>>Perempuan</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter me-1"></i>Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white h-100">
                        <div class="card-body">
                            <h5 class="card-title">Total Siswa</h5>
                            <h2 class="display-5"><?php echo ($summary['total_laki'] + $summary['total_perempuan']); ?></h2>
                            <p class="card-text">Total siswa aktif saat ini</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white h-100">
                        <div class="card-body">
                            <h5 class="card-title">Siswa Terjuruskan</h5>
                            <h2 class="display-5"><?php echo $summary['total_terjuruskan']; ?></h2>
                            <p class="card-text">Siswa yang sudah terjuruskan</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white h-100">
                        <div class="card-body">
                            <h5 class="card-title">IPA</h5>
                            <h2 class="display-5"><?php echo $jurusan['total_ipa']; ?></h2>
                            <p class="card-text">Siswa dengan jurusan IPA</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white h-100">
                        <div class="card-body">
                            <h5 class="card-title">IPS</h5>
                            <h2 class="display-5"><?php echo $jurusan['total_ips']; ?></h2>
                            <p class="card-text">Siswa dengan jurusan IPS</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Data Siswa</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="dataSiswa">
                            <thead class="table-light">
                                <tr>
                                    <th>No</th>
                                    <th>NIS</th>
                                    <th>NISN</th>
                                    <th>Nama Lengkap</th>
                                    <th>L/P</th>
                                    <th>TTL</th>
                                    <th>Kelas</th>
                                    <th>Status Jurusan</th>
                                    <th>Hasil Prediksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no = 1;
                                if (mysqli_num_rows($result) > 0) {
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        $status_jurusan = $row['sudah_jurusan'] > 0 ? "<span class='badge bg-success'>Sudah</span>" : "<span class='badge bg-danger'>Belum</span>";
                                        $hasil_prediksi = $row['hasil_prediksi'] ? "<span class='badge bg-" . ($row['hasil_prediksi'] == 'IPA' ? 'info' : 'warning') . "'>{$row['hasil_prediksi']}</span>" : "-";
                                        $ttl = $row['tempat_lahir'] . ', ' . date('d-m-Y', strtotime($row['tanggal_lahir']));
                                ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo $row['nis']; ?></td>
                                    <td><?php echo $row['nisn']; ?></td>
                                    <td><?php echo $row['nama_lengkap']; ?></td>
                                    <td><?php echo $row['jenis_kelamin']; ?></td>
                                    <td><?php echo $ttl; ?></td>
                                    <td><?php echo $row['nama_kelas']; ?></td>
                                    <td><?php echo $status_jurusan; ?></td>
                                    <td><?php echo $hasil_prediksi; ?></td>
                                </tr>
                                <?php
                                    }
                                } else {
                                ?>
                                <tr>
                                    <td colspan="9" class="text-center">Data tidak ditemukan</td>
                                </tr>
                                <?php
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#dataSiswa').DataTable({
            "pageLength": 10,
            "language": {
                "lengthMenu": "Tampilkan _MENU_ data per halaman",
                "zeroRecords": "Data tidak ditemukan",
                "info": "Menampilkan halaman _PAGE_ dari _PAGES_",
                "infoEmpty": "Tidak ada data yang tersedia",
                "infoFiltered": "(difilter dari _MAX_ total data)",
                "search": "Cari:",
                "paginate": {
                    "first": "Pertama",
                    "last": "Terakhir",
                    "next": "Selanjutnya",
                    "previous": "Sebelumnya"
                }
            }
        });
    });
</script>

<?php require_once '../include/footer.php'; ?>