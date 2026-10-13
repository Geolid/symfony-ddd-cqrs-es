<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Support\Factory;

use Iam\Authentication\Domain\TotpCredential\ValueObject\TotpCredentialId;
use Ramsey\Uuid\Uuid;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\ObjectFactory;

/**
 * @extends ObjectFactory<TotpCredentialId>
 */
final class TotpCredentialIdFactory extends ObjectFactory
{
    public static function class(): string
    {
        return TotpCredentialId::class;
    }

    public function withId(string $id): self
    {
        return $this->with(['id' => $id]);
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
