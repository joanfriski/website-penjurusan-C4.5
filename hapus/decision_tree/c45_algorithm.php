<?php
class C45Algorithm {
    private $dataset = [];
    private $attributes = [];
    private $target = '';
    private $tree = null;
    
    public function setDataset($dataset) {
        $this->dataset = $dataset;
    }
    
    public function setAttributes($attributes) {
        $this->attributes = $attributes;
    }
    
    public function setTarget($target) {
        $this->target = $target;
    }
    
    public function buildTree() {
        $this->tree = $this->buildDecisionTree($this->dataset, $this->attributes, '0');
        return $this->tree;
    }
    
    private function buildDecisionTree($dataset, $attributes, $nodeId, $parentId = null, $parentValue = null, $level = 0) {
        // Jika dataset kosong, kembalikan null
        if(empty($dataset)) {
            return null;
        }
        
        // Hitung jumlah data per kelas (jurusan)
        $targetCounts = $this->countTargetValues($dataset);
        
        // Jika semua data memiliki kelas yang sama, buat leaf node
        if(count($targetCounts) === 1) {
            $decision = array_keys($targetCounts)[0];
            return [
                'node_id' => $nodeId,
                'parent_id' => $parentId,
                'attribute' => null,
                'value' => $parentValue,
                'is_leaf' => true,
                'decision' => $decision,
                'entropy' => 0,
                'level' => $level
            ];
        }
        
        // Jika tidak ada atribut yang tersisa, buat leaf node dengan kelas terbanyak
        if(empty($attributes)) {
            $decision = $this->findMajorityClass($targetCounts);
            return [
                'node_id' => $nodeId,
                'parent_id' => $parentId,
                'attribute' => null,
                'value' => $parentValue,
                'is_leaf' => true,
                'decision' => $decision,
                'entropy' => $this->calculateEntropy($dataset),
                'level' => $level
            ];
        }
        
        // Pilih atribut terbaik berdasarkan information gain
        $bestAttributeInfo = $this->selectBestAttribute($dataset, $attributes);
        $bestAttribute = $bestAttributeInfo['attribute'];
        $gain = $bestAttributeInfo['gain'];
        
        // Buat node untuk atribut terbaik
        $node = [
            'node_id' => $nodeId,
            'parent_id' => $parentId,
            'attribute' => $bestAttribute,
            'value' => $parentValue,
            'is_leaf' => false,
            'decision' => null,
            'entropy' => $bestAttributeInfo['entropy'],
            'gain' => $gain,
            'level' => $level,
            'children' => []
        ];
        
        // Hapus atribut terbaik dari daftar atribut
        $remainingAttributes = array_diff($attributes, [$bestAttribute]);
        
        // Untuk setiap nilai atribut, buat cabang baru
        $attributeValues = $this->getAttributeValues($dataset, $bestAttribute);
        $childIndex = 1;
        
        foreach($attributeValues as $value) {
            // Filter dataset berdasarkan nilai atribut
            $subDataset = array_filter($dataset, function($data) use ($bestAttribute, $value) {
                return $data[$bestAttribute] === $value;
            });
            
            // Jika subset tidak kosong, rekursi untuk membuat subtree
            if(!empty($subDataset)) {
                $childNodeId = $nodeId . '.' . $childIndex;
                $child = $this->buildDecisionTree(
                    $subDataset, 
                    $remainingAttributes, 
                    $childNodeId, 
                    $nodeId, 
                    $value,
                    $level + 1
                );
                
                if($child) {
                    $node['children'][] = $child;
                }
            }
            
            $childIndex++;
        }
        
        return $node;
    }
    
    private function countTargetValues($dataset) {
        $counts = [];
        foreach($dataset as $data) {
            $targetValue = $data[$this->target];
            if(!isset($counts[$targetValue])) {
                $counts[$targetValue] = 0;
            }
            $counts[$targetValue]++;
        }
        return $counts;
    }
    
    private function findMajorityClass($targetCounts) {
        arsort($targetCounts);
        return array_keys($targetCounts)[0];
    }
    
    private function calculateEntropy($dataset) {
        if(empty($dataset)) {
            return 0;
        }
        
        $totalSize = count($dataset);
        $targetCounts = $this->countTargetValues($dataset);
        
        $entropy = 0;
        foreach($targetCounts as $count) {
            $probability = $count / $totalSize;
            $entropy -= $probability * log($probability, 2);
        }
        
        return $entropy;
    }
    
    private function selectBestAttribute($dataset, $attributes) {
        $baseEntropy = $this->calculateEntropy($dataset);
        $totalSize = count($dataset);
        $bestAttribute = null;
        $bestGain = -1;
        
        foreach($attributes as $attribute) {
            $attributeValues = $this->getAttributeValues($dataset, $attribute);
            $attributeEntropy = 0;
            
            foreach($attributeValues as $value) {
                $subset = array_filter($dataset, function($data) use ($attribute, $value) {
                    return $data[$attribute] === $value;
                });
                
                $subsetSize = count($subset);
                $weight = $subsetSize / $totalSize;
                $attributeEntropy += $weight * $this->calculateEntropy($subset);
            }
            
            $gain = $baseEntropy - $attributeEntropy;
            
            if($gain > $bestGain) {
                $bestGain = $gain;
                $bestAttribute = $attribute;
            }
        }
        
        return [
            'attribute' => $bestAttribute,
            'gain' => $bestGain,
            'entropy' => $baseEntropy
        ];
    }
    
