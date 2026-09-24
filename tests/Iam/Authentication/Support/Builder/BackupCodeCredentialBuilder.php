<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Support\Builder;

use Iam\Authentication\Domain\BackupCodeCredential\BackupCodeCredential;
use Iam\Authentication\Domain\BackupCodeCredential\Service\BackupCodeHasherInterface;
use Iam\Authentication\Domain\BackupCodeCredential\ValueObject\BackupCodeCredentialId;
use Ramsey\Uuid\Uuid;
use Support\Builder\AbstractAggregateBuilder;
use Symfony\Component\Clock\Clock;
use Webmozart\Assert\Assert;

/**
 * @phpstan-type Attributes = array{
 *     id: BackupCodeCredentialId,
 *     identityId: string,
 *     plainBackupCodes: list<non-empty-string>,
 *     generatedAt: \DateTimeImmutable,
 *     regeneratedBackupCodes: list<non-empty-string>,
 *     regeneratedAt: \DateTimeImmutable,
 *     consumedBackupCode: non-empty-string,
 *     consumedAt: \DateTimeImmutable,
 *     backupCodeHasher?: BackupCodeHasherInterface,
 * }
 *
 * @extends AbstractAggregateBuilder<BackupCodeCredential, Attributes>
 */
final class BackupCodeCredentialBuilder extends AbstractAggregateBuilder
{
    public function withIdentityId(string $identityId): self
    {
        return $this->withAttributes(identityId: $identityId);
    }

    /**
     * @param list<non-empty-string> $plainBackupCodes
     */
    public function withPlainBackupCodes(array $plainBackupCodes): self
    {
        return $this->withAttributes(plainBackupCodes: $plainBackupCodes);
    }

    public function withBackupCodeHasher(BackupCodeHasherInterface $backupCodeHasher): self
    {
        return $this->withAttributes(backupCodeHasher: $backupCodeHasher);
    }

    public function withGeneratedAt(\DateTimeImmutable $generatedAt): self
    {
        return $this->withAttributes(generatedAt: $generatedAt);
    }

    /**
     * @param ?list<non-empty-string> $plainBackupCodes
     */
    public function regenerated(?array $plainBackupCodes = null, ?\DateTimeImmutable $regeneratedAt = null): self
    {
        $builder = $this->withAttributes(...array_filter([
            'regeneratedBackupCodes' => $plainBackupCodes,
            'regeneratedAt' => $regeneratedAt,
        ], static fn (mixed $value): bool => null !== $value));

        return $builder->withModifier(static function (BackupCodeCredential $credential, self $builder): void {
            $credential->regenerate($builder['regeneratedBackupCodes'], $builder->hasher(), $builder['regeneratedAt']);
        });
    }

    public function consumed(?string $plainCode = null, ?\DateTimeImmutable $consumedAt = null): self
    {
        $builder = $this->withAttributes(...array_filter([
            'consumedBackupCode' => $plainCode,
            'consumedAt' => $consumedAt,
        ], static fn (mixed $value): bool => null !== $value));

        return $builder->withModifier(static function (BackupCodeCredential $credential, self $builder): void {
            $credential->consume($builder['consumedBackupCode'], $builder->hasher(), $builder['consumedAt']);
        });
    }

    protected static function defaults(): array
    {
        $now = Clock::get()->now();

        return [
            'id' => static fn (?self $builder): BackupCodeCredentialId => BackupCodeCredentialId::forIdentity(
                null !== $builder ? $builder['identityId'] : self::sample('identityId'),
            ),
            'identityId' => static fn (): string => Uuid::uuid7()->toString(),
            'plainBackupCodes' => static fn (): array => [bin2hex(random_bytes(5)), bin2hex(random_bytes(5))],
            'generatedAt' => static fn (): \DateTimeImmutable => $now,
            'regeneratedBackupCodes' => static fn (): array => [bin2hex(random_bytes(5)), bin2hex(random_bytes(5))],
            'regeneratedAt' => static fn (): \DateTimeImmutable => $now->modify('+1 day'),
            'consumedBackupCode' => static fn (?self $builder): string => null !== $builder ? $builder['plainBackupCodes'][0] : bin2hex(random_bytes(5)),
            'consumedAt' => static fn (): \DateTimeImmutable => $now->modify('+1 day'),
        ];
    }

    protected function build(): BackupCodeCredential
    {
        return BackupCodeCredential::generate(
            id: $this['id'],
            identityId: $this['identityId'],
            plainBackupCodes: $this['plainBackupCodes'],
            backupCodeHasher: $this->hasher(),
            generatedAt: $this['generatedAt'],
        );
    }

    private function hasher(): BackupCodeHasherInterface
    {
        Assert::isInstanceOf($hasher = $this['backupCodeHasher'], BackupCodeHasherInterface::class);

        return $hasher;
    }
}
