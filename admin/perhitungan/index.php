<?php
/**
 * Halaman Data Siswa dengan Penilaian Lengkap
 * Menampilkan daftar siswa yang memiliki data nilai IPA, IPS, IQ, dan minat
 */

ob_start();
require_once '../../database/config.php';
require_once '../include/header.php';

// ==========================================
// KONFIGURASI PAGINATION DAN PENCARIAN
// ==========================================

// Konfigurasi jumlah data per halaman
$per_page = 25;

// Ambil halaman saat ini dari parameter URL
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page > 1) ? ($page * $per_page) - $per_page : 0;

// Ambil keyword pencarian dari parameter URL
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// ==========================================
// QUERY UNTUK MENGHITUNG TOTAL DATA
// ==========================================

$query_count = "SELECT COUNT(DISTINCT s.id) as total 
                FROM siswa s
                JOIN kelas k ON s.kelas_id = k.id
                LEFT JOIN nilai n ON s.id = n.siswa_id
                LEFT JOIN mata_pelajaran mp ON n.mapel_id = mp.id
                LEFT JOIN test_iq ti ON s.id = ti.siswa_id AND ti.id = (
                    SELECT MAX(id) FROM test_iq WHERE siswa_id = s.id
                )
                LEFT JOIN minat_siswa ms ON s.id = ms.siswa_id
                WHERE s.status = 'aktif'";

// Tambahkan kondisi pencarian jika ada
if (!empty($search)) {
    $query_count .= " AND (s.nis LIKE '%$search%' 
                          OR s.nama_lengkap LIKE '%$search%' 
                          OR k.nama_kelas LIKE '%$search%')";
}

// Filter hanya siswa dengan data lengkap
$query_count .= " GROUP BY s.id
                  HAVING COUNT(CASE WHEN mp.kategori = 'IPA' THEN n.nilai END) > 0 
                  AND COUNT(CASE WHEN mp.kategori = 'IPS' THEN n.nilai END) > 0 
                  AND COUNT(ti.id) > 0 
                  AND COUNT(ms.id) > 0";

$result_count = mysqli_query($conn, $query_count);
$total_data = mysqli_num_rows($result_count);
$total_pages = ceil($total_data / $per_page);

// ==========================================
// QUERY UTAMA UNTUK MENGAMBIL DATA SISWA
// ==========================================

$query = "SELECT s.id, 
                 s.nis, 
                 s.nama_lengkap, 
                 k.nama_kelas,
                 AVG(CASE WHEN mp.kategori = 'IPA' THEN n.nilai ELSE NULL END) AS nilai_ipa,
                 AVG(CASE WHEN mp.kategori = 'IPS' THEN n.nilai ELSE NULL END) AS nilai_ips,
                 ti.skor AS nilai_iq,
                 ti.kategori AS kategori_iq,
                 ms.minat,
                 s.status_klasifikasi
          FROM siswa s
          JOIN kelas k ON s.kelas_id = k.id
          LEFT JOIN nilai n ON s.id = n.siswa_id
          LEFT JOIN mata_pelajaran mp ON n.mapel_id = mp.id
          LEFT JOIN test_iq ti ON s.id = ti.siswa_id AND ti.id = (
              SELECT MAX(id) FROM test_iq WHERE siswa_id = s.id
          )
          LEFT JOIN minat_siswa ms ON s.id = ms.siswa_id
          WHERE s.status = 'aktif' AND s.status_klasifikasi = 'Belum Diklasifikasi'";

// Tambahkan kondisi pencarian jika ada
if (!empty($search)) {
    $query .= " AND (s.nis LIKE '%$search%' 
                     OR s.nama_lengkap LIKE '%$search%' 
                     OR k.nama_kelas LIKE '%$search%')";
}

// Grouping dan filtering data lengkap
$query .= " GROUP BY s.id, s.nis, s.nama_lengkap, k.nama_kelas, ti.skor, ti.kategori, ms.minat, s.status_klasifikasi
            HAVING nilai_ipa IS NOT NULL 
            AND nilai_ips IS NOT NULL 
            AND nilai_iq IS NOT NULL 
            AND minat IS NOT NULL
            ORDER BY k.nama_kelas, s.nama_lengkap
            LIMIT $start, $per_page";

$result = mysqli_query($conn, $query);

?>

<!-- ==========================================
     CSS UNTUK RESPONSIVITAS
     ========================================== -->
