<?php

declare(strict_types=1);

namespace Api\Input;

use ApiPlatform\Metadata\ApiProperty;
use Sales\Customer\Application\Validation\ValidCustomerId;
use Sales\Order\Application\Validation\ValidMoney;

final readonly class PlaceOrderInput
{
    public function __construct(
        #[ApiProperty(description: 'The identifier of the customer placing the order.', example: '0193c5f4-7b10-7c42-9a6e-4d8f1b3c5e72')]
        #[ValidCustomerId]
        public ?string $customerId = null,
        #[ApiProperty(description: 'The total amount of the order, in cents.', example: 3500)]
        #[ValidMoney]
        public ?int $totalAmountInCents = null,
    ) {
    }
}
