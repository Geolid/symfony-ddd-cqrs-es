<?php

declare(strict_types=1);

namespace Web\Form\FormData;

use Ordering\Order\Application\Validation\ValidMoney;

final class PlaceOrderFormData
{
    public ?string $customerId = null;

    #[ValidMoney]
    public ?int $totalAmountInCents = null;
}
