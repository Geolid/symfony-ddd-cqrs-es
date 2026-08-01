<?php

declare(strict_types=1);

namespace Web\Form\FormData;

use Sales\Order\Application\Validation\ValidMoney;
use Symfony\Component\Validator\Constraints as Assert;

final class OrderLineFormData
{
    #[Assert\NotBlank(normalizer: 'trim')]
    public ?string $label = null;

    #[Assert\Positive]
    public ?int $quantity = null;

    #[ValidMoney]
    public ?int $unitAmountInCents = null;
}
