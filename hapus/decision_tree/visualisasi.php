<?php
require_once '../../database/config.php';
require_once '../include/header.php';
?>



<div class="container-fluid">
    <div class="row">
        <?php require_once '../include/sidebar.php'; ?>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Visualisasi Pohon Keputusan</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="index.php" class="btn btn-sm btn-outline-secondary">Kembali ke Pohon Keputusan</a>
                    </div>
                </div>
            </div>

            <?php
            // Cek apakah ada data pohon keputusan
            $check_query = "SELECT * FROM decision_tree LIMIT 1";
            $check_result = mysqli_query($conn, $check_query);
            
            if (mysqli_num_rows($check_result) == 0) {
                echo '<div class="alert alert-warning">
                        <strong>Perhatian!</strong> Belum ada pohon keputusan yang dibuat. Silakan buat pohon keputusan terlebih dahulu.
                        <div class="mt-3">
                            <a href="index.php" class="btn btn-primary">Buat Pohon Keputusan</a>
                        </div>
                      </div>';
            } else {
            ?>
            
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0">Pohon Keputusan</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-4">
                                <h6>Keterangan:</h6>
                                <div class="d-flex flex-wrap gap-2">
                                    <span class="badge bg-primary p-2"><i class="bi bi-diagram-3"></i> Node Keputusan</span>
                                    <span class="badge bg-success p-2"><i class="bi bi-check-circle"></i> Keputusan IPA</span>
                                    <span class="badge bg-info p-2"><i class="bi bi-check-circle"></i> Keputusan IPS</span>
                                </div>
                            </div>
                            
                            <div class="tree-visualization">
                                <?php
                                // Fungsi untuk mendapatkan node root
                                function getRootNode($conn) {
                                    $query = "SELECT * FROM decision_tree WHERE parent_id IS NULL ORDER BY node_id LIMIT 1";
                                    $result = mysqli_query($conn, $query);
                                    return mysqli_fetch_assoc($result);
                                }
                                
                                // Fungsi untuk mendapatkan anak-anak node
                                function getChildNodes($conn, $parentNodeId) {
                                    $query = "SELECT * FROM decision_tree WHERE parent_id = '$parentNodeId' ORDER BY node_id";
                                    $result = mysqli_query($conn, $query);
                                    $children = [];
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        $children[] = $row;
                                    }
                                    return $children;
                                }
                                
                                // Fungsi untuk menampilkan node secara rekursif
                                function displayNode($conn, $node, $level = 0) {
                                    $indent = str_repeat('    ', $level);
                                    $nodeClass = $node['is_leaf'] ? 
                                        ($node['keputusan'] == 'IPA' ? 'node-leaf node-ipa' : 'node-leaf node-ips') : 
                                        'node-decision';
                                    
                                    $atribut = getAttributeLabel($node['atribut']);
                                    
                                    echo "<div class='tree-node $nodeClass' style='margin-left: " . ($level * 40) . "px;'>";
                                    
                                    if ($node['is_leaf']) {
                                        echo "<div class='node-content leaf-node'>";
                                        echo "<div class='badge " . ($node['keputusan'] == 'IPA' ? 'bg-success' : 'bg-info') . " p-2'>";
                                        echo "Jurusan: " . $node['keputusan'];
                                        echo "</div>";
                                        echo "</div>";
                                    } else {
                                        echo "<div class='node-content decision-node badge bg-primary p-2'>";
                                        echo "$atribut";
                                        echo "</div>";
                                        
                                        $children = getChildNodes($conn, $node['node_id']);
                                        if (!empty($children)) {
                                            echo "<div class='node-children'>";
                                            foreach ($children as $child) {
                                                echo "<div class='node-branch'>";
                                                echo "<div class='branch-label badge bg-secondary'>" . $child['nilai_atribut'] . "</div>";
                                                displayNode($conn, $child, $level + 1);
                                                echo "</div>";
                                            }
                                            echo "</div>";
                                        }
                                    }
                                    
                                    echo "</div>";
                                }
                                
                                // Fungsi untuk mendapatkan label atribut yang lebih deskriptif
                                function getAttributeLabel($attribute) {
                                    $labels = [
                                        'nilai_ipa' => 'Nilai IPA',
                                        'nilai_ips' => 'Nilai IPS',
                                        'skor_iq' => 'Test IQ',
                                        'minat' => 'Minat Siswa'
                                    ];
                                    
                                    return isset($labels[$attribute]) ? $labels[$attribute] : $attribute;
                                }
                                
                                // Tampilkan pohon keputusan
                                $rootNode = getRootNode($conn);
                                if ($rootNode) {
                                    displayNode($conn, $rootNode);
                                } else {
                                    echo "<p>Tidak dapat menemukan node root.</p>";
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0">Struktur Data Pohon Keputusan</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Node ID</th>
                                            <th>Parent ID</th>
                                            <th>Atribut</th>
                                            <th>Nilai</th>
                                            <th>Leaf?</th>
                                            <th>Keputusan</th>
                                            <th>Entropy</th>
                                            <th>Gain</th>
                                            <th>Level</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $query = "SELECT * FROM decision_tree ORDER BY node_id";
                                        $result = mysqli_query($conn, $query);
                                        
                                        while ($row = mysqli_fetch_assoc($result)) {
                                            echo "<tr>";
                                            echo "<td>{$row['node_id']}</td>";
                                            echo "<td>" . ($row['parent_id'] ? $row['parent_id'] : 'null') . "</td>";
                                            echo "<td>" . ($row['atribut'] ? getAttributeLabel($row['atribut']) : '-') . "</td>";
                                            echo "<td>" . ($row['nilai_atribut'] ? $row['nilai_atribut'] : '-') . "</td>";
                                            echo "<td>" . ($row['is_leaf'] ? 'Ya' : 'Tidak') . "</td>";
                                            echo "<td>" . ($row['keputusan'] ? $row['keputusan'] : '-') . "</td>";
                                            echo "<td>" . number_format($row['entropy'], 4) . "</td>";
                                            echo "<td>" . number_format($row['gain'], 4) . "</td>";
                                            echo "<td>{$row['level']}</td>";
                                            echo "</tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0">Sistem Prediksi Jurusan</h5>
                        </div>
                        <div class="card-body">
                            <p>Gunakan formulir ini untuk memprediksi jurusan dengan pohon keputusan yang ada:</p>
                            <form id="predictionForm" method="post" action="">
                                <div class="row mb-3">
                                    <div class="col-md-3">
                                        <label for="nilai_ipa" class="form-label">Nilai IPA</label>
                                        <select name="nilai_ipa" id="nilai_ipa" class="form-select" required>
                                            <option value="">Pilih Kategori</option>
                                            <option value="Tinggi">Tinggi (≥ 85)</option>
                                            <option value="Sedang">Sedang (75-84)</option>
                                            <option value="Rendah">Rendah (< 75)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="nilai_ips" class="form-label">Nilai IPS</label>
                                        <select name="nilai_ips" id="nilai_ips" class="form-select" required>
                                            <option value="">Pilih Kategori</option>
                                            <option value="Tinggi">Tinggi (≥ 85)</option>
                                            <option value="Sedang">Sedang (75-84)</option>
                                            <option value="Rendah">Rendah (< 75)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="skor_iq" class="form-label">Skor IQ</label>
                                        <select name="skor_iq" id="skor_iq" class="form-select" required>
                                            <option value="">Pilih Kategori</option>
                                            <option value="Tinggi">Tinggi (≥ 115)</option>
                                            <option value="Sedang">Sedang (100-114)</option>
                                            <option value="Rendah">Rendah (< 100)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="minat" class="form-label">Minat Siswa</label>
                                        <select name="minat" id="minat" class="form-select" required>
                                            <option value="">Pilih Minat</option>
                                            <option value="IPA">IPA</option>
                                            <option value="IPS">IPS</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" name="predict" class="btn btn-primary">Prediksi Jurusan</button>
                                </div>
                            </form>
                            
                            <?php
                            if (isset($_POST['predict'])) {
                                $nilai_ipa = $_POST['nilai_ipa'];
                                $nilai_ips = $_POST['nilai_ips'];
                                $skor_iq = $_POST['skor_iq'];
                                $minat = $_POST['minat'];
                                
                                // Fungsi untuk menelusuri pohon keputusan
                                function traverseTree($conn, $instance) {
                                    $node = getRootNode($conn);
                                    
                                    while (!$node['is_leaf']) {
                                        $attribute = $node['atribut'];
                                        $value = $instance[$attribute];
                                        
                                        // Cari child node yang sesuai dengan nilai atribut
                                        $found = false;
                                        $children = getChildNodes($conn, $node['node_id']);
                                        
                                        foreach ($children as $child) {
                                            if ($child['nilai_atribut'] === $value) {
                                                $node = $child;
                                                $found = true;
                                                break;
                                            }
                                        }
                                        
                                        // Jika tidak ada child yang sesuai, gunakan majority rule
                                        if (!$found) {
                                            // Cek apakah ada keputusan mayoritas
                                            $leafChildren = array_filter($children, function($c) {
                                                return $c['is_leaf'] == 1;
                                            });
                                            
                                            if (!empty($leafChildren)) {
                                                $decisions = [];
                                                foreach ($leafChildren as $leaf) {
                                                    $decision = $leaf['keputusan'];
                                                    if (!isset($decisions[$decision])) {
                                                        $decisions[$decision] = 0;
                                                    }
                                                    $decisions[$decision]++;
                                                }
                                                
                                                arsort($decisions);
                                                return key($decisions);
                                            } else {
                                                return "Tidak dapat menentukan";
                                            }
                                        }
                                    }
                                    
                                    return $node['keputusan'];
                                }
                                
                                // Data instance untuk prediksi
                                $instance = [
                                    'nilai_ipa' => $nilai_ipa,
                                    'nilai_ips' => $nilai_ips,
                                    'skor_iq' => $skor_iq,
                                    'minat' => $minat
                                ];
                                
                                // Lakukan prediksi
                                $prediction = traverseTree($conn, $instance);
                                
                                // Tampilkan hasil prediksi
                                echo '<div class="mt-4">';
                                echo '<div class="card">';
                                echo '<div class="card-header bg-info text-white">';
                                echo '<h5 class="card-title mb-0">Hasil Prediksi</h5>';
                                echo '</div>';
                                echo '<div class="card-body">';
                                echo '<table class="table table-bordered">';
                                echo '<tr><th>Nilai IPA</th><td>' . $nilai_ipa . '</td></tr>';
                                echo '<tr><th>Nilai IPS</th><td>' . $nilai_ips . '</td></tr>';
                                echo '<tr><th>Skor IQ</th><td>' . $skor_iq . '</td></tr>';
                                echo '<tr><th>Minat</th><td>' . $minat . '</td></tr>';
                                echo '<tr class="table-primary"><th>Prediksi Jurusan</th><td><strong>' . $prediction . '</strong></td></tr>';
                                echo '</table>';
                                echo '</div>';
                                echo '</div>';
                                echo '</div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php } // End of check if tree exists ?>
        </main>
    </div>
</div>

<style>
.tree-visualization {
    padding: 20px;
    overflow-x: auto;
    min-height: 300px;
}

.tree-node {
    position: relative;
    margin-bottom: 20px;
}

.node-content {
    display: inline-block;
    padding: 8px 12px;
    border-radius: 5px;
    margin-bottom: 10px;
}

.decision-node {
    font-weight: bold;
}

.leaf-node {
    font-weight: bold;
}

.node-children {
    position: relative;
    padding-left: 20px;
}

.node-branch {
    position: relative;
    margin-bottom: 15px;
    padding-left: 20px;
    border-left: 2px solid #ccc;
}

.branch-label {
    position: absolute;
    left: -45px;
    top: 0;
}

.node-leaf.node-ipa .node-content {
    border-color: #28a745;
}

.node-leaf.node-ips .node-content {
    border-color: #17a2b8;
}
</style>

<script>
$(document).ready(function() {
    // Inisialisasi Select2 jika digunakan
    if($.fn.select2) {
        $('#nilai_ipa').select2();
        $('#nilai_ips').select2();
        $('#skor_iq').select2();
        $('#minat').select2();
    }
});
</script>

<?php require_once '../include/footer.php'; ?>