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
        return static::new(array_merge($this->attributes, ['identityId' => $identityId]));
    }

    public function withLogin(string $login): self
    {
        return static::new(array_merge($this->attributes, ['login' => $login]));
    }

    public function withPassword(string $password): self
    {
        return static::new(array_merge($this->attributes, ['password' => $password]));
    }

    public function withHasher(SecretHasherInterface $hasher): self
    {
        return static::new(array_merge($this->attributes, ['hasher' => $hasher]));
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
        Assert::keyExists($attributes, 'hasher', 'Missing hasher — call withHasher() before create().');
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
