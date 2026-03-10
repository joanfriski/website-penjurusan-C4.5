<?php
ob_start();
require_once '../../database/config.php';
require_once '../include/header.php';


// Cek parameter siswa_id
if (!isset($_GET['siswa_id']) || empty($_GET['siswa_id'])) {
    $_SESSION['error'] = "Parameter siswa id tidak valid!";
    header("Location: index.php");
    exit;
}

$siswa_id = (int)$_GET['siswa_id'];

// Ambil informasi siswa
$query_siswa = "SELECT s.id, s.nis, s.nama_lengkap, s.jenis_kelamin, s.tempat_lahir, s.tanggal_lahir,
               k.nama_kelas, k.tahun_ajaran
               FROM siswa s
               LEFT JOIN kelas k ON s.kelas_id = k.id
               WHERE s.id = $siswa_id";
$result_siswa = mysqli_query($conn, $query_siswa);

if (mysqli_num_rows($result_siswa) == 0) {
    $_SESSION['error'] = "Data siswa tidak ditemukan!";
    header("Location: index.php");
    exit;
}

$siswa = mysqli_fetch_assoc($result_siswa);

// Filter nilai berdasarkan tahun ajaran
$tahun_filter = isset($_GET['tahun_ajaran']) ? $_GET['tahun_ajaran'] : '';
$where = "WHERE n.siswa_id = $siswa_id";

if (!empty($tahun_filter)) {
    $where .= " AND n.tahun_ajaran = '$tahun_filter'";
}

// Ambil data tahun ajaran untuk filter
$query_tahun = "SELECT DISTINCT tahun_ajaran FROM nilai WHERE siswa_id = $siswa_id ORDER BY tahun_ajaran DESC";
$result_tahun = mysqli_query($conn, $query_tahun);

// Ambil semua nilai siswa
$query_nilai = "SELECT n.id, mp.kode, mp.nama as mata_pelajaran, mp.kategori, n.nilai, n.tahun_ajaran
               FROM nilai n
               JOIN mata_pelajaran mp ON n.mapel_id = mp.id
               $where
               ORDER BY mp.kategori, mp.nama ASC";
$result_nilai = mysqli_query($conn, $query_nilai);

// Hitung rata-rata nilai berdasarkan kategori
$query_avg = "SELECT 
                mp.kategori,
                AVG(n.nilai) as rata_rata
              FROM nilai n
              JOIN mata_pelajaran mp ON n.mapel_id = mp.id
              $where
              GROUP BY mp.kategori";
$result_avg = mysqli_query($conn, $query_avg);

$rata_rata = [];
while ($row_avg = mysqli_fetch_assoc($result_avg)) {
    $rata_rata[$row_avg['kategori']] = $row_avg['rata_rata'];
}

