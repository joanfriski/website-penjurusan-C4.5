<?php
session_start();
require_once '../../database/config.php';

if (!isset($_GET['siswa_id'])) {
    echo '<div class="alert alert-danger">ID siswa tidak ditemukan</div>';
    exit;
}

$siswaId = $_GET['siswa_id'];

// Ambil data siswa
$stmt = $conn->prepare("SELECT s.*, k.nama_kelas FROM siswa s LEFT JOIN kelas k ON s.kelas_id = k.id WHERE s.id = ?");
$stmt->bind_param("i", $siswaId);
$stmt->execute();
$result = $stmt->get_result();
$siswa = $result->fetch_assoc();

if (!$siswa) {
    echo '<div class="alert alert-danger">Siswa tidak ditemukan</div>';
    exit;
}

// Cek nilai
$stmt = $conn->prepare("
    SELECT mp.kategori, mp.nama as mapel_nama, n.nilai
    FROM nilai n
    JOIN mata_pelajaran mp ON n.mapel_id = mp.id
    WHERE n.siswa_id = ?
    ORDER BY mp.kategori, mp.nama
");
$stmt->bind_param("i", $siswaId);
$stmt->execute();
$result = $stmt->get_result();
$nilai = $result->fetch_all(MYSQLI_ASSOC);

// Cek test IQ
$stmt = $conn->prepare("SELECT * FROM test_iq WHERE siswa_id = ? ORDER BY tanggal_test DESC");
$stmt->bind_param("i", $siswaId);
$stmt->execute();
$result = $stmt->get_result();
$testIQ = $result->fetch_all(MYSQLI_ASSOC);

// Cek minat
$stmt = $conn->prepare("SELECT * FROM minat_siswa WHERE siswa_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $siswaId);
$stmt->execute();
$result = $stmt->get_result();
$minat = $result->fetch_all(MYSQLI_ASSOC);

// Kategorisasi nilai
$nilai_by_category = ['IPA' => [], 'IPS' => []];
foreach ($nilai as $n) {
    $nilai_by_category[$n['kategori']][] = $n;
}

$avg_ipa = 0;
$avg_ips = 0;
if (!empty($nilai_by_category['IPA'])) {
    $avg_ipa = array_sum(array_column($nilai_by_category['IPA'], 'nilai')) / count($nilai_by_category['IPA']);
}
if (!empty($nilai_by_category['IPS'])) {
    $avg_ips = array_sum(array_column($nilai_by_category['IPS'], 'nilai')) / count($nilai_by_category['IPS']);
}

$kategori_nilai = 'Tidak Dapat Ditentukan';
if ($avg_ipa > 0 || $avg_ips > 0) {
    $rata_total = ($avg_ipa + $avg_ips) / 2;
    if ($rata_total >= 85) {
        $kategori_nilai = 'Tinggi';
    } elseif ($rata_total >= 70) {
        $kategori_nilai = 'Sedang';
    } else {
        $kategori_nilai = 'Rendah';
    }
}
?>

<div class="container-fluid">
    <h6><?= htmlspecialchars($siswa['nama_lengkap']) ?> (NIS: <?= htmlspecialchars($siswa['nis']) ?>)</h6>
    <p>Kelas: <?= htmlspecialchars($siswa['nama_kelas'] ?? '-') ?></p>
    
    <div class="row">
        <div class="col-md-6">
            <h6>Nilai Mata Pelajaran</h6>
            <?php if (empty($nilai)): ?>
                <div class="alert alert-warning">Tidak ada data nilai</div>
            <?php else: ?>
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Kategori</th>
                            <th>Mata Pelajaran</th>
                            <th>Nilai</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($nilai as $n): ?>
                            <tr>
                                <td><?= $n['kategori'] ?></td>
                                <td><?= $n['mapel_nama'] ?></td>
                                <td><?= $n['nilai'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td colspan="2">Rata-rata IPA</td>
                            <td><?= number_format($avg_ipa, 2) ?></td>
                        </tr>
                        <tr class="fw-bold">
                            <td colspan="2">Rata-rata IPS</td>
                            <td><?= number_format($avg_ips, 2) ?></td>
                        </tr>
                        <tr class="fw-bold">
                            <td colspan="2">Kategori Nilai</td>
                            <td>
                                <span class="badge bg-<?= $kategori_nilai == 'Tinggi' ? 'success' : ($kategori_nilai == 'Sedang' ? 'warning' : 'danger') ?>">
                                    <?= $kategori_nilai ?>
                                </span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
                
                <?php if (count($nilai_by_category['IPA']) == 0 || count($nilai_by_category['IPS']) == 0): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Masalah:</strong> Data nilai tidak lengkap. 
                        <?php if (count($nilai_by_category['IPA']) == 0): ?>
                            Tidak ada nilai untuk mata pelajaran IPA.
                        <?php endif; ?>
                        <?php if (count($nilai_by_category['IPS']) == 0): ?>
                            Tidak ada nilai untuk mata pelajaran IPS.
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <div class="col-md-6">
            <h6>Test IQ</h6>
            <?php if (empty($testIQ)): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Masalah:</strong> Tidak ada data test IQ
                </div>
            <?php else: ?>
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Skor</th>
                            <th>Kategori</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($testIQ as $iq): ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($iq['tanggal_test'])) ?></td>
                                <td><?= $iq['skor'] ?></td>
                                <td>
                                    <?php if ($iq['kategori']): ?>
                                        <span class="badge bg-<?= $iq['kategori'] == 'Tinggi' ? 'success' : ($iq['kategori'] == 'Sedang' ? 'warning' : 'danger') ?>">
                                            <?= $iq['kategori'] ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Tidak Ada Kategori</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if (!$testIQ[0]['kategori']): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Masalah:</strong> Test IQ tidak memiliki kategori
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <h6 class="mt-4">Minat Siswa</h6>
            <?php if (empty($minat)): ?>
                <div class="alert alert-warning">
                    <i class="bi bi-info-circle me-2"></i>
                    Tidak ada data minat (Opsional)
                </div>
            <?php else: ?>
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Minat</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($minat as $m): ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($m['created_at'])) ?></td>
                                <td>
                                    <span class="badge bg-<?= $m['minat'] == 'IPA' ? 'primary' : 'info' ?>">
                                        <?= $m['minat'] ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="alert alert-info mt-3">
        <h6>Catatan:</h6>
        <ul>
            <li>Untuk dapat diprediksi, siswa harus memiliki minimal nilai untuk mata pelajaran IPA dan IPS</li>
            <li>Test IQ wajib ada dan harus memiliki kategori</li>
            <li>Data minat bersifat opsional</li>
        </ul>
    </div>
</div>