<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Support\Factory;

use Finance\Payment\Domain\ValueObject\PaymentReference;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\ObjectFactory;

use function Zenstruck\Foundry\faker;

/**
 * @extends ObjectFactory<PaymentReference>
 */
final class PaymentReferenceFactory extends ObjectFactory
{
    public static function class(): string
    {
        return PaymentReference::class;
    }

    protected function initialize(): static
    {
        return $this->instantiateWith(Instantiator::namedConstructor('fromString'));
    }

    protected function defaults(): array
    {
        return ['value' => faker()->unique()->regexify('GLBX-[A-Z0-9]{8}')];
    }
}
