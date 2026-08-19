<?php

declare(strict_types=1);

require dirname(__DIR__, 2).'/shared/reference.php';
require dirname(__DIR__, 2).'/shared/store.php';

$reference = (string) ($_GET['reference'] ?? '');

$record = fake_api_store_read('acme-pickups')[$reference] ?? fake_api_store_read('acme-returns')[$reference] ?? null;

if (null === $record) {
    http_response_code(404);
    fake_api_respond(['error' => 'unknown_reference']);
    exit;
}

fake_api_respond([
    'reference' => $reference,
    'status' => $record['status'],
]);
