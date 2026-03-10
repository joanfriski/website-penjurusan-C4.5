<?php
require_once 'TreeNode.php';

class DecisionTree {
    private $conn;
    private $modelVersion;
    
    public function __construct($conn, $modelVersion = 'default') {
        $this->conn = $conn;
        $this->modelVersion = $modelVersion;
    }
    
    // Method untuk menghitung entropy
    private function calculateEntropy($data) {
        $total = count($data);
        if ($total <= 1) return 0;
        
        $classCounts = [];
        foreach ($data as $item) {
            $class = $item['hasil_aktual'];
            if (!isset($classCounts[$class])) {
                $classCounts[$class] = 0;
            }
            $classCounts[$class]++;
        }
        
        $entropy = 0;
        foreach ($classCounts as $count) {
            $probability = $count / $total;
            $entropy -= $probability * log($probability, 2);
        }
        
        return $entropy;
    }
    
    // Method untuk menghitung information gain
    private function calculateGain($data, $attribute) {
        $totalEntropy = $this->calculateEntropy($data);
        $total = count($data);
        
        $subsets = [];
        foreach ($data as $item) {
            $value = $item[$attribute];
            if (!isset($subsets[$value])) {
                $subsets[$value] = [];
            }
            $subsets[$value][] = $item;
        }
        
        $weightedEntropy = 0;
        foreach ($subsets as $subset) {
            $weight = count($subset) / $total;
            $weightedEntropy += $weight * $this->calculateEntropy($subset);
        }
        
        return $totalEntropy - $weightedEntropy;
    }
    
    // Method untuk memilih atribut terbaik
    private function selectBestAttribute($data, $attributes) {
        $bestGain = -1;
        $bestAttribute = null;
        
        foreach ($attributes as $attribute) {
            $gain = $this->calculateGain($data, $attribute);
            if ($gain > $bestGain) {
                $bestGain = $gain;
                $bestAttribute = $attribute;
            }
        }
        
        return ['attribute' => $bestAttribute, 'gain' => $bestGain];
    }
    
    // Method untuk membangun pohon keputusan
    public function buildTree($data, $attributes, $parentId = null) {
        // Base case: jika semua data memiliki kelas yang sama
        $classes = array_unique(array_column($data, 'hasil_aktual'));
        if (count($classes) == 1) {
            $node = new TreeNode(null, null, true, $classes[0]);
            $this->saveNode($node, $parentId);
            return $node;
        }
        
        // Base case: jika tidak ada atribut yang tersisa
        if (empty($attributes)) {
            $classCounts = array_count_values(array_column($data, 'hasil_aktual'));
            $mostCommon = array_search(max($classCounts), $classCounts);
            $node = new TreeNode(null, null, true, $mostCommon);
            $this->saveNode($node, $parentId);
            return $node;
        }
        
        // Pilih atribut terbaik
        $bestResult = $this->selectBestAttribute($data, $attributes);
        $bestAttribute = $bestResult['attribute'];
        $gain = $bestResult['gain'];
        
        // Buat node untuk atribut terbaik
        $node = new TreeNode($bestAttribute);
        $node->gain = $gain;
        $nodeId = $this->saveNode($node, $parentId);
        
        // Buat subset berdasarkan nilai atribut
        $subsets = [];
        foreach ($data as $item) {
            $value = $item[$bestAttribute];
            if (!isset($subsets[$value])) {
                $subsets[$value] = [];
            }
            $subsets[$value][] = $item;
        }
        
        // Hapus atribut yang sudah digunakan
        $remainingAttributes = array_filter($attributes, function($attr) use ($bestAttribute) {
            return $attr != $bestAttribute;
        });
        
        // Rekursi untuk setiap subset
        foreach ($subsets as $value => $subset) {
            $childNode = $this->buildTree($subset, $remainingAttributes, $nodeId);
            $childNode->value = $value;
            $node->addChild($childNode);
        }
        
        return $node;
    }
    
    // Method untuk menyimpan node ke database - BAGIAN INI DIPERBAIKI
    private function saveNode($node, $parentId = null) {
        $stmt = $this->conn->prepare("
            INSERT INTO decision_tree_nodes 
            (parent_id, attribute_name, attribute_value, is_leaf, class_value, entropy, gain, model_version) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        // Pisahkan variabel untuk bind_param
        $isLeaf = $node->isLeaf ? 1 : 0;
        $attributeName = $node->attribute;
        $attributeValue = $node->value;
        $classValue = $node->prediction;
        $entropy = $node->entropy;
        $gain = $node->gain;
        $modelVersion = $this->modelVersion;
        
        // Tipe data: i=integer, s=string, d=double
        $stmt->bind_param("issisdds", 
            $parentId,
            $attributeName,
            $attributeValue,
            $isLeaf,
            $classValue,
            $entropy,
            $gain,
            $modelVersion
        );
        $stmt->execute();
        
        return $this->conn->insert_id;
    }
    
    // Method untuk melakukan prediksi
    public function predict($data) {
        $stmt = $this->conn->prepare("
            SELECT * FROM decision_tree_nodes 
            WHERE model_version = ? AND parent_id IS NULL
        ");
        $stmt->bind_param("s", $this->modelVersion);
        $stmt->execute();
        $result = $stmt->get_result();
        $rootNode = $result->fetch_assoc();
        
        if (!$rootNode) {
            throw new Exception("Model tidak ditemukan");
        }
        
        return $this->traverseTree($data, $rootNode['id']);
    }
    
    // Method untuk traverse pohon
// Di method traverseTree(), tambahkan debug:
private function traverseTree($data, $nodeId) {
    $stmt = $this->conn->prepare("
        SELECT * FROM decision_tree_nodes WHERE id = ?
    ");
    $stmt->bind_param("i", $nodeId);
    $stmt->execute();
    $result = $stmt->get_result();
    $node = $result->fetch_assoc();
    
    if ($node['is_leaf']) {
        return $node['class_value'];
    }
    
    $attributeName = $node['attribute_name'];
    
    // Debug: cek apakah atribut ada dalam data input
    if (!isset($data[$attributeName])) {
        // Return default jika atribut tidak ada
        return 'IPA'; // Atau 'IPS' atau null
    }
    
    $attributeValue = $data[$attributeName];
    
    $stmt = $this->conn->prepare("
        SELECT * FROM decision_tree_nodes 
        WHERE parent_id = ? AND attribute_value = ?
    ");
    $stmt->bind_param("is", $nodeId, $attributeValue);
    $stmt->execute();
    $result = $stmt->get_result();
    $childNode = $result->fetch_assoc();
    
    if ($childNode) {
        return $this->traverseTree($data, $childNode['id']);
    }
    
    // Return default jika tidak ada child node yang cocok
    // Ini bisa terjadi jika data test memiliki nilai yang tidak ada di training
    return 'IPA'; // Atau 'IPS' berdasarkan mayoritas
}
}
?>