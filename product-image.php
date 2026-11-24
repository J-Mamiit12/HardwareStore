<?php
require __DIR__.'/includes/layout.php';

// SKU lang ang tinatanggap dito para laging sa tamang catalog item galing ang photo.
$code = param('code');
$items = products();
if ($items === false) {
    header('HTTP/1.1 503 Service Unavailable');
    exit;
}
foreach ($items as $item) {
    if ($item['product_code'] !== $code) continue;
    $path = productImagePath($item);
    if (!$path) break;
    $types = array('png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'webp' => 'image/webp');
    header('Content-Type: '.$types[strtolower(pathinfo($path, PATHINFO_EXTENSION))]);
    header('Cache-Control: private, max-age=300');
    readfile($path);
    exit;
}
header('HTTP/1.1 404 Not Found');
