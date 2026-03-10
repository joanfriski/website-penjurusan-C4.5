<?php
require_once 'DecisionTree.php';

class ModelTrainer {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    // Method untuk mengambil data training
    public function getTrainingData() {
        $stmt = $this->conn->prepare("SELECT * FROM training_data WHERE is_training = 1");
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    // Method untuk menghitung akurasi model
    public function calculateAccuracy($modelVersion) {
        // Ambil data test
        $stmt = $this->conn->prepare("SELECT * FROM training_data WHERE is_training = 0");
        $stmt->execute();
        $result = $stmt->get_result();
        $testData = $result->fetch_all(MYSQLI_ASSOC);
        
        if (empty($testData)) {
            return null;
        }
        
        $correct = 0;
        $total = count($testData);
        $tree = new DecisionTree($this->conn, $modelVersion);
        
        foreach ($testData as $data) {
            $prediction = $tree->predict($data);
            if ($prediction == $data['hasil_aktual']) {
                $correct++;
            }
        }
        
        return ($correct / $total) * 100;
    }
    
    // Method untuk menyimpan evaluasi model
    public function saveEvaluation($modelVersion, $accuracy, $total) {
        $stmt = $this->conn->prepare("
            INSERT INTO model_evaluation (model_version, accuracy, dataset_size, evaluation_date) 
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->bind_param("sdi", $modelVersion, $accuracy, $total);
        $stmt->execute();
    }
}
?>