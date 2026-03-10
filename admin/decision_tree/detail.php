<?php
ob_start();
require_once '../../database/config.php';
require_once '../include/header.php';


// Pastikan ada ID prediksi yang dikirim
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script>alert('ID Prediksi tidak valid!'); window.location.href='index.php';</script>";
    exit;
}

$prediksi_id = mysqli_real_escape_string($conn, $_GET['id']);

// Ambil data prediksi jurusan
$query_prediksi = "SELECT pj.*, s.nis, s.nama_lengkap, k.nama_kelas 
                FROM prediksi_jurusan pj 
                JOIN siswa s ON pj.siswa_id = s.id 
                JOIN kelas k ON s.kelas_id = k.id 
                WHERE pj.id = '$prediksi_id'";
$result_prediksi = mysqli_query($conn, $query_prediksi);

if (!$result_prediksi || mysqli_num_rows($result_prediksi) == 0) {
    echo "<script>alert('Data prediksi tidak ditemukan!'); window.location.href='index.php';</script>";
    exit;
}

$prediksi = mysqli_fetch_assoc($result_prediksi);

// Fungsi untuk mendapatkan keterangan kategori nilai
function getKeteranganNilai($kategori) {
    switch ($kategori) {
        case 'Tinggi':
            return 'Nilai >= 80';
        case 'Sedang':
            return 'Nilai >= 70 dan < 80';
        case 'Rendah':
            return 'Nilai < 70';
        default:
            return '-';
    }
}

// Fungsi untuk mendapatkan keterangan kategori IQ
function getKeteranganIQ($kategori) {
    switch ($kategori) {
        case 'Tinggi':
            return 'IQ >= 110';
        case 'Sedang':
            return 'IQ >= 90 dan < 110';
        case 'Rendah':
            return 'IQ < 90';
        default:
            return '-';
    }
}

