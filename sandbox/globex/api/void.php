<?php

declare(strict_types=1);

require dirname(__DIR__, 2).'/shared/reference.php';
require dirname(__DIR__, 2).'/shared/request.php';
require dirname(__DIR__, 2).'/shared/store.php';

$rawBody = fake_api_read_raw_body();
$body = fake_api_decode_json_body($rawBody);
$reference = filter_var($body['reference'] ?? '', \FILTER_UNSAFE_RAW) ?: '';

fake_api_store_transition_status('globex-charges', $reference, 'voided');

fake_api_respond([
    'reference' => $reference,
    'status' => 'voided',
]);
