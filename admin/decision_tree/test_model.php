<?php
ob_start();
session_start();
require_once '../../database/config.php';
require_once '../include/header.php';
require_once 'classes/DecisionTree.php';
require_once 'classes/ModelTrainer.php';


// Tambahkan di awal test_model.php setelah include
$debug_stmt = $conn->prepare("SELECT COUNT(*) as test_count FROM training_data WHERE is_training = 0");
$debug_stmt->execute();
$debug_result = $debug_stmt->get_result();
$test_count = $debug_result->fetch_assoc()['test_count'];
echo "<div class='alert alert-info'>DEBUG: Jumlah data test: $test_count</div>";
// Tambahkan di akhir test_model.php

$modelVersion = $_GET['version'] ?? null;

// Ambil metadata model
if ($modelVersion) {
    $stmt = $conn->prepare("SELECT * FROM model_metadata WHERE model_version = ?");
    $stmt->bind_param("s", $modelVersion);
    $stmt->execute();
    $result = $stmt->get_result();
    $modelData = $result->fetch_assoc();
    
    if (!$modelData) {
        header("Location: index.php");
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['test_model'])) {
    $modelToTest = $_POST['model_version'];
    
    try {
        $trainer = new ModelTrainer($conn);
        
        // Ambil data test (data yang tidak digunakan untuk training)
        $stmt = $conn->prepare("SELECT * FROM training_data WHERE is_training = 0");
        $stmt->execute();
        $result = $stmt->get_result();
        $testData = $result->fetch_all(MYSQLI_ASSOC);
        
        if (empty($testData)) {
            throw new Exception("Tidak ada data test. Pastikan ada data dengan is_training = 0");
        }
        
        $tree = new DecisionTree($conn, $modelToTest);
        $correct = 0;
        $total = count($testData);
        $confusionMatrix = [
            'IPA' => ['IPA' => 0, 'IPS' => 0],
            'IPS' => ['IPA' => 0, 'IPS' => 0]
        ];
        $details = [];
        
        foreach ($testData as $data) {
            $prediction = $tree->predict($data);
            $actual = $data['hasil_aktual'];
            
            if ($prediction == $actual) {
                $correct++;
            }
            
            $confusionMatrix[$actual][$prediction]++;
            $details[] = [
                'data' => $data,
                'prediction' => $prediction,
                'actual' => $actual,
                'correct' => $prediction == $actual
            ];
        }
        
        $accuracy = ($correct / $total) * 100;
        
        // Hitung precision, recall, f1-score
        $precision = [];
        $recall = [];
        $f1Score = [];
        
        foreach (['IPA', 'IPS'] as $class) {
            $tp = $confusionMatrix[$class][$class];
            $fp = $confusionMatrix[$class == 'IPA' ? 'IPS' : 'IPA'][$class];
            $fn = $confusionMatrix[$class][$class == 'IPA' ? 'IPS' : 'IPA'];
            
            $precision[$class] = ($tp + $fp) > 0 ? $tp / ($tp + $fp) * 100 : 0;
            $recall[$class] = ($tp + $fn) > 0 ? $tp / ($tp + $fn) * 100 : 0;
            $f1Score[$class] = ($precision[$class] + $recall[$class]) > 0 ? 
                2 * ($precision[$class] * $recall[$class]) / ($precision[$class] + $recall[$class]) : 0;
        }
        
        // Simpan hasil evaluasi
        $stmt = $conn->prepare("
        INSERT INTO model_evaluation 
        (model_version, accuracy, `precision`, recall, f1_score, confusion_matrix, dataset_size) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    // The rest of your code remains the same:
    $avgPrecision = array_sum($precision) / count($precision);
    $avgRecall = array_sum($recall) / count($recall);
    $avgF1Score = array_sum($f1Score) / count($f1Score);
    $confusionMatrixJson = json_encode($confusionMatrix);
            
    $stmt->bind_param("sdddisi", 
        $modelToTest, 
        $accuracy, 
        $avgPrecision, 
        $avgRecall, 
        $avgF1Score, 
        $confusionMatrixJson, 
        $total
    );
    $stmt->execute();
        
        $success = true;
        $testResults = [
            'accuracy' => $accuracy,
            'precision' => $precision,
            'recall' => $recall,
            'f1Score' => $f1Score,
            'confusionMatrix' => $confusionMatrix,
            'details' => $details,
            'total' => $total
        ];
        
    } catch (Exception $e) {
        $error = true;
        $message = "Error: " . $e->getMessage();
    }
}

// Ambil riwayat evaluasi jika ada model yang dipilih
if ($modelVersion) {
    $stmt = $conn->prepare("
        SELECT * FROM model_evaluation 
        WHERE model_version = ? 
        ORDER BY evaluation_date DESC 
        LIMIT 5
    ");
    $stmt->bind_param("s", $modelVersion);
    $stmt->execute();
    $result = $stmt->get_result();
    $evaluationHistory = $result->fetch_all(MYSQLI_ASSOC);
}

// Ambil daftar model
$stmt = $conn->prepare("SELECT * FROM model_metadata ORDER BY active DESC, created_at DESC");
$stmt->execute();
$result = $stmt->get_result();
$modelList = $result->fetch_all(MYSQLI_ASSOC);
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
    
    .metric-card {
        text-align: center;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    
    .metric-value {
        font-size: 2rem;
        font-weight: bold;
        margin: 10px 0;
    }
    
    .confusion-matrix {
        max-width: 400px;
        margin: 0 auto;
    }
    
    .cm-cell {
        text-align: center;
        padding: 10px;
        border: 1px solid #dee2e6;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <?php require_once '../include/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4 content-wrapper" id="content">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Test dan Evaluasi Model</h1>
                <a href="index.php" class="btn btn-secondary">Kembali</a>
            </div>

            <?php if (isset($success)): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    Model berhasil ditest. Akurasi: <?= number_format($testResults['accuracy'], 2) ?>%
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= $message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Form untuk memilih model dan test -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Pilih Model untuk Test</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row align-items-end">
                            <div class="col-md-8">
                                <label class="form-label">Model</label>
                                <select class="form-select" name="model_version" required>
                                    <option value="">-- Pilih Model --</option>
                                    <?php foreach ($modelList as $model): ?>
                                        <option value="<?= $model['model_version'] ?>" 
                                            <?= ($model['model_version'] == $modelVersion) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($model['model_version']) ?> 
                                            <?= $model['active'] ? '(Active)' : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" name="test_model" class="btn btn-primary">Test Model</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <?php if (isset($testResults)): ?>
            <!-- Hasil Evaluasi -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white metric-card">
                        <div>Akurasi</div>
                        <div class="metric-value"><?= number_format($testResults['accuracy'], 2) ?>%</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white metric-card">
                        <div>Precision (Avg)</div>
                        <div class="metric-value"><?= number_format(array_sum($testResults['precision']) / count($testResults['precision']), 2) ?>%</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white metric-card">
                        <div>Recall (Avg)</div>
                        <div class="metric-value"><?= number_format(array_sum($testResults['recall']) / count($testResults['recall']), 2) ?>%</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white metric-card">
                        <div>F1-Score (Avg)</div>
                        <div class="metric-value"><?= number_format(array_sum($testResults['f1Score']) / count($testResults['f1Score']), 2) ?>%</div>
                    </div>
                </div>
            </div>

            <!-- Confusion Matrix -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5>Confusion Matrix</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered confusion-matrix">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th colspan="2" class="text-center">Predicted</th>
                                    </tr>
                                    <tr>
                                        <th>Actual</th>
                                        <th>IPA</th>
                                        <th>IPS</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <th>IPA</th>
                                        <td class="cm-cell"><?= $testResults['confusionMatrix']['IPA']['IPA'] ?></td>
                                        <td class="cm-cell"><?= $testResults['confusionMatrix']['IPA']['IPS'] ?></td>
                                    </tr>
                                    <tr>
                                        <th>IPS</th>
                                        <td class="cm-cell"><?= $testResults['confusionMatrix']['IPS']['IPA'] ?></td>
                                        <td class="cm-cell"><?= $testResults['confusionMatrix']['IPS']['IPS'] ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5>Metrik per Kelas</h5>
                        </div>
                        <div class="card-body">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Kelas</th>
                                        <th>Precision</th>
                                        <th>Recall</th>
                                        <th>F1-Score</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (['IPA', 'IPS'] as $class): ?>
                                        <tr>
                                            <td><?= $class ?></td>
                                            <td><?= number_format($testResults['precision'][$class], 2) ?>%</td>
                                            <td><?= number_format($testResults['recall'][$class], 2) ?>%</td>
                                            <td><?= number_format($testResults['f1Score'][$class], 2) ?>%</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detail Hasil Test -->
            <div class="card">
                <div class="card-header">
                    <h5>Detail Hasil Test</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Kategori Nilai</th>
                                    <th>Kategori IQ</th>
                                    <th>Minat</th>
                                    <th>Actual</th>
                                    <th>Predicted</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($testResults['details'] as $index => $detail): ?>
                                    <tr class="<?= $detail['correct'] ? '' : 'table-danger' ?>">
                                        <td><?= $index + 1 ?></td>
                                        <td><?= $detail['data']['kategori_nilai'] ?></td>
                                        <td><?= $detail['data']['kategori_iq'] ?></td>
                                        <td><?= $detail['data']['minat'] ?? '-' ?></td>
                                        <td><?= $detail['actual'] ?></td>
                                        <td><?= $detail['prediction'] ?></td>
                                        <td>
                                            <?php if ($detail['correct']): ?>
                                                <span class="badge bg-success">Benar</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Salah</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (isset($evaluationHistory) && !empty($evaluationHistory)): ?>
<!-- Riwayat Evaluasi -->
<div class="card mt-4">
               <div class="card-header">
                   <h5>Riwayat Evaluasi - <?= htmlspecialchars($modelVersion) ?></h5>
               </div>
               <div class="card-body">
                   <div class="table-responsive">
                       <table class="table table-hover">
                           <thead>
                               <tr>
                                   <th>Tanggal</th>
                                   <th>Akurasi</th>
                                   <th>Precision</th>
                                   <th>Recall</th>
                                   <th>F1-Score</th>
                                   <th>Dataset Size</th>
                               </tr>
                           </thead>
                           <tbody>
                               <?php foreach ($evaluationHistory as $eval): ?>
                                   <tr>
                                       <td><?= date('d/m/Y H:i', strtotime($eval['evaluation_date'])) ?></td>
                                       <td><?= number_format($eval['accuracy'], 2) ?>%</td>
                                       <td><?= number_format($eval['precision'], 2) ?>%</td>
                                       <td><?= number_format($eval['recall'], 2) ?>%</td>
                                       <td><?= number_format($eval['f1_score'], 2) ?>%</td>
                                       <td><?= $eval['dataset_size'] ?></td>
                                   </tr>
                               <?php endforeach; ?>
                           </tbody>
                       </table>
                   </div>
               </div>
           </div>
           <?php endif; ?>
       </main>
   </div>
</div>

<script>
// Chart untuk menampilkan riwayat evaluasi (optional)
<?php if (isset($evaluationHistory) && !empty($evaluationHistory)): ?>
   document.addEventListener('DOMContentLoaded', function() {
       const ctx = document.getElementById('evaluationChart');
       if (ctx) {
           const data = {
               labels: <?= json_encode(array_map(function($eval) { 
                   return date('d/m', strtotime($eval['evaluation_date'])); 
               }, array_reverse($evaluationHistory))) ?>,
               datasets: [{
                   label: 'Akurasi (%)',
                   data: <?= json_encode(array_map(function($eval) { 
                       return $eval['accuracy']; 
                   }, array_reverse($evaluationHistory))) ?>,
                   borderColor: 'rgb(75, 192, 192)',
                   tension: 0.1
               }]
           };
           
           new Chart(ctx, {
               type: 'line',
               data: data,
               options: {
                   responsive: true,
                   scales: {
                       y: {
                           beginAtZero: true,
                           max: 100
                       }
                   }
               }
           });
       }
   });
<?php endif; ?>
</script>

<?php require_once '../include/footer.php'; 
ob_end_flush();
?>