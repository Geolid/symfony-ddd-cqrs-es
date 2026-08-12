<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Support\Factory;

use Iam\Identity\Domain\ApiTokenCredential;
use Iam\Identity\Domain\Service\SecretHasherInterface;
use Iam\Identity\Domain\ValueObject\ApiTokenCredentialId;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Iam\Identity\Domain\ValueObject\Label;
use Shared\Tests\Support\Factory\AbstractAggregateTestFactory;
use Webmozart\Assert\Assert;

/**
 * @extends AbstractAggregateTestFactory<ApiTokenCredential>
 */
final class ApiTokenCredentialTestFactory extends AbstractAggregateTestFactory
{
    public function withId(string $id): self
    {
        return $this->withAttributes(array_merge($this->attributes, ['id' => $id]));
    }

    public function withIdentityId(string $identityId): self
    {
        return $this->withAttributes(array_merge($this->attributes, ['identityId' => $identityId]));
    }

    public function withIdentifier(string $identifier): self
    {
        return $this->withAttributes(array_merge($this->attributes, ['identifier' => $identifier]));
    }

    public function withLabel(string $label): self
    {
        return $this->withAttributes(array_merge($this->attributes, ['label' => $label]));
    }

    public function withSecret(string $secret): self
    {
        return $this->withAttributes(array_merge($this->attributes, ['secret' => $secret]));
    }

    public function withIssuedAt(\DateTimeImmutable $issuedAt): self
    {
        return $this->withAttributes(array_merge($this->attributes, ['issuedAt' => $issuedAt]));
    }

    public function withExpiresAt(\DateTimeImmutable $expiresAt): self
    {
        return $this->withAttributes(array_merge($this->attributes, ['expiresAt' => $expiresAt]));
    }

    public function withHasher(SecretHasherInterface $hasher): self
    {
        return $this->withAttributes(array_merge($this->attributes, ['hasher' => $hasher]));
    }

    public function revoked(\DateTimeImmutable $revokedAt = new \DateTimeImmutable('now +00:00')): self
    {
        return $this->withModifier(static fn (ApiTokenCredential $credential) => $credential->revoke($revokedAt));
    }

    public function rehashed(?string $plainSecret = null, \DateTimeImmutable $rehashedAt = new \DateTimeImmutable('now +00:00')): self
    {
        return $this->withModifier(function (ApiTokenCredential $credential) use ($plainSecret, $rehashedAt): void {
            Assert::stringNotEmpty($secret = $plainSecret ?? $this->attributes['secret']);
            Assert::isInstanceOf($hasher = $this->attributes['hasher'], SecretHasherInterface::class);

            $credential->rehash($secret, $hasher, $rehashedAt);
        });
    }

    protected function defaults(): array
    {
        return [
            'id' => ApiTokenCredentialId::generate()->toString(),
            'identityId' => IdentityId::generate()->toString(),
            'identifier' => 'key_'.self::faker()->unique()->uuid(),
            'label' => self::faker()->words(2, true),
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
        Assert::stringNotEmpty($label = $attributes['label']);
        Assert::stringNotEmpty($secret = $attributes['secret']);
        Assert::isInstanceOf($issuedAt = $attributes['issuedAt'], \DateTimeInterface::class);
        Assert::isInstanceOf($expiresAt = $attributes['expiresAt'], \DateTimeInterface::class);
        Assert::keyExists($attributes, 'hasher', 'Missing hasher — call withHasher() before create().');
        Assert::isInstanceOf($hasher = $attributes['hasher'], SecretHasherInterface::class);

        return ApiTokenCredential::issue(
            ApiTokenCredentialId::fromString($id),
            IdentityId::fromString($identityId),
            $identifier,
            Label::fromString($label),
            $secret,
            $hasher,
            \DateTimeImmutable::createFromInterface($issuedAt),
            \DateTimeImmutable::createFromInterface($expiresAt),
        );
    }
}
