<?php
ob_start();
require_once '../../database/config.php';
require_once '../include/header.php';

// Ambil ID prediksi dari parameter
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

// Query detail prediksi (JOIN ke kelas)
$query = "SELECT hk.*, s.nama_lengkap, s.nis, s.id as siswa_id, k.nama_kelas,
            (SELECT minat FROM minat_siswa WHERE siswa_id = s.id ORDER BY id DESC LIMIT 1) as minat
          FROM hasil_klasifikasi hk
          JOIN siswa s ON hk.siswa_id = s.id
          LEFT JOIN kelas k ON s.kelas_id = k.id
          WHERE hk.id = $id LIMIT 1";
$result = mysqli_query($conn, $query);
$data = mysqli_fetch_assoc($result);
if (!$data) {
    header('Location: index.php');
    exit;
}
?>
<div class="container-fluid">
    <div class="row">
        <?php require_once '../include/sidebar.php'; ?>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4 content-wrapper" id="content">
            <div class="container-fluid mt-4 mb-4">
                <div class="row justify-content-center">
                    <div class="col-lg-8 col-md-10">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Detail Hasil Prediksi Jurusan</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-borderless mb-3">
                                    <tr>
                                        <th width="180">Nama Siswa</th>
                                        <td>: <?= htmlspecialchars($data['nama_lengkap']) ?></td>
                                    </tr>
                                    <tr>
                                        <th>NIS</th>
                                        <td>: <?= htmlspecialchars($data['nis']) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Kelas</th>
                                        <td>: <?= htmlspecialchars($data['nama_kelas'] ?: '-') ?></td>
                                    </tr>
                                    <tr>
                                        <th>Nilai Mapel IPA</th>
                                        <td>: <?= number_format($data['nilai_mapel_ipa'], 2) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Nilai Mapel IPS</th>
                                        <td>: <?= number_format($data['nilai_mapel_ips'], 2) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Kategori Nilai</th>
                                        <td>: <?= htmlspecialchars($data['kategori_nilai']) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Nilai IQ</th>
                                        <td>: <?= htmlspecialchars($data['nilai_iq']) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Kategori IQ</th>
                                        <td>: <?= htmlspecialchars($data['kategori_iq']) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Minat</th>
                                        <td>: <?= htmlspecialchars($data['minat'] ?: '-') ?></td>
                                    </tr>
                                    <tr>
                                        <th>Hasil Prediksi</th>
                                        <td>: <span class="badge bg-<?= $data['hasil_prediksi'] == 'IPA' ? 'info' : 'warning' ?>"><?= htmlspecialchars($data['hasil_prediksi']) ?></span></td>
                                    </tr>
                                    <tr>
                                        <th>Tanggal Prediksi</th>
                                        <td>: <?= date('d-m-Y H:i', strtotime($data['created_at'])) ?></td>
                                    </tr>
                                </table>
                                <?php
                                $minat = $data['minat'] ?? '';
                                $prediksi = $data['hasil_prediksi'] ?? '';
                                if ($minat && $prediksi) {
                                    if ($minat === $prediksi) {
                                        echo '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Minat dan hasil prediksi <b>SESUAI</b>.</div>';
                                    } else {
                                        echo '<div class="alert alert-danger"><i class="fas fa-times-circle"></i> Minat dan hasil prediksi <b>TIDAK SESUAI</b>.</div>';
                                    }
                                }
                                ?>
                                <div class="d-flex justify-content-between">
                                    <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
                                    <a href="edit.php?id=<?= $data['id'] ?>" class="btn btn-warning"><i class="fas fa-edit"></i> Edit</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
<?php require_once '../include/footer.php'; ob_end_flush(); ?> 