<?php

declare(strict_types=1);

namespace Sales\Ordering\Application;

enum CartStatus: string
{
    case ACTIVE = 'active';
    case CHECKOUT = 'checkout';
    case CONVERTED = 'converted';
}
