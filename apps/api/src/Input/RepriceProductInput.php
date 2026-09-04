<?php

declare(strict_types=1);

namespace Api\Input;

use ApiPlatform\Metadata\ApiProperty;
use Shared\Application\Validation\ValidMoney;

final readonly class RepriceProductInput
{
    public function __construct(
        #[ApiProperty(description: 'The new unit price of the product, in cents.', example: 3_499)]
        #[ValidMoney]
        public ?int $unitPriceInCents = null,
    ) {
    }
}
