<?php
$content = file_get_contents('app/views/project-other-costs/index.php');
$tokens = token_get_all($content);
$openIfs = [];
$inIf = false;
$ifLine = 0;

for ($i = 0; $i < count($tokens); $i++) {
    $token = $tokens[$i];
    
    if (is_array($token)) {
        $text = strtolower($token[1]);
        if ($text === 'if' || $text === 'elseif') {
            // scan forward to see if it ends with colon
            $j = $i + 1;
            $line = $token[2];
            while ($j < count($tokens)) {
                $t = $tokens[$j];
                if (is_array($t) && $t[0] === T_WHITESPACE) {
                    $j++; continue;
                }
                if (is_string($t) && $t === ':') {
                    if ($text === 'if') {
                        $openIfs[] = $line;
                    } else if ($text === 'elseif') {
                        // just replace the top
                        array_pop($openIfs);
                        $openIfs[] = $line;
                    }
                    break;
                }
                if (is_string($t) && $t === '{') break;
                if (is_string($t) && $t === ';') break;
                $j++;
            }
        } elseif ($text === 'endif') {
            array_pop($openIfs);
        }
    }
}
echo "Open IFs line numbers:\n";
print_r($openIfs);
