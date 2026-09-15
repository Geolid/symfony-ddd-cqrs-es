<?php

declare(strict_types=1);

require dirname(__DIR__, 2).'/shared/reference.php';
require dirname(__DIR__, 2).'/shared/request.php';
require dirname(__DIR__, 2).'/shared/store.php';
require dirname(__DIR__, 1).'/shared/config.php';
require dirname(__DIR__, 1).'/shared/line_items.php';

if ('GET' === $_SERVER['REQUEST_METHOD']) {
    $reference = fake_api_read_query('reference');
    $record = fake_api_require_record(GLOBEX_PROVIDER, $reference);

    fake_api_respond([
        'reference' => $reference,
        'status' => $record['status'],
    ]);
    exit;
}

$rawBody = fake_api_read_raw_body();
$body = fake_api_decode_json_body($rawBody);

$idempotencyKey = fake_api_read_idempotency_key();

$existing = fake_api_find_existing_by_idempotency_key(GLOBEX_PROVIDER, $idempotencyKey);
if (null !== $existing) {
    fake_api_respond([
        'id' => $existing['reference'],
        'url' => $existing['url'],
    ]);
    exit;
}

$lineItems = is_array($body['line_items'] ?? null) ? $body['line_items'] : [];

if ([] === $lineItems) {
    http_response_code(400);
    fake_api_respond(['error' => 'empty_line_items']);
    exit;
}

if (count(fake_globex_line_items_currencies($lineItems)) > 1) {
    http_response_code(400);
    fake_api_respond(['error' => 'currency_mismatch']);
    exit;
}

$id = fake_api_reference('GLBX-LOCAL', $rawBody);
$url = rtrim((string) getenv('GLOBEX_CHECKOUT_BASE_URL'), '/').'/pay/'.$id;

fake_api_store_mutate(GLOBEX_PROVIDER, static function (array $records) use ($id, $idempotencyKey, $url, $body, $lineItems): array {
    $records[$id] = [
        'reference' => $id,
        'idempotency_key' => $idempotencyKey,
        'client_reference_id' => fake_api_read_body_field($body, 'client_reference_id'),
        'url' => $url,
        'line_items' => $lineItems,
        'success_url' => fake_api_read_body_field($body, 'success_url'),
        'cancel_url' => fake_api_read_body_field($body, 'cancel_url'),
        'status' => 'requested',
        'created_at' => gmdate('c'),
    ];

    return $records;
});

fake_api_respond([
    'id' => $id,
    'url' => $url,
]);
