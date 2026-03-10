<?php
ob_start();
session_start();
require_once '../../database/config.php';
require_once '../include/header.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_data'])) {
        // Add new training data
        $stmt = $conn->prepare("
            INSERT INTO training_data (kategori_nilai, kategori_iq, minat, hasil_aktual, is_training) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("ssssi", 
            $_POST['kategori_nilai'], 
            $_POST['kategori_iq'], 
            $_POST['minat'], 
            $_POST['hasil_aktual'], 
            $_POST['is_training']
        );
        
        if ($stmt->execute()) {
            $success = "Data training berhasil ditambahkan.";
        } else {
            $error = "Gagal menambahkan data.";
        }
    }
    
    if (isset($_POST['delete_data'])) {
        // Delete training data
        $stmt = $conn->prepare("DELETE FROM training_data WHERE id = ?");
        $stmt->bind_param("i", $_POST['data_id']);
        
        if ($stmt->execute()) {
            $success = "Data berhasil dihapus.";
        } else {
            $error = "Gagal menghapus data.";
        }
    }
    
    if (isset($_POST['import_from_siswa'])) {
        // Import data from existing siswa data
        $minSamples = $_POST['min_samples'] ?? 10;
        
        try {
            // Ambil data siswa lengkap
            $stmt = $conn->prepare("
                SELECT s.id, s.nama_lengkap,
                    AVG(CASE WHEN mp.kategori = 'IPA' THEN n.nilai END) as rata_ipa,
                    AVG(CASE WHEN mp.kategori = 'IPS' THEN n.nilai END) as rata_ips,
                    tq.kategori as kategori_iq,
                    mi.minat
                FROM siswa s
                LEFT JOIN nilai n ON s.id = n.siswa_id
                LEFT JOIN mata_pelajaran mp ON n.mapel_id = mp.id
                LEFT JOIN (SELECT siswa_id, kategori FROM test_iq 
                           WHERE (siswa_id, tanggal_test) IN (
                               SELECT siswa_id, MAX(tanggal_test) 
                               FROM test_iq GROUP BY siswa_id
                           )) tq ON s.id = tq.siswa_id
                LEFT JOIN (SELECT siswa_id, minat FROM minat_siswa
                           WHERE (siswa_id, created_at) IN (
                               SELECT siswa_id, MAX(created_at)
                               FROM minat_siswa GROUP BY siswa_id
                           )) mi ON s.id = mi.siswa_id
                WHERE s.status = 'aktif'
                GROUP BY s.id
                HAVING rata_ipa IS NOT NULL AND rata_ips IS NOT NULL
            ");
            $stmt->execute();
            $result = $stmt->get_result();
            
            $imported = 0;
            while ($row = $result->fetch_assoc()) {
                // Determine actual jurusan based on scores
                $hasil_aktual = null;
                if ($row['rata_ipa'] > $row['rata_ips'] + 5) {
                    $hasil_aktual = 'IPA';
                } elseif ($row['rata_ips'] > $row['rata_ipa'] + 5) {
                    $hasil_aktual = 'IPS';
                } else {
                    // If similar, use minat if available
                    $hasil_aktual = $row['minat'] ?? 'IPA';
                }
                
                // Categorize nilai
                $rata_total = ($row['rata_ipa'] + $row['rata_ips']) / 2;
                if ($rata_total >= 85) {
                    $kategori_nilai = 'Tinggi';
                } elseif ($rata_total >= 70) {
                    $kategori_nilai = 'Sedang';
                } else {
                    $kategori_nilai = 'Rendah';
                }
                
                if ($hasil_aktual && $row['kategori_iq']) {
                    $stmt2 = $conn->prepare("
                        INSERT INTO training_data (kategori_nilai, kategori_iq, minat, hasil_aktual, is_training) 
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    // Randomly assign 70% to training, 30% to test
                  // Ubah bagian ini:
$is_training = (rand(1, 10) <= 7) ? 1 : 0; // 70% training, 30% test

// Atau paksa buat test data:
if ($imported < 5) {
    $is_training = 0; // 5 data pertama jadi test
} else {
    $is_training = 1; // sisanya training
}
                    $stmt2->bind_param("ssssi", 
                        $kategori_nilai, 
                        $row['kategori_iq'], 
                        $row['minat'], 
                        $hasil_aktual, 
                        $is_training
                    );
                    
                    if ($stmt2->execute()) {
                        $imported++;
                    }
                }
            }
            
            $success = "Berhasil mengimpor $imported data training dari data siswa. Data otomatis di-split 70% training dan 30% test.";
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
    
    // Handle auto split
// Perbaiki bagian auto_split di manage_training_data.php
if (isset($_POST['auto_split'])) {
    $testPercentage = $_POST['test_percentage'] ?? 30;
    
    try {
        // Reset semua data menjadi training
        $stmt = $conn->prepare("UPDATE training_data SET is_training = 1");
        $stmt->execute();
        
        // Ambil total data
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM training_data");
        $stmt->execute();
        $result = $stmt->get_result();
        $total = $result->fetch_assoc()['total'];
        
        // Hitung jumlah data test
        $testCount = ceil($total * ($testPercentage / 100));
        
        // PERBAIKAN: Ambil ID secara acak dan update satu per satu
        $stmt = $conn->prepare("SELECT id FROM training_data ORDER BY RAND() LIMIT ?");
        $stmt->bind_param("i", $testCount);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $testIds = [];
        while ($row = $result->fetch_assoc()) {
            $testIds[] = $row['id'];
        }
        
        // Update data yang dipilih menjadi test data
        foreach ($testIds as $id) {
            $stmt = $conn->prepare("UPDATE training_data SET is_training = 0 WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
        }
        
        $success = "Data berhasil di-split. $testCount data menjadi test data ($testPercentage%).";
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}
}

// Ambil data training
$stmt = $conn->prepare("SELECT * FROM training_data ORDER BY created_at DESC");
$stmt->execute();
$result = $stmt->get_result();
$trainingData = $result->fetch_all(MYSQLI_ASSOC);

// Statistics
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN is_training = 1 THEN 1 ELSE 0 END) as training_count,
        SUM(CASE WHEN is_training = 0 THEN 1 ELSE 0 END) as test_count,
        SUM(CASE WHEN hasil_aktual = 'IPA' THEN 1 ELSE 0 END) as ipa_count,
        SUM(CASE WHEN hasil_aktual = 'IPS' THEN 1 ELSE 0 END) as ips_count
    FROM training_data
");
$stmt->execute();
$result = $stmt->get_result();
$stats = $result->fetch_assoc();
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
    
    .stats-card {
        padding: 15px;
        text-align: center;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    
    .stats-number {
        font-size: 2rem;
        font-weight: bold;
        margin-bottom: 5px;
    }
    
    .info-box {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 20px;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <?php require_once '../include/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4 content-wrapper" id="content">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Kelola Data Training</h1>
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

            <!-- Information Box -->
            <div class="info-box">
                <h5><i class="bi bi-info-circle me-2"></i>Tentang Data Training dan Test</h5>
                <div class="row">
                    <div class="col-md-6">
                        <h6>Data Training:</h6>
                        <ul>
                            <li>Digunakan untuk melatih model decision tree</li>
                            <li>Model mempelajari pola dari data ini</li>
                            <li>Biasanya 70-80% dari total data</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6>Data Test:</h6>
                        <ul>
                            <li>Digunakan untuk menguji akurasi model</li>
                            <li>Data yang tidak pernah dilihat model saat training</li>
                            <li>Biasanya 20-30% dari total data</li>
                        </ul>
                    </div>
                </div>
                <div class="alert alert-warning mt-3 mb-0">
                    <strong>Penting!</strong> Untuk menggunakan fitur Test Model, Anda harus memiliki data test (is_training = 0). 
                    Gunakan fitur Auto Split atau import data untuk membuat data test secara otomatis.
                </div>
            </div>

            <!-- Statistics -->
            <div class="row mb-4">
                <div class="col-md-2">
                    <div class="card bg-primary text-white stats-card">
                        <div class="stats-number"><?= $stats['total'] ?></div>
                        <div>Total Data</div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-success text-white stats-card">
                        <div class="stats-number"><?= $stats['training_count'] ?></div>
                        <div>Training Data</div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-info text-white stats-card">
                        <div class="stats-number"><?= $stats['test_count'] ?></div>
                        <div>Test Data</div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-warning text-white stats-card">
                        <div class="stats-number"><?= $stats['ipa_count'] ?></div>
                        <div>Data IPA</div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-danger text-white stats-card">
                        <div class="stats-number"><?= $stats['ips_count'] ?></div>
                        <div>Data IPS</div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-secondary text-white stats-card">
                        <div class="stats-number"><?= $stats['test_count'] > 0 ? 'Ready' : 'No' ?></div>
                        <div>Test Ready</div>
                    </div>
                </div>
            </div>

            <!-- Import from Siswa -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Import dari Data Siswa</h5>
                </div>
                <div class="card-body">
                    <form method="POST" class="row g-3">
                        <div class="col-md-10">
                            <p class="text-muted">
                                Import data training dari data siswa yang sudah ada. 
                                Sistem akan mengkategorikan nilai dan menggunakan data IQ serta minat yang tersedia.
                                <strong>Data akan otomatis di-split menjadi 70% training dan 30% test.</strong>
                            </p>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" name="import_from_siswa" class="btn btn-success">Import Data</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Auto Split Data -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5><i class="bi bi-shuffle me-2"></i>Auto Split Data Training/Test</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row align-items-end">
                            <div class="col-md-4">
                                <label class="form-label">Persentase Test Data</label>
                                <select class="form-select" name="test_percentage">
                                    <option value="20">20% Test, 80% Training</option>
                                    <option value="30" selected>30% Test, 70% Training</option>
                                    <option value="40">40% Test, 60% Training</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <button type="submit" name="auto_split" class="btn btn-warning">
                                    <i class="bi bi-shuffle me-2"></i>Auto Split Data
                                </button>
                                <small class="text-muted d-block mt-2">
                                    Sistem akan secara otomatis memisahkan data untuk training dan testing
                                </small>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Add Data Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Tambah Data Training</h5>
                </div>
                <div class="card-body">
                    <form method="POST" class="row g-3">
                        <div class="col-md-2">
                            <label class="form-label">Kategori Nilai</label>
                            <select class="form-select" name="kategori_nilai" required>
                                <option value="Tinggi">Tinggi</option>
                                <option value="Sedang">Sedang</option>
                                <option value="Rendah">Rendah</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Kategori IQ</label>
                            <select class="form-select" name="kategori_iq" required>
                                <option value="Tinggi">Tinggi</option>
                                <option value="Sedang">Sedang</option>
                                <option value="Rendah">Rendah</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Minat</label>
                            <select class="form-select" name="minat">
                                <option value="">-</option>
                                <option value="IPA">IPA</option>
                                <option value="IPS">IPS</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Hasil Aktual</label>
                            <select class="form-select" name="hasil_aktual" required>
                                <option value="IPA">IPA</option>
                                <option value="IPS">IPS</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Tipe</label>
                            <select class="form-select" name="is_training" required>
                                <option value="1">Training</option>
                                <option value="0">Test</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" name="add_data" class="btn btn-primary d-block w-100">Tambah</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Data Table -->
            <div class="card">
                <div class="card-header">
                    <h5>Data Training</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="trainingDataTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Kategori Nilai</th>
                                    <th>Kategori IQ</th>
                                    <th>Minat</th>
                                    <th>Hasil Aktual</th>
                                    <th>Tipe</th>
                                    <th>Tanggal</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($trainingData as $data): ?>
                                    <tr>
                                        <td><?= $data['id'] ?></td>
                                        <td>
                                            <span class="badge bg-<?= $data['kategori_nilai'] == 'Tinggi' ? 'success' : ($data['kategori_nilai'] == 'Sedang' ? 'warning' : 'danger') ?>">
                                                <?= $data['kategori_nilai'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $data['kategori_iq'] == 'Tinggi' ? 'success' : ($data['kategori_iq'] == 'Sedang' ? 'warning' : 'danger') ?>">
                                                <?= $data['kategori_iq'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($data['minat']): ?>
                                                <span class="badge bg-<?= $data['minat'] == 'IPA' ? 'primary' : 'info' ?>">
                                                    <?= $data['minat'] ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $data['hasil_aktual'] == 'IPA' ? 'primary' : 'info' ?>">
                                                <?= $data['hasil_aktual'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $data['is_training'] ? 'success' : 'secondary' ?>">
                                                <?= $data['is_training'] ? 'Training' : 'Test' ?>
                                            </span>
                                        </td>
                                        <td><?= date('d/m/Y', strtotime($data['created_at'])) ?></td>
                                        <td>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="data_id" value="<?= $data['id'] ?>">
                                                <button type="submit" name="delete_data" class="btn btn-sm btn-danger" 
                                                        onclick="return confirm('Yakin hapus data ini?')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
// Initialize DataTable
document.addEventListener('DOMContentLoaded', function() {
    if (typeof DataTable !== 'undefined') {
        new DataTable('#trainingDataTable', {
            pageLength: 25,
            order: [[0, 'desc']],
            language: {
                search: "Cari:",
                lengthMenu: "Tampilkan _MENU_ data",
                info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                paginate: {
                    first: "Pertama",
                    last: "Terakhir",
                    next: "Selanjutnya",
                    previous: "Sebelumnya"
                }
            }
        });
    }
});
</script>

<?php require_once '../include/footer.php'; 
ob_end_flush();
?>