<?php
ob_start();
require_once '../../database/config.php';
require_once '../include/header.php';


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

// Fungsi untuk mengkonversi nilai IQ ke kategori tes IQ
function kategoriTesIQ($skor_iq) {
    if ($skor_iq >= 110) {
        return 'Baik';
    } else if ($skor_iq >= 90) {
        return 'Cukup';
    } else {
        return 'Kurang'; // Default jika rendah
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

// Inisialisasi variabel
$berhasil = 0;
$gagal = 0;
$error_msgs = [];
$processed_count = 0;

// Proses batch jika ada POST request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Query untuk mendapatkan semua siswa yang belum diprediksi
    $query = "SELECT s.id, s.nama_lengkap, 
            AVG(CASE WHEN mp.kategori = 'IPA' THEN n.nilai ELSE NULL END) AS nilai_ipa,
            AVG(CASE WHEN mp.kategori = 'IPS' THEN n.nilai ELSE NULL END) AS nilai_ips,
            ti.skor as nilai_iq,
            ms.minat
            FROM siswa s
            JOIN kelas k ON s.kelas_id = k.id
            LEFT JOIN nilai n ON s.id = n.siswa_id
            LEFT JOIN mata_pelajaran mp ON n.mapel_id = mp.id
            LEFT JOIN test_iq ti ON s.id = ti.siswa_id AND ti.id = (
                SELECT MAX(id) FROM test_iq WHERE siswa_id = s.id
            )
            LEFT JOIN minat_siswa ms ON s.id = ms.siswa_id
            LEFT JOIN prediksi_jurusan pj ON s.id = pj.siswa_id
            WHERE pj.id IS NULL AND s.status = 'aktif'
            GROUP BY s.id, s.nama_lengkap, ti.skor, ms.minat
            HAVING nilai_ipa IS NOT NULL 
            AND nilai_ips IS NOT NULL 
            AND nilai_iq IS NOT NULL 
            AND minat IS NOT NULL";
    
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        $error_msgs[] = "Error pada query: " . mysqli_error($conn);
    } else {
        while ($row = mysqli_fetch_assoc($result)) {
            $processed_count++;
            
            $siswa_id = $row['id'];
            $nilai_mapel_ipa = $row['nilai_ipa'];
            $nilai_mapel_ips = $row['nilai_ips'];
            $nilai_iq = $row['nilai_iq'];
            $minat = $row['minat'];
            
            // Kategorikan nilai dan IQ
            $kategori_nilai = kategoriNilai($nilai_mapel_ipa);
            $kategori_iq = kategoriIQ($nilai_iq);
            
            // Konversi nilai IQ ke kategori tes IQ
            $tes_iq_kategori = kategoriTesIQ($nilai_iq);
            
            // Prediksi jurusan
            $hasil_prediksi = prediksijurusan($kategori_nilai, $tes_iq_kategori);
            
            // Simpan hasil prediksi ke database
            $query_insert = "INSERT INTO prediksi_jurusan (siswa_id, nilai_mapel_ipa, nilai_mapel_ips, kategori_nilai, nilai_iq, kategori_iq, minat, tes_iq_kategori, hasil_prediksi) 
                            VALUES ('$siswa_id', '$nilai_mapel_ipa', '$nilai_mapel_ips', '$kategori_nilai', '$nilai_iq', '$kategori_iq', '$minat', '$tes_iq_kategori', '$hasil_prediksi')";
            
            if (mysqli_query($conn, $query_insert)) {
                $berhasil++;
            } else {
                $gagal++;
                $error_msgs[] = "Error menyimpan data untuk siswa " . $row['nama_lengkap'] . ": " . mysqli_error($conn);
            }
        }
    }
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
                    <i class="fas fa-calculator me-2"></i>Proses Penjurusan Batch
                </h2>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="index.php" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Proses Semua Siswa</h5>
                </div>
                <div class="card-body">
                    <?php if ($_SERVER['REQUEST_METHOD'] == 'POST'): ?>
                        <?php if ($processed_count > 0): ?>
                            <div class="alert <?= $berhasil > 0 ? 'alert-success' : 'alert-warning' ?>">
                                <h5 class="alert-heading">Hasil Proses Penjurusan</h5>
                                <p>Total siswa diproses: <?= $processed_count ?></p>
                                <p>Berhasil: <?= $berhasil ?> siswa</p>
                                <p>Gagal: <?= $gagal ?> siswa</p>
                                
                                <?php if (!empty($error_msgs)): ?>
                                    <hr>
                                    <h6>Detail Error:</h6>
                                    <ul>
                                        <?php foreach ($error_msgs as $msg): ?>
                                            <li><?= htmlspecialchars($msg) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <h5 class="alert-heading">Tidak Ada Data untuk Diproses</h5>
                                <p>Semua siswa telah memiliki prediksi jurusan atau tidak ada siswa yang aktif.</p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="text-center">
                            <a href="index.php" class="btn btn-primary">
                                <i class="fas fa-list me-1"></i> Lihat Hasil Prediksi
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Informasi:</strong> Fitur ini akan memproses prediksi jurusan untuk semua siswa yang belum memiliki prediksi jurusan secara otomatis. Pastikan data nilai, IQ, dan minat siswa sudah diisi.
                        </div>
                        
                        <div class="mb-3">
                            <h5>Proses Prediksi Menggunakan:</h5>
                            <ul>
                                <li>Rata-rata nilai mata pelajaran IPA dan IPS</li>
                                <li>Skor IQ terbaru (akan dikonversi otomatis ke kategori tes IQ)</li>
                                <li>Data minat siswa</li>
                            </ul>
                        </div>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Perhatian:</strong> Siswa yang tidak memiliki data lengkap (nilai, IQ, minat) akan dilewati dan tidak akan diproses.
                        </div>
                        
                        <form method="POST" action="" onsubmit="return confirm('Apakah Anda yakin ingin memproses prediksi jurusan untuk semua siswa yang belum diprediksi?');">
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-play me-1"></i> Mulai Proses Batch
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
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

