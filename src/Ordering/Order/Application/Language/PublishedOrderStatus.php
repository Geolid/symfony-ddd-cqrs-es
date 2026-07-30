<?php

declare(strict_types=1);

namespace Ordering\Order\Application\Language;

use Ordering\Order\Domain\OrderStatus;
use Shared\Application\Language\PublishedLanguageInterface;

enum PublishedOrderStatus: string implements PublishedLanguageInterface
{
    case PLACED = OrderStatus::PLACED->value;
    case CANCELLED = OrderStatus::CANCELLED->value;
}
