<?php

declare(strict_types=1);

namespace Web\Form\FormData;

use Sales\Order\Application\Validation\ValidBuyerId;
use Sales\Order\Application\Validation\ValidMoney;

final class PlaceOrderFormData
{
    #[ValidBuyerId]
    public ?string $customerId = null;

    #[ValidMoney]
    public ?int $totalAmountInCents = null;
}
