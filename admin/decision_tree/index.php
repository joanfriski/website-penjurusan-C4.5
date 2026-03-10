<?php
ob_start();
session_start();
require_once '../../database/config.php';
require_once '../include/header.php';

// Handle delete prediksi
if (isset($_POST['delete_prediction'])) {
    $predictionId = $_POST['prediction_id'];
    $stmt = $conn->prepare("DELETE FROM prediksi_jurusan WHERE id = ?");
    $stmt->bind_param("i", $predictionId);
    if ($stmt->execute()) {
        $success = "Data prediksi berhasil dihapus.";
    } else {
        $error = "Gagal menghapus data prediksi.";
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
                <h1 class="h2">Klasifikasi Siswa</h1>
            </div>

            <?php if (isset($success)): ?>
                <div class="alert alert-success alert-dismissible fade show"><?= $success ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show"><?= $error ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Quick Actions -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="bi bi-database-add fs-2 text-primary mb-2"></i>
                            <h5>Data Training</h5>
                            <p class="text-muted">Kelola data untuk training model</p>
                            <a href="manage_training_data.php" class="btn btn-primary">Kelola Data</a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="bi bi-gear fs-2 text-success mb-2"></i>
                            <h5>Train Model</h5>
                            <p class="text-muted">Training model pohon keputusan</p>
                            <a href="train_model.php" class="btn btn-success">Train Model</a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="bi bi-graph-down fs-2 text-info mb-2"></i>
                            <h5>Klasifikasi</h5>
                            <p class="text-muted">Lakukan Klasifikasi jurusan siswa</p>
                            <a href="predict.php" class="btn btn-info">Klasifikasi</a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="bi bi-speedometer2 fs-2 text-warning mb-2"></i>
                            <h5>Evaluasi</h5>
                            <p class="text-muted">Test dan evaluasi model</p>
                            <a href="test_model.php" class="btn btn-warning">Test Model</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Models -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Model yang Tersedia</h5>
                </div>
                <div class="card-body">
                    <?php
                    $stmt = $conn->prepare("SELECT m.*, u.nama_lengkap 
                                          FROM model_metadata m 
                                          LEFT JOIN users u ON m.created_by = u.id 
                                          ORDER BY m.active DESC, m.created_at DESC");
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $models = $result->fetch_all(MYSQLI_ASSOC);
                    ?>
                    
                    <?php if (!empty($models)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Versi</th>
                                        <th>Deskripsi</th>
                                        <th>Data Training</th>
                                        <th>Dibuat Oleh</th>
                                        <th>Tanggal</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($models as $model): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($model['model_version']) ?></td>
                                            <td><?= htmlspecialchars($model['description']) ?></td>
                                            <td><?= $model['training_data_size'] ?> record</td>
                                            <td><?= htmlspecialchars($model['nama_lengkap'] ?? 'System') ?></td>
                                            <td><?= date('d/m/Y', strtotime($model['created_at'])) ?></td>
                                            <td>
                                                <?php if ($model['active']): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="view_tree.php?version=<?= $model['model_version'] ?>" 
                                                   class="btn btn-sm btn-info" title="Lihat Pohon">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="test_model.php?version=<?= $model['model_version'] ?>" 
                                                   class="btn btn-sm btn-warning" title="Test Model">
                                                    <i class="bi bi-speedometer2"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            Belum ada model yang ditraining. <a href="train_model.php">Mulai training model</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Hasil Prediksi Terbaru -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Hasil Prediksi Terbaru</h5>
                    <a href="predict.php" class="btn btn-sm btn-primary">Prediksi Baru</a>
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
                    $predictions = $result->fetch_all(MYSQLI_ASSOC);
                    ?>
                    
                    <?php if (!empty($predictions)): ?>
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
                                    <?php foreach ($predictions as $pred): ?>
                                        <tr>
                                            <td><?= date('d/m/Y H:i', strtotime($pred['created_at'])) ?></td>
                                            <td><?= htmlspecialchars($pred['nama_lengkap']) ?><br>
                                                <small class="text-muted"><?= htmlspecialchars($pred['nis']) ?></small>
                                            </td>
                                            <td><?= htmlspecialchars($pred['nama_kelas'] ?? '-') ?></td>
                                            <td><?= $pred['kategori_nilai'] ?></td>
                                            <td><?= $pred['kategori_iq'] ?? '-' ?></td>
                                            <td><?= $pred['minat'] ?? '-' ?></td>
                                            <td>
                                                <span class="badge bg-<?= $pred['hasil_prediksi'] == 'IPA' ? 'success' : 'info' ?>">
                                                    <?= $pred['hasil_prediksi'] ?>
                                                </span>
                                            </td>
                                            <td>
                                            <div class="btn-group" role="group">
    <!-- Detail Button -->
    <a href="detail.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-info me-1" title="Lihat Detail">
        <i class="fas fa-eye"></i>
    </a>
    
    <!-- Delete Button -->
    <form method="POST" style="display: inline;">
        <input type="hidden" name="prediction_id" value="<?= $item['id'] ?>">
        <button type="submit" name="delete_prediction" class="btn btn-sm btn-danger"
                onclick="return confirm('Yakin hapus hasil prediksi ini?')"
                title="Hapus Prediksi">
            <i class="fas fa-trash"></i>
        </button>
    </form>
</div>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            Belum ada hasil prediksi. <a href="predict.php">Lakukan prediksi sekarang</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once '../include/footer.php'; 
ob_end_flush();
?>