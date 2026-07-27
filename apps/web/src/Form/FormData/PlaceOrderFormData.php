<?php

declare(strict_types=1);

namespace Web\Form\FormData;

use Symfony\Component\Validator\Constraints as Assert;

final class PlaceOrderFormData
{
    #[Assert\NotBlank]
    public ?string $customerId = null;

    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    public ?int $totalAmountInCents = null;
}
