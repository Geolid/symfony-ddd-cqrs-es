<?php

declare(strict_types=1);

require dirname(__DIR__, 2).'/shared/reference.php';
require dirname(__DIR__, 2).'/shared/store.php';

$rawBody = file_get_contents('php://input') ?: '';
$body = json_decode($rawBody ?: '[]', true, 512, \JSON_THROW_ON_ERROR);
$idempotencyKey = $_SERVER['HTTP_IDEMPOTENCY_KEY'] ?? null;

if (null !== $idempotencyKey) {
    $existing = fake_api_store_find('acme-pickups', 'idempotencyKey', $idempotencyKey);
    if (null !== $existing) {
        fake_api_respond(['trackingNumber' => $existing['reference']]);
        exit;
    }
}

$trackingNumber = fake_api_reference('ACME-LOCAL', $rawBody);

fake_api_store_mutate('acme-pickups', static function (array $records) use ($trackingNumber, $idempotencyKey, $body): array {
    $records[$trackingNumber] = [
        'reference' => $trackingNumber,
        'idempotencyKey' => $idempotencyKey,
        'clientReferenceId' => (string) ($body['clientReferenceId'] ?? ''),
        'status' => 'requested',
        'createdAt' => gmdate('c'),
    ];

    return $records;
});

fake_api_respond(['trackingNumber' => $trackingNumber]);
