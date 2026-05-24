<?php
$a = null;
echo htmlspecialchars($a['b'] ?? '');
echo htmlspecialchars($a['c'] ?? null);
