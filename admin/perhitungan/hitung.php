<?php
require_once '../../database/config.php';
require_once '../include/header.php';

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
$query_minat = "SELECT * FROM minat_siswa WHERE siswa_id = '$siswa_id' ORDER BY created_at DESC LIMIT 1";
$result_minat = mysqli_query($conn, $query_minat);
$minat = mysqli_fetch_assoc($result_minat)['minat'] ?? '';

// Fungsi untuk menentukan jurusan berdasarkan kriteria
function tentukanJurusan($nilai_ipa, $nilai_ips, $kategori_iq, $minat) {
    $skor_ipa = 0;
    $skor_ips = 0;
    
    // Hitung skor berdasarkan nilai
    if ($nilai_ipa >= 80) $skor_ipa += 3;
    elseif ($nilai_ipa >= 75) $skor_ipa += 2;
    else $skor_ipa += 1;
    
    if ($nilai_ips >= 80) $skor_ips += 3;
    elseif ($nilai_ips >= 70) $skor_ips += 2;
    else $skor_ips += 1;
    
    // Tambah skor berdasarkan IQ
    if ($kategori_iq == 'Tinggi') {
        $skor_ipa += 2;
        $skor_ips += 2;
    } elseif ($kategori_iq == 'Sedang') {
        $skor_ipa += 1;
        $skor_ips += 1;
    }
    
    // Tambah skor berdasarkan minat
    if ($minat == 'IPA') $skor_ipa += 2;
    else $skor_ips += 2;
    
    // Tentukan jurusan
    if ($skor_ipa > $skor_ips) {
        return 'IPA';
    } elseif ($skor_ips > $skor_ipa) {
        return 'IPS';
    } else {
        return $minat; // Jika skor sama, gunakan minat sebagai penentu
    }
}

// Fungsi untuk menentukan kategori nilai rata-rata
function kategori_nilai($nilai) {
    if ($nilai >= 80) return 'Tinggi';
    if ($nilai >= 70) return 'Sedang';
    return 'Rendah';
}

// Tentukan jurusan
$jurusan = tentukanJurusan($nilai_ipa, $nilai_ips, $iq['kategori'], $minat);

// Simpan hasil ke database
$kategori_nilai = kategori_nilai(($nilai_ipa + $nilai_ips) / 2);
$query_insert = "INSERT INTO prediksi_jurusan 
    (siswa_id, nilai_mapel_ipa, nilai_mapel_ips, kategori_nilai, nilai_iq, kategori_iq, minat, hasil_prediksi) 
    VALUES (
        '$siswa_id',
        '$nilai_ipa',
        '$nilai_ips',
        '$kategori_nilai',
        '{$iq['skor']}',
        '{$iq['kategori']}',
        '$minat',
        '$jurusan'
    )";
mysqli_query($conn, $query_insert);

// Hitung Entropy dan Gain untuk jalur pohon keputusan siswa ini
function entropy2($a, $b) {
    $total = $a + $b;
    if ($total == 0) return 0;
    $pa = $a / $total;
    $pb = $b / $total;
    $e = 0;
    if ($pa > 0) $e -= $pa * log($pa, 2);
    if ($pb > 0) $e -= $pb * log($pb, 2);
    return round($e, 3);
}

// Simulasi: Data node (misal, untuk 1 siswa, gunakan kategori dan hasil prediksi)
$kategori_nilai = kategori_nilai(($nilai_ipa + $nilai_ips) / 2);
$kategori_iq = $iq['kategori'];

// Node 1: Jumlah Nilai Mapel
$node1 = [
    'Tinggi' => ['IPA' => ($kategori_nilai == 'Tinggi' && $jurusan == 'IPA') ? 1 : 0, 'IPS' => ($kategori_nilai == 'Tinggi' && $jurusan == 'IPS') ? 1 : 0],
    'Sedang' => ['IPA' => ($kategori_nilai == 'Sedang' && $jurusan == 'IPA') ? 1 : 0, 'IPS' => ($kategori_nilai == 'Sedang' && $jurusan == 'IPS') ? 1 : 0],
    'Rendah' => ['IPA' => ($kategori_nilai == 'Rendah' && $jurusan == 'IPA') ? 1 : 0, 'IPS' => ($kategori_nilai == 'Rendah' && $jurusan == 'IPS') ? 1 : 0],
];

// Node 2: Test IQ
$node2 = [
    'Tinggi' => ['IPA' => ($kategori_iq == 'Tinggi' && $jurusan == 'IPA') ? 1 : 0, 'IPS' => ($kategori_iq == 'Tinggi' && $jurusan == 'IPS') ? 1 : 0],
    'Sedang' => ['IPA' => ($kategori_iq == 'Sedang' && $jurusan == 'IPA') ? 1 : 0, 'IPS' => ($kategori_iq == 'Sedang' && $jurusan == 'IPS') ? 1 : 0],
    'Rendah' => ['IPA' => ($kategori_iq == 'Rendah' && $jurusan == 'IPA') ? 1 : 0, 'IPS' => ($kategori_iq == 'Rendah' && $jurusan == 'IPS') ? 1 : 0],
];

