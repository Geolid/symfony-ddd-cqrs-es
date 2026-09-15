<?php

declare(strict_types=1);

require dirname(__DIR__, 2).'/shared/reference.php';
require dirname(__DIR__, 2).'/shared/request.php';
require dirname(__DIR__, 2).'/shared/store.php';
require dirname(__DIR__, 1).'/shared/config.php';

$reference = fake_api_read_query('reference');
$record = fake_api_require_record(ACME_PROVIDER, $reference);

fake_api_respond([
    'reference' => $reference,
    'status' => $record['status'],
]);
