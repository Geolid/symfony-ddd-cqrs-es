<?php

declare(strict_types=1);

namespace Web\Form\FormData;

use Ordering\Order\Infrastructure\Validation\ValidMoney;
use Symfony\Component\Validator\Constraints as Assert;

final class PlaceOrderFormData
{
    #[Assert\NotBlank]
    public ?string $customerId = null;

    #[Assert\NotNull]
    #[ValidMoney]
    public ?int $totalAmountInCents = null;
}
