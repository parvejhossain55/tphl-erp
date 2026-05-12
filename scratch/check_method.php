<?php
$content = file_get_contents('c:\\xampp\\htdocs\\TPHL-ERP\\app\\models\\AccountShopClient.php');
if (strpos($content, 'getTransactionsByBookingId') !== false) {
    echo "YES! AccountShopClient HAS getTransactionsByBookingId!";
} else {
    echo "NO! AccountShopClient does not have it.";
}
