<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Support\Builder;

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
 *     enrolledAt: \DateTimeImmutable,
 *     unenrolledAt: \DateTimeImmutable,
 *     cipher?: TotpCipherInterface,
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

    public function withEnrolledAt(\DateTimeImmutable $enrolledAt): self
    {
        return $this->withAttributes(enrolledAt: $enrolledAt);
    }

    public function unenrolled(?\DateTimeImmutable $unenrolledAt = null): self
    {
        $builder = null !== $unenrolledAt ? $this->withAttributes(unenrolledAt: $unenrolledAt) : $this;

        return $builder->withModifier(static function (TotpCredential $credential, self $builder): void {
            $credential->unenroll($builder['identityId'], $builder['unenrolledAt']);
        });
    }

    protected static function defaults(): array
    {
        $now = Clock::get()->now();

        return [
            'id' => static fn (): TotpCredentialId => TotpCredentialId::fromString(Uuid::uuid7()->toString()),
            'identityId' => static fn (): string => Uuid::uuid7()->toString(),
            'secret' => static fn (): string => TOTP::generate()->getSecret(),
            'enrolledAt' => static fn (): \DateTimeImmutable => $now,
            'unenrolledAt' => static fn (): \DateTimeImmutable => $now->modify('+1 day'),
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
}
