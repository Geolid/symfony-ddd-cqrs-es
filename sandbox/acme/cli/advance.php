<?php

declare(strict_types=1);

require dirname(__DIR__, 2).'/shared/store.php';
require dirname(__DIR__, 2).'/shared/webhook_caller.php';

$reference = $argv[1] ?? '';

if ('' === $reference) {
    fwrite(\STDERR, "Usage: advance.php <reference>\n");
    exit(1);
}

$shipments = fake_api_store_read('acme-shipments');

if (isset($shipments[$reference])) {
    $status = $shipments[$reference]['status'];
    assert(is_string($status));

    $next = match ($status) {
        'requested' => ['dispatched', 'carrier-pickup-confirmed'],
        'dispatched' => ['delivered', 'carrier-delivery'],
        default => null,
    };

    if (null === $next) {
        fwrite(\STDERR, sprintf("Shipment \"%s\" is \"%s\", nothing to advance.\n", $reference, $status));
        exit(1);
    }

    [$nextStatus, $eventType] = $next;

    fake_api_call_webhook('CARRIER_WEBHOOK_SECRET', 'X-Carrier-Signature', $eventType, ['trackingReference' => $reference]);
    fake_api_store_transition_status('acme-shipments', $reference, $nextStatus);

    echo sprintf("Shipment \"%s\": %s -> %s.\n", $reference, $status, $nextStatus);
    exit(0);
}

$returns = fake_api_store_read('acme-returns');

if (isset($returns[$reference])) {
    $status = $returns[$reference]['status'];
    assert(is_string($status));

    $next = match ($status) {
        'requested' => ['return_dispatched', 'carrier-return-picked-up'],
        'return_dispatched' => ['return_received', 'carrier-return-received'],
        default => null,
    };

    if (null === $next) {
        fwrite(\STDERR, sprintf("Return \"%s\" is \"%s\", nothing to advance.\n", $reference, $status));
        exit(1);
    }

    [$nextStatus, $eventType] = $next;

    fake_api_call_webhook('CARRIER_WEBHOOK_SECRET', 'X-Carrier-Signature', $eventType, ['returnTrackingReference' => $reference]);
    fake_api_store_transition_status('acme-returns', $reference, $nextStatus);

    echo sprintf("Return \"%s\": %s -> %s.\n", $reference, $status, $nextStatus);
    exit(0);
}

fwrite(\STDERR, sprintf("Unknown reference \"%s\".\n", $reference));
exit(1);
