<?php
require_once '../../database/config.php';

// Helper kategori
function kategori_nilai($nilai) {
    if ($nilai >= 80) return 'Tinggi';
    if ($nilai >= 75) return 'Sedang';
    return 'Rendah';
}
function kategori_iq($skor) {
    if ($skor >= 110) return 'Tinggi';
    if ($skor >= 90) return 'Sedang';
    return 'Rendah';
}
function pohon_keputusan($kategori_nilai, $kategori_iq) {
    if ($kategori_nilai == 'Tinggi') {
        if ($kategori_iq == 'Tinggi' || $kategori_iq == 'Sedang') return 'IPS';
        else return 'IPA';
    } elseif ($kategori_nilai == 'Sedang') {
        if ($kategori_iq == 'Tinggi') return 'IPA';
        elseif ($kategori_iq == 'Sedang') return 'IPS';
        else return 'IPS';
    } else {
        if ($kategori_iq == 'Tinggi' || $kategori_iq == 'Sedang') return 'IPA';
        else return 'IPS';
    }
}

// Query untuk mengambil data siswa yang belum diklasifikasi
$query = "SELECT s.id as siswa_id, 
                 AVG(CASE WHEN mp.kategori = 'IPA' THEN n.nilai ELSE NULL END) AS nilai_ipa,
                 AVG(CASE WHEN mp.kategori = 'IPS' THEN n.nilai ELSE NULL END) AS nilai_ips,
                 ti.skor AS nilai_iq
          FROM siswa s
          LEFT JOIN nilai n ON s.id = n.siswa_id
          LEFT JOIN mata_pelajaran mp ON n.mapel_id = mp.id
          LEFT JOIN test_iq ti ON s.id = ti.siswa_id
          WHERE s.status_klasifikasi = 'Belum Diklasifikasi'
          AND NOT EXISTS (SELECT 1 FROM prediksi_jurusan pj WHERE pj.siswa_id = s.id)
          GROUP BY s.id, ti.skor";
$result = mysqli_query($conn, $query);
$count = 0;
while ($row = mysqli_fetch_assoc($result)) {
    $siswa_id = $row['siswa_id'];
    $nilai_ipa = $row['nilai_ipa'];
    $nilai_ips = $row['nilai_ips'];
    $nilai_iq = $row['nilai_iq'];
    $kategori_nilai = kategori_nilai(($nilai_ipa + $nilai_ips) / 2);
    $kategori_iq = kategori_iq($nilai_iq);
    $hasil_prediksi = pohon_keputusan($kategori_nilai, $kategori_iq);
    // Ambil minat siswa
    $minat = '-';
    $q_minat = mysqli_query($conn, "SELECT minat FROM minat_siswa WHERE siswa_id='$siswa_id' ORDER BY id DESC LIMIT 1");
    if ($q_minat && mysqli_num_rows($q_minat) > 0) {
        $minat = mysqli_fetch_assoc($q_minat)['minat'];
    }
    // Cek apakah sudah ada prediksi dan status klasifikasi
    $cek = mysqli_query($conn, "SELECT id, status_klasifikasi FROM prediksi_jurusan WHERE siswa_id='$siswa_id'");
    if (mysqli_num_rows($cek) > 0) {
        $row_pred = mysqli_fetch_assoc($cek);
        if ($row_pred['status_klasifikasi'] === 'Sudah Diklasifikasi') {
            continue; // skip jika sudah diklasifikasi
        }
        // Update
        mysqli_query($conn, "UPDATE prediksi_jurusan SET nilai_mapel_ipa='$nilai_ipa', nilai_mapel_ips='$nilai_ips', kategori_nilai='$kategori_nilai', nilai_iq='$nilai_iq', kategori_iq='$kategori_iq', minat='$minat', hasil_prediksi='$hasil_prediksi', status_klasifikasi='Sudah Diklasifikasi' WHERE siswa_id='$siswa_id'");
    } else {
        // Insert
        mysqli_query($conn, "INSERT INTO prediksi_jurusan (siswa_id, nilai_mapel_ipa, nilai_mapel_ips, kategori_nilai, nilai_iq, kategori_iq, minat, hasil_prediksi, status_klasifikasi) VALUES ('$siswa_id', '$nilai_ipa', '$nilai_ips', '$kategori_nilai', '$nilai_iq', '$kategori_iq', '$minat', '$hasil_prediksi', 'Sudah Diklasifikasi')");
    }
    $count++;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Batch Prediksi Jurusan</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <div class="alert alert-success">
        <h4 class="alert-heading">Batch Prediksi Selesai!</h4>
        <p>Berhasil melakukan prediksi jurusan untuk <b><?= $count ?></b> siswa.</p>
        <hr>
        <a href="entropy_gain_dataset.php" class="btn btn-primary">Lihat Tabel Perhitungan Node 1</a>
    </div>
</div>
</body>
</html> 