<?php
ob_start();
require_once '../../database/config.php';
require_once '../include/header.php';

// Mengatur jumlah data per halaman
$per_page = 10;

// Fungsi untuk mengambil data siswa yang belum diprediksi dengan pagination
function getSiswaBelumPrediksi($conn, $page, $per_page, $search = '') {
    $offset = ($page - 1) * $per_page;
    
    $search_condition = '';
    if (!empty($search)) {
        $search = mysqli_real_escape_string($conn, $search);
        $search_condition = "AND (s.nis LIKE '%$search%' OR s.nama_lengkap LIKE '%$search%' OR k.nama_kelas LIKE '%$search%')";
    }
    
    $query = "SELECT s.id, s.nis, s.nama_lengkap, k.nama_kelas, 
            AVG(CASE WHEN mp.kategori = 'IPA' THEN n.nilai ELSE NULL END) AS nilai_ipa,
            AVG(CASE WHEN mp.kategori = 'IPS' THEN n.nilai ELSE NULL END) AS nilai_ips,
            ti.skor AS nilai_iq,
            ms.minat
            FROM siswa s
            JOIN kelas k ON s.kelas_id = k.id
            LEFT JOIN nilai n ON s.id = n.siswa_id
            LEFT JOIN mata_pelajaran mp ON n.mapel_id = mp.id
            LEFT JOIN test_iq ti ON s.id = ti.siswa_id AND ti.id = (
                SELECT MAX(id) FROM test_iq WHERE siswa_id = s.id
            )
            LEFT JOIN minat_siswa ms ON s.id = ms.siswa_id
            LEFT JOIN prediksi_jurusan pj ON s.id = pj.siswa_id
            WHERE pj.id IS NULL AND s.status = 'aktif' $search_condition
            GROUP BY s.id, s.nis, s.nama_lengkap, k.nama_kelas, ti.skor, ms.minat
            HAVING nilai_ipa IS NOT NULL 
            AND nilai_ips IS NOT NULL 
            AND nilai_iq IS NOT NULL 
            AND minat IS NOT NULL
            ORDER BY k.nama_kelas, s.nama_lengkap
            LIMIT $per_page OFFSET $offset";
    
    return mysqli_query($conn, $query);
}

// Fungsi untuk menghitung total jumlah data siswa belum diprediksi
function getTotalSiswaBelumPrediksi($conn, $search = '') {
    $search_condition = '';
    if (!empty($search)) {
        $search = mysqli_real_escape_string($conn, $search);
        $search_condition = "AND (s.nis LIKE '%$search%' OR s.nama_lengkap LIKE '%$search%' OR k.nama_kelas LIKE '%$search%')";
    }
    
    $query = "SELECT COUNT(*) AS total FROM (
                SELECT s.id
                FROM siswa s
                JOIN kelas k ON s.kelas_id = k.id
                LEFT JOIN nilai n ON s.id = n.siswa_id
                LEFT JOIN mata_pelajaran mp ON n.mapel_id = mp.id
                LEFT JOIN test_iq ti ON s.id = ti.siswa_id AND ti.id = (
                    SELECT MAX(id) FROM test_iq WHERE siswa_id = s.id
                )
                LEFT JOIN minat_siswa ms ON s.id = ms.siswa_id
                LEFT JOIN prediksi_jurusan pj ON s.id = pj.siswa_id
                WHERE pj.id IS NULL AND s.status = 'aktif' $search_condition
                GROUP BY s.id, s.nis, s.nama_lengkap, k.nama_kelas, ti.skor, ms.minat
                HAVING AVG(CASE WHEN mp.kategori = 'IPA' THEN n.nilai ELSE NULL END) IS NOT NULL 
                AND AVG(CASE WHEN mp.kategori = 'IPS' THEN n.nilai ELSE NULL END) IS NOT NULL 
                AND ti.skor IS NOT NULL 
                AND ms.minat IS NOT NULL
            ) AS subquery";
    
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    return $row['total'];
}

// Fungsi untuk mengambil data siswa yang sudah diprediksi dengan pagination
function getSiswaSudahPrediksi($conn, $page, $per_page, $search = '') {
    $offset = ($page - 1) * $per_page;
    
    $search_condition = '';
    if (!empty($search)) {
        $search = mysqli_real_escape_string($conn, $search);
        $search_condition = "AND (s.nis LIKE '%$search%' OR s.nama_lengkap LIKE '%$search%' OR k.nama_kelas LIKE '%$search%' OR pj.hasil_prediksi LIKE '%$search%')";
    }
    
    $query = "SELECT s.id, s.nis, s.nama_lengkap, k.nama_kelas, 
            pj.nilai_mapel_ipa, pj.nilai_mapel_ips,
            pj.nilai_iq, pj.minat, pj.hasil_prediksi,
            pj.id as prediksi_id
            FROM siswa s
            JOIN kelas k ON s.kelas_id = k.id
            JOIN prediksi_jurusan pj ON s.id = pj.siswa_id
            WHERE s.status = 'aktif' $search_condition
            ORDER BY k.nama_kelas, s.nama_lengkap
            LIMIT $per_page OFFSET $offset";
    
    return mysqli_query($conn, $query);
}

