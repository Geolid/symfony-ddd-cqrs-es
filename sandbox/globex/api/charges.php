<?php

declare(strict_types=1);

require dirname(__DIR__, 2).'/shared/reference.php';
require dirname(__DIR__, 2).'/shared/store.php';

$rawBody = file_get_contents('php://input') ?: '';
$body = json_decode($rawBody ?: '[]', true, 512, \JSON_THROW_ON_ERROR);

$idempotencyKey = $_SERVER['HTTP_IDEMPOTENCY_KEY'] ?? null;

if (null !== $idempotencyKey) {
    $existing = fake_api_store_find('globex-charges', 'idempotencyKey', $idempotencyKey);
    if (null !== $existing) {
        fake_api_respond([
            'chargeReference' => $existing['reference'],
            'checkoutUrl' => $existing['checkoutUrl'],
        ]);
        exit;
    }
}

$chargeReference = fake_api_reference('GLBX-LOCAL', $rawBody);

$checkoutUrl = rtrim((string) getenv('FAKE_CHECKOUT_BASE_URL'), '/').'/?'.http_build_query([
    'ref' => $chargeReference,
    'total' => (int) ($body['amountInCents'] ?? 0),
    'returnUrl' => (string) ($body['returnUrl'] ?? ''),
]);

fake_api_store_mutate('globex-charges', static function (array $records) use ($chargeReference, $idempotencyKey, $checkoutUrl, $body): array {
    $records[$chargeReference] = [
        'reference' => $chargeReference,
        'idempotencyKey' => $idempotencyKey,
        'clientReferenceId' => (string) ($body['clientReferenceId'] ?? ''),
        'checkoutUrl' => $checkoutUrl,
        'amountInCents' => (int) ($body['amountInCents'] ?? 0),
        'status' => 'requested',
        'createdAt' => gmdate('c'),
    ];

    return $records;
});

fake_api_respond([
    'chargeReference' => $chargeReference,
    'checkoutUrl' => $checkoutUrl,
]);
