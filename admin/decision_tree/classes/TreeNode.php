<?php
class TreeNode {
    public $attribute;
    public $value;
    public $children = [];
    public $isLeaf = false;
    public $prediction;
    public $entropy;
    public $gain;
    
    public function __construct($attribute = null, $value = null, $isLeaf = false, $prediction = null) {
        $this->attribute = $attribute;
        $this->value = $value;
        $this->isLeaf = $isLeaf;
        $this->prediction = $prediction;
    }
    
    public function addChild($node) {
        $this->children[] = $node;
    }
}
?>