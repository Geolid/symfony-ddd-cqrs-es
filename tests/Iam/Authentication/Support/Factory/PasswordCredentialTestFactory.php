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
use Support\Factory\AbstractAggregateTestFactory;
use Support\SeededFaker;
use Symfony\Component\Clock\Clock;
use Webmozart\Assert\Assert;

/**
 * @phpstan-type Attributes = array{
 *     id: PasswordCredentialId,
 *     identityId: string,
 *     login: Login,
 *     password: Password,
 *     definedAt: \DateTimeImmutable,
 *     changedAt: \DateTimeImmutable,
 *     rehashedAt: \DateTimeImmutable,
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
        return $this->withAttributes(identityId: $identityId);
    }

    public function withLogin(string $login): self
    {
        return $this->withAttributes(login: Login::fromString($login));
    }

    public function withPassword(string $password): self
    {
        return $this->withAttributes(password: Password::fromString($password));
    }

    public function withDefinedAt(\DateTimeImmutable $definedAt): self
    {
        return $this->withAttributes(definedAt: $definedAt);
    }

    public function withPasswordStrength(PasswordStrengthInterface $passwordStrength): self
    {
        return $this->withAttributes(passwordStrength: $passwordStrength);
    }

    public function withHasher(PasswordHasherInterface $hasher): self
    {
        return $this->withAttributes(hasher: $hasher);
    }

    public function changed(
        string $newPassword,
        ?PasswordStrengthInterface $passwordStrength = null,
        ?PasswordHasherInterface $hasher = null,
        ?\DateTimeImmutable $changedAt = null,
    ): self {
        $factory = $this->withAttributes(...array_filter([
            'passwordStrength' => $passwordStrength,
            'hasher' => $hasher,
            'changedAt' => $changedAt,
        ]));

        return $factory->withModifier(
            static fn (PasswordCredential $credential, self $factory) => $credential->change(
                Password::fromString($newPassword),
                $factory->passwordStrength(),
                $factory->hasher(),
                $factory['changedAt'],
            ),
        );
    }

    public function rehashed(
        string $plainPassword,
        ?PasswordHasherInterface $hasher = null,
        ?\DateTimeImmutable $rehashedAt = null,
    ): self {
        $factory = $this->withAttributes(...array_filter([
            'hasher' => $hasher,
            'rehashedAt' => $rehashedAt,
        ]));

        return $factory->withModifier(
            static fn (PasswordCredential $credential, self $factory) => $credential->rehash(
                $plainPassword,
                $factory->hasher(),
                $factory['rehashedAt'],
            ),
        );
    }

    protected static function defaults(): array
    {
        return [
            'id' => static fn (?self $factory): PasswordCredentialId => PasswordCredentialId::forIdentity(
                null !== $factory ? $factory['identityId'] : self::sample('identityId'),
            ),
            'identityId' => static fn (): string => Uuid::uuid7()->toString(),
            'login' => static fn (): Login => Login::fromString(SeededFaker::get()->userName()),
            'password' => static fn (): Password => Password::fromString('Marmoset-42-Zephyr!'),
            'definedAt' => static fn (): \DateTimeImmutable => Clock::get()->now(),
            'changedAt' => static fn (): \DateTimeImmutable => Clock::get()->now()->modify('+1 day'),
            'rehashedAt' => static fn (): \DateTimeImmutable => Clock::get()->now()->modify('+2 day'),
        ];
    }

    protected function build(): PasswordCredential
    {
        return PasswordCredential::define(
            id: $this['id'],
            identityId: $this['identityId'],
            login: $this['login'],
            password: $this['password'],
            passwordStrength: $this->passwordStrength(),
            hasher: $this->hasher(),
            definedAt: $this['definedAt'],
        );
    }

    private function passwordStrength(): PasswordStrengthInterface
    {
        Assert::isInstanceOf($passwordStrength = $this['passwordStrength'], PasswordStrengthInterface::class);

        return $passwordStrength;
    }

    private function hasher(): PasswordHasherInterface
    {
        Assert::isInstanceOf($hasher = $this['hasher'], PasswordHasherInterface::class);

        return $hasher;
    }
}
