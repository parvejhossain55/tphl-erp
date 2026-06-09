<?php
$files = [
    'public/assets/css/style.css',
    'public/assets/css/main.css'
];

foreach ($files as $file) {
    $path = __DIR__ . '/../' . $file;
    if (!file_exists($path)) continue;
    $content = file_get_contents($path);
    $lines = explode("\n", $content);
    
    echo "--- File: $file ---\n";
    // Search for blocks that look like selector { ... } where selector has body
    // and block contains display: flex
    $len = strlen($content);
    $offset = 0;
    while (($pos = strpos($content, '{', $offset)) !== false) {
        // find matching }
        $end = strpos($content, '}', $pos);
        if ($end === false) break;
        
        $selector = substr($content, $offset, $pos - $offset);
        $body = substr($content, $pos + 1, $end - $pos - 1);
        
        // Check if selector matches body (as a tag, not part of a class like .modal-body)
        // We look for word boundary body
        if (preg_match('/\bbody\b/i', $selector) && !preg_match('/\.body/i', $selector) && !preg_match('/-body\b/i', $selector)) {
            if (strpos($body, 'display') !== false && strpos($body, 'flex') !== false) {
                $line = substr_count(substr($content, 0, $pos), "\n") + 1;
                echo "Line $line: Selector: " . trim($selector) . "\n";
                echo "Body:\n" . trim($body) . "\n\n";
            }
        }
        $offset = $end + 1;
    }
}