// Fungsi untuk menghitung total jumlah data siswa yang sudah diprediksi
function getTotalSiswaSudahPrediksi($conn, $search = '') {
    $search_condition = '';
    if (!empty($search)) {
        $search = mysqli_real_escape_string($conn, $search);
        $search_condition = "AND (s.nis LIKE '%$search%' OR s.nama_lengkap LIKE '%$search%' OR k.nama_kelas LIKE '%$search%' OR pj.hasil_prediksi LIKE '%$search%')";
    }
    
    $query = "SELECT COUNT(*) AS total FROM siswa s
              JOIN kelas k ON s.kelas_id = k.id
              JOIN prediksi_jurusan pj ON s.id = pj.siswa_id
              WHERE s.status = 'aktif' $search_condition";
    
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    return $row['total'];
}

// Ambil parameter pencarian dan halaman dari URL
$search_belum = isset($_GET['search_belum']) ? $_GET['search_belum'] : '';
$search_sudah = isset($_GET['search_sudah']) ? $_GET['search_sudah'] : '';
$page_belum = isset($_GET['page_belum']) ? (int)$_GET['page_belum'] : 1;
$page_sudah = isset($_GET['page_sudah']) ? (int)$_GET['page_sudah'] : 1;

// Pastikan nilai halaman tidak negatif
$page_belum = max(1, $page_belum);
$page_sudah = max(1, $page_sudah);

// Hitung total data dan halaman
$total_belum = getTotalSiswaBelumPrediksi($conn, $search_belum);
$total_pages_belum = ceil($total_belum / $per_page);

$total_sudah = getTotalSiswaSudahPrediksi($conn, $search_sudah);
$total_pages_sudah = ceil($total_sudah / $per_page);

