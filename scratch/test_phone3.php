<?php
$str = "8801910‑703190";
for ($i = 0; $i < strlen($str); $i++) {
    echo $str[$i] . " (" . dechex(ord($str[$i])) . ")\n";
}
