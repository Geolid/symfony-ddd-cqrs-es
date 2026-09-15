<?php

declare(strict_types=1);

require dirname(__DIR__, 2).'/shared/reference.php';
require dirname(__DIR__, 2).'/shared/request.php';
require dirname(__DIR__, 2).'/shared/store.php';
require dirname(__DIR__, 1).'/shared/config.php';

$rawBody = fake_api_read_raw_body();
$body = fake_api_decode_json_body($rawBody);
$idempotencyKey = fake_api_read_idempotency_key();

$existing = fake_api_find_existing_by_idempotency_key(ACME_PROVIDER, $idempotencyKey, 'idempotency_key');
if (null !== $existing) {
    fake_api_respond(['tracking_number' => $existing['reference']]);
    exit;
}

$trackingNumber = fake_api_reference('ACME-LOCAL', $rawBody);

fake_api_store_mutate(ACME_PROVIDER, static function (array $records) use ($trackingNumber, $idempotencyKey, $body): array {
    $records[$trackingNumber] = [
        'reference' => $trackingNumber,
        'idempotency_key' => $idempotencyKey,
        'reference_number' => filter_var($body['reference_number'] ?? '', \FILTER_UNSAFE_RAW) ?: '',
        'status' => 'requested',
        'created_at' => gmdate('c'),
    ];

    return $records;
});

fake_api_respond(['tracking_number' => $trackingNumber]);
