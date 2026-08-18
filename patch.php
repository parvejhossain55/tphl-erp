<?php
$content = file_get_contents('app/models/AccountClient.php');

// Replace standard aggregate queries
$content = str_replace('WHERE lp.booking_id = lb.id', 'WHERE lp.is_trashed = 0 AND lp.booking_id = lb.id', $content);

// Replace getTransactionsByBookingId and others
$content = str_replace('WHERE lp.booking_id = ?', 'WHERE lp.is_trashed = 0 AND lp.booking_id = ?', $content);

file_put_contents('app/models/AccountClient.php', $content);
echo "Patch applied successfully.\n";
