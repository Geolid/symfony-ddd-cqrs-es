<?php

declare(strict_types=1);

require dirname(__DIR__, 2).'/shared/store.php';
require dirname(__DIR__, 1).'/shared/config.php';

$reference = $argv[1] ?? '';

if ('' === $reference) {
    fwrite(\STDERR, "Usage: decline.php <reference>\n");
    exit(1);
}

$sessions = fake_api_store_read(GLOBEX_PROVIDER);

if (!isset($sessions[$reference])) {
    fwrite(\STDERR, sprintf("Unknown reference \"%s\".\n", $reference));
    exit(1);
}

$status = $sessions[$reference]['status'];
assert(is_string($status));

if ('authorized' !== $status) {
    fwrite(\STDERR, sprintf("Session \"%s\" is \"%s\", not awaiting capture.\n", $reference, $status));
    exit(1);
}

fake_api_store_mutate(GLOBEX_PROVIDER, static function (array $records) use ($reference): array {
    if (isset($records[$reference])) {
        $records[$reference]['capture_outcome'] = 'declined';
    }

    return $records;
});

echo sprintf("Session \"%s\": armed to decline on next capture.\n", $reference);
