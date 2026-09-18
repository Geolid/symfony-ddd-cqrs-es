<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Support\Builder;

use Iam\Authentication\Domain\PasswordCredential\PasswordCredential;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Specification\PasswordStrengthSpecificationInterface;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\Password;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\PasswordCredentialId;
use Ramsey\Uuid\Uuid;
use Support\Builder\AbstractAggregateBuilder;
use Symfony\Component\Clock\Clock;
use Webmozart\Assert\Assert;

/**
 * @phpstan-type Attributes = array{
 *     id: PasswordCredentialId,
 *     identityId: string,
 *     password: Password,
 *     definedAt: \DateTimeImmutable,
 *     changedAt: \DateTimeImmutable,
 *     rehashedAt: \DateTimeImmutable,
 *     requestedAt: \DateTimeImmutable,
 *     passwordStrength?: PasswordStrengthSpecificationInterface,
 *     hasher?: PasswordHasherInterface,
 * }
 *
 * @extends AbstractAggregateBuilder<PasswordCredential, Attributes>
 */
final class PasswordCredentialBuilder extends AbstractAggregateBuilder
{
    public function withIdentityId(string $identityId): self
    {
        return $this->withAttributes(identityId: $identityId);
    }

    public function withPassword(string $password): self
    {
        return $this->withAttributes(password: Password::fromString($password));
    }

    public function withDefinedAt(\DateTimeImmutable $definedAt): self
    {
        return $this->withAttributes(definedAt: $definedAt);
    }

    public function withPasswordStrength(PasswordStrengthSpecificationInterface $passwordStrength): self
    {
        return $this->withAttributes(passwordStrength: $passwordStrength);
    }

    public function withHasher(PasswordHasherInterface $hasher): self
    {
        return $this->withAttributes(hasher: $hasher);
    }

    public function changed(
        string $newPassword,
        ?PasswordStrengthSpecificationInterface $passwordStrength = null,
        ?PasswordHasherInterface $hasher = null,
        ?\DateTimeImmutable $changedAt = null,
    ): self {
        $builder = $this->withAttributes(...array_filter([
            'passwordStrength' => $passwordStrength,
            'hasher' => $hasher,
            'changedAt' => $changedAt,
        ]));

        return $builder->withModifier(
            static fn (PasswordCredential $credential, self $builder) => $credential->change(
                Password::fromString($newPassword),
                $builder->passwordStrength(),
                $builder->hasher(),
                $builder['changedAt'],
            ),
        );
    }

    public function resetRequested(?\DateTimeImmutable $requestedAt = null): self
    {
        $builder = null !== $requestedAt ? $this->withAttributes(requestedAt: $requestedAt) : $this;

        return $builder->withModifier(
            static fn (PasswordCredential $credential, self $builder) => $credential->requestReset($builder['identityId'], $builder['requestedAt']),
        );
    }

    public function rehashed(
        string $plainPassword,
        ?PasswordHasherInterface $hasher = null,
        ?\DateTimeImmutable $rehashedAt = null,
    ): self {
        $builder = $this->withAttributes(...array_filter([
            'hasher' => $hasher,
            'rehashedAt' => $rehashedAt,
        ]));

        return $builder->withModifier(
            static fn (PasswordCredential $credential, self $builder) => $credential->rehash(
                $plainPassword,
                $builder->hasher(),
                $builder['rehashedAt'],
            ),
        );
    }

    protected static function defaults(): array
    {
        $now = Clock::get()->now();

        return [
            'id' => static fn (?self $builder): PasswordCredentialId => PasswordCredentialId::forIdentity(
                null !== $builder ? $builder['identityId'] : self::sample('identityId'),
            ),
            'identityId' => static fn (): string => Uuid::uuid7()->toString(),
            'password' => static fn (): Password => Password::fromString('Marmoset-42-Zephyr!'),
            'definedAt' => static fn (): \DateTimeImmutable => $now,
            'changedAt' => static fn (): \DateTimeImmutable => $now->modify('+1 day'),
            'rehashedAt' => static fn (): \DateTimeImmutable => $now->modify('+2 day'),
            'requestedAt' => static fn (): \DateTimeImmutable => $now->modify('+3 day'),
        ];
    }

    protected function build(): PasswordCredential
    {
        return PasswordCredential::define(
            id: $this['id'],
            identityId: $this['identityId'],
            password: $this['password'],
            passwordStrengthSpecification: $this->passwordStrength(),
            hasher: $this->hasher(),
            definedAt: $this['definedAt'],
        );
    }

    private function passwordStrength(): PasswordStrengthSpecificationInterface
    {
        Assert::isInstanceOf($passwordStrength = $this['passwordStrength'], PasswordStrengthSpecificationInterface::class);

        return $passwordStrength;
    }

    private function hasher(): PasswordHasherInterface
    {
        Assert::isInstanceOf($hasher = $this['hasher'], PasswordHasherInterface::class);

        return $hasher;
    }
}
