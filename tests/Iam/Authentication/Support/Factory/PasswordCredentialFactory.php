<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Support\Factory;

use Iam\Authentication\Domain\PasswordCredential\PasswordCredential;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Specification\PasswordStrengthSpecificationInterface;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\Password;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\PasswordCredentialId;
use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Ramsey\Uuid\Uuid;
use Shared\Tests\Support\Double\FakeCodeChallenger;
use Support\Foundry\AbstractAggregateFactory;
use Symfony\Component\Clock\Clock;
use Webmozart\Assert\Assert;

/**
 * @phpstan-type Inputs = array{
 *     id: PasswordCredentialId,
 *     identityId: string,
 *     password: Password,
 *     passwordStrength: PasswordStrengthSpecificationInterface,
 *     hasher: PasswordHasherInterface,
 *     definedAt: \DateTimeImmutable,
 *     changedAt: \DateTimeImmutable,
 *     rehashedAt: \DateTimeImmutable,
 *     requestedAt: \DateTimeImmutable,
 *     resetAt: \DateTimeImmutable,
 * }
 *
 * @extends AbstractAggregateFactory<PasswordCredential, Inputs>
 */
final class PasswordCredentialFactory extends AbstractAggregateFactory
{
    public static function class(): string
    {
        return PasswordCredential::class;
    }

    public function withIdentityId(string $identityId): self
    {
        return $this->with(['identityId' => $identityId]);
    }

    public function withPassword(string $password): self
    {
        return $this->with(['password' => Password::fromString($password)]);
    }

    public function withDefinedAt(\DateTimeImmutable $definedAt): self
    {
        return $this->with(['definedAt' => $definedAt]);
    }

    public function withPasswordStrength(PasswordStrengthSpecificationInterface $passwordStrength): self
    {
        return $this->with(['passwordStrength' => $passwordStrength]);
    }

    public function withHasher(PasswordHasherInterface $hasher): self
    {
        return $this->with(['hasher' => $hasher]);
    }

    public function changed(
        string $newPassword,
        ?PasswordStrengthSpecificationInterface $passwordStrength = null,
        ?PasswordHasherInterface $hasher = null,
        ?\DateTimeImmutable $changedAt = null,
    ): self {
        return $this->with(array_filter(['passwordStrength' => $passwordStrength, 'hasher' => $hasher, 'changedAt' => $changedAt]))->transition(
            static fn (PasswordCredential $credential, array $inputs) => $credential->change(
                $inputs['password']->value,
                Password::fromString($newPassword),
                self::strength($inputs),
                self::hasherOf($inputs),
                $inputs['changedAt'],
            ),
        );
    }

    public function resetRequested(?\DateTimeImmutable $requestedAt = null): self
    {
        return $this->with(array_filter(['requestedAt' => $requestedAt]))->transition(
            static fn (PasswordCredential $credential, array $inputs) => $credential->requestReset($inputs['requestedAt']),
        );
    }

    public function reset(
        string $newPassword,
        ?PasswordStrengthSpecificationInterface $passwordStrength = null,
        ?PasswordHasherInterface $hasher = null,
        ?\DateTimeImmutable $resetAt = null,
    ): self {
        return $this->with(array_filter(['passwordStrength' => $passwordStrength, 'hasher' => $hasher, 'resetAt' => $resetAt]))->transition(
            static fn (PasswordCredential $credential, array $inputs) => $credential->resetPassword(
                FakeCodeChallenger::CODE,
                new FakeCodeChallenger(),
                Password::fromString($newPassword),
                self::strength($inputs),
                self::hasherOf($inputs),
                $inputs['resetAt'],
            ),
        );
    }

    public function rehashed(string $plainPassword, ?PasswordHasherInterface $hasher = null, ?\DateTimeImmutable $rehashedAt = null): self
    {
        return $this->with(array_filter(['hasher' => $hasher, 'rehashedAt' => $rehashedAt]))->transition(
            static fn (PasswordCredential $credential, array $inputs) => $credential->rehash($plainPassword, self::hasherOf($inputs), $inputs['rehashedAt']),
        );
    }

    protected static function build(array $parameters): AggregateRoot
    {
        return PasswordCredential::define(
            id: $parameters['id'],
            identityId: $parameters['identityId'],
            password: $parameters['password'],
            passwordStrengthSpecification: self::strength($parameters),
            hasher: self::hasherOf($parameters),
            definedAt: $parameters['definedAt'],
        );
    }

    protected function initialize(): static
    {
        // The id derives from the FINAL identityId, so a with(['identityId' => ...]) override carries over.
        return parent::initialize()->beforeInstantiate(static function (array $parameters): array {
            Assert::string($parameters['identityId']);
            $parameters['id'] ??= PasswordCredentialId::forIdentity($parameters['identityId']);

            return $parameters;
        });
    }

    protected function defaults(): array
    {
        $now = Clock::get()->now();

        return [
            'identityId' => Uuid::uuid7()->toString(),
            'password' => Password::fromString('Marmoset-42-Zephyr!'),
            'definedAt' => $now,
            'changedAt' => $now->modify('+1 day'),
            'rehashedAt' => $now->modify('+2 day'),
            'requestedAt' => $now->modify('+3 day'),
            'resetAt' => $now->modify('+4 day'),
        ];
    }

    /**
     * @param array<string, mixed> $inputs
     */
    private static function strength(array $inputs): PasswordStrengthSpecificationInterface
    {
        Assert::isInstanceOf($strength = $inputs['passwordStrength'] ?? null, PasswordStrengthSpecificationInterface::class);

        return $strength;
    }

    /**
     * @param array<string, mixed> $inputs
     */
    private static function hasherOf(array $inputs): PasswordHasherInterface
    {
        Assert::isInstanceOf($hasher = $inputs['hasher'] ?? null, PasswordHasherInterface::class);

        return $hasher;
    }
}
