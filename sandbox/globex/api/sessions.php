<?php

declare(strict_types=1);

require dirname(__DIR__, 2).'/shared/reference.php';
require dirname(__DIR__, 2).'/shared/request.php';
require dirname(__DIR__, 2).'/shared/store.php';

if ('GET' === $_SERVER['REQUEST_METHOD']) {
    $reference = filter_var($_GET['reference'] ?? '', \FILTER_UNSAFE_RAW) ?: '';
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
    exit;
}

$rawBody = fake_api_read_raw_body();
$body = fake_api_decode_json_body($rawBody);

$idempotencyKey = fake_api_read_idempotency_key();

$existing = fake_api_find_existing_by_idempotency_key('globex-charges', $idempotencyKey);
if (null !== $existing) {
    fake_api_respond([
        'id' => $existing['reference'],
        'url' => $existing['checkoutUrl'],
    ]);
    exit;
}

$id = fake_api_reference('GLBX-LOCAL', $rawBody);

$url = rtrim((string) getenv('GLOBEX_CHECKOUT_BASE_URL'), '/').'/pay/'.$id.'?'.http_build_query([
    'total' => filter_var($body['amountInCents'] ?? 0, \FILTER_VALIDATE_INT) ?: 0,
    'returnUrl' => filter_var($body['success_url'] ?? '', \FILTER_UNSAFE_RAW) ?: '',
]);

fake_api_store_mutate('globex-charges', static function (array $records) use ($id, $idempotencyKey, $url, $body): array {
    $records[$id] = [
        'reference' => $id,
        'idempotencyKey' => $idempotencyKey,
        'merchantReference' => filter_var($body['client_reference_id'] ?? '', \FILTER_UNSAFE_RAW) ?: '',
        'checkoutUrl' => $url,
        'amountInCents' => filter_var($body['amountInCents'] ?? 0, \FILTER_VALIDATE_INT) ?: 0,
        'status' => 'requested',
        'createdAt' => gmdate('c'),
    ];

    return $records;
});

fake_api_respond([
    'id' => $id,
    'url' => $url,
]);
