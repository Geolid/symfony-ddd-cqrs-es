<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Support\Factory;

use Iam\Authentication\Domain\TrustedDevice\ValueObject\TrustedDeviceId;
use Ramsey\Uuid\Uuid;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\ObjectFactory;

/**
 * @extends ObjectFactory<TrustedDeviceId>
 */
final class TrustedDeviceIdFactory extends ObjectFactory
{
    public static function class(): string
    {
        return TrustedDeviceId::class;
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