// Ambil data sesuai halaman
$result_belum = getSiswaBelumPrediksi($conn, $page_belum, $per_page, $search_belum);
$result_sudah = getSiswaSudahPrediksi($conn, $page_sudah, $per_page, $search_sudah);
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
                    <i class="fas fa-user-graduate me-2"></i>Data Penjurusan Siswa
                </h2>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="proses.php" class="btn btn-sm btn-primary">
                        <i class="fas fa-calculator me-1"></i> Proses Penjurusan Batch
                    </a>
                </div>
            </div>
            
            <!-- Tabel Siswa yang Belum Diprediksi -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Daftar Siswa yang Belum Diprediksi Jurusan</h5>
                </div>
                <div class="card-body">
                    <!-- Form pencarian untuk siswa belum diprediksi -->
                    <form class="search-form" method="get">
                        <div class="input-group mb-3">
                            <input type="text" class="form-control" placeholder="Cari berdasarkan NIS, Nama, atau Kelas" name="search_belum" value="<?php echo htmlspecialchars($search_belum); ?>">
                            <button class="btn btn-outline-primary" type="submit">Cari</button>
                            <?php if (!empty($search_belum)): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['search_belum' => ''])); ?>" class="btn btn-outline-secondary">Reset</a>
                            <?php endif; ?>
                        </div>
                    </form>
                    
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>NIS</th>
                                    <th>Nama Lengkap</th>
                                    <th>Kelas</th>
                                    <th>Nilai IPA</th>
                                    <th>Nilai IPS</th>
                                    <th>IQ</th>
                                    <th>Minat</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                            // Tampilkan data siswa belum diprediksi
                            if (mysqli_num_rows($result_belum) > 0) {
                                $no = ($page_belum - 1) * $per_page + 1;
                                while ($row = mysqli_fetch_assoc($result_belum)) {
                                    echo "<tr>";
                                    echo "<td>" . $no++ . "</td>";
                                    echo "<td>" . htmlspecialchars($row['nis']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['nama_lengkap']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['nama_kelas']) . "</td>";
                                    echo "<td>" . ($row['nilai_ipa'] ? number_format($row['nilai_ipa'], 2) : '-') . "</td>";
                                    echo "<td>" . ($row['nilai_ips'] ? number_format($row['nilai_ips'], 2) : '-') . "</td>";
                                    echo "<td>" . ($row['nilai_iq'] ?: '-') . "</td>";
                                    echo "<td>" . ($row['minat'] ?: '-') . "</td>";
                                    echo "<td>";
                                    echo "<a href='prediksi.php?id=" . $row['id'] . "' class='btn btn-sm btn-success me-1'><i class='fas fa-calculator'></i> Prediksi</a>";
                                    echo "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='9' class='text-center'>Tidak ada siswa yang perlu diprediksi jurusannya</td></tr>";
                            }
                            ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination untuk siswa belum diprediksi -->
                    <?php if ($total_pages_belum > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination">
                            <?php if ($page_belum > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page_belum' => $page_belum - 1])); ?>">Sebelumnya</a>
                            </li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page_belum - 2); $i <= min($total_pages_belum, $page_belum + 2); $i++): ?>
                            <li class="page-item <?php echo $i == $page_belum ? 'active' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page_belum' => $i])); ?>"><?php echo $i; ?></a>
                            </li>
                            <?php endfor; ?>
                            
                            <?php if ($page_belum < $total_pages_belum): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page_belum' => $page_belum + 1])); ?>">Selanjutnya</a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                    <?php endif; ?>
                    
                    <p class="text-center text-muted">
                        Menampilkan <?php echo $total_belum > 0 ? (($page_belum - 1) * $per_page + 1) . ' - ' . min($page_belum * $per_page, $total_belum) : 0; ?> 
                        dari <?php echo $total_belum; ?> data
                    </p>
                </div>
            </div>
            
            <!-- Tabel Siswa yang Sudah Diprediksi -->
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Daftar Siswa yang Sudah Diprediksi Jurusan</h5>
                </div>
                <div class="card-body">
                    <!-- Form pencarian untuk siswa sudah diprediksi -->
                    <form class="search-form" method="get">
                        <div class="input-group mb-3">
                            <input type="text" class="form-control" placeholder="Cari berdasarkan NIS, Nama, Kelas, atau Jurusan" name="search_sudah" value="<?php echo htmlspecialchars($search_sudah); ?>">
                            <button class="btn btn-outline-primary" type="submit">Cari</button>
                            <?php if (!empty($search_sudah)): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['search_sudah' => ''])); ?>" class="btn btn-outline-secondary">Reset</a>
                            <?php endif; ?>
                        </div>
                    </form>
                    
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>NIS</th>
                                    <th>Nama Lengkap</th>
                                    <th>Kelas</th>
                                    <th>Nilai IPA</th>
                                    <th>Nilai IPS</th>
                                    <th>IQ</th>
                                    <th>Minat</th>
                                    <th>Hasil Prediksi</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Tampilkan data siswa sudah diprediksi
                                if (mysqli_num_rows($result_sudah) > 0) {
                                    $no = ($page_sudah - 1) * $per_page + 1;
                                    while ($row = mysqli_fetch_assoc($result_sudah)) {
                                        echo "<tr>";
                                        echo "<td>" . $no++ . "</td>";
                                        echo "<td>" . htmlspecialchars($row['nis']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['nama_lengkap']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['nama_kelas']) . "</td>";
                                        echo "<td>" . number_format($row['nilai_mapel_ipa'], 2) . "</td>";
                                        echo "<td>" . number_format($row['nilai_mapel_ips'], 2) . "</td>";
                                        echo "<td>" . $row['nilai_iq'] . "</td>";
                                        echo "<td>" . $row['minat'] . "</td>";
                                        echo "<td><span class='badge bg-" . ($row['hasil_prediksi'] == 'IPA' ? 'primary' : 'success') . "'>" . $row['hasil_prediksi'] . "</span></td>";
                                        echo "<td>";
                                        echo "<a href='detail.php?id=" . $row['prediksi_id'] . "' class='btn btn-sm btn-info me-1'><i class='fas fa-eye'></i></a>";
                                        echo "<a href='hapus.php?id=" . $row['prediksi_id'] . "' class='btn btn-sm btn-danger' onclick='return confirm(\"Yakin ingin menghapus data prediksi ini?\")'><i class='fas fa-trash'></i></a>";
                                        echo "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='10' class='text-center'>Belum ada siswa yang diprediksi jurusannya</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination untuk siswa sudah diprediksi -->
                    <?php if ($total_pages_sudah > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination">
                            <?php if ($page_sudah > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page_sudah' => $page_sudah - 1])); ?>">Sebelumnya</a>
                            </li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page_sudah - 2); $i <= min($total_pages_sudah, $page_sudah + 2); $i++): ?>
                            <li class="page-item <?php echo $i == $page_sudah ? 'active' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page_sudah' => $i])); ?>"><?php echo $i; ?></a>
                            </li>
                            <?php endfor; ?>
                            
                            <?php if ($page_sudah < $total_pages_sudah): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page_sudah' => $page_sudah + 1])); ?>">Selanjutnya</a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                    <?php endif; ?>
                    
                    <p class="text-center text-muted">
                        Menampilkan <?php echo $total_sudah > 0 ? (($page_sudah - 1) * $per_page + 1) . ' - ' . min($page_sudah * $per_page, $total_sudah) : 0; ?> 
                        dari <?php echo $total_sudah; ?> data
                    </p>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
$(document).ready(function() {
    // Menghilangkan DataTables karena sudah menggunakan pagination kustom
    // Jika ingin tetap menggunakan DataTables, hapus pagination kustom dan sesuaikan
});
</script>


<?php require_once '../include/footer.php'; 
ob_end_flush();
?>

