<?php
require_once '../../database/config.php';

// Simpan data ke tabel hasil_klasifikasi
$query = "INSERT INTO hasil_klasifikasi (siswa_id, nilai_mapel_ipa, nilai_mapel_ips, kategori_nilai, nilai_iq, kategori_iq, minat, hasil_prediksi, status_klasifikasi, created_at) 
          SELECT siswa_id, nilai_mapel_ipa, nilai_mapel_ips, kategori_nilai, nilai_iq, kategori_iq, minat, hasil_prediksi, 'Sudah Diklasifikasi', NOW()
          FROM prediksi_jurusan 
          WHERE status_klasifikasi = 'Sudah Diklasifikasi'
          AND NOT EXISTS (
              SELECT 1 FROM hasil_klasifikasi hk 
              WHERE hk.siswa_id = prediksi_jurusan.siswa_id
          )";
$result = mysqli_query($conn, $query);

// Update status siswa menjadi 'Sudah Diklasifikasi'
$update_siswa = mysqli_query($conn, "UPDATE siswa SET status_klasifikasi = 'Sudah Diklasifikasi' WHERE id IN (SELECT siswa_id FROM prediksi_jurusan WHERE status_klasifikasi = 'Sudah Diklasifikasi')");

if ($result && $update_siswa) {
    echo "Data berhasil disimpan ke tabel hasil_klasifikasi dan status siswa diperbarui.";
} else {
    echo "Gagal menyimpan data.";
}
?> 