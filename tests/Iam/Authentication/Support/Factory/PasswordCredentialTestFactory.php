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
use Support\ClockSequence;
use Support\Factory\AbstractAggregateTestFactory;
use Support\SeededFaker;
use Symfony\Component\Clock\Clock;
use Webmozart\Assert\Assert;

/**
 * @phpstan-type Attributes = array{
 *     identityId: string,
 *     login: Login,
 *     password: Password,
 *     definedAt: \DateTimeImmutable,
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
        return $this->withAttributes(['identityId' => $identityId]);
    }

    public function withLogin(string $login): self
    {
        return $this->withAttributes(['login' => Login::fromString($login)]);
    }

    public function withPassword(string $password): self
    {
        return $this->withAttributes(['password' => Password::fromString($password)]);
    }

    public function withDefinedAt(\DateTimeImmutable $definedAt): self
    {
        return $this->withAttributes(['definedAt' => $definedAt]);
    }

    public function withPasswordStrength(PasswordStrengthInterface $passwordStrength): self
    {
        return $this->withAttributes(['passwordStrength' => $passwordStrength]);
    }

    public function withHasher(PasswordHasherInterface $hasher): self
    {
        return $this->withAttributes(['hasher' => $hasher]);
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
            'login' => Login::fromString(SeededFaker::get()->userName()),
            'password' => Password::fromString('Marmoset-42-Zephyr!'),
            'definedAt' => ClockSequence::next(),
        ];
    }

    protected function build(): PasswordCredential
    {
        $identityId = $this->attribute('identityId');

        return PasswordCredential::define(
            id: PasswordCredentialId::forIdentity($identityId),
            identityId: $identityId,
            login: $this->attribute('login'),
            password: $this->attribute('password'),
            passwordStrength: $this->passwordStrength(),
            hasher: $this->hasher(),
            definedAt: $this->attribute('definedAt'),
        );
    }

    private function passwordStrength(): PasswordStrengthInterface
    {
        Assert::isInstanceOf($passwordStrength = $this->attribute('passwordStrength'), PasswordStrengthInterface::class);

        return $passwordStrength;
    }

    private function hasher(): PasswordHasherInterface
    {
        Assert::isInstanceOf($hasher = $this->attribute('hasher'), PasswordHasherInterface::class);

        return $hasher;
    }
}
