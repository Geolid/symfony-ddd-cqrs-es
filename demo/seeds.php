<?php

declare(strict_types=1);

// Order matters: each seed may depend on the previous one. Shipments aren't seeded directly —
// placing an order fans out into Fulfilment automatically through the same Integration Event
// path a real caller goes through (see Fulfilment\Shipment\Application\Processor).
return [
    'demo:sales:orders',
];
