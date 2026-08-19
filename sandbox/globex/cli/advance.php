<?php

declare(strict_types=1);

require dirname(__DIR__, 2).'/shared/store.php';
require dirname(__DIR__, 2).'/shared/webhook_caller.php';

$reference = $argv[1] ?? '';

if ('' === $reference) {
    fwrite(\STDERR, "Usage: advance.php <reference>\n");
    exit(1);
}

$charges = fake_api_store_read('globex-charges');

if (!isset($charges[$reference])) {
    fwrite(\STDERR, sprintf("Unknown reference \"%s\".\n", $reference));
    exit(1);
}

$status = $charges[$reference]['status'];
assert(is_string($status));

if ('refunding' !== $status) {
    fwrite(\STDERR, sprintf("Charge \"%s\" is \"%s\", not awaiting a refund confirmation.\n", $reference, $status));
    exit(1);
}

fake_api_call_webhook('PAYMENT_WEBHOOK_SECRET', 'X-Payment-Signature', 'payment-refunded', ['paymentReference' => $reference]);
fake_api_store_transition_status('globex-charges', $reference, 'refunded');

echo sprintf("Charge \"%s\": refunding -> refunded.\n", $reference);
