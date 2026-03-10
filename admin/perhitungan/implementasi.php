<?php
require_once '../../database/config.php';
require_once '../include/header.php';

// Helper functions
function entropy($jumlah_ipa, $jumlah_ips) {
    $total = $jumlah_ipa + $jumlah_ips;
    if ($total == 0) return 0;
    $p_ipa = $jumlah_ipa / $total;
    $p_ips = $jumlah_ips / $total;
    $entropy = 0;
    if ($p_ipa > 0) $entropy -= $p_ipa * log($p_ipa, 2);
    if ($p_ips > 0) $entropy -= $p_ips * log($p_ips, 2);
    return round($entropy, 3);
}

function gain($entropy_s, $subsets) {
    $total = array_sum(array_column($subsets, 'total'));
    $gain = $entropy_s;
    foreach ($subsets as $subset) {
        $gain -= ($subset['total'] / $total) * $subset['entropy'];
    }
    return round($gain, 3);
}

// Kategori mapping
function kategori_nilai($nilai) {
    if ($nilai >= 80) return 'Tinggi';
    if ($nilai >= 70) return 'Sedang';
    return 'Rendah';
}

function kategori_iq($skor) {
    if ($skor >= 110) return 'Tinggi';
    if ($skor >= 90) return 'Sedang';
    return 'Rendah';
}

// Ambil semua siswa dengan data lengkap
$query = "SELECT s.id, s.nis, s.nama_lengkap, k.nama_kelas,
          AVG(CASE WHEN mp.kategori = 'IPA' THEN n.nilai ELSE NULL END) AS nilai_ipa,
          AVG(CASE WHEN mp.kategori = 'IPS' THEN n.nilai ELSE NULL END) AS nilai_ips,
          ti.skor AS nilai_iq,
          ms.minat,
          pj.jurusan
          FROM siswa s
          JOIN kelas k ON s.kelas_id = k.id
          LEFT JOIN nilai n ON s.id = n.siswa_id
          LEFT JOIN mata_pelajaran mp ON n.mapel_id = mp.id
          LEFT JOIN test_iq ti ON s.id = ti.siswa_id AND ti.id = (
              SELECT MAX(id) FROM test_iq WHERE siswa_id = s.id
          )
          LEFT JOIN minat_siswa ms ON s.id = ms.siswa_id
          LEFT JOIN prediksi_jurusan pj ON s.id = pj.siswa_id
          WHERE s.status = 'aktif'
          GROUP BY s.id, s.nis, s.nama_lengkap, k.nama_kelas, ti.skor, ms.minat, pj.jurusan
          HAVING nilai_ipa IS NOT NULL 
          AND nilai_ips IS NOT NULL 
          AND nilai_iq IS NOT NULL 
          AND minat IS NOT NULL";
$result = mysqli_query($conn, $query);

$siswa_data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $row['kategori_nilai'] = kategori_nilai(($row['nilai_ipa'] + $row['nilai_ips']) / 2);
    $row['kategori_iq'] = kategori_iq($row['nilai_iq']);
    $siswa_data[] = $row;
}

// Node 1: Total
$total = count($siswa_data);
$ips = count(array_filter($siswa_data, fn($s) => $s['jurusan'] == 'IPS'));
$ipa = count(array_filter($siswa_data, fn($s) => $s['jurusan'] == 'IPA'));
$entropy_total = entropy($ipa, $ips);

// Node 1: Jumlah Nilai Mapel
$kategori_nilai_mapel = ['Tinggi', 'Sedang', 'Rendah'];
$nilai_mapel_subsets = [];
foreach ($kategori_nilai_mapel as $kat) {
    $subset = array_filter($siswa_data, fn($s) => $s['kategori_nilai'] == $kat);
    $jumlah = count($subset);
    $ips_sub = count(array_filter($subset, fn($s) => $s['jurusan'] == 'IPS'));
    $ipa_sub = count(array_filter($subset, fn($s) => $s['jurusan'] == 'IPA'));
    $nilai_mapel_subsets[$kat] = [
        'total' => $jumlah,
        'ips' => $ips_sub,
        'ipa' => $ipa_sub,
        'entropy' => entropy($ipa_sub, $ips_sub)
    ];
}
$gain_nilai_mapel = gain($entropy_total, $nilai_mapel_subsets);

