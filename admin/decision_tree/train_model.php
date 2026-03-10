<?php
ob_start();
session_start();
require_once '../../database/config.php';
require_once '../include/header.php';
require_once 'classes/DecisionTree.php';
require_once 'classes/ModelTrainer.php';

// Instance model trainer
$trainer = new ModelTrainer($conn);
$trainingData = $trainer->getTrainingData();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $modelVersion = $_POST['model_version'] ?? 'default';
    $description = $_POST['description'] ?? '';
    
    try {
        // Buat instance decision tree
        $tree = new DecisionTree($conn, $modelVersion);
        
        // Atribut yang akan digunakan
        $attributes = ['kategori_nilai', 'kategori_iq', 'minat'];
        
        // Build the tree
        $tree->buildTree($trainingData, $attributes);
        
        // Hitung akurasi
        $accuracy = $trainer->calculateAccuracy($modelVersion);
        if ($accuracy !== null) {
            $trainer->saveEvaluation($modelVersion, $accuracy, count($trainingData));
        }
        
        // Simpan metadata model - BAGIAN INI DIPERBAIKI
        $stmt = $conn->prepare("
            INSERT INTO model_metadata (model_version, description, training_data_size, created_by, active) 
            VALUES (?, ?, ?, ?, 1)
        ");
        
        // Pisahkan variabel untuk bind_param
        $userId = $_SESSION['user_id'] ?? null;
        $dataSize = count($trainingData);
        
        // Tipe data: s=string, i=integer
        $stmt->bind_param("ssii", $modelVersion, $description, $dataSize, $userId);
        $stmt->execute();
        
        $success = true;
        $message = "Model berhasil ditraining dengan " . count($trainingData) . " data.";
        if ($accuracy !== null) {
            $message .= " Akurasi: " . number_format($accuracy, 2) . "%";
        }
    } catch (Exception $e) {
        $error = true;
        $message = "Error: " . $e->getMessage();
    }
}
?>

<style>
    .content-wrapper {
        transition: all 0.3s ease;
        width: calc(100% - 250px);
        margin-left: 250px;
    }
    
    .content-wrapper.expanded {
        width: 100%;
        margin-left: 0;
    }
    
    @media (max-width: 768px) {
        .content-wrapper {
            width: 100%;
            margin-left: 0;
        }
    }
    
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
                <h1 class="h2">Training Model Decision Tree</h1>
                <a href="index.php" class="btn btn-secondary">Kembali</a>
            </div>

            <?php if (isset($success)): ?>
                <div class="alert alert-success alert-dismissible fade show"><?= $message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show"><?= $message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5>Training Model Baru</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Versi Model</label>
                                    <input type="text" class="form-control" name="model_version" required 
                                           placeholder="contoh: v1.0">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Deskripsi</label>
                                    <textarea class="form-control" name="description" rows="3"
                                              placeholder="Deskripsi model..."></textarea>
                                </div>
                                <div class="mb-3">
                                    <small class="text-muted">
                                        Data training tersedia: <strong><?= count($trainingData) ?></strong> record
                                    </small>
                                </div>
                                <button type="submit" class="btn btn-primary">Train Model</button>
                                <a href="index.php" class="btn btn-secondary">Batal</a>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5>Model yang Tersedia</h5>
                        </div>
                        <div class="card-body">
                            <?php
                            $stmt = $conn->prepare("SELECT * FROM model_metadata ORDER BY created_at DESC");
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $models = $result->fetch_all(MYSQLI_ASSOC);
                            ?>
                            
                            <?php if (!empty($models)): ?>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Versi</th>
                                                <th>Deskripsi</th>
                                                <th>Status</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($models as $model): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($model['model_version']) ?></td>
                                                    <td><?= htmlspecialchars($model['description']) ?></td>
                                                    <td>
                                                        <?php if ($model['active']): ?>
                                                            <span class="badge bg-success">Active</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">Inactive</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <a href="view_tree.php?version=<?= $model['model_version'] ?>" 
                                                           class="btn btn-sm btn-info">Lihat</a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">Belum ada model yang ditraining.</p>
                            <?php endif; ?>
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