<?php

declare(strict_types=1);

namespace Web\Form\FormData;

use Sales\Customer\Application\Validation\ValidCustomerId;
use Symfony\Component\Validator\Constraints as Assert;

final class PlaceOrderFormData
{
    #[ValidCustomerId]
    public ?string $customerId = null;

    /** @var list<OrderLineFormData> */
    #[Assert\Count(min: 1)]
    #[Assert\Valid]
    public array $lines = [];
}