// Entropy dan Gain Node 1
$total_ipa = ($jurusan == 'IPA') ? 1 : 0;
$total_ips = ($jurusan == 'IPS') ? 1 : 0;
$entropy_total = entropy2($total_ipa, $total_ips);
$entropy_nilai = [];
foreach ($node1 as $kat => $v) {
    $entropy_nilai[$kat] = entropy2($v['IPA'], $v['IPS']);
}
$gain_nilai = $entropy_total;
foreach ($node1 as $kat => $v) {
    $sum = $v['IPA'] + $v['IPS'];
    if ($sum > 0) $gain_nilai -= ($sum / 1) * $entropy_nilai[$kat];
}

// Entropy dan Gain Node 2
$entropy_iq = [];
foreach ($node2 as $kat => $v) {
    $entropy_iq[$kat] = entropy2($v['IPA'], $v['IPS']);
}
$gain_iq = $entropy_total;
foreach ($node2 as $kat => $v) {
    $sum = $v['IPA'] + $v['IPS'];
    if ($sum > 0) $gain_iq -= ($sum / 1) * $entropy_iq[$kat];
}

// Step-step pengambilan keputusan
$steps = [];
$steps[] = "Cek Jumlah Nilai Mapel: <b>$kategori_nilai</b>";
$steps[] = "Cek Test IQ: <b>$kategori_iq</b>";
$steps[] = "Hasil akhir: <b>$jurusan</b>";

// Tampilkan hasil
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Hasil Perhitungan Penjurusan</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Data Siswa</h5>
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
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h5>Hasil Penilaian</h5>
                                <table class="table table-bordered">
                                    <tr>
                                        <th width="30%">Nilai IPA</th>
                                        <td><?= number_format($nilai_ipa, 2) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Nilai IPS</th>
                                        <td><?= number_format($nilai_ips, 2) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Skor IQ</th>
                                        <td><?= $iq['skor'] ?> (<?= $iq['kategori'] ?>)</td>
                                    </tr>
                                    <tr>
                                        <th>Minat</th>
                                        <td><?= $minat ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <div class="alert alert-info mt-4">
                            <h4 class="alert-heading">Hasil Prediksi Jurusan</h4>
                            <p class="mb-0">Berdasarkan analisis data, siswa direkomendasikan untuk masuk jurusan:</p>
                            <h3 class="text-center mt-3">
                                <span class="badge bg-<?= $jurusan == 'IPA' ? 'primary' : 'info' ?> p-2">
                                    <?= $jurusan ?>
                                </span>
                            </h3>
                        </div>
                        
                        <div class="text-center mt-4">
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Kembali
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <h4 class="card-title">Perhitungan Entropy & Gain (Siswa Ini)</h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Node</th>
                        <th>IPA</th>
                        <th>IPS</th>
                        <th>Entropy</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Total</td>
                        <td><?= $total_ipa ?></td>
                        <td><?= $total_ips ?></td>
                        <td><?= $entropy_total ?></td>
                    </tr>
                    <?php foreach($node1 as $kat => $v): ?>
                    <tr>
                        <td>Jumlah Nilai Mapel (<?= $kat ?>)</td>
                        <td><?= $v['IPA'] ?></td>
                        <td><?= $v['IPS'] ?></td>
                        <td><?= $entropy_nilai[$kat] ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php foreach($node2 as $kat => $v): ?>
                    <tr>
                        <td>Test IQ (<?= $kat ?>)</td>
                        <td><?= $v['IPA'] ?></td>
                        <td><?= $v['IPS'] ?></td>
                        <td><?= $entropy_iq[$kat] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            <strong>Gain Jumlah Nilai Mapel:</strong> <?= $gain_nilai ?><br>
            <strong>Gain Test IQ:</strong> <?= $gain_iq ?><br>
        </div>
        <div class="mt-3">
            <strong>Rumus Entropy:</strong><br>
            Entropy(S) = - (S1/S) * log2(S1/S) - (S2/S) * log2(S2/S)<br>
            <strong>Rumus Gain:</strong><br>
            Gain(S, A) = Entropy(S) - Σ (|Si|/|S| * Entropy(Si))<br>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <h4 class="card-title">Step-step Pengambilan Keputusan</h4>
    </div>
    <div class="card-body">
        <ol>
            <?php foreach($steps as $s): ?>
            <li><?= $s ?></li>
            <?php endforeach; ?>
        </ol>
    </div>
</div>

<div class="card mt-4 mb-4">
    <div class="card-header">
        <h4 class="card-title">Pohon Keputusan Akhir</h4>
    </div>
    <div class="card-body">
        <div class="mermaid">
graph TD
    A["Jumlah Nilai Mata Pelajaran"]
    A -->|Tinggi| B["1.1 Test IQ"]
    A -->|Sedang| C["1.2 Test IQ"]
    A -->|Rendah| D["1.3 Test IQ"]

    B -->|Tinggi| E[IPS]
    B -->|Sedang| F[IPS]
    B -->|Rendah| G[IPA]

    C -->|Tinggi| H[IPA]
    C -->|Sedang| I[IPS]
    C -->|Rendah| J[IPS]

    D -->|Tinggi| K[IPA]
    D -->|Sedang| L[IPA]
    D -->|Rendah| M[IPS]
        </div>
        <div><b>Hasil akhir untuk siswa ini: <?= $jurusan ?></b></div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/mermaid/dist/mermaid.min.js"></script>
<script>mermaid.initialize({startOnLoad:true});</script>

<?php require_once '../include/footer.php'; ?> 