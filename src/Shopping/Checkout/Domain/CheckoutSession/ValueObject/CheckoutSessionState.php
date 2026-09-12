<?php

declare(strict_types=1);

namespace Shopping\Checkout\Domain\CheckoutSession\ValueObject;

enum CheckoutSessionState: string
{
    case OPEN = 'open';
    case EXPIRED = 'expired';
    case STALE = 'stale';
    case COMPLETED = 'completed';
}
