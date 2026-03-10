<?php
require_once '../../database/config.php';
// Hapus semua data prediksi jurusan
mysqli_query($conn, "DELETE FROM prediksi_jurusan");
// Reset status_klasifikasi siswa
// mysqli_query($conn, "UPDATE siswa SET status_klasifikasi = 'Belum Diklasifikasi'");
echo "OK";
?> 