// Fungsi untuk mendapatkan keterangan minat
function getKeteranganMinat($minat) {
    switch ($minat) {
        case 'Baik':
            return 'Minat sangat tinggi pada IPA';
        case 'Cukup':
            return 'Minat cukup pada IPS';
        case 'Kurang':
            return 'Tidak ada minat khusus';
        default:
            return $minat; // Tampilkan nilai asli jika tidak cocok
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
                    <i class="fas fa-search me-2"></i>Detail Hasil Prediksi Jurusan
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
                                    <td><?= htmlspecialchars($prediksi['nis']) ?></td>
                                </tr>
                                <tr>
                                    <th>Nama Lengkap</th>
                                    <td><?= htmlspecialchars($prediksi['nama_lengkap']) ?></td>
                                </tr>
                                <tr>
                                    <th>Kelas</th>
                                    <td><?= htmlspecialchars($prediksi['nama_kelas']) ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="card-title mb-0">Hasil Prediksi</h5>
                        </div>
                        <div class="card-body text-center">
                            <h1 class="display-4 fw-bold text-<?= $prediksi['hasil_prediksi'] == 'IPA' ? 'primary' : 'success' ?>">
                                <?= $prediksi['hasil_prediksi'] ?>
                            </h1>
                            <p class="lead">Jurusan yang direkomendasikan</p>
                            <hr>
                            <p class="text-muted">Prediksi dilakukan pada: <?= date('d/m/Y H:i', strtotime($prediksi['created_at'])) ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Data Akademik</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h5 class="card-title">Nilai Mata Pelajaran</h5>
                                    <table class="table table-bordered">
                                        <tr>
                                            <th>Rata-rata Nilai IPA</th>
                                            <td><?= number_format($prediksi['nilai_mapel_ipa'], 2) ?></td>
                                        </tr>
                                        <tr>
                                            <th>Rata-rata Nilai IPS</th>
                                            <td><?= number_format($prediksi['nilai_mapel_ips'], 2) ?></td>
                                        </tr>
                                        <tr>
                                            <th>Kategori Nilai</th>
                                            <td>
                                                <span class="badge bg-<?= $prediksi['kategori_nilai'] == 'Tinggi' ? 'success' : ($prediksi['kategori_nilai'] == 'Sedang' ? 'warning' : 'danger') ?>">
                                                    <?= $prediksi['kategori_nilai'] ?>
                                                </span>
                                                <small class="d-block text-muted"><?= getKeteranganNilai($prediksi['kategori_nilai']) ?></small>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h5 class="card-title">Data Pendukung</h5>
                                    <table class="table table-bordered">
                                        <tr>
                                            <th>Nilai IQ</th>
                                            <td><?= $prediksi['nilai_iq'] ?></td>
                                        </tr>
                                        <tr>
                                            <th>Kategori IQ</th>
                                            <td>
                                                <span class="badge bg-<?= $prediksi['kategori_iq'] == 'Tinggi' ? 'success' : ($prediksi['kategori_iq'] == 'Sedang' ? 'warning' : 'danger') ?>">
                                                    <?= $prediksi['kategori_iq'] ?>
                                                </span>
                                                <small class="d-block text-muted"><?= getKeteranganIQ($prediksi['kategori_iq']) ?></small>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Minat</th>
                                            <td>
                                                <span class="badge bg-info"><?= $prediksi['minat'] ?></span>
                                                <small class="d-block text-muted"><?= getKeteranganMinat($prediksi['minat']) ?></small>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Penjelasan Hasil Prediksi</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Prediksi Jurusan:</strong> <?= $prediksi['hasil_prediksi'] ?>
                    </div>
                    
                    <h6>Berdasarkan Aturan Algoritma C4.5:</h6>
                    
                    <?php if ($prediksi['minat'] == 'Kurang'): ?>
                        <p>Karena minat siswa adalah <strong><?= $prediksi['minat'] ?></strong> (<?= getKeteranganMinat($prediksi['minat']) ?>), maka prediksi jurusan adalah <strong>IPS</strong>.</p>
                    <?php elseif ($prediksi['minat'] == 'Baik'): ?>
                        <?php if ($prediksi['kategori_nilai'] == 'Tinggi' || $prediksi['kategori_nilai'] == 'Sedang'): ?>
                            <p>Karena minat siswa adalah <strong><?= $prediksi['minat'] ?></strong> (<?= getKeteranganMinat($prediksi['minat']) ?>) dan kategori nilai adalah <strong><?= $prediksi['kategori_nilai'] ?></strong> (<?= getKeteranganNilai($prediksi['kategori_nilai']) ?>), maka prediksi jurusan adalah <strong>IPA</strong>.</p>
                        <?php else: ?>
                            <p>Karena minat siswa adalah <strong><?= $prediksi['minat'] ?></strong> (<?= getKeteranganMinat($prediksi['minat']) ?>) tetapi kategori nilai adalah <strong><?= $prediksi['kategori_nilai'] ?></strong> (<?= getKeteranganNilai($prediksi['kategori_nilai']) ?>), maka prediksi jurusan adalah <strong>IPS</strong>.</p>
                        <?php endif; ?>
                    <?php elseif ($prediksi['minat'] == 'Cukup'): ?>
                        <?php if ($prediksi['kategori_nilai'] == 'Tinggi'): ?>
                            <p>Karena minat siswa adalah <strong><?= $prediksi['minat'] ?></strong> (<?= getKeteranganMinat($prediksi['minat']) ?>) tetapi kategori nilai adalah <strong><?= $prediksi['kategori_nilai'] ?></strong> (<?= getKeteranganNilai($prediksi['kategori_nilai']) ?>), maka prediksi jurusan adalah <strong>IPA</strong>.</p>
                        <?php else: ?>
                            <p>Karena minat siswa adalah <strong><?= $prediksi['minat'] ?></strong> (<?= getKeteranganMinat($prediksi['minat']) ?>) dan kategori nilai adalah <strong><?= $prediksi['kategori_nilai'] ?></strong> (<?= getKeteranganNilai($prediksi['kategori_nilai']) ?>), maka prediksi jurusan adalah <strong>IPS</strong>.</p>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between">
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Kembali
                        </a>
                        <div>
                            <a href="hapus.php?id=<?= $prediksi_id ?>" class="btn btn-danger" onclick="return confirm('Yakin ingin menghapus data prediksi ini?')">
                                <i class="fas fa-trash me-1"></i> Hapus Prediksi
                            </a>
                            <button type="button" class="btn btn-primary ms-2" onclick="window.print()">
                                <i class="fas fa-print me-1"></i> Cetak
                            </button>
                        </div>
                    </div>
                </div>
            </div>

 
<div class="card mt-4">
    <div class="card-header bg-light">
        <h5 class="card-title mb-0">Visualisasi Pohon Keputusan</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="alert alert-secondary">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Informasi:</strong> Berikut adalah visualisasi pohon keputusan algoritma C4.5 yang digunakan dalam prediksi jurusan.
                </div>
                
                <div class="tree-container p-3" style="overflow-x: auto;">
                    <!-- SVG Diagram Pohon Keputusan -->
                    <svg width="100%" height="360" viewBox="0 0 1000 360">
                        <!-- Nodes -->
                        <!-- Root Node (IQ) -->
                        <rect x="450" y="10" width="100" height="50" rx="5" fill="#6c757d" />
                        <text x="500" y="40" font-family="Arial" font-size="14" text-anchor="middle" fill="white">Tes IQ</text>
                        
                        <!-- Level 1 Nodes -->
                        <!-- Kurang Node -->
                        <rect x="200" y="110" width="100" height="50" rx="5" fill="#17a2b8" />
                        <text x="250" y="140" font-family="Arial" font-size="14" text-anchor="middle" fill="white">IQ "Kurang"</text>
                        
                        <!-- Cukup Node -->
                        <rect x="450" y="110" width="100" height="50" rx="5" fill="#17a2b8" />
                        <text x="500" y="140" font-family="Arial" font-size="14" text-anchor="middle" fill="white">IQ "Cukup"</text>
                        
                        <!-- Baik Node -->
                        <rect x="700" y="110" width="100" height="50" rx="5" fill="#17a2b8" />
                        <text x="750" y="140" font-family="Arial" font-size="14" text-anchor="middle" fill="white">IQ "Baik"</text>
                        
                        <!-- Level 2 Nodes for Cukup -->
                        <rect x="350" y="210" width="100" height="50" rx="5" fill="#ffc107" />
                        <text x="400" y="240" font-family="Arial" font-size="14" text-anchor="middle" fill="black">Nilai "Tinggi"</text>
                        
                        <rect x="500" y="210" width="150" height="50" rx="5" fill="#ffc107" />
                        <text x="575" y="240" font-family="Arial" font-size="14" text-anchor="middle" fill="black">Nilai "Sedang/Rendah"</text>
                        
                        <!-- Level 2 Nodes for Baik -->
                        <rect x="650" y="210" width="150" height="50" rx="5" fill="#ffc107" />
                        <text x="725" y="240" font-family="Arial" font-size="14" text-anchor="middle" fill="black">Nilai "Tinggi/Sedang"</text>
                        
                        <rect x="850" y="210" width="100" height="50" rx="5" fill="#ffc107" />
                        <text x="900" y="240" font-family="Arial" font-size="14" text-anchor="middle" fill="black">Nilai "Rendah"</text>
                        
                        <!-- Leaf Nodes -->
                        <rect x="200" y="290" width="100" height="50" rx="5" fill="#28a745" />
                        <text x="250" y="320" font-family="Arial" font-size="14" text-anchor="middle" fill="white">IPS</text>
                        
                        <rect x="350" y="290" width="100" height="50" rx="5" fill="#007bff" />
                        <text x="400" y="320" font-family="Arial" font-size="14" text-anchor="middle" fill="white">IPA</text>
                        
                        <rect x="500" y="290" width="100" height="50" rx="5" fill="#28a745" />
                        <text x="550" y="320" font-family="Arial" font-size="14" text-anchor="middle" fill="white">IPS</text>
                        
                        <rect x="650" y="290" width="100" height="50" rx="5" fill="#007bff" />
                        <text x="700" y="320" font-family="Arial" font-size="14" text-anchor="middle" fill="white">IPA</text>
                        
                        <rect x="850" y="290" width="100" height="50" rx="5" fill="#28a745" />
                        <text x="900" y="320" font-family="Arial" font-size="14" text-anchor="middle" fill="white">IPS</text>
                        
                        <!-- Connecting Lines -->
                        <!-- From Root to Level 1 -->
                        <line x1="500" y1="60" x2="250" y2="110" stroke="#6c757d" stroke-width="2" />
                        <line x1="500" y1="60" x2="500" y2="110" stroke="#6c757d" stroke-width="2" />
                        <line x1="500" y1="60" x2="750" y2="110" stroke="#6c757d" stroke-width="2" />
                        
                        <!-- From Level 1 to Level 2 -->
                        <line x1="500" y1="160" x2="400" y2="210" stroke="#17a2b8" stroke-width="2" />
                        <line x1="500" y1="160" x2="575" y2="210" stroke="#17a2b8" stroke-width="2" />
                        
                        <line x1="750" y1="160" x2="725" y2="210" stroke="#17a2b8" stroke-width="2" />
                        <line x1="750" y1="160" x2="900" y2="210" stroke="#17a2b8" stroke-width="2" />
                        
                        <!-- From Level 2 to Leaf -->
                        <line x1="250" y1="160" x2="250" y2="290" stroke="#17a2b8" stroke-width="2" />
                        <line x1="400" y1="260" x2="400" y2="290" stroke="#ffc107" stroke-width="2" />
                        <line x1="575" y1="260" x2="550" y2="290" stroke="#ffc107" stroke-width="2" />
                        <line x1="725" y1="260" x2="700" y2="290" stroke="#ffc107" stroke-width="2" />
                        <line x1="900" y1="260" x2="900" y2="290" stroke="#ffc107" stroke-width="2" />
                        
                        <!-- Labels on edges -->
                        <text x="350" y="90" font-family="Arial" font-size="12" fill="#495057">< 90</text>
                        <text x="500" y="90" font-family="Arial" font-size="12" fill="#495057">≥ 90 dan < 110</text>
                        <text x="650" y="90" font-family="Arial" font-size="12" fill="#495057">≥ 110</text>
                        
                        <text x="430" y="190" font-family="Arial" font-size="12" fill="#495057">≥ 80</text>
                        <text x="560" y="190" font-family="Arial" font-size="12" fill="#495057">< 80</text>
                        
                        <text x="680" y="190" font-family="Arial" font-size="12" fill="#495057">≥ 70</text>
                        <text x="830" y="190" font-family="Arial" font-size="12" fill="#495057">< 70</text>
                    </svg>
                </div>
                
                <!-- Highlight Current Path in Decision Tree Based on Student Data -->
                <div class="mt-4">
                    <h6>Jalur Keputusan untuk Siswa Ini:</h6>
                    <div class="card bg-light">
                        <div class="card-body">
                            <ol class="mb-0">
                                <?php 
                                // Tentukan jalur berdasarkan data prediksi
                                echo "<li><strong>Tes IQ</strong>: " . $prediksi['tes_iq_kategori'] . " (Skor IQ: " . $prediksi['nilai_iq'] . ")</li>";
                                
                                if ($prediksi['tes_iq_kategori'] == "Kurang") {
                                    echo "<li><strong>Hasil langsung</strong>: IPS (Karena IQ terkategori kurang)</li>";
                                } else if ($prediksi['tes_iq_kategori'] == "Cukup") {
                                    echo "<li><strong>Periksa Nilai</strong>: Kategori " . $prediksi['kategori_nilai'] . " (" . number_format($prediksi['nilai_mapel_ipa'], 2) . ")</li>";
                                    if ($prediksi['kategori_nilai'] == "Tinggi") {
                                        echo "<li><strong>Hasil</strong>: IPA (Karena IQ cukup dan nilai tinggi)</li>";
                                    } else {
                                        echo "<li><strong>Hasil</strong>: IPS (Karena IQ cukup tetapi nilai tidak tinggi)</li>";
                                    }
                                } else if ($prediksi['tes_iq_kategori'] == "Baik") {
                                    echo "<li><strong>Periksa Nilai</strong>: Kategori " . $prediksi['kategori_nilai'] . " (" . number_format($prediksi['nilai_mapel_ipa'], 2) . ")</li>";
                                    if ($prediksi['kategori_nilai'] == "Tinggi" || $prediksi['kategori_nilai'] == "Sedang") {
                                        echo "<li><strong>Hasil</strong>: IPA (Karena IQ baik dan nilai tinggi/sedang)</li>";
                                    } else {
                                        echo "<li><strong>Hasil</strong>: IPS (Karena IQ baik tetapi nilai rendah)</li>";
                                    }
                                }
                                ?>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

        </main>
    </div>
</div>

<style>
@media print {
    .navbar, .sidebar, .btn, .non-printable {
        display: none !important;
    }
    
    main {
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    
    .card {
        break-inside: avoid;
    }
}
</style>


<?php require_once '../include/footer.php'; 
ob_end_flush();
?>

