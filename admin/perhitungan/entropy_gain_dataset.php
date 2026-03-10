<?php
ob_start();
require_once '../../database/config.php';
require_once '../include/header.php';
if (isset($_GET['reset']) && $_GET['reset'] == 'true') {
    $_SESSION['reset_prediksi'] = true;
    header('Location: entropy_gain_dataset.php');
    exit;
}
?>
<div class="container-fluid">
    <div class="row">
        <?php require_once '../include/sidebar.php'; ?>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4 content-wrapper" id="content">
<div class="container-fluid mt-4 mb-4">
    <div class="row">
        <div class="col-12 text-center mb-3">
            <button id="btnBatchPrediksi" class="btn btn-primary">
                <span id="btnText"><i class="fas fa-cogs"></i> Hitung Semua Data</span>
                <span id="btnLoading" style="display:none"><span class="spinner-border spinner-border-sm"></span> Memproses...</span>
            </button>
            <button id="btnResetPrediksi" class="btn btn-danger ms-2">
                <i class="fas fa-undo"></i> Reset Data Prediksi
            </button>
        </div>
    </div>
</div>
<script>
document.getElementById('btnBatchPrediksi').onclick = function() {
    var btn = document.getElementById('btnBatchPrediksi');
    var btnText = document.getElementById('btnText');
    var btnLoading = document.getElementById('btnLoading');
    btn.disabled = true;
    btnText.style.display = 'none';
    btnLoading.style.display = '';
    fetch('proses_batch_prediksi.php')
        .then(response => response.text())
        .then(html => {
            setTimeout(function() {
                window.location.reload();
            }, 1000);
        })
        .catch(err => {
            alert('Terjadi kesalahan saat proses batch prediksi!');
            btn.disabled = false;
            btnText.style.display = '';
            btnLoading.style.display = 'none';
        });
};

