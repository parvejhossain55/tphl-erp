<?php
$file = 'c:\xampp\htdocs\TPHL-ERP\app\controllers\PayrollController.php';
$content = file_get_contents($file);

// Extract just the disburseBatch method to safely replace prepare statements
$startPos = strpos($content, 'public function disburseBatch()');
$endPos = strpos($content, 'public function generateMultipleSalaryCertificate', $startPos);

if ($startPos !== false && $endPos !== false) {
    $funcContent = substr($content, $startPos, $endPos - $startPos);
    
    // Add check to all generic prepares
    // First, find all lines with `$this->db->prepare(` and the assignment variable, e.g., $stmt = $this->db->prepare
    $funcContent = preg_replace(
        '/(\$[a-zA-Z0-9_]+)\s*=\s*\$this->db->prepare\((.*?)\);/s',
        "$1 = \$this->db->prepare($2);\r\n            if (!$1) throw new Exception(\"Database prepare failed: \" . \$this->db->error);",
        $funcContent
    );

    // Also let's set header before json_encode in the catch block to be strictly safe
    $funcContent = str_replace(
        "            \$this->db->rollback();\r\n            echo json_encode(['success' => false,",
        "            \$this->db->rollback();\r\n            header('Content-Type: application/json');\r\n            ob_clean();\r\n            echo json_encode(['success' => false,",
        $funcContent
    );
    // and for success
    $funcContent = str_replace(
        "            echo json_encode([\r\n                'success' => true,",
        "            header('Content-Type: application/json');\r\n            ob_clean();\r\n            echo json_encode([\r\n                'success' => true,",
        $funcContent
    );
    // and for early errors
    $funcContent = str_replace(
        "            echo json_encode(['success' => false, 'message' => 'No payslips selected",
        "            header('Content-Type: application/json');\r\n            echo json_encode(['success' => false, 'message' => 'No payslips selected",
        $funcContent
    );
    $funcContent = str_replace(
        "            echo json_encode(['success' => false, 'message' => 'Invalid payslips",
        "            header('Content-Type: application/json');\r\n            echo json_encode(['success' => false, 'message' => 'Invalid payslips",
        $funcContent
    );
    $funcContent = str_replace(
        "            echo json_encode(['success' => false, 'message' => 'No eligible payslips",
        "            header('Content-Type: application/json');\r\n            echo json_encode(['success' => false, 'message' => 'No eligible payslips",
        $funcContent
    );

    $content = substr_replace($content, $funcContent, $startPos, $endPos - $startPos);
    file_put_contents($file, $content);
    echo "Added exception checks & strict JSON headers.";
} else {
    echo "Could not find function bounds.";
}
