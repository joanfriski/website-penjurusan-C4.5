<?php
ob_start();
require_once '../../database/config.php';
require_once '../include/header.php';

// Pastikan ada ID siswa yang dikirim
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

// Ambil data nilai IPA
$query_nilai_ipa = "SELECT AVG(n.nilai) as rata_nilai 
                    FROM nilai n 
                    JOIN mata_pelajaran mp ON n.mapel_id = mp.id 
                    WHERE n.siswa_id = '$siswa_id' AND mp.kategori = 'IPA'";
$result_nilai_ipa = mysqli_query($conn, $query_nilai_ipa);
$nilai_ipa = mysqli_fetch_assoc($result_nilai_ipa)['rata_nilai'] ?? 0;

// Ambil data nilai IPS
$query_nilai_ips = "SELECT AVG(n.nilai) as rata_nilai 
                    FROM nilai n 
                    JOIN mata_pelajaran mp ON n.mapel_id = mp.id 
                    WHERE n.siswa_id = '$siswa_id' AND mp.kategori = 'IPS'";
$result_nilai_ips = mysqli_query($conn, $query_nilai_ips);
$nilai_ips = mysqli_fetch_assoc($result_nilai_ips)['rata_nilai'] ?? 0;

// Ambil data tes IQ
$query_iq = "SELECT * FROM test_iq WHERE siswa_id = '$siswa_id' ORDER BY id DESC LIMIT 1";
$result_iq = mysqli_query($conn, $query_iq);
$iq = mysqli_fetch_assoc($result_iq) ?? ['skor' => 0, 'kategori' => ''];

// Ambil data minat siswa
$query_minat = "SELECT * FROM minat_siswa WHERE siswa_id = '$siswa_id'";
$result_minat = mysqli_query($conn, $query_minat);
$minat = mysqli_fetch_assoc($result_minat)['minat'] ?? '';

// Fungsi untuk kategorikan nilai
function kategoriNilai($nilai) {
    if ($nilai >= 80) {
        return 'Tinggi';
    } elseif ($nilai >= 70) {
        return 'Sedang';
    } else {
        return 'Rendah';
    }
}

// Fungsi untuk kategorikan IQ
function kategoriIQ($skor) {
    if ($skor >= 110) {
        return 'Tinggi';
    } elseif ($skor >= 90) {
        return 'Sedang';
    } else {
        return 'Rendah';
    }
}

// Fungsi untuk mengkonversi minat ke kategori tes IQ
function kategoriTesIQ($skor_iq) {
    if ($skor_iq >= 110) {
        return 'Baik';
    } else if ($skor_iq >= 90) {
        return 'Cukup';
    } else {
        return 'Kurang';
    }
}

// Proses form jika ada POST request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Gunakan nilai yang sudah ada di database karena form sekarang readonly
    $nilai_mapel_ipa = $nilai_ipa;
    $nilai_mapel_ips = $nilai_ips;
    $nilai_iq = $iq['skor'] ?? 0;
    $minat_siswa = $minat;
    
    // Kategorikan nilai mata pelajaran
    $kategori_nilai = kategoriNilai($nilai_mapel_ipa);
    
    // Kategorikan IQ
    $kategori_iq = kategoriIQ($nilai_iq);
    
    // Konversi skor IQ ke kategori tes IQ
    $tes_iq_kategori = kategoriTesIQ($nilai_iq);
    
    // Algoritma C4.5 untuk prediksi jurusan
    $hasil_prediksi = prediksijurusan($kategori_nilai, $tes_iq_kategori);
    
    // Simpan hasil prediksi ke database
    $query_insert = "INSERT INTO prediksi_jurusan (siswa_id, nilai_mapel_ipa, nilai_mapel_ips, kategori_nilai, nilai_iq, kategori_iq, minat, tes_iq_kategori, hasil_prediksi) 
                    VALUES ('$siswa_id', '$nilai_mapel_ipa', '$nilai_mapel_ips', '$kategori_nilai', '$nilai_iq', '$kategori_iq', '$minat_siswa', '$tes_iq_kategori', '$hasil_prediksi')";
    
    if (mysqli_query($conn, $query_insert)) {
        echo "<script>alert('Prediksi jurusan berhasil disimpan!'); window.location.href='index.php';</script>";
    } else {
        echo "<script>alert('Gagal menyimpan prediksi jurusan: " . mysqli_error($conn) . "');</script>";
    }
}

