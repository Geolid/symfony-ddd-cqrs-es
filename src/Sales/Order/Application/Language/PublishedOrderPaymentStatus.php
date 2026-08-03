<?php

declare(strict_types=1);

namespace Sales\Order\Application\Language;

use Sales\Order\Domain\ValueObject\OrderPaymentStatus;
use Shared\Application\Language\PublishedLanguageInterface;

enum PublishedOrderPaymentStatus: string implements PublishedLanguageInterface
{
    case REQUESTED = OrderPaymentStatus::REQUESTED->value;
    case CAPTURED = OrderPaymentStatus::CAPTURED->value;
}
