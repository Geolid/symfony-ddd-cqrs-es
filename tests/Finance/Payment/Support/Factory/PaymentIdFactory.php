<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Support\Factory;

use Finance\Payment\Domain\ValueObject\PaymentId;
use Ramsey\Uuid\Uuid;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\ObjectFactory;

/**
 * @extends ObjectFactory<PaymentId>
 */
final class PaymentIdFactory extends ObjectFactory
{
    public static function class(): string
    {
        return PaymentId::class;
    }

    protected function initialize(): static
    {
        return $this->instantiateWith(Instantiator::namedConstructor('fromString'));
    }

    protected function defaults(): array
    {
        return ['id' => Uuid::uuid7()->toString()];
    }
}
