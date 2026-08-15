<?php

declare(strict_types=1);

require dirname(__DIR__, 2).'/shared/reference.php';

$rawBody = file_get_contents('php://input') ?: '';
$body = json_decode($rawBody ?: '[]', true, 512, \JSON_THROW_ON_ERROR);

fake_api_respond([
    'reference' => (string) ($body['reference'] ?? ''),
    'status' => 'refunding',
]);
