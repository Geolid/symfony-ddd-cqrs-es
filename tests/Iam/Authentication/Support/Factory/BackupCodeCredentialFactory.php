<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Support\Factory;

use Iam\Authentication\Domain\BackupCodeCredential\BackupCodeCredential;
use Iam\Authentication\Domain\BackupCodeCredential\Service\BackupCodeHasherInterface;
use Iam\Authentication\Domain\BackupCodeCredential\ValueObject\BackupCodeCredentialId;
use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Ramsey\Uuid\Uuid;
use Support\Foundry\AbstractAggregateFactory;
use Symfony\Component\Clock\Clock;
use Webmozart\Assert\Assert;

use function Zenstruck\Foundry\faker;

/**
 * @phpstan-type Inputs = array{
 *     id: BackupCodeCredentialId,
 *     identityId: string,
 *     plainBackupCodes: list<non-empty-string>,
 *     backupCodeHasher: BackupCodeHasherInterface,
 *     generatedAt: \DateTimeImmutable,
 *     regeneratedBackupCodes: list<non-empty-string>,
 *     regeneratedAt: \DateTimeImmutable,
 *     consumedBackupCode: string,
 *     consumedAt: \DateTimeImmutable,
 * }
 *
 * @extends AbstractAggregateFactory<BackupCodeCredential, Inputs>
 */
final class BackupCodeCredentialFactory extends AbstractAggregateFactory
{
    public static function class(): string
    {
        return BackupCodeCredential::class;
    }

    public function withIdentityId(string $identityId): self
    {
        return $this->with(['identityId' => $identityId]);
    }

    /**
     * @param list<non-empty-string> $plainBackupCodes
     */
    public function withPlainBackupCodes(array $plainBackupCodes): self
    {
        return $this->with(['plainBackupCodes' => $plainBackupCodes]);
    }

    public function withBackupCodeHasher(BackupCodeHasherInterface $backupCodeHasher): self
    {
        return $this->with(['backupCodeHasher' => $backupCodeHasher]);
    }

    public function withGeneratedAt(\DateTimeImmutable $generatedAt): self
    {
        return $this->with(['generatedAt' => $generatedAt]);
    }

    /**
     * @param ?list<non-empty-string> $plainBackupCodes
     */
    public function regenerated(?array $plainBackupCodes = null, ?\DateTimeImmutable $regeneratedAt = null): self
    {
        return $this->with(array_filter(
            ['regeneratedBackupCodes' => $plainBackupCodes, 'regeneratedAt' => $regeneratedAt],
            static fn (mixed $value): bool => null !== $value,
        ))->transition(
            static function (BackupCodeCredential $credential, array $inputs): void {
                $credential->regenerate($inputs['regeneratedBackupCodes'], self::hasherOf($inputs), $inputs['regeneratedAt']);
            },
        );
    }

    public function consumed(?string $plainCode = null, ?\DateTimeImmutable $consumedAt = null): self
    {
        return $this->with(array_filter(
            ['consumedBackupCode' => $plainCode, 'consumedAt' => $consumedAt],
            static fn (mixed $value): bool => null !== $value,
        ))->transition(
            static function (BackupCodeCredential $credential, array $inputs): void {
                $credential->consume($inputs['consumedBackupCode'], self::hasherOf($inputs), $inputs['consumedAt']);
            },
        );
    }

    protected static function build(array $parameters): AggregateRoot
    {
        return BackupCodeCredential::generate(
            id: $parameters['id'],
            identityId: $parameters['identityId'],
            plainBackupCodes: $parameters['plainBackupCodes'],
            backupCodeHasher: self::hasherOf($parameters),
            generatedAt: $parameters['generatedAt'],
        );
    }

    protected function initialize(): static
    {
        return parent::initialize()->beforeInstantiate(static function (array $parameters): array {
            Assert::string($parameters['identityId']);
            Assert::isNonEmptyList($parameters['plainBackupCodes']);
            $parameters['id'] ??= BackupCodeCredentialId::forIdentity($parameters['identityId']);
            $parameters['consumedBackupCode'] ??= $parameters['plainBackupCodes'][0];

            return $parameters;
        });
    }

    protected function defaults(): array
    {
        $now = Clock::get()->now();

        return [
            'identityId' => Uuid::uuid7()->toString(),
            'plainBackupCodes' => faker()->backupCodes(),
            'generatedAt' => $now,
            'regeneratedBackupCodes' => faker()->backupCodes(),
            'regeneratedAt' => $now->modify('+1 day'),
            'consumedAt' => $now->modify('+1 day'),
        ];
    }

    /**
     * @param array<string, mixed> $inputs
     */
    private static function hasherOf(array $inputs): BackupCodeHasherInterface
    {
        Assert::isInstanceOf($hasher = $inputs['backupCodeHasher'] ?? null, BackupCodeHasherInterface::class);

        return $hasher;
    }
}
