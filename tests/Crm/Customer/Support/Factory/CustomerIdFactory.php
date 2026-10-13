<?php

declare(strict_types=1);

namespace Crm\Tests\Customer\Support\Factory;

use Crm\Customer\Domain\Customer\ValueObject\CustomerId;
use Ramsey\Uuid\Uuid;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\ObjectFactory;

/**
 * @extends ObjectFactory<CustomerId>
 */
final class CustomerIdFactory extends ObjectFactory
{
    public static function class(): string
    {
        return CustomerId::class;
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