// Log aktivitas
$user_id = $_SESSION['user_id'];
$aktivitas = "Melihat detail nilai siswa: " . $siswa['nama_lengkap'] . " (ID: $siswa_id)";
$query_log = "INSERT INTO log_aktivitas (user_id, aktivitas, ip_address) VALUES ('$user_id', '$aktivitas', '{$_SERVER['REMOTE_ADDR']}')";
mysqli_query($conn, $query_log);
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once '../include/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4 content-wrapper" id="content">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h2 class="h3">
                    <i class="fas fa-user-graduate me-2"></i>Detail Nilai Siswa
                </h2>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="index.php" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Kembali
                    </a>
                    <a href="tambah.php?siswa_id=<?php echo $siswa_id; ?>" class="btn btn-sm btn-primary ms-2">
                        <i class="fas fa-plus me-1"></i> Tambah Nilai
                    </a>
                    <div class="btn-group ms-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="fas fa-download me-1"></i> Export
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="../../laporan/nilai_siswa_excel.php?siswa_id=<?php echo $siswa_id; ?>"><i class="fas fa-file-excel me-1"></i> Excel</a></li>
                            <li><a class="dropdown-item" href="../../laporan/nilai_siswa_pdf.php?siswa_id=<?php echo $siswa_id; ?>"><i class="fas fa-file-pdf me-1"></i> PDF</a></li>
                        </ul>
                    </div>
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
            
            <!-- Profile Card -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Profil Siswa</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="35%">NIS</th>
                                    <td width="5%">:</td>
                                    <td width="60%"><?php echo $siswa['nis']; ?></td>
                                </tr>
                                <tr>
                                    <th>Nama Lengkap</th>
                                    <td>:</td>
                                    <td><?php echo $siswa['nama_lengkap']; ?></td>
                                </tr>
                                <tr>
                                    <th>Jenis Kelamin</th>
                                    <td>:</td>
                                    <td><?php echo $siswa['jenis_kelamin']; ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="35%">Tempat, Tgl Lahir</th>
                                    <td width="5%">:</td>
                                    <td width="60%">
                                        <?php 
                                        echo $siswa['tempat_lahir'] . ', ' . date('d-m-Y', strtotime($siswa['tanggal_lahir']));
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Kelas</th>
                                    <td>:</td>
                                    <td><?php echo $siswa['nama_kelas'] ?: '-'; ?></td>
                                </tr>
                                <tr>
                                    <th>Tahun Ajaran</th>
                                    <td>:</td>
                                    <td><?php echo $siswa['tahun_ajaran'] ?: '-'; ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filter Tahun Ajaran -->
            <div class="card mb-4 shadow-sm">
                <div class="card-body">
                    <form method="GET" action="" class="row g-3">
                        <input type="hidden" name="siswa_id" value="<?php echo $siswa_id; ?>">
                        <div class="col-md-4">
                            <select class="form-select" name="tahun_ajaran">
                                <option value="">Semua Tahun Ajaran</option>
                                <?php while ($tahun = mysqli_fetch_assoc($result_tahun)): ?>
                                    <option value="<?php echo $tahun['tahun_ajaran']; ?>" <?php echo $tahun_filter == $tahun['tahun_ajaran'] ? 'selected' : ''; ?>>
                                        <?php echo $tahun['tahun_ajaran']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Data Rata-rata -->
            <div class="row mb-4">
                <?php foreach ($rata_rata as $kategori => $nilai): ?>
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body d-flex align-items-center">
                            <div class="me-3">
                                <?php if ($kategori == 'IPA'): ?>
                                <div class="bg-info text-white rounded-circle p-3">
                                    <i class="fas fa-flask fa-2x"></i>
                                </div>
                                <?php else: ?>
                                <div class="bg-warning text-white rounded-circle p-3">
                                    <i class="fas fa-book fa-2x"></i>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1">Rata-rata Nilai <?php echo $kategori; ?></h6>
                                <h3 class="mb-0"><?php echo number_format($nilai, 2); ?></h3>
                                <?php 
                                // Tentukan status berdasarkan nilai
                                if ($nilai >= 80) {
                                    $status = 'Sangat Baik';
                                    $badge_class = 'bg-success';
                                } elseif ($nilai >= 77) {
                                    $status = 'Baik';
                                    $badge_class = 'bg-primary';
                                } elseif ($nilai >= 75) {
                                    $status = 'Cukup';
                                    $badge_class = 'bg-warning';
                                } else {
                                    $status = 'Kurang';
                                    $badge_class = 'bg-danger';
                                }
                                ?>
                                <span class="badge <?php echo $badge_class; ?>"><?php echo $status; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Tabel Nilai -->
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Daftar Nilai Siswa</h5>
                </div>
                <div class="card-body">
                    <?php if (mysqli_num_rows($result_nilai) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">No</th>
                                    <th width="15%">Kode</th>
                                    <th width="30%">Mata Pelajaran</th>
                                    <th width="15%">Kategori</th>
                                    <th width="15%">Nilai</th>
                                    <th width="15%">Tahun Ajaran</th>
                                    <th width="10%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                $last_kategori = '';
                                while ($row = mysqli_fetch_assoc($result_nilai)):
                                    $bg_class = ($last_kategori != $row['kategori'] && $last_kategori != '') ? 'table-secondary' : '';
                                    $last_kategori = $row['kategori'];
                                ?>
                                <tr class="<?php echo $bg_class; ?>">
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo $row['kode']; ?></td>
                                    <td><?php echo $row['mata_pelajaran']; ?></td>
                                    <td>
                                        <span class="badge <?php echo $row['kategori'] == 'IPA' ? 'bg-info' : 'bg-warning'; ?>">
                                            <?php echo $row['kategori']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        // Warna nilai berdasarkan range
                                        if ($row['nilai'] >= 80) {
                                            $nilai_class = 'text-success fw-bold';
                                        } elseif ($row['nilai'] >= 77) {
                                            $nilai_class = 'text-primary';
                                        } elseif ($row['nilai'] >= 75) {
                                            $nilai_class = 'text-warning';
                                        } else {
                                            $nilai_class = 'text-danger';
                                        }
                                        ?>
                                        <span class="<?php echo $nilai_class; ?>"><?php echo $row['nilai']; ?></span>
                                    </td>
                                    <td><?php echo $row['tahun_ajaran']; ?></td>
                                    <td>
                                        <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="hapus.php?id=<?php echo $row['id']; ?>&siswa_id=<?php echo $siswa_id; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Apakah Anda yakin ingin menghapus nilai ini?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> Belum ada data nilai untuk siswa ini.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="mt-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Informasi Penilaian</h5>
                    </div>
                    <div class="card-body">
                        <ul class="mb-0">
                            <li>Kategori nilai dibagi menjadi dua kelompok: IPA dan IPS.</li>
                            <li>Nilai dengan range 80-100 termasuk kategori Sangat Baik.</li>
                            <li>Nilai dengan range 70-79 termasuk kategori Baik.</li>
                            <li>Nilai dengan range 60-69 termasuk kategori Cukup.</li>
                            <li>Nilai dibawah 60 termasuk kategori Kurang.</li>
                            <li>Rata-rata nilai yang dikelompokkan berdasarkan kategori membantu dalam analisis potensi siswa.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </main>
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
});
</script>


<?php require_once '../include/footer.php'; 
ob_end_flush();
?>

