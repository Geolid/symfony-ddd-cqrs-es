<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Support\Builder;

use Iam\Authentication\Domain\TotpCredential\Service\TotpBackupCodeHasherInterface;
use Iam\Authentication\Domain\TotpCredential\Service\TotpCipherInterface;
use Iam\Authentication\Domain\TotpCredential\TotpCredential;
use Iam\Authentication\Domain\TotpCredential\ValueObject\TotpCredentialId;
use OTPHP\TOTP;
use Ramsey\Uuid\Uuid;
use Support\Builder\AbstractAggregateBuilder;
use Symfony\Component\Clock\Clock;
use Webmozart\Assert\Assert;

/**
 * @phpstan-type Attributes = array{
 *     id: TotpCredentialId,
 *     identityId: string,
 *     secret: string,
 *     plainBackupCodes: list<non-empty-string>,
 *     issuedAt: \DateTimeImmutable,
 *     revokedAt: \DateTimeImmutable,
 *     regeneratedBackupCodes: list<non-empty-string>,
 *     regeneratedAt: \DateTimeImmutable,
 *     consumedBackupCode: non-empty-string,
 *     consumedAt: \DateTimeImmutable,
 *     cipher?: TotpCipherInterface,
 *     backupCodeHasher?: TotpBackupCodeHasherInterface,
 * }
 *
 * @extends AbstractAggregateBuilder<TotpCredential, Attributes>
 */
final class TotpCredentialBuilder extends AbstractAggregateBuilder
{
    public function withId(string $id): self
    {
        return $this->withAttributes(id: TotpCredentialId::fromString($id));
    }

    public function withIdentityId(string $identityId): self
    {
        return $this->withAttributes(identityId: $identityId);
    }

    public function withSecret(string $secret): self
    {
        return $this->withAttributes(secret: $secret);
    }

    public function withCipher(TotpCipherInterface $cipher): self
    {
        return $this->withAttributes(cipher: $cipher);
    }

    /**
     * @param list<non-empty-string> $plainBackupCodes
     */
    public function withPlainBackupCodes(array $plainBackupCodes): self
    {
        return $this->withAttributes(plainBackupCodes: $plainBackupCodes);
    }

    public function withBackupCodeHasher(TotpBackupCodeHasherInterface $backupCodeHasher): self
    {
        return $this->withAttributes(backupCodeHasher: $backupCodeHasher);
    }

    public function withIssuedAt(\DateTimeImmutable $issuedAt): self
    {
        return $this->withAttributes(issuedAt: $issuedAt);
    }

    public function revoked(?\DateTimeImmutable $revokedAt = null): self
    {
        $builder = null !== $revokedAt ? $this->withAttributes(revokedAt: $revokedAt) : $this;

        return $builder->withModifier(static function (TotpCredential $credential, self $builder): void {
            $credential->revoke($builder['identityId'], $builder['revokedAt']);
        });
    }

    /**
     * @param ?list<non-empty-string> $plainBackupCodes
     */
    public function backupCodesRegenerated(?array $plainBackupCodes = null, ?\DateTimeImmutable $regeneratedAt = null): self
    {
        $builder = $this->withAttributes(...array_filter([
            'regeneratedBackupCodes' => $plainBackupCodes,
            'regeneratedAt' => $regeneratedAt,
        ], static fn (mixed $value): bool => null !== $value));

        return $builder->withModifier(static function (TotpCredential $credential, self $builder): void {
            $credential->regenerateBackupCodes(
                $builder['identityId'],
                $builder['regeneratedBackupCodes'],
                $builder->hasher(),
                $builder['regeneratedAt'],
            );
        });
    }

    public function backupCodeConsumed(?string $plainCode = null, ?\DateTimeImmutable $consumedAt = null): self
    {
        $builder = $this->withAttributes(...array_filter([
            'consumedBackupCode' => $plainCode,
            'consumedAt' => $consumedAt,
        ], static fn (mixed $value): bool => null !== $value));

        return $builder->withModifier(static function (TotpCredential $credential, self $builder): void {
            $credential->consumeBackupCode(
                $builder['consumedBackupCode'],
                $builder->hasher(),
                $builder['consumedAt'],
            );
        });
    }

    protected static function defaults(): array
    {
        $now = Clock::get()->now();

        return [
            'id' => static fn (): TotpCredentialId => TotpCredentialId::fromString(Uuid::uuid7()->toString()),
            'identityId' => static fn (): string => Uuid::uuid7()->toString(),
            'secret' => static fn (): string => TOTP::generate()->getSecret(),
            'plainBackupCodes' => static fn (): array => [bin2hex(random_bytes(5)), bin2hex(random_bytes(5))],
            'issuedAt' => static fn (): \DateTimeImmutable => $now,
            'revokedAt' => static fn (): \DateTimeImmutable => $now->modify('+1 day'),
            'regeneratedBackupCodes' => static fn (): array => [bin2hex(random_bytes(5)), bin2hex(random_bytes(5))],
            'regeneratedAt' => static fn (): \DateTimeImmutable => $now->modify('+1 day'),
            'consumedBackupCode' => static fn (?self $builder): string => null !== $builder ? $builder['plainBackupCodes'][0] : bin2hex(random_bytes(5)),
            'consumedAt' => static fn (): \DateTimeImmutable => $now->modify('+1 day'),
        ];
    }

    protected function build(): TotpCredential
    {
        return TotpCredential::issue(
            id: $this['id'],
            identityId: $this['identityId'],
            secret: $this['secret'],
            cipher: $this->cipher(),
            plainBackupCodes: $this['plainBackupCodes'],
            backupCodeHasher: $this->hasher(),
            issuedAt: $this['issuedAt'],
        );
    }

    private function cipher(): TotpCipherInterface
    {
        Assert::isInstanceOf($cipher = $this['cipher'], TotpCipherInterface::class);

        return $cipher;
    }

    private function hasher(): TotpBackupCodeHasherInterface
    {
        Assert::isInstanceOf($hasher = $this['backupCodeHasher'], TotpBackupCodeHasherInterface::class);

        return $hasher;
    }
}