document.getElementById('btnResetPrediksi').onclick = function() {
    if (!confirm('Yakin ingin mengembalikan data prediksi ke kondisi awal? Data di tabel prediksi_jurusan akan dihapus.')) return;
    var btn = document.getElementById('btnResetPrediksi');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Reset...';
    fetch('reset_prediksi.php')
        .then(response => response.text())
        .then(html => {
            alert('Reset data prediksi berhasil!');
            window.location.reload();
        })
        .catch(err => {
            alert('Terjadi kesalahan saat reset data!');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-undo"></i> Reset Data Prediksi';
        });
};
</script>
<?php
// Helper functions
function entropy($a, $b, &$formula = null) {
    $total = $a + $b;
    if ($total == 0) {
        $formula = "0";
        return 0;
    }
    $pa = $a / $total;
    $pb = $b / $total;
    $e = 0;
    $formula = "";
    if ($pa > 0) {
        $e -= $pa * log($pa, 2);
        $formula .= "(-$a/$total * log2($a/$total))";
    } else {
        $formula .= "(-$a/$total * log2($a/$total))";
    }
    if ($pb > 0) {
        $e -= $pb * log($pb, 2);
        if ($formula) $formula .= " + ";
        $formula .= "(-$b/$total * log2($b/$total))";
    } else {
        if ($formula) $formula .= " + ";
        $formula .= "(-$b/$total * log2($b/$total))";
    }
    $formula .= " = " . round($e, 3);
    return round($e, 3);
}
function gain($entropy_s, $subsets, &$formula = null) {
    $total = array_sum(array_column($subsets, 'total'));
    if ($total == 0) {
        $formula = "0";
        return 0;
    }
    $gain = $entropy_s;
    $formula = "$entropy_s - (";
    $subs = [];
    foreach ($subsets as $subset) {
        $subs[] = "({$subset['total']}/$total * {$subset['entropy']})";
        $gain -= ($subset['total'] / $total) * $subset['entropy'];
    }
    $formula .= implode(" + ", $subs) . ") = " . round($gain, 3);
    return round($gain, 3);
}
// Ambil data dari prediksi_jurusan
$query = "SELECT kategori_nilai, kategori_iq, hasil_prediksi, minat, status_klasifikasi FROM prediksi_jurusan WHERE kategori_nilai IS NOT NULL AND kategori_iq IS NOT NULL AND hasil_prediksi IS NOT NULL";
if (isset($_SESSION['reset_prediksi']) && $_SESSION['reset_prediksi']) {
    $query = "SELECT NULL as kategori_nilai, NULL as kategori_iq, NULL as hasil_prediksi, NULL as minat, NULL as status_klasifikasi FROM prediksi_jurusan LIMIT 0";
    unset($_SESSION['reset_prediksi']);
}
$result = mysqli_query($conn, $query);
$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}
// Node 1 (root)
$total = count($data);
$ips = count(array_filter($data, fn($d) => $d['hasil_prediksi'] == 'IPS'));
$ipa = count(array_filter($data, fn($d) => $d['hasil_prediksi'] == 'IPA'));
$entropy_total = entropy($ips, $ipa, $formula_entropy_total);
// Node: Jumlah Nilai Mapel
$kat_nilai = ['Tinggi', 'Sedang', 'Rendah'];
$nilai_mapel = [];
foreach ($kat_nilai as $kat) {
    $subset = array_filter($data, fn($d) => $d['kategori_nilai'] == $kat);
    $jml = count($subset);
    $ips_sub = count(array_filter($subset, fn($d) => $d['hasil_prediksi'] == 'IPS'));
    $ipa_sub = count(array_filter($subset, fn($d) => $d['hasil_prediksi'] == 'IPA'));
    $nilai_mapel[$kat] = [
        'total' => $jml,
        'ips' => $ips_sub,
        'ipa' => $ipa_sub,
    ];
    $nilai_mapel[$kat]['entropy'] = entropy($ips_sub, $ipa_sub, $nilai_mapel[$kat]['formula']);
}
$gain_nilai = gain($entropy_total, $nilai_mapel, $formula_gain_nilai);
// Node: Test IQ
$kat_iq = ['Tinggi', 'Sedang', 'Rendah'];
$test_iq = [];
foreach ($kat_iq as $kat) {
    $subset = array_filter($data, fn($d) => $d['kategori_iq'] == $kat);
    $jml = count($subset);
    $ips_sub = count(array_filter($subset, fn($d) => $d['hasil_prediksi'] == 'IPS'));
    $ipa_sub = count(array_filter($subset, fn($d) => $d['hasil_prediksi'] == 'IPA'));
    $test_iq[$kat] = [
        'total' => $jml,
        'ips' => $ips_sub,
        'ipa' => $ipa_sub,
    ];
    $test_iq[$kat]['entropy'] = entropy($ips_sub, $ipa_sub, $test_iq[$kat]['formula']);
}
$gain_iq = gain($entropy_total, $test_iq, $formula_gain_iq);
// Tentukan root node berdasarkan gain tertinggi
$root_node = $gain_nilai > $gain_iq ? 'Nilai Mapel' : 'Test IQ';
// --- Node 1.1, 1.2, 1.3 ---
$node_cabang = [];
foreach ($kat_nilai as $kat) {
    $subset = array_filter($data, fn($d) => $d['kategori_nilai'] == $kat);
    $total_sub = count($subset);
    $ips_sub = count(array_filter($subset, fn($d) => $d['hasil_prediksi'] == 'IPS'));
    $ipa_sub = count(array_filter($subset, fn($d) => $d['hasil_prediksi'] == 'IPA'));
    $entropy_sub = entropy($ips_sub, $ipa_sub, $formula_entropy_sub);
    // Perhitungan Test IQ pada subset ini
    $test_iq_sub = [];
    foreach ($kat_iq as $kat_iq_val) {
        $sub2 = array_filter($subset, fn($d) => $d['kategori_iq'] == $kat_iq_val);
        $jml2 = count($sub2);
        $ips2 = count(array_filter($sub2, fn($d) => $d['hasil_prediksi'] == 'IPS'));
        $ipa2 = count(array_filter($sub2, fn($d) => $d['hasil_prediksi'] == 'IPA'));
        $test_iq_sub[$kat_iq_val] = [
            'total' => $jml2,
            'ips' => $ips2,
            'ipa' => $ipa2,
        ];
        $test_iq_sub[$kat_iq_val]['entropy'] = entropy($ips2, $ipa2, $test_iq_sub[$kat_iq_val]['formula']);
    }
    $gain_sub = gain($entropy_sub, $test_iq_sub, $formula_gain_sub);
    $node_cabang[$kat] = [
        'total' => $total_sub,
        'ips' => $ips_sub,
        'ipa' => $ipa_sub,
        'entropy' => $entropy_sub,
        'formula_entropy' => $formula_entropy_sub,
        'test_iq' => $test_iq_sub,
        'gain' => $gain_sub,
        'formula_gain' => $formula_gain_sub
    ];
}
// Ambil parameter search dan halaman
$search = isset($_GET['search_prediksi']) ? trim($_GET['search_prediksi']) : '';
$page = isset($_GET['page_prediksi']) ? max(1, intval($_GET['page_prediksi'])) : 1;
$per_page = 25;
$offset = ($page - 1) * $per_page;
$where = '';
if ($search !== '') {
    $search_sql = mysqli_real_escape_string($conn, $search);
    $where = "AND (s.nama_lengkap LIKE '%$search_sql%' OR pj.hasil_prediksi LIKE '%$search_sql%' OR pj.minat LIKE '%$search_sql%')";
}
// Hitung total data
$q_count = mysqli_query($conn, "SELECT COUNT(*) as total FROM prediksi_jurusan pj JOIN siswa s ON pj.siswa_id = s.id WHERE 1=1 $where");
$total_data = mysqli_fetch_assoc($q_count)['total'];
$total_pages = ceil($total_data / $per_page);
// Query data dengan limit
$q_siswa = mysqli_query($conn, "SELECT s.nama_lengkap, pj.nilai_mapel_ipa, pj.nilai_mapel_ips, pj.kategori_nilai, pj.nilai_iq, pj.kategori_iq, pj.hasil_prediksi, pj.minat, pj.status_klasifikasi, s.id as siswa_id
                                FROM prediksi_jurusan pj 
                                JOIN siswa s ON pj.siswa_id = s.id 
                                WHERE 1=1 $where 
                                ORDER BY s.nama_lengkap 
                                LIMIT $per_page OFFSET $offset");
// QUERY UTAMA UNTUK MENGAMBIL DATA SISWA
$query = "SELECT s.id, 
                 s.nis, 
                 s.nama_lengkap, 
                 k.nama_kelas,
                 AVG(CASE WHEN mp.kategori = 'IPA' THEN n.nilai ELSE NULL END) AS nilai_ipa,
                 AVG(CASE WHEN mp.kategori = 'IPS' THEN n.nilai ELSE NULL END) AS nilai_ips,
                 ti.skor AS nilai_iq,
                 ti.kategori AS kategori_iq,
                 ms.minat,
                 s.status_klasifikasi
          FROM siswa s
          JOIN kelas k ON s.kelas_id = k.id
          LEFT JOIN nilai n ON s.id = n.siswa_id
          LEFT JOIN mata_pelajaran mp ON n.mapel_id = mp.id
          LEFT JOIN test_iq ti ON s.id = ti.siswa_id
          LEFT JOIN minat_siswa ms ON s.id = ms.siswa_id
          WHERE s.status_klasifikasi = 'Belum Diklasifikasi'
          GROUP BY s.id, s.nis, s.nama_lengkap, k.nama_kelas, ti.skor, ti.kategori, ms.minat, s.status_klasifikasi
          ORDER BY s.nama_lengkap";
$result = mysqli_query($conn, $query);
$ada_data = false;
if (mysqli_num_rows($result) > 0) {
    while($row = mysqli_fetch_assoc($result)) {
        if (($row['status_klasifikasi'] ?? '') !== 'Belum Diklasifikasi') continue;
        $ada_data = true;
        // ... existing code untuk menampilkan baris ...
    }
}
if (!$ada_data) {
    echo '<div class="alert alert-info text-center">Tidak ada data yang harus diklasifikasi</div>';
    echo '<script>document.getElementById("btnBatchPrediksi").disabled = true;</script>';
}
?>
<!-- ==========================================
     CSS UNTUK RESPONSIVITAS
     ========================================== -->
     <style>
    /* Styling untuk content wrapper */
    .content-wrapper {
        transition: all 0.3s ease;
        width: calc(100% - 250px);
        margin-left: 250px;
    }
    
    /* Saat sidebar dalam kondisi collapsed */
    .content-wrapper.expanded {
        width: 100%;
        margin-left: 0;
    }
    
    /* Responsive untuk perangkat mobile */
    @media (max-width: 768px) {
        .content-wrapper {
            width: 100%;
            margin-left: 0;
        }
    }
    
    /* Styling untuk main content */
    main {
        width: 100%;
        transition: all 0.3s ease;
    }
    
    /* Styling untuk badge status */
    .badge {
        font-size: 0.8em;
    }
    
    /* Styling untuk tabel responsive */
    .table-responsive {
        border-radius: 0.5rem;
        overflow: hidden;
    }
</style>
    <div class="container-fluid">
        <!-- Node 1 (Root) -->
        <div class="row mb-4">
            <div class="col-12">
                <h4 class="mb-3 mt-4 text-center"><em>Perhitungan Node 1 (Root)</em></h4>
                <div class="table-responsive">
                    <table class="table table-bordered" style="background:#fff;">
                        <thead class="text-center align-middle">
                            <tr>
                                <th rowspan="2">Node</th>
                                <th rowspan="2">Jmlh kasus (S)</th>
                                <th rowspan="2">IPS (S1)</th>
                                <th rowspan="2">IPA (S2)</th>
                                <th rowspan="2">Entropy</th>
                                <th rowspan="2">Gain</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-center">1</td>
                                <td class="text-center"><?= $total ?></td>
                                <td class="text-center"><?= $ips ?></td>
                                <td class="text-center"><?= $ipa ?></td>
                                <td class="text-center"><?= $entropy_total ?></td>
                                <td class="text-center"></td>
                            </tr>
                            <tr>
                                <td colspan="6"><b>Jumlah Nilai Mata pelajaran</b> <span class="float-end">Gain: <?= $gain_nilai ?></span></td>
                            </tr>
                            <?php foreach($kat_nilai as $kat): ?>
                            <tr>
                                <td class="text-center">1.<?= array_search($kat, $kat_nilai)+1 ?> (<?= $kat ?>)</td>
                                <td class="text-center"><?= $nilai_mapel[$kat]['total'] ?></td>
                                <td class="text-center"><?= $nilai_mapel[$kat]['ips'] ?></td>
                                <td class="text-center"><?= $nilai_mapel[$kat]['ipa'] ?></td>
                                <td class="text-center"><?= $nilai_mapel[$kat]['entropy'] ?></td>
                                <td class="text-center"></td>
                            </tr>
                            <?php endforeach; ?>
                            <tr>
                                <td colspan="6"><b>Test IQ</b> <span class="float-end">Gain: <?= $gain_iq ?></span></td>
                            </tr>
                            <?php foreach($kat_iq as $kat): ?>
                            <tr>
                                <td class="text-center">Test IQ (<?= $kat ?>)</td>
                                <td class="text-center"><?= $test_iq[$kat]['total'] ?></td>
                                <td class="text-center"><?= $test_iq[$kat]['ips'] ?></td>
                                <td class="text-center"><?= $test_iq[$kat]['ipa'] ?></td>
                                <td class="text-center"><?= $test_iq[$kat]['entropy'] ?></td>
                                <td class="text-center"></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    <b>Menghitung Entropy pada tabel di atas dengan persamaan 2 sebagai berikut:</b><br>
                    Entropy(S) = <?= $formula_entropy_total ?><br>
                    <?php foreach($kat_nilai as $kat): ?>
                        Entropy(Jumlah Nilai Mapel (<?= $kat ?>)) = <?= isset($nilai_mapel[$kat]['formula']) ? $nilai_mapel[$kat]['formula'] : '0' ?><br>
                    <?php endforeach; ?>
                    <?php foreach($kat_iq as $kat): ?>
                        Entropy(Test IQ (<?= $kat ?>)) = <?= isset($test_iq[$kat]['formula']) ? $test_iq[$kat]['formula'] : '0' ?><br>
                    <?php endforeach; ?>
                    <br>
                    <b>Menghitung Gain pada tabel di atas dengan persamaan 1 sebagai berikut:</b><br>
                    Gain(S, Jumlah Nilai Mapel) = <?= $formula_gain_nilai ?><br>
                    Gain(S, Test IQ) = <?= $formula_gain_iq ?><br>
                    <br>
                    <b>Dari hasil di atas dapat diketahui bahwa atribut Nilai Mata Pelajaran memiliki nilai Gain yang lebih baik untuk jadi node akar.</b>
                </div>
                <!-- Node 1 Pohon Keputusan Sementara -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="mermaid">
                            graph TD
                                A((Jumlah Nilai Mata Pelajaran))
                                A -- "Tinggi" --> B(("1.1?"))
                                A -- "Sedang" --> C(("1.2?"))
                                A -- "Rendah" --> D(("1.3?"))
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Node 1.1, 1.2, 1.3 -->
        <?php foreach($kat_nilai as $idx => $kat): $node = $node_cabang[$kat]; ?>
        <div class="row mb-4">
            <div class="col-12">
                <h4 class="mb-3 mt-4 text-center"><em>Perhitungan Node 1.<?= $idx+1 ?> (<?= $kat ?>)</em></h4>
                <div class="table-responsive">
                    <table class="table table-bordered" style="background:#fff;">
                        <thead class="text-center align-middle">
                            <tr>
                                <th rowspan="2">Node</th>
                                <th rowspan="2">Jmlh kasus (S)</th>
                                <th rowspan="2">IPS (S1)</th>
                                <th rowspan="2">IPA (S2)</th>
                                <th rowspan="2">Entropy</th>
                                <th rowspan="2">Gain</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-center">1.<?= $idx+1 ?></td>
                                <td class="text-center"><?= $node['total'] ?></td>
                                <td class="text-center"><?= $node['ips'] ?></td>
                                <td class="text-center"><?= $node['ipa'] ?></td>
                                <td class="text-center"><?= $node['entropy'] ?></td>
                                <td class="text-center"></td>
                            </tr>
                            <tr>
                                <td colspan="6"><b>Test IQ</b> <span class="float-end">Gain: <?= $node['gain'] ?></span></td>
                            </tr>
                            <?php foreach($kat_iq as $kat_iq_val): ?>
                            <tr>
                                <td class="text-center">Test IQ (<?= $kat_iq_val ?>)</td>
                                <td class="text-center"><?= $node['test_iq'][$kat_iq_val]['total'] ?></td>
                                <td class="text-center"><?= $node['test_iq'][$kat_iq_val]['ips'] ?></td>
                                <td class="text-center"><?= $node['test_iq'][$kat_iq_val]['ipa'] ?></td>
                                <td class="text-center"><?= $node['test_iq'][$kat_iq_val]['entropy'] ?></td>
                                <td class="text-center"></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    <b>Menghitung Entropy pada tabel di atas dengan persamaan 2 sebagai berikut:</b><br>
                    Entropy(Jumlah Nilai Mapel (<?= $kat ?>)) = <?= isset($node['formula_entropy']) ? $node['formula_entropy'] : '0' ?><br>
                    <?php foreach($kat_iq as $kat_iq_val): ?>
                        Entropy(Test IQ (<?= $kat ?>, <?= $kat_iq_val ?>)) = <?= isset($node['test_iq'][$kat_iq_val]['formula']) ? $node['test_iq'][$kat_iq_val]['formula'] : '0' ?><br>
                    <?php endforeach; ?>
                    <br>
                    <b>Menghitung Gain pada tabel di atas dengan persamaan 1 sebagai berikut:</b><br>
                    Gain(S, Test IQ) = <?= isset($node['formula_gain']) ? $node['formula_gain'] : '0' ?><br>
                    <br>
                    <!-- <b>Dari hasil di atas dapat diketahui bahwa atribut dengan Gain tertinggi adalah <u>Test IQ</u> karena memiliki nilai Gain yang lebih baik untuk jadi node akar.</b> -->
                </div>
                <!-- Pohon Keputusan Sementara/Akhir sesuai node -->
                <?php if($idx == 0): ?>
                <!-- Node 1.1 Pohon Keputusan Sementara -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="mermaid">
                            graph TD
                                A((Jumlah Nilai Mata Pelajaran))
                                A -- "Tinggi" --> B(("1.1 Test IQ"))
                                A -- "Sedang" --> C(("1.2?"))
                                A -- "Rendah" --> D(("1.3?"))
                                B -- "Tinggi" --> B1["IPS"]
                                B -- "Sedang" --> B2["IPS"]
                                B -- "Rendah" --> B3["IPA"]
                        </div>
                    </div>
                </div>
                <?php elseif($idx == 1): ?>
                <!-- Node 1.2 Pohon Keputusan Sementara -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="mermaid">
                            graph TD
                                A((Jumlah Nilai Mata Pelajaran))
                                A -- "Tinggi" --> B(("1.1 Test IQ"))
                                A -- "Sedang" --> C(("1.2 Test IQ"))
                                A -- "Rendah" --> D(("1.3?"))
                                B -- "Tinggi" --> B1["IPS"]
                                B -- "Sedang" --> B2["IPS"]
                                B -- "Rendah" --> B3["IPA"]
                                C -- "Tinggi" --> C1["IPA"]
                                C -- "Sedang" --> C2["IPS"]
                                C -- "Rendah" --> C3["IPS"]
                        </div>
                    </div>
                </div>
                <?php elseif($idx == 2): ?>
                <!-- Node 1.3 Pohon Keputusan Akhir -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="mermaid">
                            graph TD
                                A((Jumlah Nilai Mata Pelajaran))
                                A -- "Tinggi" --> B(("1.1 Test IQ"))
                                A -- "Sedang" --> C(("1.2 Test IQ"))
                                A -- "Rendah" --> D(("1.3 Test IQ"))
                                B -- "Tinggi" --> B1["IPS"]
                                B -- "Sedang" --> B2["IPS"]
                                B -- "Rendah" --> B3["IPA"]
                                C -- "Tinggi" --> C1["IPA"]
                                C -- "Sedang" --> C2["IPS"]
                                C -- "Rendah" --> C3["IPS"]
                                D -- "Tinggi" --> D1["IPA"]
                                D -- "Sedang" --> D2["IPA"]
                                D -- "Rendah" --> D3["IPS"]
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <!-- Tabel hasil prediksi siswa dengan search dan pagination -->
        <div class="row mb-4">
            <div class="col-12">
                <h4 class="mb-3 text-center"><em>Daftar Siswa dan Hasil Prediksi Jurusan</em></h4>
                <form method="get" class="mb-2 d-flex justify-content-end" style="gap:8px;">
                    <input type="text" name="search_prediksi" value="<?= htmlspecialchars($search) ?>" class="form-control" style="max-width:220px;" placeholder="Cari nama/minat/hasil...">
                    <button type="submit" class="btn btn-primary">Cari</button>
                    <button type="button" id="btnSimpanKlasifikasi" class="btn btn-success">
                        <i class="fas fa-save"></i> Simpan Klasifikasi
                    </button>
                </form>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-light text-center">
                            <tr>
                                <th>No</th>
                                <th>Nama Siswa</th>
                                <th>Nilai Mapel IPA</th>
                                <th>Nilai Mapel IPS</th>
                                <th>Kategori Nilai</th>
                                <th>Nilai IQ</th>
                                <th>Kategori IQ</th>
                                <th>Minat</th>
                                <th>Status Klasifikasi</th>
                                <th>Hasil Prediksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $no = $offset + 1;
                        if (mysqli_num_rows($q_siswa) > 0) {
                            while ($row = mysqli_fetch_assoc($q_siswa)) {
                                echo '<tr>';
                                echo '<td class="text-center">' . $no++ . '</td>';
                                echo '<td>' . htmlspecialchars($row['nama_lengkap']) . '</td>';
                                echo '<td class="text-center">' . number_format($row['nilai_mapel_ipa'], 2) . '</td>';
                                echo '<td class="text-center">' . number_format($row['nilai_mapel_ips'], 2) . '</td>';
                                echo '<td class="text-center">' . htmlspecialchars($row['kategori_nilai']) . '</td>';
                                echo '<td class="text-center">' . $row['nilai_iq'] . '</td>';
                                echo '<td class="text-center">' . htmlspecialchars($row['kategori_iq']) . '</td>';
                                echo '<td class="text-center">' . htmlspecialchars($row['minat'] ?: '-') . '</td>';
                                echo '<td class="text-center">' . htmlspecialchars($row['status_klasifikasi'] ?: '-') . '</td>';
                                echo '<td class="text-center"><span class="badge bg-' . ($row['hasil_prediksi'] == 'IPA' ? 'info' : 'warning') . '">' . htmlspecialchars($row['hasil_prediksi']) . '</span></td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="9" class="text-center">Tidak ada data</td></tr>';
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item<?= $i == $page ? ' active' : '' ?>">
                                <a class="page-link" href="?search_prediksi=<?= urlencode($search) ?>&page_prediksi=<?= $i ?>#daftar-siswa-prediksi"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
        </main>
    </div>
</div>
<!-- Mermaid JS -->
<script src="https://cdn.jsdelivr.net/npm/mermaid/dist/mermaid.min.js"></script>
<script>
mermaid.initialize({
    startOnLoad: true,
    theme: 'default',
    securityLevel: 'loose',
});
</script>
<?php
// Setelah proses batch prediksi (misal setelah proses prediksi_jurusan selesai), update status_klasifikasi siswa
if (isset($conn)) {
    $update_siswa = mysqli_query($conn, "UPDATE siswa SET status_klasifikasi = 'Sudah Diklasifikasi' WHERE id IN (SELECT siswa_id FROM prediksi_jurusan WHERE status_klasifikasi = 'Sudah Diklasifikasi')");
}
require_once '../include/footer.php'; ob_end_flush(); ?>
<script>
document.getElementById('btnSimpanKlasifikasi').onclick = function() {
    if (!confirm('Yakin ingin menyimpan hasil klasifikasi ini?')) return;
    var btn = document.getElementById('btnSimpanKlasifikasi');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Menyimpan...';
    fetch('simpan_klasifikasi.php')
        .then(response => response.text())
        .then(html => {
            alert(html);
            // Reset data prediksi setelah simpan
            fetch('reset_prediksi.php')
                .then(response => response.text())
                .then(html => {
                    alert('Reset data prediksi berhasil!');
                    window.location.reload();
                })
                .catch(err => {
                    alert('Terjadi kesalahan saat reset data!');
                });
        })
        .catch(err => {
            alert('Terjadi kesalahan saat menyimpan hasil klasifikasi!');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save"></i> Simpan Klasifikasi';
        });
};
</script>
 