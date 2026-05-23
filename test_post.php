<?php
$ch = curl_init('http://localhost/TPHL-ERP/con-purchase-order-approve/1');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, "approval_note=Test");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// We need to send session cookies to be logged in as PM
// Let's do this directly by modifying the DB or testing the query.
