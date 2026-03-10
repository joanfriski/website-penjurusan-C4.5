<?php
require_once '../include/header.php';
require_once '../../database/config.php';

// Filter
$kelas_id = isset($_GET['kelas_id']) ? $_GET['kelas_id'] : '';
$hasil_prediksi = isset($_GET['hasil_prediksi']) ? $_GET['hasil_prediksi'] : '';

// Query untuk data kelas (untuk filter)
$query_kelas_filter = "SELECT id, nama_kelas FROM kelas ORDER BY nama_kelas";
$result_kelas_filter = mysqli_query($conn, $query_kelas_filter);

// Base query
$query = "SELECT p.id, s.nis, s.nama_lengkap, k.nama_kelas, p.nilai_mapel_ipa, 
          p.nilai_mapel_ips, p.nilai_iq, p.kategori_iq, p.minat, p.hasil_prediksi, p.created_at 
          FROM hasil_klasifikasi p
          JOIN siswa s ON p.siswa_id = s.id
          LEFT JOIN kelas k ON s.kelas_id = k.id
          WHERE 1=1";

// Apply filters
if (!empty($kelas_id)) {
    $query .= " AND s.kelas_id = " . mysqli_real_escape_string($conn, $kelas_id);
}
if (!empty($hasil_prediksi)) {
    $query .= " AND p.hasil_prediksi = '" . mysqli_real_escape_string($conn, $hasil_prediksi) . "'";
}

$query .= " ORDER BY s.nama_lengkap";
$result = mysqli_query($conn, $query);

// Hitung jumlah per jurusan untuk ringkasan
$query_ipa = "SELECT COUNT(*) as total FROM hasil_klasifikasi WHERE hasil_prediksi = 'IPA'";
$result_ipa = mysqli_query($conn, $query_ipa);
$total_ipa = mysqli_fetch_assoc($result_ipa)['total'];

$query_ips = "SELECT COUNT(*) as total FROM hasil_klasifikasi WHERE hasil_prediksi = 'IPS'";
$result_ips = mysqli_query($conn, $query_ips);
$total_ips = mysqli_fetch_assoc($result_ips)['total'];
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
                    <i class="fas fa-sitemap me-2"></i>Laporan Hasil Penjurusan
                </h2>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="cetak_penjurusan.php?type=penjurusan<?php echo !empty($kelas_id) ? '&kelas_id='.$kelas_id : ''; ?><?php echo !empty($hasil_prediksi) ? '&hasil_prediksi='.$hasil_prediksi : ''; ?>" class="btn btn-sm btn-outline-secondary" target="_blank">
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
                            <label for="hasil_prediksi" class="form-label">Hasil Penjurusan</label>
                            <select name="hasil_prediksi" id="hasil_prediksi" class="form-select">
                                <option value="">Semua Jurusan</option>
                                <option value="IPA" <?php echo ($hasil_prediksi == 'IPA') ? 'selected' : ''; ?>>IPA</option>
                                <option value="IPS" <?php echo ($hasil_prediksi == 'IPS') ? 'selected' : ''; ?>>IPS</option>
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
                <div class="col-md-4">
                    <div class="card bg-info text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title">Total Siswa Terjuruskan</h5>
                                    <p class="card-text display-6"><?php echo $total_ipa + $total_ips; ?></p>
                                </div>
                                <i class="fas fa-users fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-primary text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title">Jurusan IPA</h5>
                                    <p class="card-text display-6"><?php echo $total_ipa; ?></p>
                                </div>
                                <i class="fas fa-flask fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-danger text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title">Jurusan IPS</h5>
                                    <p class="card-text display-6"><?php echo $total_ips; ?></p>
                                </div>
                                <i class="fas fa-book fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Data Hasil Penjurusan</h5>
                </div>
                <div class="card-body">
                    <?php if(mysqli_num_rows($result) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-bordered" id="penjurusanTable">
                            <thead class="table-primary">
                                <tr>
                                    <th>No</th>
                                    <th>NIS</th>
                                    <th>Nama Siswa</th>
                                    <th>Kelas</th>
                                    <th>Nilai IPA</th>
                                    <th>Nilai IPS</th>
                                    <th>Nilai IQ</th>
                                    <th>Kategori IQ</th>
                                    <th>Minat</th>
                                    <th>Hasil Penjurusan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                while($row = mysqli_fetch_assoc($result)): 
                                ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo $row['nis']; ?></td>
                                    <td><?php echo $row['nama_lengkap']; ?></td>
                                    <td><?php echo $row['nama_kelas']; ?></td>
                                    <td><?php echo $row['nilai_mapel_ipa']; ?></td>
                                    <td><?php echo $row['nilai_mapel_ips']; ?></td>
                                    <td><?php echo $row['nilai_iq']; ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo ($row['kategori_iq'] == 'Tinggi') ? 'success' : 
                                                (($row['kategori_iq'] == 'Sedang') ? 'info' : 'warning'); 
                                        ?>">
                                            <?php echo $row['kategori_iq']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $row['minat']; ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo ($row['hasil_prediksi'] == 'IPA') ? 'primary' : 'danger'; ?>">
                                            <?php echo $row['hasil_prediksi']; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>Tidak ada data penjurusan yang ditemukan.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- DataTables -->
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(document).ready(function() {
        $('#penjurusanTable').DataTable({
            "paging": true,
            "lengthChange": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/id.json"
            }
        });
    });
</script>

<?php require_once '../include/footer.php'; ?>