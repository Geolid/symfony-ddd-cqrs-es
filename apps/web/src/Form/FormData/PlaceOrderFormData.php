<?php

declare(strict_types=1);

namespace Web\Form\FormData;

use Sales\Customer\Application\Validation\ValidCustomerId;
use Sales\Order\Application\Validation\ValidMoney;

final class PlaceOrderFormData
{
    #[ValidCustomerId]
    public ?string $customerId = null;

    #[ValidMoney]
    public ?int $totalAmountInCents = null;
}
