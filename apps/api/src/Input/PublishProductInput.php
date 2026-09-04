<?php

declare(strict_types=1);

namespace Api\Input;

use ApiPlatform\Metadata\ApiProperty;
use Catalog\Listing\Application\Validation\ValidUniqueProductLabel;
use Shared\Application\Validation\ValidLabel;
use Shared\Application\Validation\ValidMoney;

final readonly class PublishProductInput
{
    public function __construct(
        #[ApiProperty(description: 'The label of the product.', example: 'Wireless mouse')]
        #[ValidLabel]
        #[ValidUniqueProductLabel]
        public ?string $label = null,
        #[ApiProperty(description: 'The unit price of the product, in cents.', example: 2_999)]
        #[ValidMoney]
        public ?int $unitPriceInCents = null,
    ) {
    }
}
