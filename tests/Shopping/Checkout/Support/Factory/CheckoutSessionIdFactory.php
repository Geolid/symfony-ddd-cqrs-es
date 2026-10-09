<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Support\Factory;

use Ramsey\Uuid\Uuid;
use Shopping\Checkout\Domain\ValueObject\CheckoutSessionId;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\ObjectFactory;

/**
 * @extends ObjectFactory<CheckoutSessionId>
 */
final class CheckoutSessionIdFactory extends ObjectFactory
{
    public static function class(): string
    {
        return CheckoutSessionId::class;
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
