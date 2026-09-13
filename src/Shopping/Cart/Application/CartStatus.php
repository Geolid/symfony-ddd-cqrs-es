<?php

declare(strict_types=1);

namespace Shopping\Cart\Application;

enum CartStatus: string
{
    case ACTIVE = 'active';
    case PURCHASED = 'purchased';
}