// Node 1: Test IQ
$kategori_iq = ['Tinggi', 'Sedang', 'Rendah'];
$iq_subsets = [];
foreach ($kategori_iq as $kat) {
    $subset = array_filter($siswa_data, fn($s) => $s['kategori_iq'] == $kat);
    $jumlah = count($subset);
    $ips_sub = count(array_filter($subset, fn($s) => $s['jurusan'] == 'IPS'));
    $ipa_sub = count(array_filter($subset, fn($s) => $s['jurusan'] == 'IPA'));
    $iq_subsets[$kat] = [
        'total' => $jumlah,
        'ips' => $ips_sub,
        'ipa' => $ipa_sub,
        'entropy' => entropy($ipa_sub, $ips_sub)
    ];
}
$gain_iq = gain($entropy_total, $iq_subsets);

?>
<div class="content-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="card-title">Tabel Data Siswa</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>NIS</th>
                                        <th>Nama</th>
                                        <th>Kelas</th>
                                        <th>Nilai IPA</th>
                                        <th>Nilai IPS</th>
                                        <th>Kategori Nilai</th>
                                        <th>IQ</th>
                                        <th>Kategori IQ</th>
                                        <th>Minat</th>
                                        <th>Jurusan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no=1; foreach($siswa_data as $s): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= htmlspecialchars($s['nis']) ?></td>
                                        <td><?= htmlspecialchars($s['nama_lengkap']) ?></td>
                                        <td><?= htmlspecialchars($s['nama_kelas']) ?></td>
                                        <td><?= number_format($s['nilai_ipa'],2) ?></td>
                                        <td><?= number_format($s['nilai_ips'],2) ?></td>
                                        <td><?= $s['kategori_nilai'] ?></td>
                                        <td><?= $s['nilai_iq'] ?></td>
                                        <td><?= $s['kategori_iq'] ?></td>
                                        <td><?= $s['minat'] ?></td>
                                        <td><?= $s['jurusan'] ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Node 1 Calculation Table -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h4 class="card-title">Perhitungan Node 1</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Node</th>
                                        <th>Jumlah Kasus (S)</th>
                                        <th>IPS (S1)</th>
                                        <th>IPA (S2)</th>
                                        <th>Entropy</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Total</td>
                                        <td><?= $total ?></td>
                                        <td><?= $ips ?></td>
                                        <td><?= $ipa ?></td>
                                        <td><?= $entropy_total ?></td>
                                    </tr>
                                    <?php foreach($nilai_mapel_subsets as $kat => $v): ?>
                                    <tr>
                                        <td>Jumlah Nilai Mapel (<?= $kat ?>)</td>
                                        <td><?= $v['total'] ?></td>
                                        <td><?= $v['ips'] ?></td>
                                        <td><?= $v['ipa'] ?></td>
                                        <td><?= $v['entropy'] ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php foreach($iq_subsets as $kat => $v): ?>
                                    <tr>
                                        <td>Test IQ (<?= $kat ?>)</td>
                                        <td><?= $v['total'] ?></td>
                                        <td><?= $v['ips'] ?></td>
                                        <td><?= $v['ipa'] ?></td>
                                        <td><?= $v['entropy'] ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            <strong>Gain Jumlah Nilai Mapel:</strong> <?= $gain_nilai_mapel ?><br>
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

                <div class="text-center mb-4">
                    <a href="pohon_keputusan.php" class="btn btn-primary">
                        Lanjut ke Pohon Keputusan
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../include/footer.php'; ?> 