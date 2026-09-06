<?php

declare(strict_types=1);

require dirname(__DIR__, 2).'/shared/reference.php';
require dirname(__DIR__, 2).'/shared/store.php';

$reference = filter_var($_GET['reference'] ?? '', \FILTER_UNSAFE_RAW) ?: '';

fake_api_store_transition_status('globex-charges', $reference, 'refunding');

fake_api_respond([
    'reference' => $reference,
    'status' => 'refunding',
]);
