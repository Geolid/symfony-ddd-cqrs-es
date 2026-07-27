<?php

declare(strict_types=1);

// Order matters: each seed may depend on the previous one. Shipments aren't seeded directly —
// placing an order fans out into Shipping automatically through the same Integration Event
// path a real caller goes through (see Shipping\Shipment\Application\Processor).
return [
    'demo:ordering:orders',
];
