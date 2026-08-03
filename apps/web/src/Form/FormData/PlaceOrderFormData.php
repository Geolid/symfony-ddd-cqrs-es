<?php

declare(strict_types=1);

namespace Web\Form\FormData;

use Symfony\Component\Validator\Constraints as Assert;

final class PlaceOrderFormData
{
    /** @var list<OrderLineFormData> */
    #[Assert\Count(min: 1)]
    #[Assert\Valid]
    public array $lines = [];
}
