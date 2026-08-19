<?php

declare(strict_types=1);

require dirname(__DIR__, 2).'/shared/reference.php';
require dirname(__DIR__, 2).'/shared/request.php';
require dirname(__DIR__, 2).'/shared/store.php';

$rawBody = fake_api_read_raw_body();
$body = fake_api_decode_json_body($rawBody);
$idempotencyKey = fake_api_read_idempotency_key();

$existing = fake_api_find_existing_by_idempotency_key('acme-pickups', $idempotencyKey);
if (null !== $existing) {
    fake_api_respond(['trackingNumber' => $existing['reference']]);
    exit;
}

$trackingNumber = fake_api_reference('ACME-LOCAL', $rawBody);

fake_api_store_mutate('acme-pickups', static function (array $records) use ($trackingNumber, $idempotencyKey, $body): array {
    $records[$trackingNumber] = [
        'reference' => $trackingNumber,
        'idempotencyKey' => $idempotencyKey,
        'clientReferenceId' => filter_var($body['clientReferenceId'] ?? '', \FILTER_UNSAFE_RAW) ?: '',
        'status' => 'requested',
        'createdAt' => gmdate('c'),
    ];

    return $records;
});

fake_api_respond(['trackingNumber' => $trackingNumber]);
