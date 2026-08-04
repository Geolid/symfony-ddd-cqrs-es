<?php

declare(strict_types=1);

namespace Sales\Order\Application\Language;

use Sales\Order\Domain\ValueObject\OrderStatus;
use Shared\Application\Language\PublishedLanguageInterface;

enum PublishedOrderStatus: string implements PublishedLanguageInterface
{
    case PLACED = OrderStatus::PLACED->value;
    case CANCELLED = OrderStatus::CANCELLED->value;
}