<style>
    /* Styling untuk content wrapper */
    .content-wrapper {
        transition: all 0.3s ease;
        width: calc(100% - 250px);
        margin-left: 250px;
    }
    
    /* Saat sidebar dalam kondisi collapsed */
    .content-wrapper.expanded {
        width: 100%;
        margin-left: 0;
    }
    
    /* Responsive untuk perangkat mobile */
    @media (max-width: 768px) {
        .content-wrapper {
            width: 100%;
            margin-left: 0;
        }
    }
    
    /* Styling untuk main content */
    main {
        width: 100%;
        transition: all 0.3s ease;
    }
    
    /* Styling untuk badge status */
    .badge {
        font-size: 0.8em;
    }
    
    /* Styling untuk tabel responsive */
    .table-responsive {
        border-radius: 0.5rem;
        overflow: hidden;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <?php require_once '../include/sidebar.php'; ?>
        
        <!-- ==========================================
             KONTEN UTAMA
             ========================================== -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4 content-wrapper" id="content">
            
            <!-- Header Halaman -->
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h2 class="h3">
                    <i class="fas fa-chart-line me-2"></i>
                    Data Siswa dengan Penilaian Lengkap
                </h2>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="entropy_gain_dataset.php" class="btn btn-sm btn-primary me-2">
                        <i class="fas fa-plus me-1"></i> 
                        Tambah Klasifikasi Siswa
                    </a>
                </div>
            </div>

            <!-- Form Pencarian -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <form action="" method="GET" class="d-flex">
                        <div class="input-group">
                            <input type="text" 
                                   class="form-control" 
                                   placeholder="Cari berdasarkan NIS, Nama, atau Kelas..." 
                                   name="search" 
                                   value="<?= htmlspecialchars($search) ?>">
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-search"></i> Cari
                            </button>
                            <?php if (!empty($search)): ?>
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Reset
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
                
                <!-- Info Total Data -->
                <div class="col-md-6 text-end">
                    <small class="text-muted">
                        Menampilkan <?= mysqli_num_rows($result) ?> dari <?= $total_data ?> siswa
                        <?= !empty($search) ? 'yang sesuai pencarian' : '' ?>
                    </small>
                </div>
            </div>
            
            <!-- Tabel Data Siswa -->
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover" id="dataTable">
                    <thead class="table-dark">
                        <tr>
                            <th class="text-center" style="width: 5%;">No</th>
                            <th style="width: 10%;">NIS</th>
                            <th style="width: 20%;">Nama Lengkap</th>
                            <th style="width: 10%;">Kelas</th>
                            <th class="text-center" style="width: 10%;">Nilai IPA</th>
                            <th class="text-center" style="width: 10%;">Nilai IPS</th>
                            <th class="text-center" style="width: 10%;">Skor IQ</th>
                            <th class="text-center" style="width: 12%;">Kategori IQ</th>
                            <th class="text-center" style="width: 8%;">Minat</th>
                            <th class="text-center" style="width: 12%;">Status Klasifikasi</th>
                            <th class="text-center" style="width: 5%;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = $start + 1;
                        $ada_data = false;
                        if (mysqli_num_rows($result) > 0):
                            while($row = mysqli_fetch_assoc($result)):
                                $ada_data = true;
                        ?>
                        <tr>
                            <td class="text-center"><?= $no++ ?></td>
                            <td><?= htmlspecialchars($row['nis']) ?></td>
                            <td><?= htmlspecialchars($row['nama_lengkap']) ?></td>
                            <td><?= htmlspecialchars($row['nama_kelas']) ?></td>
                            <td class="text-center">
                                <span class="badge bg-info">
                                    <?= number_format($row['nilai_ipa'], 2) ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-warning text-dark">
                                    <?= number_format($row['nilai_ips'], 2) ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <strong><?= $row['nilai_iq'] ?></strong>
                            </td>
                            <td class="text-center">
                                <?php
                                $badge_class = '';
                                switch($row['kategori_iq']) {
                                    case 'Tinggi':
                                        $badge_class = 'bg-success';
                                        break;
                                    case 'Sedang':
                                        $badge_class = 'bg-warning text-dark';
                                        break;
                                    case 'Rendah':
                                        $badge_class = 'bg-danger';
                                        break;
                                    default:
                                        $badge_class = 'bg-secondary';
                                }
                                ?>
                                <span class="badge <?= $badge_class ?>">
                                    <?= htmlspecialchars($row['kategori_iq']) ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge <?= $row['minat'] == 'IPA' ? 'bg-primary' : ($row['minat'] == 'IPS' ? 'bg-info' : 'bg-secondary') ?>">
                                    <?= htmlspecialchars($row['minat']) ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <?php
                                $status = $row['status_klasifikasi'] ?? '-';
                                $status_badge = $status == 'Sudah Diklasifikasi' ? 'success' : ($status == 'Belum Diklasifikasi' ? 'secondary' : 'secondary');
                                ?>
                                <span class="badge bg-<?= $status_badge ?>">
                                    <?= htmlspecialchars($status) ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="detail.php?id=<?= $row['id'] ?>" 
                                   class="btn btn-info btn-sm" 
                                   title="Lihat Detail Siswa">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php 
                            endwhile;
                        endif;
                        if (!$ada_data): ?>
                        <tr>
                            <td colspan="11" class="text-center py-4">
                                <div class="text-muted">
                                    <i class="fas fa-info-circle fa-2x mb-2"></i>
                                    <p class="mb-0">
                                        Tidak ada data yang harus diklasifikasi
                                    </p>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <nav aria-label="Navigasi Halaman" class="mt-4">
                <ul class="pagination justify-content-center">
                    
                    <!-- Tombol Previous -->
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" 
                               href="?page=<?= $page-1 ?><?= !empty($search) ? '&search='.urlencode($search) : '' ?>"
                               title="Halaman Sebelumnya">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php
                    // Logika untuk menampilkan nomor halaman
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    // Tampilkan halaman pertama jika tidak termasuk dalam range
                    if ($start_page > 1) {
                        echo '<li class="page-item">
                                <a class="page-link" href="?page=1'.(!empty($search) ? '&search='.urlencode($search) : '').'">1</a>
                              </li>';
                        if ($start_page > 2) {
                            echo '<li class="page-item disabled">
                                    <span class="page-link">...</span>
                                  </li>';
                        }
                    }
                    
                    // Tampilkan halaman dalam range
                    for ($i = $start_page; $i <= $end_page; $i++) {
                        $active_class = ($i == $page) ? 'active' : '';
                        echo '<li class="page-item '.$active_class.'">
                                <a class="page-link" href="?page='.$i.(!empty($search) ? '&search='.urlencode($search) : '').'">'.$i.'</a>
                              </li>';
                    }
                    
                    // Tampilkan halaman terakhir jika tidak termasuk dalam range
                    if ($end_page < $total_pages) {
                        if ($end_page < $total_pages - 1) {
                            echo '<li class="page-item disabled">
                                    <span class="page-link">...</span>
                                  </li>';
                        }
                        echo '<li class="page-item">
                                <a class="page-link" href="?page='.$total_pages.(!empty($search) ? '&search='.urlencode($search) : '').'">'.$total_pages.'</a>
                              </li>';
                    }
                    ?>

                    <!-- Tombol Next -->
                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" 
                               href="?page=<?= $page+1 ?><?= !empty($search) ? '&search='.urlencode($search) : '' ?>"
                               title="Halaman Selanjutnya">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                    
                </ul>
            </nav>
            <?php endif; ?>
           
        </main>
    </div>
</div>

<!-- ==========================================
     JAVASCRIPT
     ========================================== -->
<script>
/**
 * Fungsi untuk menghitung penjurusan siswa individual
 */
function hitungPenjurusan(siswaId) {
    if (confirm('Apakah Anda yakin ingin melakukan perhitungan penjurusan untuk siswa ini?')) {
        window.location.href = 'hitung.php?id=' + siswaId;
    }
}

/**
 * Fungsi untuk menghitung penjurusan semua siswa
 */
function hitungSemua() {
    if (confirm('Apakah Anda yakin ingin melakukan perhitungan penjurusan untuk semua siswa? Proses ini mungkin membutuhkan waktu lama.')) {
        window.location.href = 'hitung_semua.php';
    }
}

/**
 * Fungsi untuk export data
 */
function exportData() {
    window.location.href = 'export.php';
}

/**
 * Inisialisasi DataTable ketika dokumen siap
 */
$(document).ready(function() {
    $('#dataTable').DataTable({
        "responsive": true,
        "lengthChange": false,
        "searching": false,      // Disable karena sudah ada custom search
        "paging": false,         // Disable karena sudah ada custom pagination
        "info": false,           // Disable karena sudah ada custom info
        "autoWidth": false,
        "buttons": ["copy", "csv", "excel", "pdf", "print"],
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Indonesian.json"
        }
    });
    
    // Tooltip untuk tombol aksi
    $('[title]').tooltip();
});
</script>

<?php 
require_once '../include/footer.php'; 
ob_end_flush();
?>