// Fungsi untuk prediksi jurusan menggunakan algoritma C4.5
function prediksijurusan($kategori_nilai, $tes_iq) {
    // Berdasarkan pohon keputusan yang telah dibuat, aturan prediksi adalah:
    // Jika tes IQ = "Kurang" maka IPS
    // Jika tes IQ = "Baik" dan nilai = "Tinggi" maka IPA
    // Jika tes IQ = "Baik" dan nilai = "Sedang" maka IPA
    // Jika tes IQ = "Baik" dan nilai = "Rendah" maka IPS
    // Jika tes IQ = "Cukup" dan nilai = "Tinggi" maka IPA
    // Jika tes IQ = "Cukup" dan nilai = "Sedang" maka IPS
    // Jika tes IQ = "Cukup" dan nilai = "Rendah" maka IPS
    
    if ($tes_iq == "Kurang") {
        return "IPS";
    } else if ($tes_iq == "Baik") {
        if ($kategori_nilai == "Tinggi" || $kategori_nilai == "Sedang") {
            return "IPA";
        } else {
            return "IPS";
        }
    } else if ($tes_iq == "Cukup") {
        if ($kategori_nilai == "Tinggi") {
            return "IPA";
        } else {
            return "IPS";
        }
    }
    
    // Default jika tidak ada aturan yang cocok
    return "IPS";
}
?>
<!-- CSS untuk perbaikan responsif content -->
<style>
    /* Base styling untuk content-wrapper */
    .content-wrapper {
        transition: all 0.3s ease;
        width: calc(100% - 250px);
        margin-left: 250px;
    }
    
    /* Saat sidebar collapsed */
    .content-wrapper.expanded {
        width: 100%;
        margin-left: 0;
    }
    
    /* Untuk perangkat mobile */
    @media (max-width: 768px) {
        .content-wrapper {
            width: 100%;
            margin-left: 0;
        }
    }
    
    /* Pastikan main content selalu mengikuti lebar yang tersedia */
    main {
        width: 100%;
        transition: all 0.3s ease;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <?php require_once '../include/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4 content-wrapper" id="content">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h2 class="h3">
                    <i class="fas fa-calculator me-2"></i>Prediksi Jurusan Siswa
                </h2>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="index.php" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="card-title mb-0">Data Siswa</h5>
                        </div>
                        <div class="card-body">
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
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="card-title mb-0">Data Akademik</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered">
                                <tr>
                                    <th width="30%">Rata-rata Nilai IPA</th>
                                    <td><?= $nilai_ipa ? number_format($nilai_ipa, 2) : '-' ?></td>
                                </tr>
                                <tr>
                                    <th>Rata-rata Nilai IPS</th>
                                    <td><?= $nilai_ips ? number_format($nilai_ips, 2) : '-' ?></td>
                                </tr>
                                <tr>
                                    <th>Nilai IQ</th>
                                    <td><?= $iq['skor'] ?? '-' ?></td>
                                </tr>
                                <tr>
                                    <th>Minat</th>
                                    <td><?= $minat ?: '-' ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Form Prediksi Jurusan</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="nilai_ipa" class="form-label">Nilai Rata-rata IPA</label>
                                <input type="text" class="form-control" id="nilai_ipa" name="nilai_ipa" value="<?= number_format($nilai_ipa, 2) ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label for="nilai_ips" class="form-label">Nilai Rata-rata IPS</label>
                                <input type="text" class="form-control" id="nilai_ips" name="nilai_ips" value="<?= number_format($nilai_ips, 2) ?>" readonly>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="nilai_iq" class="form-label">Nilai IQ</label>
                                <input type="text" class="form-control" id="nilai_iq" name="nilai_iq" value="<?= $iq['skor'] ?? '' ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label for="minat" class="form-label">Minat Siswa</label>
                                <input type="text" class="form-control" id="minat" name="minat" value="<?= $minat ?: 'Belum Ada Minat' ?>" readonly>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Informasi:</strong> Sistem akan melakukan prediksi jurusan berdasarkan algoritma C4.5 dengan aturan yang telah ditetapkan. Nilai IQ siswa akan dikonversi otomatis ke kategori tes IQ untuk perhitungan.
                        </div>
                        
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-calculator me-1"></i> Proses Prediksi
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Tambahan informasi algoritma C4.5 dari proses.php -->
            <div class="card mt-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Informasi Algoritma C4.5</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Aturan Pohon Keputusan:</h6>
                            <ul>
                                <li>Jika tes IQ = "Kurang" maka IPS</li>
                                <li>Jika tes IQ = "Baik" dan nilai = "Tinggi" maka IPA</li>
                                <li>Jika tes IQ = "Baik" dan nilai = "Sedang" maka IPA</li>
                                <li>Jika tes IQ = "Baik" dan nilai = "Rendah" maka IPS</li>
                                <li>Jika tes IQ = "Cukup" dan nilai = "Tinggi" maka IPA</li>
                                <li>Jika tes IQ = "Cukup" dan nilai = "Sedang" maka IPS</li>
                                <li>Jika tes IQ = "Cukup" dan nilai = "Rendah" maka IPS</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>Kategori Nilai:</h6>
                            <ul>
                                <li>Tinggi: >= 80</li>
                                <li>Sedang: >= 70 dan < 80</li>
                                <li>Rendah: < 70</li>
                            </ul>
                            
                            <h6>Kategori IQ:</h6>
                            <ul>
                                <li>Tinggi: >= 110</li>
                                <li>Sedang: >= 90 dan < 110</li>
                                <li>Rendah: < 90</li>
                            </ul>
                            
                            <h6>Konversi Nilai IQ ke Kategori Tes IQ (Otomatis):</h6>
                            <ul>
                                <li>IQ >= 110 = Kategori "Baik"</li>
                                <li>IQ >= 90 dan < 110 = Kategori "Cukup"</li>
                                <li>IQ < 90 = Kategori "Kurang"</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once '../include/footer.php'; 
ob_end_flush();
?>

