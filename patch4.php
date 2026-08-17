<?php
$content = file_get_contents('app/routes/accounts.php');

$routes = <<<'EOD'
    'trash-client-payment' => ['controller' => 'AccountClientController', 'method' => 'trashTransaction'],
    'trash-client-payment-list' => ['controller' => 'AccountClientController', 'method' => 'trashList'],
    'restore-client-payment' => ['controller' => 'AccountClientController', 'method' => 'restoreTransaction'],

];
EOD;

$content = preg_replace('/\];\s*$/', $routes, $content);
file_put_contents('app/routes/accounts.php', $content);
echo "Routes added successfully.\n";
