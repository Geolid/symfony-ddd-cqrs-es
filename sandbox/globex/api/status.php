<?php

declare(strict_types=1);

require dirname(__DIR__, 2).'/shared/reference.php';
require dirname(__DIR__, 2).'/shared/store.php';

$reference = (string) ($_GET['reference'] ?? '');
$records = fake_api_store_read('globex-charges');

if (!isset($records[$reference])) {
    http_response_code(404);
    fake_api_respond(['error' => 'unknown_reference']);
    exit;
}

fake_api_respond([
    'reference' => $reference,
    'status' => $records[$reference]['status'],
]);
