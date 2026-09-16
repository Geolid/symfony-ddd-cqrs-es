<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Support\Builder;

use Iam\Authentication\Domain\TotpCredential\Service\TotpCipherInterface;
use Iam\Authentication\Domain\TotpCredential\Service\TotpVerifierInterface;
use Iam\Authentication\Domain\TotpCredential\TotpCredential;
use Iam\Authentication\Domain\TotpCredential\ValueObject\TotpCredentialId;
use Iam\Tests\Authentication\Support\Double\FakeTotpVerifier;
use Ramsey\Uuid\Uuid;
use Support\Builder\AbstractAggregateBuilder;
use Symfony\Component\Clock\Clock;
use Webmozart\Assert\Assert;

/**
 * @phpstan-type Attributes = array{
 *     id: TotpCredentialId,
 *     identityId: string,
 *     secret: string,
 *     enrolledAt: \DateTimeImmutable,
 *     confirmedAt: \DateTimeImmutable,
 *     revokedAt: \DateTimeImmutable,
 *     cipher?: TotpCipherInterface,
 *     verifier?: TotpVerifierInterface,
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

    public function withEnrolledAt(\DateTimeImmutable $enrolledAt): self
    {
        return $this->withAttributes(enrolledAt: $enrolledAt);
    }

    public function withCipher(TotpCipherInterface $cipher): self
    {
        return $this->withAttributes(cipher: $cipher);
    }

    public function withVerifier(TotpVerifierInterface $verifier): self
    {
        return $this->withAttributes(verifier: $verifier);
    }

    public function confirmed(?\DateTimeImmutable $confirmedAt = null): self
    {
        $builder = null !== $confirmedAt ? $this->withAttributes(confirmedAt: $confirmedAt) : $this;

        return $builder->withModifier(static function (TotpCredential $credential, self $builder): void {
            $credential->confirm(FakeTotpVerifier::codeFor($builder['secret']), $builder->cipher(), $builder->verifier(), $builder['confirmedAt']);
        });
    }

    public function revoked(?\DateTimeImmutable $revokedAt = null): self
    {
        $builder = null !== $revokedAt ? $this->withAttributes(revokedAt: $revokedAt) : $this;

        return $builder->withModifier(static function (TotpCredential $credential, self $builder): void {
            $credential->revoke($builder['revokedAt']);
        });
    }

    protected static function defaults(): array
    {
        $now = Clock::get()->now();

        return [
            'id' => static fn (): TotpCredentialId => TotpCredentialId::fromString(Uuid::uuid7()->toString()),
            'identityId' => static fn (): string => Uuid::uuid7()->toString(),
            'secret' => static fn (): string => bin2hex(random_bytes(20)),
            'enrolledAt' => static fn (): \DateTimeImmutable => $now,
            'confirmedAt' => static fn (): \DateTimeImmutable => $now->modify('+15 seconds'),
            'revokedAt' => static fn (): \DateTimeImmutable => $now->modify('+1 day'),
        ];
    }

    protected function build(): TotpCredential
    {
        return TotpCredential::enroll(
            id: $this['id'],
            identityId: $this['identityId'],
            secret: $this['secret'],
            cipher: $this->cipher(),
            enrolledAt: $this['enrolledAt'],
        );
    }

    private function cipher(): TotpCipherInterface
    {
        Assert::isInstanceOf($cipher = $this['cipher'], TotpCipherInterface::class);

        return $cipher;
    }

    private function verifier(): TotpVerifierInterface
    {
        Assert::isInstanceOf($verifier = $this['verifier'], TotpVerifierInterface::class);

        return $verifier;
    }
}
