<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Support\Factory;

use Iam\Identity\Domain\ApiTokenCredential;
use Iam\Identity\Domain\ApiTokenCredentialId;
use Iam\Identity\Domain\IdentityId;
use Iam\Identity\Infrastructure\Security\SecretHasher;
use Shared\Tests\Support\Factory\AbstractAggregateTestFactory;
use Webmozart\Assert\Assert;

/**
 * @extends AbstractAggregateTestFactory<ApiTokenCredential>
 */
final class ApiTokenCredentialTestFactory extends AbstractAggregateTestFactory
{
    public function forIdentity(string $identityId): self
    {
        return static::new(array_merge($this->attributes, ['identityId' => $identityId]));
    }

    public function withIdentifier(string $identifier): self
    {
        return static::new(array_merge($this->attributes, ['identifier' => $identifier]));
    }

    public function withSecret(string $secret): self
    {
        return static::new(array_merge($this->attributes, ['secret' => $secret]));
    }

    public function revoked(): self
    {
        return $this->withModifier(static fn (ApiTokenCredential $credential) => $credential->revoke(new \DateTimeImmutable('now +00:00')));
    }

    public function expired(): self
    {
        return static::new(array_merge($this->attributes, ['expiresAt' => new \DateTimeImmutable('-1 day +00:00')]));
    }

    protected function defaults(): array
    {
        return [
            'id' => ApiTokenCredentialId::generate()->toString(),
            'identityId' => IdentityId::generate()->toString(),
            'identifier' => 'key_'.self::faker()->unique()->uuid(),
            'secret' => self::faker()->uuid(),
            'issuedAt' => self::faker()->dateTimeBetween('-1 year', '-1 day'),
            'expiresAt' => new \DateTimeImmutable('+1 year +00:00'),
        ];
    }

    protected function build(array $attributes): ApiTokenCredential
    {
        Assert::stringNotEmpty($id = $attributes['id']);
        Assert::stringNotEmpty($identityId = $attributes['identityId']);
        Assert::stringNotEmpty($identifier = $attributes['identifier']);
        Assert::stringNotEmpty($secret = $attributes['secret']);
        Assert::isInstanceOf($issuedAt = $attributes['issuedAt'], \DateTimeInterface::class);
        Assert::isInstanceOf($expiresAt = $attributes['expiresAt'], \DateTimeInterface::class);

        return ApiTokenCredential::issue(
            ApiTokenCredentialId::fromString($id),
            IdentityId::fromString($identityId),
            $identifier,
            $secret,
            new SecretHasher(),
            \DateTimeImmutable::createFromInterface($issuedAt),
            \DateTimeImmutable::createFromInterface($expiresAt),
        );
    }
}
