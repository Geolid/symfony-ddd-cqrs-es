<?php

declare(strict_types=1);

namespace Api\Input;

use Ordering\Order\Infrastructure\Validation\ValidMoney;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class PlaceOrderInput
{
    public function __construct(
        #[Assert\NotBlank]
        public ?string $customerId = null,
        #[Assert\NotNull]
        #[ValidMoney]
        public ?int $totalAmountInCents = null,
    ) {
    }
}
