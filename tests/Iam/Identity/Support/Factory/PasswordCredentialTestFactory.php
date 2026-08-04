<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Support\Factory;

use Iam\Identity\Domain\PasswordCredential;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Iam\Identity\Domain\ValueObject\Login;
use Iam\Identity\Domain\ValueObject\PasswordCredentialId;
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
            'identityId' => IdentityId::generate()->toString(),
            'login' => self::faker()->unique()->safeEmail(),
            'password' => 'correct horse battery staple',
            'setAt' => self::faker()->dateTimeBetween('-1 year', '-1 day'),
        ];
    }

    protected function build(array $attributes): PasswordCredential
    {
        Assert::stringNotEmpty($identityId = $attributes['identityId']);
        Assert::stringNotEmpty($login = $attributes['login']);
        Assert::stringNotEmpty($password = $attributes['password']);
        Assert::isInstanceOf($setAt = $attributes['setAt'], \DateTimeInterface::class);

        return PasswordCredential::set(
            // SetPasswordCredentialHandler upserts by treating the PasswordCredentialId as the
            // identityId itself (a real Identity has at most one PasswordCredential) — the id must
            // match that invariant, or a later SetPasswordCredential dispatch (e.g. changing the
            // password) takes the "create" branch instead of "change" and silently produces a
            // second, disconnected credential for the same login.
            PasswordCredentialId::fromString($identityId),
            IdentityId::fromString($identityId),
            Login::fromString($login),
            $password,
            new SecretHasher(),
            \DateTimeImmutable::createFromInterface($setAt),
        );
    }
}
