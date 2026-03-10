<?php
// Tetapkan header untuk CORS dan JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Koneksi ke database
require_once '../../database/config.php';

// Validasi parameter pencarian
if (!isset($_GET['q']) || empty($_GET['q'])) {
    echo json_encode(['error' => 'Parameter pencarian kosong']);
    exit;
}

// Ambil dan bersihkan parameter pencarian
$search = mysqli_real_escape_string($conn, $_GET['q']);

// Query untuk mencari siswa berdasarkan nama atau NIS
$query = "SELECT s.id, s.nis, s.nama_lengkap, s.jenis_kelamin, s.status, k.nama_kelas 
          FROM siswa s 
          LEFT JOIN kelas k ON s.kelas_id = k.id 
          WHERE (s.nama_lengkap LIKE '%$search%' OR s.nis LIKE '%$search%') 
          ORDER BY s.nama_lengkap 
          LIMIT 10";

$result = mysqli_query($conn, $query);

if (!$result) {
    echo json_encode(['error' => 'Error pada database: ' . mysqli_error($conn)]);
    exit;
}

// Inisialisasi array untuk menyimpan hasil
$students = [];

// Ambil data siswa
while ($row = mysqli_fetch_assoc($result)) {
    $students[] = [
        'id' => $row['id'],
        'nis' => $row['nis'],
        'nama_lengkap' => $row['nama_lengkap'],
        'jenis_kelamin' => $row['jenis_kelamin'],
        'status' => $row['status'],
        'nama_kelas' => $row['nama_kelas']
    ];
}

// Kembalikan hasil dalam format JSON
echo json_encode($students);
?>