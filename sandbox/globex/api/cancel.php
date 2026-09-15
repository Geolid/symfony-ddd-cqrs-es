<?php

declare(strict_types=1);

require dirname(__DIR__, 2).'/shared/reference.php';
require dirname(__DIR__, 2).'/shared/request.php';
require dirname(__DIR__, 2).'/shared/store.php';
require dirname(__DIR__, 1).'/shared/config.php';

$reference = fake_api_read_query('reference');
fake_api_require_record(GLOBEX_PROVIDER, $reference);

fake_api_store_transition_status(GLOBEX_PROVIDER, $reference, 'voided');

fake_api_respond([
    'reference' => $reference,
    'status' => 'voided',
]);
