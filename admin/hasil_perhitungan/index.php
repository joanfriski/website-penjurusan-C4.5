<?php
ob_start();
require_once '../../database/config.php';
require_once '../include/header.php';
?>
<div class="container-fluid">
    <div class="row">
        <?php require_once '../include/sidebar.php'; ?>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4 content-wrapper" id="content">
            <div class="container-fluid mt-4 mb-4">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Hasil Perhitungan Prediksi Jurusan</h5>
                            </div>
                            <div class="card-body">
                                <!-- Search Form -->
                                <form method="get" class="mb-3">
                                    <div class="input-group">
                                        <input type="text" name="search" class="form-control" placeholder="Cari nama siswa..." value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                                        <button class="btn btn-primary" type="submit">
                                            <i class="fas fa-search"></i> Cari
                                        </button>
                                    </div>
                                </form>

                                <!-- Data Table -->
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="text-center" width="50">No</th>
                                                <th>Nama Siswa</th>
                                                <th class="text-center">Nilai Mapel IPA</th>
                                                <th class="text-center">Nilai Mapel IPS</th>
                                                <th class="text-center">Kategori Nilai</th>
                                                <th class="text-center">Nilai IQ</th>
                                                <th class="text-center">Kategori IQ</th>
                                                <th class="text-center">Minat</th>
                                                <th class="text-center">Hasil Prediksi</th>
                                                <th class="text-center">Status Klasifikasi</th>
                                                <th class="text-center">Status Kesesuaian</th>
                                                <th class="text-center" width="100">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            // Pagination settings
                                            $per_page = 25;
                                            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                                            $start = ($page > 1) ? ($page * $per_page) - $per_page : 0;

                                            // Search condition
                                            $search = isset($_GET['search']) ? $_GET['search'] : '';
                                            $where = "";
                                            if (!empty($search)) {
                                                $search = mysqli_real_escape_string($conn, $search);
                                                $where = "WHERE s.nama_lengkap LIKE '%$search%'";
                                            }

                                            // Get total records
                                            $total_query = "SELECT COUNT(*) as total FROM hasil_klasifikasi hk 
                                                          JOIN siswa s ON hk.siswa_id = s.id $where";
                                            $total_result = mysqli_query($conn, $total_query);
                                            $total_row = mysqli_fetch_assoc($total_result);
                                            $total = $total_row['total'];
                                            $total_pages = ceil($total / $per_page);

                                            // Get data with pagination
                                            $query = "SELECT hk.*, s.nama_lengkap, s.id as siswa_id,
                                                    (SELECT minat FROM minat_siswa WHERE siswa_id = s.id ORDER BY id DESC LIMIT 1) as minat
                                                    FROM hasil_klasifikasi hk 
                                                    JOIN siswa s ON hk.siswa_id = s.id 
                                                    $where 
                                                    ORDER BY (hk.nilai_mapel_ipa + hk.nilai_mapel_ips) DESC, s.nama_lengkap ASC
                                                    LIMIT $start, $per_page";
                                            $result = mysqli_query($conn, $query);

                                            $no = $start + 1;
                                            if (mysqli_num_rows($result) > 0) {
                                                while ($row = mysqli_fetch_assoc($result)) {
                                                    echo "<tr>";
                                                    echo "<td class='text-center'>" . $no++ . "</td>";
                                                    echo "<td>" . htmlspecialchars($row['nama_lengkap']) . "</td>";
                                                    echo "<td class='text-center'>" . number_format($row['nilai_mapel_ipa'], 2) . "</td>";
                                                    echo "<td class='text-center'>" . number_format($row['nilai_mapel_ips'], 2) . "</td>";
                                                    echo "<td class='text-center'>" . htmlspecialchars($row['kategori_nilai']) . "</td>";
                                                    echo "<td class='text-center'>" . $row['nilai_iq'] . "</td>";
                                                    echo "<td class='text-center'>" . htmlspecialchars($row['kategori_iq']) . "</td>";
                                                    echo "<td class='text-center'><span class='badge bg-" . ($row['minat'] == 'IPA' ? 'primary' : ($row['minat'] == 'IPS' ? 'info' : 'secondary')) . "'>" . htmlspecialchars($row['minat'] ?: '-') . "</span></td>";
                                                    echo "<td class='text-center'><span class='badge bg-" . ($row['hasil_prediksi'] == 'IPA' ? 'info' : 'warning') . "'>" . htmlspecialchars($row['hasil_prediksi']) . "</span></td>";
                                                    echo "<td class='text-center'><span class='badge bg-" . ($row['status_klasifikasi'] == 'Sudah Diklasifikasi' ? 'success' : ($row['status_klasifikasi'] == 'Belum Diklasifikasi' ? 'secondary' : 'secondary')) . "'>" . htmlspecialchars($row['status_klasifikasi'] ?? '-') . "</span></td>";
                                                    // Add status kesesuaian
                                                    $minat = $row['minat'] ?? '';
                                                    $prediksi = $row['hasil_prediksi'] ?? '';
                                                    $status_kesesuaian = '';
                                                    $badge_color = '';
                                                    if ($minat && $prediksi) {
                                                        if ($minat === $prediksi) {
                                                            $status_kesesuaian = 'Sesuai';
                                                            $badge_color = 'success';
                                                        } else {
                                                            $status_kesesuaian = 'Tidak Sesuai';
                                                            $badge_color = 'danger';
                                                        }
                                                    } else {
                                                        $status_kesesuaian = '-';
                                                        $badge_color = 'secondary';
                                                    }
                                                    echo "<td class='text-center'><span class='badge bg-$badge_color'>$status_kesesuaian</span></td>";
                                                    echo "<td class='text-center'>";
                                                    echo "<a href='detail.php?id=" . $row['id'] . "' class='btn btn-info btn-sm me-1' title='Detail'><i class='fas fa-eye'></i></a>";
                                                    echo "<a href='edit.php?id=" . $row['id'] . "' class='btn btn-warning btn-sm' title='Edit'><i class='fas fa-edit'></i></a>";
                                                    echo "</td>";
                                                    echo "</tr>";
                                                }
                                            } else {
                                                echo "<tr><td colspan='10' class='text-center'>Tidak ada data</td></tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Pagination -->
                                <?php if ($total_pages > 1): ?>
                                <nav aria-label="Page navigation" class="mt-3">
                                    <ul class="pagination justify-content-center">
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?= $page-1 ?><?= !empty($search) ? '&search='.urlencode($search) : '' ?>">
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
                                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                                <a class="page-link" href="?page=<?= $i ?><?= !empty($search) ? '&search='.urlencode($search) : '' ?>"><?= $i ?></a>
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
                                                <a class="page-link" href="?page=<?= $page+1 ?><?= !empty($search) ? '&search='.urlencode($search) : '' ?>">
                                                    <i class="fas fa-chevron-right"></i>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
<?php require_once '../include/footer.php'; ob_end_flush(); ?> 