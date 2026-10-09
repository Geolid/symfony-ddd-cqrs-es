<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Support\Factory;

use Iam\Identity\Domain\ValueObject\IdentityId;
use Ramsey\Uuid\Uuid;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\ObjectFactory;

use function Zenstruck\Foundry\faker;

/**
 * @extends ObjectFactory<IdentityId>
 */
final class IdentityIdFactory extends ObjectFactory
{
    public static function class(): string
    {
        return IdentityId::class;
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
