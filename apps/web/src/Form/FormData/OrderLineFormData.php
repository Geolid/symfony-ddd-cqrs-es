<?php

declare(strict_types=1);

namespace Web\Form\FormData;

use Catalog\Listing\Application\Validation\ValidProductId;
use Symfony\Component\Validator\Constraints as Assert;

final class OrderLineFormData
{
    #[ValidProductId]
    public ?string $productId = null;

    #[Assert\Positive]
    public ?int $quantity = null;
}