    private function getAttributeValues($dataset, $attribute) {
        $values = array_map(function($data) use ($attribute) {
            return $data[$attribute];
        }, $dataset);
        
        return array_unique($values);
    }
    
    public function saveTreeToDatabase($conn, $node = null) {
        if($node === null) {
            $node = $this->tree;
        }
        
        if($node) {
            // Simpan node saat ini ke database
            $nodeId = $node['node_id'];
            $parentId = $node['parent_id'];
            $attribute = $node['attribute'];
            $value = $node['value'];
            $isLeaf = $node['is_leaf'] ? 1 : 0;
            $decision = $node['decision'];
            $entropy = isset($node['entropy']) ? $node['entropy'] : 0;
            $gain = isset($node['gain']) ? $node['gain'] : 0;
            $level = $node['level'];
            
            $sql = "INSERT INTO decision_tree (node_id, parent_id, atribut, nilai_atribut, is_leaf, keputusan, entropy, gain, level) 
                    VALUES ('$nodeId', " . ($parentId ? "'$parentId'" : "NULL") . ", " . 
                    ($attribute ? "'$attribute'" : "NULL") . ", " . 
                    ($value ? "'$value'" : "NULL") . ", $isLeaf, " . 
                    ($decision ? "'$decision'" : "NULL") . ", $entropy, $gain, $level)";
            
            mysqli_query($conn, $sql);
            
            // Jika node memiliki anak, simpan juga anak-anaknya
            if(isset($node['children']) && is_array($node['children'])) {
                foreach($node['children'] as $child) {
                    $this->saveTreeToDatabase($conn, $child);
                }
            }
        }
    }
    
    public function classifyInstance($instance) {
        if(!$this->tree) {
            return null;
        }
        
        return $this->traverseTree($this->tree, $instance);
    }
    
    private function traverseTree($node, $instance) {
        // Jika node adalah leaf node, kembalikan keputusannya
        if($node['is_leaf']) {
            return $node['decision'];
        }
        
        // Ambil atribut dan nilai dari instance
        $attribute = $node['attribute'];
        $value = $instance[$attribute];
        
        // Cari child node yang sesuai dengan nilai atribut
        foreach($node['children'] as $child) {
            if($child['value'] === $value) {
                return $this->traverseTree($child, $instance);
            }
        }
        
        // Jika tidak ada child yang sesuai, gunakan majority class
        return $this->findMajorityDecision($node['children']);
    }
    
    private function findMajorityDecision($children) {
        $decisions = [];
        
        foreach($children as $child) {
            if($child['is_leaf']) {
                $decision = $child['decision'];
                if(!isset($decisions[$decision])) {
                    $decisions[$decision] = 0;
                }
                $decisions[$decision]++;
            }
        }
        
        arsort($decisions);
        return key($decisions);
    }
    
    public function generateRules() {
        if(!$this->tree) {
            return [];
        }
        
        $rules = [];
        $this->extractRules($this->tree, [], $rules);
        
        return $rules;
    }
    
    private function extractRules($node, $conditions, &$rules) {
        if($node['is_leaf']) {
            // Jika node adalah leaf, kita dapat membuat aturan
            $ruleCondition = '';
            
            if(empty($conditions)) {
                $ruleCondition = 'Semua kasus';
            } else {
                $ruleCondition = implode(' DAN ', $conditions);
            }
            
            $rules[] = [
                'condition' => $ruleCondition,
                'decision' => $node['decision']
            ];
            
            return;
        }
        
        // Jika bukan leaf, tambahkan kondisi untuk setiap anak
        foreach($node['children'] as $child) {
            $newConditions = $conditions;
            
            if($child['value'] !== null) {
                $attributeLabel = $this->getAttributeLabel($node['attribute']);
                $valueLabel = $this->getValueLabel($node['attribute'], $child['value']);
                
                $newConditions[] = "$attributeLabel = $valueLabel";
            }
            
            $this->extractRules($child, $newConditions, $rules);
        }
    }
    
    private function getAttributeLabel($attribute) {
        $labels = [
            'nilai_ipa' => 'Nilai IPA',
            'nilai_ips' => 'Nilai IPS',
            'skor_iq' => 'Test IQ',
            'minat' => 'Minat Siswa'
        ];
        
        return isset($labels[$attribute]) ? $labels[$attribute] : $attribute;
    }
    
    private function getValueLabel($attribute, $value) {
        if($attribute === 'minat') {
            return $value;
        }
        
        return $value;
    }
}