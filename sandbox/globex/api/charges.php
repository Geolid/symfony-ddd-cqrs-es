<?php

declare(strict_types=1);

require dirname(__DIR__, 2).'/shared/reference.php';

$rawBody = file_get_contents('php://input') ?: '';
$body = json_decode($rawBody ?: '[]', true, 512, \JSON_THROW_ON_ERROR);

$chargeReference = fake_api_reference('GLBX-LOCAL', $rawBody);

$checkoutUrl = rtrim((string) getenv('FAKE_CHECKOUT_BASE_URL'), '/').'/?'.http_build_query([
    'ref' => $chargeReference,
    'items' => (int) ($body['itemCount'] ?? 0),
    'total' => (int) ($body['amountInCents'] ?? 0),
    'returnUrl' => (string) ($body['returnUrl'] ?? ''),
]);

fake_api_respond([
    'chargeReference' => $chargeReference,
    'checkoutUrl' => $checkoutUrl,
]);
