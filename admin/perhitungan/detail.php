<?php
require_once '../../database/config.php';
require_once '../include/header.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script>alert('ID Siswa tidak valid!'); window.location.href='index.php';</script>";
    exit;
}

$siswa_id = mysqli_real_escape_string($conn, $_GET['id']);

// Ambil data siswa
$query_siswa = "SELECT s.*, k.nama_kelas 
                FROM siswa s 
                JOIN kelas k ON s.kelas_id = k.id 
                WHERE s.id = '$siswa_id'";
$result_siswa = mysqli_query($conn, $query_siswa);

if (!$result_siswa || mysqli_num_rows($result_siswa) == 0) {
    echo "<script>alert('Data siswa tidak ditemukan!'); window.location.href='index.php';</script>";
    exit;
}

$siswa = mysqli_fetch_assoc($result_siswa);

// Ambil data nilai per mata pelajaran
$query_nilai = "SELECT mp.nama as mapel_nama, mp.kategori, n.nilai, n.tahun_ajaran
                FROM nilai n
                JOIN mata_pelajaran mp ON n.mapel_id = mp.id
                WHERE n.siswa_id = '$siswa_id'
                ORDER BY mp.kategori, mp.nama";
$result_nilai = mysqli_query($conn, $query_nilai);

// Ambil data test IQ
$query_iq = "SELECT * FROM test_iq WHERE siswa_id = '$siswa_id' ORDER BY tanggal_test DESC";
$result_iq = mysqli_query($conn, $query_iq);

// Ambil data minat
$query_minat = "SELECT * FROM minat_siswa WHERE siswa_id = '$siswa_id' ORDER BY created_at DESC";
$result_minat = mysqli_query($conn, $query_minat);

// Hitung rata-rata nilai per kategori
$query_rata = "SELECT mp.kategori, AVG(n.nilai) as rata_nilai
               FROM nilai n
               JOIN mata_pelajaran mp ON n.mapel_id = mp.id
               WHERE n.siswa_id = '$siswa_id'
               GROUP BY mp.kategori";
$result_rata = mysqli_query($conn, $query_rata);

$rata_nilai = [];
while ($row = mysqli_fetch_assoc($result_rata)) {
    $rata_nilai[$row['kategori']] = $row['rata_nilai'];
}
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Detail Data Siswa</h3>
                    </div>
                    <div class="card-body">
                        <!-- Data Pribadi -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5 class="border-bottom pb-2">Data Pribadi</h5>
                                <table class="table table-bordered">
                                    <tr>
                                        <th width="30%">NIS</th>
                                        <td><?= htmlspecialchars($siswa['nis']) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Nama Lengkap</th>
                                        <td><?= htmlspecialchars($siswa['nama_lengkap']) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Kelas</th>
                                        <td><?= htmlspecialchars($siswa['nama_kelas']) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Jenis Kelamin</th>
                                        <td><?= $siswa['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan' ?></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h5 class="border-bottom pb-2">Rata-rata Nilai</h5>
                                <table class="table table-bordered">
                                    <tr>
                                        <th width="30%">Rata-rata IPA</th>
                                        <td><?= number_format($rata_nilai['IPA'] ?? 0, 2) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Rata-rata IPS</th>
                                        <td><?= number_format($rata_nilai['IPS'] ?? 0, 2) ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <!-- Data Nilai -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="border-bottom pb-2">Data Nilai</h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>Mata Pelajaran</th>
                                                <th>Kategori</th>
                                                <th>Nilai</th>
                                                <th>Tahun Ajaran</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($nilai = mysqli_fetch_assoc($result_nilai)): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($nilai['mapel_nama']) ?></td>
                                                <td>
                                                    <span class="badge bg-<?= $nilai['kategori'] == 'IPA' ? 'primary' : 'info' ?>">
                                                        <?= $nilai['kategori'] ?>
                                                    </span>
                                                </td>
                                                <td><?= number_format($nilai['nilai'], 2) ?></td>
                                                <td><?= $nilai['tahun_ajaran'] ?></td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Data Test IQ -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="border-bottom pb-2">Data Test IQ</h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>Tanggal Test</th>
                                                <th>Skor</th>
                                                <th>Kategori</th>
                                                <th>Keterangan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($iq = mysqli_fetch_assoc($result_iq)): ?>
                                            <tr>
                                                <td><?= date('d/m/Y', strtotime($iq['tanggal_test'])) ?></td>
                                                <td><?= $iq['skor'] ?></td>
                                                <td>
                                                    <span class="badge bg-<?= $iq['kategori'] == 'Tinggi' ? 'success' : ($iq['kategori'] == 'Sedang' ? 'warning' : 'danger') ?>">
                                                        <?= $iq['kategori'] ?>
                                                    </span>
                                                </td>
                                                <td><?= htmlspecialchars($iq['keterangan']) ?></td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Data Minat -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="border-bottom pb-2">Data Minat</h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>Tanggal</th>
                                                <th>Minat</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($minat = mysqli_fetch_assoc($result_minat)): ?>
                                            <tr>
                                                <td><?= date('d/m/Y H:i', strtotime($minat['created_at'])) ?></td>
                                                <td>
                                                    <span class="badge bg-<?= $minat['minat'] == 'IPA' ? 'primary' : 'info' ?>">
                                                        <?= $minat['minat'] ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="text-center mt-4">
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Kembali
                            </a>
                            <button type="button" class="btn btn-primary" onclick="hitungPenjurusan(<?= $siswa_id ?>)">
                                <i class="fas fa-calculator"></i> Hitung Penjurusan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function hitungPenjurusan(siswaId) {
    if (confirm('Apakah Anda yakin ingin melakukan perhitungan penjurusan untuk siswa ini?')) {
        window.location.href = 'hitung.php?id=' + siswaId;
    }
}
</script>

<?php require_once '../include/footer.php'; ?> 