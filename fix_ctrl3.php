<?php
$file = 'c:\xampp\htdocs\TPHL-ERP\app\controllers\PayrollController.php';
$content = file_get_contents($file);

// Replace ALL $this->db->prepare inside PayrollController but ensure it only does it once (if not already done)
// Wait, doing this globally is actually standard practice for robust error handling!
$content = preg_replace(
    '/(\$[a-zA-Z0-9_]+)\s*=\s*\$this->db->prepare\((.*?)\);(?!\s*if)/s',
    "$1 = \$this->db->prepare($2);\r\n        if (!$1) throw new Exception(\"Database prepare failed: \" . \$this->db->error);",
    $content
);

file_put_contents($file, $content);
echo "Globally added exception checks to PayrollController.";
