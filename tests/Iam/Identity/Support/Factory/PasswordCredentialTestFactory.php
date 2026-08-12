<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Support\Factory;

use Iam\Identity\Domain\PasswordCredential;
use Iam\Identity\Domain\Service\SecretHasherInterface;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Iam\Identity\Domain\ValueObject\Login;
use Iam\Identity\Domain\ValueObject\PasswordCredentialId;
use Shared\Tests\Support\Factory\AbstractAggregateTestFactory;
use Webmozart\Assert\Assert;

/**
 * @extends AbstractAggregateTestFactory<PasswordCredential>
 */
final class PasswordCredentialTestFactory extends AbstractAggregateTestFactory
{
    public function withIdentityId(string $identityId): self
    {
        return $this->withAttributes(array_merge($this->attributes, ['identityId' => $identityId]));
    }

    public function withLogin(string $login): self
    {
        return $this->withAttributes(array_merge($this->attributes, ['login' => $login]));
    }

    public function withPassword(string $password): self
    {
        return $this->withAttributes(array_merge($this->attributes, ['password' => $password]));
    }

    public function withSetAt(\DateTimeImmutable $setAt): self
    {
        return $this->withAttributes(array_merge($this->attributes, ['setAt' => $setAt]));
    }

    public function withHasher(SecretHasherInterface $hasher): self
    {
        return $this->withAttributes(array_merge($this->attributes, ['hasher' => $hasher]));
    }

    public function changed(?string $plainPassword = null, \DateTimeImmutable $changedAt = new \DateTimeImmutable('now +00:00')): self
    {
        return $this->withModifier(function (PasswordCredential $credential) use ($plainPassword, $changedAt): void {
            Assert::stringNotEmpty($password = $plainPassword ?? $this->attributes['password']);
            Assert::isInstanceOf($hasher = $this->attributes['hasher'], SecretHasherInterface::class);

            $credential->change($password, $hasher, $changedAt);
        });
    }

    public function rehashed(?string $plainPassword = null, \DateTimeImmutable $rehashedAt = new \DateTimeImmutable('now +00:00')): self
    {
        return $this->withModifier(function (PasswordCredential $credential) use ($plainPassword, $rehashedAt): void {
            Assert::stringNotEmpty($password = $plainPassword ?? $this->attributes['password']);
            Assert::isInstanceOf($hasher = $this->attributes['hasher'], SecretHasherInterface::class);

            $credential->rehash($password, $hasher, $rehashedAt);
        });
    }

    protected function defaults(): array
    {
        return [
            'identityId' => IdentityId::generate()->toString(),
            'login' => self::faker()->unique()->userName(),
            'password' => self::faker()->password(),
            'setAt' => self::faker()->dateTimeBetween('-1 year', '-1 day'),
        ];
    }

    protected function build(array $attributes): PasswordCredential
    {
        Assert::stringNotEmpty($identityId = $attributes['identityId']);
        Assert::stringNotEmpty($login = $attributes['login']);
        Assert::stringNotEmpty($password = $attributes['password']);
        Assert::isInstanceOf($setAt = $attributes['setAt'], \DateTimeInterface::class);
        Assert::keyExists($attributes, 'hasher', 'Missing hasher — call withHasher() before create()/store().');
        Assert::isInstanceOf($hasher = $attributes['hasher'], SecretHasherInterface::class);

        return PasswordCredential::set(
            PasswordCredentialId::forIdentity($identityId),
            IdentityId::fromString($identityId),
            Login::fromString($login),
            $password,
            $hasher,
            \DateTimeImmutable::createFromInterface($setAt),
        );
    }
}
