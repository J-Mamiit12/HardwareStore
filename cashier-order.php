<?php
require __DIR__.'/includes/database.php';
header('Content-Type: text/plain; charset=UTF-8');
$reference = isset($_GET['reference']) && is_string($_GET['reference']) ? strtoupper(trim($_GET['reference'])) : '';
if (!preg_match('/^[A-F0-9]{20}$/D', $reference)) {
    header('HTTP/1.1 400 Bad Request');
    exit('Invalid pre-receipt reference.');
}
$db = catalogConnection();
$stmt = $db ? $db->prepare('SELECT k.items,s.sale_id FROM kiosk_orders k LEFT JOIN sales s ON s.receipt_no=k.reference COLLATE utf8mb4_unicode_ci WHERE k.reference=?') : false;
if (!$stmt) {
    header('HTTP/1.1 503 Service Unavailable');
    exit('Order database unavailable.');
}
$stmt->bind_param('s', $reference);
if (!$stmt->execute()) {
    header('HTTP/1.1 503 Service Unavailable');
    exit('Order lookup unavailable.');
}
$stmt->bind_result($items, $sale);
if (!$stmt->fetch()) {
    header('HTTP/1.1 404 Not Found');
    exit('Pre-receipt not found.');
}
if ($sale !== null) {
    header('HTTP/1.1 409 Conflict');
    exit('This pre-receipt has already been processed.');
}
// Code, quantity at centavos lang ang kailangan ng NetBeans checkout.
foreach (json_decode($items, true) as $line) {
    echo rawurlencode($line['code'])."\t".$line['qty']."\t".$line['price_cents']."\n";
}
