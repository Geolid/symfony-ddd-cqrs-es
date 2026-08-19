<?php

declare(strict_types=1);

require dirname(__DIR__, 2).'/shared/reference.php';
require dirname(__DIR__, 2).'/shared/store.php';

$rawBody = file_get_contents('php://input') ?: '';
$body = json_decode($rawBody ?: '[]', true, 512, \JSON_THROW_ON_ERROR);
$reference = (string) ($body['reference'] ?? '');

fake_api_store_mutate('globex-charges', static function (array $records) use ($reference): array {
    if (isset($records[$reference])) {
        $records[$reference]['status'] = 'voided';
    }

    return $records;
});

fake_api_respond([
    'reference' => $reference,
    'status' => 'voided',
]);
