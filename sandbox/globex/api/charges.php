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
        'chargeReference' => $existing['reference'],
        'checkoutUrl' => $existing['checkoutUrl'],
    ]);
    exit;
}

$chargeReference = fake_api_reference('GLBX-LOCAL', $rawBody);

$checkoutUrl = rtrim((string) getenv('GLOBEX_CHECKOUT_BASE_URL'), '/').'/pay/'.$chargeReference.'?'.http_build_query([
    'total' => filter_var($body['amountInCents'] ?? 0, \FILTER_VALIDATE_INT) ?: 0,
    'returnUrl' => filter_var($body['returnUrl'] ?? '', \FILTER_UNSAFE_RAW) ?: '',
]);

fake_api_store_mutate('globex-charges', static function (array $records) use ($chargeReference, $idempotencyKey, $checkoutUrl, $body): array {
    $records[$chargeReference] = [
        'reference' => $chargeReference,
        'idempotencyKey' => $idempotencyKey,
        'clientReferenceId' => filter_var($body['clientReferenceId'] ?? '', \FILTER_UNSAFE_RAW) ?: '',
        'checkoutUrl' => $checkoutUrl,
        'amountInCents' => filter_var($body['amountInCents'] ?? 0, \FILTER_VALIDATE_INT) ?: 0,
        'status' => 'requested',
        'createdAt' => gmdate('c'),
    ];

    return $records;
});

fake_api_respond([
    'chargeReference' => $chargeReference,
    'checkoutUrl' => $checkoutUrl,
]);
