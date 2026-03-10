<?php
ob_start();
session_start();
require_once '../../database/config.php';
require_once '../include/header.php';

$modelVersion = $_GET['version'] ?? 'default';

// Ambil metadata model
$stmt = $conn->prepare("SELECT * FROM model_metadata WHERE model_version = ?");
$stmt->bind_param("s", $modelVersion);
$stmt->execute();
$result = $stmt->get_result();
$modelData = $result->fetch_assoc();

if (!$modelData) {
    header("Location: index.php");
    exit;
}

// Ambil evaluasi model
$stmt = $conn->prepare("SELECT * FROM model_evaluation WHERE model_version = ? ORDER BY evaluation_date DESC LIMIT 1");
$stmt->bind_param("s", $modelVersion);
$stmt->execute();
$result = $stmt->get_result();
$evaluation = $result->fetch_assoc();
?>

<style>
    .content-wrapper {
        transition: all 0.3s ease;
        width: calc(100% - 250px);
        margin-left: 250px;
    }
    
    /* Improved tree visualization styles */
    .tree-container {
        padding: 30px;
        background: #f8f9fa;
        border-radius: 8px;
        overflow-x: auto;
        min-height: 500px;
    }
    
    .tree-wrapper {
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    
    .tree-level {
        display: flex;
        justify-content: center;
        margin-bottom: 30px;
        width: 100%;
    }
    
    .node-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 0 15px;
    }
    
    .tree-node {
        padding: 12px 15px;
        border: 2px solid #0d6efd;
        border-radius: 8px;
        background: white;
        text-align: center;
        min-width: 150px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        position: relative;
        z-index: 2;
    }
    
    .tree-node.leaf {
        border-color: #28a745;
        background: #d4edda;
    }
    
    .connector {
        width: 2px;
        height: 30px;
        background-color: #6c757d;
        margin: 5px 0;
    }
    
    .branches {
        display: flex;
        position: relative;
    }
    
    .branch-label {
        font-size: 12px;
        color: #6c757d;
        position: absolute;
        top: -20px;
        transform: translateX(-50%);
        background: #f8f9fa;
        padding: 2px 8px;
        border-radius: 10px;
        white-space: nowrap;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <?php require_once '../include/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4 content-wrapper" id="content">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Visualisasi Decision Tree - <?= htmlspecialchars($modelVersion) ?></h1>
                <a href="train_model.php" class="btn btn-secondary">Kembali</a>
            </div>

            <!-- Info Model -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Informasi Model</h5>
                        </div>
                        <div class="card-body">
                            <dl class="row mb-0">
                                <dt class="col-sm-6">Versi:</dt>
                                <dd class="col-sm-6"><?= htmlspecialchars($modelVersion) ?></dd>
                                
                                <dt class="col-sm-6">Deskripsi:</dt>
                                <dd class="col-sm-6"><?= htmlspecialchars($modelData['description']) ?></dd>
                                
                                <dt class="col-sm-6">Data Training:</dt>
                                <dd class="col-sm-6"><?= $modelData['training_data_size'] ?> record</dd>
                                
                                <dt class="col-sm-6">Dibuat:</dt>
                                <dd class="col-sm-6"><?= date('d/m/Y H:i', strtotime($modelData['created_at'])) ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
                
                <?php if ($evaluation): ?>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">Evaluasi Model</h5>
                        </div>
                        <div class="card-body">
                            <dl class="row mb-0">
                                <dt class="col-sm-6">Akurasi:</dt>
                                <dd class="col-sm-6"><?= number_format($evaluation['accuracy'], 2) ?>%</dd>
                                
                                <dt class="col-sm-6">Dataset Test:</dt>
                                <dd class="col-sm-6"><?= $evaluation['dataset_size'] ?> record</dd>
                                
                                <dt class="col-sm-6">Tanggal:</dt>
                                <dd class="col-sm-6"><?= date('d/m/Y H:i', strtotime($evaluation['evaluation_date'])) ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Visualisasi Pohon -->
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Decision Tree</h5>
                </div>
                <div class="card-body">
                    <div class="tree-container">
                        <?php
                        // Fungsi untuk mendapatkan semua node berdasarkan level
                        function getNodesByLevel($conn, $modelVersion) {
                            $nodesByLevel = [];
                            
                            // Ambil semua node untuk model ini
                            $stmt = $conn->prepare("SELECT * FROM decision_tree_nodes WHERE model_version = ? ORDER BY id");
                            $stmt->bind_param("s", $modelVersion);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $nodes = [];
                            
                            while ($node = $result->fetch_assoc()) {
                                $nodes[$node['id']] = $node;
                            }
                            
                            // Root node
                            $rootNode = null;
                            foreach ($nodes as $node) {
                                if ($node['parent_id'] === null) {
                                    $rootNode = $node;
                                    break;
                                }
                            }
                            
                            if (!$rootNode) {
                                return $nodesByLevel;
                            }
                            
                            // Build tree structure dengan level
                            $nodesByLevel[0] = [$rootNode['id'] => $rootNode];
                            
                            $currentLevel = 0;
                            while (!empty($nodesByLevel[$currentLevel])) {
                                $nodesByLevel[$currentLevel + 1] = [];
                                
                                foreach ($nodesByLevel[$currentLevel] as $parentId => $parentNode) {
                                    // Temukan semua child dari parent ini
                                    foreach ($nodes as $node) {
                                        if ($node['parent_id'] == $parentId) {
                                            $nodesByLevel[$currentLevel + 1][$node['id']] = $node;
                                        }
                                    }
                                }
                                
                                if (empty($nodesByLevel[$currentLevel + 1])) {
                                    unset($nodesByLevel[$currentLevel + 1]);
                                }
                                
                                $currentLevel++;
                            }
                            
                            return $nodesByLevel;
                        }
                        
                        // Fungsi untuk mendapatkan children dari node
                        function getChildNodes($conn, $parentId) {
                            $stmt = $conn->prepare("SELECT * FROM decision_tree_nodes WHERE parent_id = ? ORDER BY attribute_value");
                            $stmt->bind_param("i", $parentId);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            
                            $children = [];
                            while ($child = $result->fetch_assoc()) {
                                $children[] = $child;
                            }
                            
                            return $children;
                        }
                        
                        // Mendapatkan nodes berdasarkan level
                        $nodesByLevel = getNodesByLevel($conn, $modelVersion);
                        
                        if (!empty($nodesByLevel)) {
                            echo '<div class="tree-wrapper">';
                            
                            foreach ($nodesByLevel as $level => $nodes) {
                                echo '<div class="tree-level">';
                                
                                foreach ($nodes as $nodeId => $node) {
                                    echo '<div class="node-container">';
                                    
                                    // Connector dari parent jika bukan root
                                    if ($level > 0) {
                                        echo '<div class="connector"></div>';
                                    }
                                    
                                    // Node
                                    if ($node['is_leaf']) {
                                        echo '<div class="tree-node leaf">';
                                        echo '<span class="badge bg-success">' . htmlspecialchars($node['class_value']) . '</span>';
                                        echo '</div>';
                                    } else {
                                        echo '<div class="tree-node">';
                                        echo htmlspecialchars($node['attribute_name']);
                                        if ($node['gain']) {
                                            echo '<br><small class="text-muted">Gain: ' . number_format($node['gain'], 4) . '</small>';
                                        }
                                        echo '</div>';
                                    }
                                    
                                    // Ambil child nodes untuk current node
                                    $childNodes = getChildNodes($conn, $nodeId);
                                    
                                    if (!empty($childNodes)) {
                                        echo '<div class="branches">';
                                        
                                        foreach ($childNodes as $index => $childNode) {
                                            echo '<div class="branch">';
                                            if ($childNode['attribute_value']) {
                                                echo '<div class="branch-label">' . htmlspecialchars($childNode['attribute_value']) . '</div>';
                                            }
                                            echo '</div>';
                                        }
                                        
                                        echo '</div>';
                                    }
                                    
                                    echo '</div>'; // End node-container
                                }
                                
                                echo '</div>'; // End tree-level
                            }
                            
                            echo '</div>'; // End tree-wrapper
                        } else {
                            echo '<div class="alert alert-info">Pohon keputusan belum dibuat atau tidak tersedia.</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
            
            <!-- Legend -->
            <div class="card mb-4">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Keterangan</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-2">
                                <div class="tree-node me-3" style="min-width: 30px; height: 30px;"></div>
                                <span>Node keputusan (Attribute)</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <div class="tree-node leaf me-3" style="min-width: 30px; height: 30px;"></div>
                                <span>Node daun (Kelas hasil)</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Gain</strong>: Nilai information gain yang menunjukkan seberapa baik atribut tersebut dalam memisahkan data.</p>
                            <p><strong>Semakin tinggi gain</strong>: Semakin baik atribut tersebut sebagai pemisah data.</p>
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