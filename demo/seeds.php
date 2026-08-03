<?php

declare(strict_types=1);

// Order matters: each seed may depend on the previous one.
return [
    'demo:catalog:products',
    'demo:sales:customers',
    'demo:sales:orders',
];
