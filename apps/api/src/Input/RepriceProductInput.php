<?php

declare(strict_types=1);

namespace Api\Input;

use ApiPlatform\Metadata\ApiProperty;
use Shared\Application\Validation\ValidMoney;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class RepriceProductInput
{
    public function __construct(
        #[ApiProperty(description: 'The new unit price of the product, in cents.', example: 3_499)]
        #[Assert\NotNull]
        #[ValidMoney]
        public ?int $unitAmountInCents = null,
    ) {
    }
}
