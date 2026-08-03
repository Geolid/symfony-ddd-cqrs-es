<?php

declare(strict_types=1);

namespace Web\Form\FormData;

use Symfony\Component\Validator\Constraints as Assert;

final class OrderLineFormData
{
    #[Assert\NotBlank]
    public ?string $productId = null;

    #[Assert\Positive]
    public ?int $quantity = null;
}
