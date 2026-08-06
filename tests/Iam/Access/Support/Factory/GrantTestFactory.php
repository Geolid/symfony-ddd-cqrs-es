<?php

declare(strict_types=1);

namespace Iam\Tests\Access\Support\Factory;

use Iam\Access\Domain\Grant;
use Iam\Access\Domain\ValueObject\GrantId;
use Iam\Access\Domain\ValueObject\Permission;
use Ramsey\Uuid\Uuid;
use Shared\Tests\Support\Factory\AbstractAggregateTestFactory;
use Webmozart\Assert\Assert;

/**
 * @extends AbstractAggregateTestFactory<Grant>
 */
final class GrantTestFactory extends AbstractAggregateTestFactory
{
    public function withIdentityId(string $identityId): self
    {
        return static::new(array_merge($this->attributes, ['identityId' => $identityId]));
    }

    public function withPermission(string $permission): self
    {
        return static::new(array_merge($this->attributes, ['permission' => $permission]));
    }

    public function revoked(): self
    {
        return $this->withModifier(static fn (Grant $grant) => $grant->revoke(new \DateTimeImmutable('now +00:00')));
    }

    protected function defaults(): array
    {
        return [
            'id' => GrantId::generate()->toString(),
            'identityId' => Uuid::uuid7()->toString(),
            'permission' => 'fixture:read',
            'grantedAt' => self::faker()->dateTimeBetween('-1 year', '-1 day'),
        ];
    }

    protected function build(array $attributes): Grant
    {
        Assert::stringNotEmpty($id = $attributes['id']);
        Assert::stringNotEmpty($identityId = $attributes['identityId']);
        Assert::stringNotEmpty($permission = $attributes['permission']);
        Assert::isInstanceOf($grantedAt = $attributes['grantedAt'], \DateTimeInterface::class);

        return Grant::grant(
            GrantId::fromString($id),
            $identityId,
            Permission::fromString($permission),
            \DateTimeImmutable::createFromInterface($grantedAt),
        );
    }
}
