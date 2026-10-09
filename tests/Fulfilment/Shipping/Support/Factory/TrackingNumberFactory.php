<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipping\Support\Factory;

use Fulfilment\Shipping\Domain\ValueObject\TrackingNumber;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\ObjectFactory;

use function Zenstruck\Foundry\faker;

/**
 * @extends ObjectFactory<TrackingNumber>
 */
final class TrackingNumberFactory extends ObjectFactory
{
    public static function class(): string
    {
        return TrackingNumber::class;
    }

    protected function initialize(): static
    {
        return $this->instantiateWith(Instantiator::namedConstructor('fromString'));
    }

    protected function defaults(): array
    {
        return ['value' => faker()->unique()->regexify('ACME-[A-Z0-9]{8}')];
    }
}
