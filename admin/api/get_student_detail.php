<?php
// Tetapkan header untuk CORS dan JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Koneksi ke database
require_once '../../database/config.php';

// Validasi parameter ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['error' => 'Parameter ID siswa kosong']);
    exit;
}

// Ambil dan bersihkan parameter ID
$student_id = mysqli_real_escape_string($conn, $_GET['id']);

// Query untuk mendapatkan detail siswa
$query = "SELECT s.*, k.nama_kelas 
          FROM siswa s 
          LEFT JOIN kelas k ON s.kelas_id = k.id 
          WHERE s.id = '$student_id'";

$result = mysqli_query($conn, $query);

if (!$result) {
    echo json_encode(['error' => 'Error pada database: ' . mysqli_error($conn)]);
    exit;
}

if (mysqli_num_rows($result) == 0) {
    echo json_encode(['error' => 'Siswa tidak ditemukan']);
    exit;
}

// Ambil data siswa
$student = mysqli_fetch_assoc($result);

// Cek apakah siswa sudah memiliki prediksi jurusan
$query_prediksi = "SELECT * FROM prediksi_jurusan WHERE siswa_id = '$student_id' ORDER BY id DESC LIMIT 1";
$result_prediksi = mysqli_query($conn, $query_prediksi);

$response = [
    'id' => $student['id'],
    'nis' => $student['nis'],
    'nisn' => $student['nisn'],
    'nama_lengkap' => $student['nama_lengkap'],
    'jenis_kelamin' => $student['jenis_kelamin'],
    'tempat_lahir' => $student['tempat_lahir'],
    'tanggal_lahir' => $student['tanggal_lahir'],
    'alamat' => $student['alamat'],
    'no_telp' => $student['no_telp'],
    'status' => $student['status'],
    'nama_kelas' => $student['nama_kelas'],
    'has_prediksi' => mysqli_num_rows($result_prediksi) > 0
];

// Tambahkan data prediksi jika ada
if ($response['has_prediksi']) {
    $prediksi = mysqli_fetch_assoc($result_prediksi);
    
    $response['prediksi_id'] = $prediksi['id'];
    $response['nilai_mapel_ipa'] = $prediksi['nilai_mapel_ipa'];
    $response['nilai_mapel_ips'] = $prediksi['nilai_mapel_ips'];
    $response['kategori_nilai'] = $prediksi['kategori_nilai'];
    $response['nilai_iq'] = $prediksi['nilai_iq'];
    $response['kategori_iq'] = $prediksi['kategori_iq'];
    $response['minat'] = $prediksi['minat'];
    $response['hasil_prediksi'] = $prediksi['hasil_prediksi'];
    
    // Ambil kategori tes IQ jika tersedia
    if (isset($prediksi['tes_iq_kategori'])) {
        $response['tes_iq_kategori'] = $prediksi['tes_iq_kategori'];
    } else {
        // Konversi dari nilai IQ jika kolom tidak ada
        if ($prediksi['nilai_iq'] >= 110) {
            $response['tes_iq_kategori'] = 'Baik';
        } else if ($prediksi['nilai_iq'] >= 90) {
            $response['tes_iq_kategori'] = 'Cukup';
        } else {
            $response['tes_iq_kategori'] = 'Kurang';
        }
    }
}

// Kembalikan hasil dalam format JSON
echo json_encode($response);
?>