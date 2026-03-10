<?php
ob_start();
session_start();
require_once '../../database/config.php';
require_once '../include/header.php';
require_once 'classes/DecisionTree.php';

// Ambil model yang aktif
$stmt = $conn->prepare("SELECT * FROM model_metadata WHERE active = 1 ORDER BY created_at DESC LIMIT 1");
$stmt->execute();
$result = $stmt->get_result();
$activeModel = $result->fetch_assoc();

// Handle prediksi massal
if (isset($_POST['predict_all'])) {
    $modelVersion = $_POST['model_version'] ?? ($activeModel['model_version'] ?? 'default');
    
    try {
        // Ambil semua siswa yang belum diprediksi
        $stmt = $conn->prepare("
            SELECT s.* 
            FROM siswa s
            WHERE s.status = 'aktif' 
            AND s.id NOT IN (SELECT siswa_id FROM prediksi_jurusan)
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        $siswaList = $result->fetch_all(MYSQLI_ASSOC);
        
        $success_count = 0;
        $error_count = 0;
        $failed_predictions = [];
        
        foreach ($siswaList as $siswa) {
            try {
                $result = predictSingleStudent($conn, $siswa['id'], $modelVersion);
                if ($result['success']) {
                    $success_count++;
                } else {
                    $error_count++;
                    $failed_predictions[] = [
                        'siswa' => $siswa,
                        'error' => $result['message']
                    ];
                }
            } catch (Exception $e) {
                $error_count++;
                $failed_predictions[] = [
                    'siswa' => $siswa,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        $success = "Berhasil memprediksi $success_count siswa. $error_count siswa gagal.";
        $_SESSION['failed_predictions'] = $failed_predictions;
        
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Handle prediksi single
if (isset($_POST['predict_single'])) {
    $siswaId = $_POST['siswa_id'];
    $modelVersion = $_POST['model_version'] ?? ($activeModel['model_version'] ?? 'default');
    
    try {
        $result = predictSingleStudent($conn, $siswaId, $modelVersion);
        
        if ($result['success']) {
            $success = $result['message'];
            $hasilPrediksi = $result['prediction'];
            $siswa = $result['siswa'];
            $nilaiIPA = $result['nilaiIPA'];
            $nilaiIPS = $result['nilaiIPS'];
            $kategoriNilai = $result['kategoriNilai'];
            $testIQ = $result['testIQ'];
            $minat = $result['minat'];
        } else {
            $error = "Error: " . $result['message'];
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Function untuk prediksi single siswa
function predictSingleStudent($conn, $siswaId, $modelVersion) {
    // Ambil data siswa
    $stmt = $conn->prepare("SELECT * FROM siswa WHERE id = ?");
    $stmt->bind_param("i", $siswaId);
    $stmt->execute();
    $result = $stmt->get_result();
    $siswa = $result->fetch_assoc();
    
    if (!$siswa) {
        return ['success' => false, 'message' => 'Siswa tidak ditemukan'];
    }
    
    // Ambil nilai rata-rata IPA dan IPS
    $stmt = $conn->prepare("
        SELECT mp.kategori, AVG(n.nilai) as rata_nilai, COUNT(*) as count_mapel
        FROM nilai n
        JOIN mata_pelajaran mp ON n.mapel_id = mp.id
        WHERE n.siswa_id = ?
        GROUP BY mp.kategori
    ");
    $stmt->bind_param("i", $siswaId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $nilai = ['IPA' => 0, 'IPS' => 0];
    $count_mapel = ['IPA' => 0, 'IPS' => 0];
    
    while ($row = $result->fetch_assoc()) {
        $nilai[$row['kategori']] = $row['rata_nilai'] ?? 0;
        $count_mapel[$row['kategori']] = $row['count_mapel'];
    }
    
    // Validasi nilai
    if ($nilai['IPA'] == 0 && $nilai['IPS'] == 0) {
        return ['success' => false, 'message' => 'Data nilai tidak ditemukan untuk mata pelajaran IPA dan IPS'];
    }
    
    // Ambil hasil test IQ
    $stmt = $conn->prepare("SELECT * FROM test_iq WHERE siswa_id = ? ORDER BY tanggal_test DESC LIMIT 1");
    $stmt->bind_param("i", $siswaId);
    $stmt->execute();
    $result = $stmt->get_result();
    $testIQ = $result->fetch_assoc();
    
    // Validasi test IQ
    if (!$testIQ) {
        return ['success' => false, 'message' => 'Data test IQ tidak ditemukan'];
    }
    
    if (!$testIQ['kategori']) {
        return ['success' => false, 'message' => 'Data test IQ tidak memiliki kategori'];
    }
    
    // Ambil minat siswa
    $stmt = $conn->prepare("SELECT * FROM minat_siswa WHERE siswa_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->bind_param("i", $siswaId);
    $stmt->execute();
    $result = $stmt->get_result();
    $minat = $result->fetch_assoc();
    
    // Kategorikan nilai
    $nilaiIPA = $nilai['IPA'];
    $nilaiIPS = $nilai['IPS'];
    $rataTotal = ($nilaiIPA + $nilaiIPS) / 2;
    
    if ($rataTotal >= 85) {
        $kategoriNilai = 'Tinggi';
    } elseif ($rataTotal >= 70) {
        $kategoriNilai = 'Sedang';
    } else {
        $kategoriNilai = 'Rendah';
    }
    
    // Data untuk prediksi
    $dataPrediksi = [
        'kategori_nilai' => $kategoriNilai,
        'kategori_iq' => $testIQ['kategori'],
        'minat' => $minat['minat'] ?? null
    ];
    
    // Lakukan prediksi
    try {
        $tree = new DecisionTree($conn, $modelVersion);
        $hasilPrediksi = $tree->predict($dataPrediksi);
        
        if (!$hasilPrediksi) {
            return ['success' => false, 'message' => 'Model tidak dapat memprediksi dengan data yang tersedia'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error dalam prediksi: ' . $e->getMessage()];
    }
    
    // Simpan hasil prediksi
    $stmt = $conn->prepare("
        INSERT INTO prediksi_jurusan 
        (siswa_id, nilai_mapel_ipa, nilai_mapel_ips, kategori_nilai, nilai_iq, kategori_iq, minat, hasil_prediksi) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $skorIQ = $testIQ['skor'] ?? null;
    $kategoriIQ = $testIQ['kategori'];
    $minatSiswa = $minat['minat'] ?? null;
    
    $stmt->bind_param("iddsssss", 
        $siswaId, 
        $nilaiIPA, 
        $nilaiIPS, 
        $kategoriNilai, 
        $skorIQ, 
        $kategoriIQ, 
        $minatSiswa, 
        $hasilPrediksi
    );
    
    if ($stmt->execute()) {
        return [
            'success' => true,
            'message' => "Prediksi berhasil dilakukan. Hasil: " . $hasilPrediksi,
            'prediction' => $hasilPrediksi,
            'siswa' => $siswa,
            'nilaiIPA' => $nilaiIPA,
            'nilaiIPS' => $nilaiIPS,
            'kategoriNilai' => $kategoriNilai,
            'testIQ' => $testIQ,
            'minat' => $minat
        ];
    } else {
        return ['success' => false, 'message' => 'Gagal menyimpan hasil prediksi ke database'];
    }
}

// Ambil daftar siswa untuk dropdown
$stmt = $conn->prepare("
    SELECT s.*, k.nama_kelas 
    FROM siswa s 
    LEFT JOIN kelas k ON s.kelas_id = k.id 
    WHERE s.status = 'aktif' 
    ORDER BY s.nama_lengkap
");
$stmt->execute();
$result = $stmt->get_result();
$siswaList = $result->fetch_all(MYSQLI_ASSOC);

// Ambil daftar model
$stmt = $conn->prepare("SELECT * FROM model_metadata ORDER BY active DESC, created_at DESC");
$stmt->execute();
$result = $stmt->get_result();
$modelList = $result->fetch_all(MYSQLI_ASSOC);

// Hitung siswa yang belum diprediksi
$stmt = $conn->prepare("
    SELECT COUNT(*) as total 
    FROM siswa s
    WHERE s.status = 'aktif' 
    AND s.id NOT IN (SELECT siswa_id FROM prediksi_jurusan)
");
$stmt->execute();
$result = $stmt->get_result();
$unpredicted = $result->fetch_assoc()['total'];

// Ambil detail siswa yang belum diprediksi
$stmt = $conn->prepare("
    SELECT s.*, k.nama_kelas,
        GROUP_CONCAT(DISTINCT mp.kategori) as have_categories,
        COUNT(DISTINCT mp.kategori) as count_categories,
        (SELECT COUNT(*) FROM test_iq tq WHERE tq.siswa_id = s.id) as has_iq,
        (SELECT COUNT(*) FROM minat_siswa ms WHERE ms.siswa_id = s.id) as has_minat
    FROM siswa s
    LEFT JOIN kelas k ON s.kelas_id = k.id
    LEFT JOIN nilai n ON s.id = n.siswa_id
    LEFT JOIN mata_pelajaran mp ON n.mapel_id = mp.id
    WHERE s.status = 'aktif' 
    AND s.id NOT IN (SELECT siswa_id FROM prediksi_jurusan)
    GROUP BY s.id
    ORDER BY s.nama_lengkap
    LIMIT 100
");
$stmt->execute();
$result = $stmt->get_result();
$unpredicted_details = $result->fetch_all(MYSQLI_ASSOC);
?>

<style>
    .content-wrapper {
        transition: all 0.3s ease;
        width: calc(100% - 250px);
        margin-left: 250px;
    }
    
    @media (max-width: 768px) {
        .content-wrapper {
            width: 100%;
            margin-left: 0;
        }
    }
    
    .prediction-result {
        font-size: 1.5rem;
        font-weight: bold;
        padding: 15px;
        border-radius: 8px;
        text-align: center;
        margin: 15px 0;
    }
    
    .result-ipa {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    .result-ips {
        background: #d1ecf1;
        color: #0c5460;
        border: 1px solid #bee5eb;
    }
    
    .error-badge {
        cursor: pointer;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <?php require_once '../include/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4 content-wrapper" id="content">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Prediksi Jurusan Siswa</h1>
                <a href="index.php" class="btn btn-secondary">Kembali</a>
            </div>

            <?php if (isset($success)): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?= $success ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= $error ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Failed Predictions Modal -->
            <?php if (isset($_SESSION['failed_predictions']) && !empty($_SESSION['failed_predictions'])): ?>
            <div class="card mb-4 border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">Siswa yang Gagal Diprediksi</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Siswa</th>
                                    <th>NIS</th>
                                    <th>Kelas</th>
                                    <th>Alasan Gagal</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($_SESSION['failed_predictions'] as $index => $failed): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><?= htmlspecialchars($failed['siswa']['nama_lengkap']) ?></td>
                                        <td><?= htmlspecialchars($failed['siswa']['nis']) ?></td>
                                        <td><?= htmlspecialchars($failed['siswa']['nama_kelas'] ?? '-') ?></td>
                                        <td>
                                            <span class="badge bg-danger"><?= htmlspecialchars($failed['error']) ?></span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-info" onclick="checkStudentData(<?= $failed['siswa']['id'] ?>)">
                                                <i class="bi bi-search"></i> Cek Detail
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <button class="btn btn-secondary btn-sm mt-2" onclick="clearFailedPredictions()">Tutup Notifikasi</button>
                </div>
            </div>
            <?php endif; ?>

            <!-- Tab Navigation -->
            <ul class="nav nav-tabs mb-4" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="single-tab" data-bs-toggle="tab" data-bs-target="#single" 
                            type="button" role="tab" aria-controls="single" aria-selected="true">
                        Prediksi Satu Siswa
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="bulk-tab" data-bs-toggle="tab" data-bs-target="#bulk" 
                            type="button" role="tab" aria-controls="bulk" aria-selected="false">
                        Prediksi Semua Siswa
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="unpredicted-tab" data-bs-toggle="tab" data-bs-target="#unpredicted" 
                            type="button" role="tab" aria-controls="unpredicted" aria-selected="false">
                        Siswa Belum Diprediksi (<?= $unpredicted ?>)
                    </button>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content" id="myTabContent">
                <!-- Single Prediction Tab -->
                <div class="tab-pane fade show active" id="single" role="tabpanel" aria-labelledby="single-tab">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Input Data Prediksi</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <div class="mb-3">
                                            <label class="form-label">Pilih Siswa</label>
                                            <select class="form-select" name="siswa_id" required>
                                                <option value="">-- Pilih Siswa --</option>
                                                <?php foreach ($siswaList as $siswa): ?>
                                                    <option value="<?= $siswa['id'] ?>">
                                                        <?= htmlspecialchars($siswa['nama_lengkap']) ?> 
                                                        (<?= htmlspecialchars($siswa['nama_kelas'] ?? '-') ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Model yang Digunakan</label>
                                            <select class="form-select" name="model_version">
                                                <?php foreach ($modelList as $model): ?>
                                                    <option value="<?= $model['model_version'] ?>" 
                                                        <?= ($model['id'] == $activeModel['id']) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($model['model_version']) ?> 
                                                        <?= $model['active'] ? '(Active)' : '' ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <button type="submit" name="predict_single" class="btn btn-primary">Lakukan Prediksi</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (isset($hasilPrediksi)): ?>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Detail Prediksi</h5>
                                </div>
                                <div class="card-body">
                                    <div class="prediction-result <?= $hasilPrediksi == 'IPA' ? 'result-ipa' : 'result-ips' ?>">
                                        Rekomendasi Jurusan: <?= $hasilPrediksi ?>
                                    </div>
                                    
                                    <table class="table table-sm">
                                        <tr>
                                            <td>Siswa</td>
                                            <td><?= htmlspecialchars($siswa['nama_lengkap']) ?></td>
                                        </tr>
                                        <tr>
                                            <td>NIS</td>
                                            <td><?= htmlspecialchars($siswa['nis']) ?></td>
                                        </tr>
                                        <tr>
                                            <td>Nilai IPA</td>
                                            <td><?= number_format($nilaiIPA, 2) ?></td>
                                        </tr>
                                        <tr>
                                            <td>Nilai IPS</td>
                                            <td><?= number_format($nilaiIPS, 2) ?></td>
                                        </tr>
                                        <tr>
                                            <td>Kategori Nilai</td>
                                            <td><?= $kategoriNilai ?></td>
                                        </tr>
                                        <tr>
                                            <td>Skor IQ</td>
                                            <td><?= $testIQ['skor'] ?? '-' ?></td>
                                        </tr>
                                        <tr>
                                            <td>Kategori IQ</td>
                                            <td><?= $testIQ['kategori'] ?? '-' ?></td>
                                        </tr>
                                        <tr>
                                            <td>Minat</td>
                                            <td><?= $minat['minat'] ?? '-' ?></td>
                                        </tr>
                                        <tr>
                                            <td>Model</td>
                                            <td><?= htmlspecialchars($modelVersion) ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Bulk Prediction Tab -->
                <div class="tab-pane fade" id="bulk" role="tabpanel" aria-labelledby="bulk-tab">
                    <div class="card">
                        <div class="card-header">
                            <h5>Prediksi Semua Siswa</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Model yang Digunakan</label>
                                    <select class="form-select" name="model_version">
                                        <?php foreach ($modelList as $model): ?>
                                            <option value="<?= $model['model_version'] ?>" 
                                                <?= ($model['id'] == $activeModel['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($model['model_version']) ?> 
                                                <?= $model['active'] ? '(Active)' : '' ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="alert alert-info">
                                   <i class="bi bi-info-circle me-2"></i>
                                   <strong><?= $unpredicted ?></strong> siswa belum diprediksi. Sistem akan memproses semua siswa yang belum memiliki hasil prediksi.
                               </div>
                               
                               <button type="submit" name="predict_all" class="btn btn-success" 
                                       onclick="return confirm('Yakin ingin memprediksi semua siswa? Proses ini mungkin memakan waktu.')">
                                   <i class="bi bi-gear-fill me-2"></i>Prediksi Semua Siswa
                               </button>
                           </form>
                       </div>
                   </div>
               </div>

               <!-- Unpredicted Students Tab -->
               <div class="tab-pane fade" id="unpredicted" role="tabpanel" aria-labelledby="unpredicted-tab">
                   <div class="card">
                       <div class="card-header">
                           <h5>Siswa yang Belum Diprediksi</h5>
                       </div>
                       <div class="card-body">
                           <?php if (!empty($unpredicted_details)): ?>
                               <div class="table-responsive">
                                   <table class="table table-hover">
                                       <thead>
                                           <tr>
                                               <th>No</th>
                                               <th>Nama Siswa</th>
                                               <th>NIS</th>
                                               <th>Kelas</th>
                                               <th>Status Data</th>
                                               <th>Aksi</th>
                                           </tr>
                                       </thead>
                                       <tbody>
                                           <?php foreach ($unpredicted_details as $index => $student): ?>
                                               <tr>
                                                   <td><?= $index + 1 ?></td>
                                                   <td><?= htmlspecialchars($student['nama_lengkap']) ?></td>
                                                   <td><?= htmlspecialchars($student['nis']) ?></td>
                                                   <td><?= htmlspecialchars($student['nama_kelas'] ?? '-') ?></td>
                                                   <td>
                                                       <?php
                                                       $status = [];
                                                       if ($student['count_categories'] < 2) {
                                                           $status[] = '<span class="badge bg-danger">Nilai Kurang</span>';
                                                       } else {
                                                           $status[] = '<span class="badge bg-success">Nilai ✓</span>';
                                                       }
                                                       
                                                       if ($student['has_iq'] == 0) {
                                                           $status[] = '<span class="badge bg-danger">IQ Belum Ada</span>';
                                                       } else {
                                                           $status[] = '<span class="badge bg-success">IQ ✓</span>';
                                                       }
                                                       
                                                       if ($student['has_minat'] == 0) {
                                                           $status[] = '<span class="badge bg-warning">Minat Kosong</span>';
                                                       } else {
                                                           $status[] = '<span class="badge bg-success">Minat ✓</span>';
                                                       }
                                                       
                                                       echo implode(' ', $status);
                                                       ?>
                                                   </td>
                                                   <td>
                                                       <button class="btn btn-sm btn-info" onclick="checkStudentData(<?= $student['id'] ?>)">
                                                           <i class="bi bi-eye"></i> Detail
                                                       </button>
                                                       <form style="display: inline;" method="POST">
                                                           <input type="hidden" name="siswa_id" value="<?= $student['id'] ?>">
                                                           <button type="submit" name="predict_single" class="btn btn-sm btn-primary">
                                                               <i class="bi bi-calculator"></i> Prediksi
                                                           </button>
                                                       </form>
                                                   </td>
                                               </tr>
                                           <?php endforeach; ?>
                                       </tbody>
                                   </table>
                               </div>
                           <?php else: ?>
                               <div class="alert alert-success">
                                   <i class="bi bi-check-circle me-2"></i>
                                   Semua siswa sudah diprediksi!
                               </div>
                           <?php endif; ?>
                       </div>
                   </div>
               </div>
           </div>

           <!-- Riwayat Prediksi -->
           <div class="card mt-4">
               <div class="card-header">
                   <h5>Riwayat Prediksi</h5>
               </div>
               <div class="card-body">
                   <?php
                   $stmt = $conn->prepare("
                       SELECT p.*, s.nama_lengkap, s.nis, k.nama_kelas
                       FROM prediksi_jurusan p
                       JOIN siswa s ON p.siswa_id = s.id
                       LEFT JOIN kelas k ON s.kelas_id = k.id
                       ORDER BY p.created_at DESC
                       LIMIT 20
                   ");
                   $stmt->execute();
                   $result = $stmt->get_result();
                   $riwayat = $result->fetch_all(MYSQLI_ASSOC);
                   ?>
                   
                   <?php if (!empty($riwayat)): ?>
                       <div class="table-responsive">
                           <table class="table table-hover">
                               <thead>
                                   <tr>
                                       <th>Tanggal</th>
                                       <th>Siswa</th>
                                       <th>Kelas</th>
                                       <th>Kategori Nilai</th>
                                       <th>Kategori IQ</th>
                                       <th>Minat</th>
                                       <th>Hasil</th>
                                       <th>Aksi</th>
                                   </tr>
                               </thead>
                               <tbody>
                                   <?php foreach ($riwayat as $item): ?>
                                       <tr>
                                           <td><?= date('d/m/Y H:i', strtotime($item['created_at'])) ?></td>
                                           <td><?= htmlspecialchars($item['nama_lengkap']) ?><br>
                                               <small class="text-muted"><?= htmlspecialchars($item['nis']) ?></small>
                                           </td>
                                           <td><?= htmlspecialchars($item['nama_kelas'] ?? '-') ?></td>
                                           <td><?= $item['kategori_nilai'] ?></td>
                                           <td><?= $item['kategori_iq'] ?? '-' ?></td>
                                           <td><?= $item['minat'] ?? '-' ?></td>
                                           <td>
                                               <span class="badge bg-<?= $item['hasil_prediksi'] == 'IPA' ? 'success' : 'info' ?>">
                                                   <?= $item['hasil_prediksi'] ?>
                                               </span>
                                           </td>
                                           <td>
                                               <form method="POST" style="display: inline;">
                                                   <input type="hidden" name="prediction_id" value="<?= $item['id'] ?>">
                                                   <button type="submit" name="delete_prediction" class="btn btn-sm btn-danger" 
                                                           onclick="return confirm('Yakin hapus hasil prediksi ini?')"
                                                           title="Hapus Prediksi">
                                                       <i class=""fas fa-trash"></i>
                                                    </button>
                                                </form>
                                           </td>
                                       </tr>
                                   <?php endforeach; ?>
                               </tbody>
                           </table>
                       </div>
                   <?php else: ?>
                       <div class="alert alert-info">
                           <i class="bi bi-info-circle me-2"></i>
                           Belum ada riwayat prediksi.
                       </div>
                   <?php endif; ?>
               </div>
           </div>
       </main>
       </div>
</div>
<!-- Modal Detail Siswa -->
<div class="modal fade" id="studentDetailModal" tabindex="-1" aria-labelledby="studentDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="studentDetailModalLabel">Detail Data Siswa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="studentDetailContent">
                <!-- Content will be loaded via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
<script>
// Function to clear failed predictions from session
function clearFailedPredictions() {
    fetch('<?= $_SERVER['PHP_SELF'] ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'clear_failed_predictions=1'
    }).then(() => {
        window.location.reload();
    });
}

// Function to check student data details
function checkStudentData(siswaId) {
    // Create modal if it doesn't exist
    var modal = document.getElementById('studentDetailModal');
    var bsModal = new bootstrap.Modal(modal);
    
    // Show loading
    document.getElementById('studentDetailContent').innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';
    
    // Show modal
    bsModal.show();
    
    // Load data via AJAX
    fetch('check_student_data.php?siswa_id=' + siswaId)
        .then(response => response.text())
        .then(data => {
            document.getElementById('studentDetailContent').innerHTML = data;
        })
        .catch(error => {
            document.getElementById('studentDetailContent').innerHTML = '<div class="alert alert-danger">Error loading data</div>';
        });
}
</script>
<?php 
// Clean up session
if (isset($_POST['clear_failed_predictions'])) {
    unset($_SESSION['failed_predictions']);
    exit;
}

require_once '../include/footer.php'; 
ob_end_flush();
?>