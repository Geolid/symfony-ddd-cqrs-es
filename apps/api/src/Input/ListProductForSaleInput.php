<?php

declare(strict_types=1);

namespace Api\Input;

use ApiPlatform\Metadata\ApiProperty;
use Shared\Application\Validation\ValidMoney;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class ListProductForSaleInput
{
    public function __construct(
        #[ApiProperty(description: 'The label of the product.', example: 'Wireless mouse')]
        #[Assert\NotBlank(normalizer: 'trim')]
        public ?string $label = null,
        #[ApiProperty(description: 'The unit price of the product, in cents.', example: 2_999)]
        #[Assert\NotNull]
        #[ValidMoney]
        public ?int $unitAmountInCents = null,
    ) {
    }
}
