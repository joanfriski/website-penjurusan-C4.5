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

// Proses update jika form disubmit
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hasil_prediksi = isset($_POST['hasil_prediksi']) ? $_POST['hasil_prediksi'] : '';
    if ($hasil_prediksi !== 'IPA' && $hasil_prediksi !== 'IPS') {
        $error = 'Hasil prediksi harus dipilih.';
    } else {
        $update = mysqli_query($conn, "UPDATE hasil_klasifikasi SET hasil_prediksi='$hasil_prediksi', updated_at=NOW() WHERE id=$id");
        if ($update) {
            $success = 'Data berhasil diperbarui.';
            $data['hasil_prediksi'] = $hasil_prediksi;
        } else {
            $error = 'Gagal memperbarui data.';
        }
    }
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
                            <div class="card-header bg-warning text-dark">
                                <h5 class="mb-0">Edit Hasil Prediksi Jurusan</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($success): ?>
                                    <div class="alert alert-success"> <?= $success ?> </div>
                                <?php elseif ($error): ?>
                                    <div class="alert alert-danger"> <?= $error ?> </div>
                                <?php endif; ?>
                                <form method="post">
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
                                            <th>Minat</th>
                                            <td>: <input type="text" class="form-control-plaintext" value="<?= htmlspecialchars($data['minat'] ?: '-') ?>" readonly></td>
                                        </tr>
                                        <tr>
                                            <th>Hasil Prediksi</th>
                                            <td>
                                                <select name="hasil_prediksi" class="form-select" required>
                                                    <option value="">- Pilih -</option>
                                                    <option value="IPA" <?= $data['hasil_prediksi'] == 'IPA' ? 'selected' : '' ?>>IPA</option>
                                                    <option value="IPS" <?= $data['hasil_prediksi'] == 'IPS' ? 'selected' : '' ?>>IPS</option>
                                                </select>
                                            </td>
                                        </tr>
                                    </table>
                                    <div class="mb-3">
                                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
                                        <a href="detail.php?id=<?= $data['id'] ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
                                    </div>
                                </form>
                                <?php
                                $minat = $data['minat'] ?? '';
                                $prediksi = $data['hasil_prediksi'] ?? '';
                                if ($minat && $prediksi) {
                                    if ($minat === $prediksi) {
                                        echo '<div class="alert alert-success mt-3"><i class="fas fa-check-circle"></i> Minat dan hasil prediksi <b>SESUAI</b>.</div>';
                                    } else {
                                        echo '<div class="alert alert-danger mt-3"><i class="fas fa-times-circle"></i> Minat dan hasil prediksi <b>TIDAK SESUAI</b>.</div>';
                                    }
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
<?php require_once '../include/footer.php'; ob_end_flush(); ?> 