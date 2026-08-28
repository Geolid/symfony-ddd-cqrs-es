<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Support\Factory;

use Iam\Authentication\Domain\PasswordCredential\PasswordCredential;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordStrengthInterface;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\Login;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\Password;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\PasswordCredentialId;
use Ramsey\Uuid\Uuid;
use Shared\Tests\Support\Factory\AbstractAggregateTestFactory;
use Symfony\Component\Clock\Clock;
use Webmozart\Assert\Assert;

/**
 * @phpstan-type Attributes = array{
 *     identityId: string,
 *     login: string,
 *     password: string,
 *     definedAt: \DateTimeInterface,
 *     passwordStrength?: PasswordStrengthInterface,
 *     hasher?: PasswordHasherInterface,
 * }
 *
 * @extends AbstractAggregateTestFactory<PasswordCredential, Attributes>
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

    public function withDefinedAt(\DateTimeImmutable $definedAt): self
    {
        return $this->withAttributes(array_merge($this->attributes, ['definedAt' => $definedAt]));
    }

    public function withPasswordStrength(PasswordStrengthInterface $passwordStrength): self
    {
        return $this->withAttributes(array_merge($this->attributes, ['passwordStrength' => $passwordStrength]));
    }

    public function withHasher(PasswordHasherInterface $hasher): self
    {
        return $this->withAttributes(array_merge($this->attributes, ['hasher' => $hasher]));
    }

    public function changed(
        string $newPassword,
        ?PasswordStrengthInterface $passwordStrength = null,
        ?PasswordHasherInterface $hasher = null,
        ?\DateTimeImmutable $changedAt = null,
    ): self {
        $passwordStrength ??= $this->passwordStrength();
        $hasher ??= $this->hasher();
        $changedAt ??= Clock::get()->now();

        return $this->withModifier(static fn (PasswordCredential $credential) => $credential->change(
            Password::fromString($newPassword),
            $passwordStrength,
            $hasher,
            $changedAt,
        ));
    }

    public function rehashed(
        string $plainPassword,
        ?PasswordHasherInterface $hasher = null,
        ?\DateTimeImmutable $rehashedAt = null,
    ): self {
        $hasher ??= $this->hasher();
        $rehashedAt ??= Clock::get()->now();

        return $this->withModifier(static fn (PasswordCredential $credential) => $credential->rehash(
            $plainPassword,
            $hasher,
            $rehashedAt,
        ));
    }

    protected function defaults(): array
    {
        return [
            'identityId' => Uuid::uuid7()->toString(),
            'login' => self::faker()->userName(),
            'password' => 'Xk9$mQ2vLp7&zR4w',
            'definedAt' => self::faker()->dateTimeBetween('-1 year', '-1 day'),
        ];
    }

    protected function build(array $attributes): PasswordCredential
    {
        Assert::stringNotEmpty($identityId = $attributes['identityId']);
        Assert::stringNotEmpty($login = $attributes['login']);
        Assert::stringNotEmpty($password = $attributes['password']);
        Assert::isInstanceOf($definedAt = $attributes['definedAt'], \DateTimeInterface::class);

        return PasswordCredential::define(
            PasswordCredentialId::forIdentity($identityId),
            $identityId,
            Login::fromString($login),
            Password::fromString($password),
            $this->passwordStrength(),
            $this->hasher(),
            \DateTimeImmutable::createFromInterface($definedAt),
        );
    }

    private function passwordStrength(): PasswordStrengthInterface
    {
        Assert::isInstanceOf($passwordStrength = $this->attributes['passwordStrength'] ?? null, PasswordStrengthInterface::class);

        return $passwordStrength;
    }

    private function hasher(): PasswordHasherInterface
    {
        Assert::isInstanceOf($hasher = $this->attributes['hasher'] ?? null, PasswordHasherInterface::class);

        return $hasher;
    }
}
