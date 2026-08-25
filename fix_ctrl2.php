<?php
$file = 'c:\xampp\htdocs\TPHL-ERP\app\controllers\PayrollController.php';
$content = file_get_contents($file);

$startPos = strpos($content, 'public function disburseBatch()');
$endPos = strpos($content, 'public function myApprovalRequests()', $startPos);

if ($startPos !== false && $endPos !== false) {
    $funcContent = substr($content, $startPos, $endPos - $startPos);
    
    // Add check to all generic prepares
    $funcContent = preg_replace(
        '/(\$[a-zA-Z0-9_]+)\s*=\s*\$this->db->prepare\((.*?)\);/s',
        "$1 = \$this->db->prepare($2);\r\n            if (!$1) throw new Exception(\"Database prepare failed: \" . \$this->db->error);",
        $funcContent
    );

    $funcContent = str_replace(
        "            \$this->db->rollback();\r\n            echo json_encode(['success' => false,",
        "            \$this->db->rollback();\r\n            ob_clean(); header('Content-Type: application/json');\r\n            echo json_encode(['success' => false,",
        $funcContent
    );
    
    $funcContent = str_replace(
        "            echo json_encode([\r\n                'success' => true,",
        "            ob_clean(); header('Content-Type: application/json');\r\n            echo json_encode([\r\n                'success' => true,",
        $funcContent
    );

    $content = substr_replace($content, $funcContent, $startPos, $endPos - $startPos);
    file_put_contents($file, $content);
    echo "Added exception checks.";
} else {
    echo "Could not find function bounds.";
}
