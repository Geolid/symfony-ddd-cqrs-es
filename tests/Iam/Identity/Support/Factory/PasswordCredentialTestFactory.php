<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Support\Factory;

use Iam\Identity\Domain\IdentityId;
use Iam\Identity\Domain\Login;
use Iam\Identity\Domain\PasswordCredential;
use Iam\Identity\Domain\PasswordCredentialId;
use Iam\Identity\Infrastructure\Security\SecretHasher;
use Shared\Tests\Support\Factory\AbstractAggregateTestFactory;
use Webmozart\Assert\Assert;

/**
 * @extends AbstractAggregateTestFactory<PasswordCredential>
 */
final class PasswordCredentialTestFactory extends AbstractAggregateTestFactory
{
    public function forIdentity(string $identityId): self
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

    protected function defaults(): array
    {
        return [
            'id' => PasswordCredentialId::generate()->toString(),
            'identityId' => IdentityId::generate()->toString(),
            'login' => self::faker()->unique()->safeEmail(),
            'password' => 'correct horse battery staple',
            'setAt' => self::faker()->dateTimeBetween('-1 year', '-1 day'),
        ];
    }

    protected function build(array $attributes): PasswordCredential
    {
        Assert::stringNotEmpty($id = $attributes['id']);
        Assert::stringNotEmpty($identityId = $attributes['identityId']);
        Assert::stringNotEmpty($login = $attributes['login']);
        Assert::stringNotEmpty($password = $attributes['password']);
        Assert::isInstanceOf($setAt = $attributes['setAt'], \DateTimeInterface::class);

        return PasswordCredential::set(
            PasswordCredentialId::fromString($id),
            IdentityId::fromString($identityId),
            Login::fromString($login),
            $password,
            new SecretHasher(),
            \DateTimeImmutable::createFromInterface($setAt),
        );
    }
}
