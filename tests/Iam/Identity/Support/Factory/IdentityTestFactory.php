<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Support\Factory;

use Iam\Identity\Domain\Identity;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Shared\Tests\Support\Factory\AbstractAggregateTestFactory;
use Webmozart\Assert\Assert;

/**
 * @extends AbstractAggregateTestFactory<Identity>
 */
final class IdentityTestFactory extends AbstractAggregateTestFactory
{
    public function suspended(): self
    {
        return $this->withModifier(static fn (Identity $identity) => $identity->suspend(new \DateTimeImmutable('now +00:00')));
    }

    protected function defaults(): array
    {
        return [
            'id' => IdentityId::generate()->toString(),
            'registeredAt' => self::faker()->dateTimeBetween('-1 year', '-1 day'),
        ];
    }

    protected function build(array $attributes): Identity
    {
        Assert::stringNotEmpty($id = $attributes['id']);
        Assert::isInstanceOf($registeredAt = $attributes['registeredAt'], \DateTimeInterface::class);

        return Identity::register(
            IdentityId::fromString($id),
            \DateTimeImmutable::createFromInterface($registeredAt),
        );
    }
}
