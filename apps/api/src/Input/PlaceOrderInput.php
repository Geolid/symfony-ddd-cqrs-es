<?php

declare(strict_types=1);

namespace Api\Input;

use ApiPlatform\Metadata\ApiProperty;
use Sales\Customer\Application\Validation\ValidCustomerId;
use Sales\Order\Application\Validation\ValidOrderLines;

final readonly class PlaceOrderInput
{
    /**
     * @param list<array{label: string, quantity: int, unitAmountInCents: int}> $lines
     */
    public function __construct(
        #[ApiProperty(description: 'The identifier of the customer placing the order.', example: '0193c5f4-7b10-7c42-9a6e-4d8f1b3c5e72')]
        #[ValidCustomerId]
        public ?string $customerId = null,
        #[ApiProperty(
            description: 'The lines the order is made of. Its total is derived from them.',
            example: [['label' => 'Espresso cups, set of 6', 'quantity' => 2, 'unitAmountInCents' => 1750]],
        )]
        #[ValidOrderLines]
        public array $lines = [],
    ) {
    }
}
