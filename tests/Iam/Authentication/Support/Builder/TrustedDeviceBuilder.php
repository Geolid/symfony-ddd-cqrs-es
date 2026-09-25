<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Support\Builder;

use Iam\Authentication\Domain\TrustedDevice\TrustedDevice;
use Iam\Authentication\Domain\TrustedDevice\ValueObject\TrustedDeviceId;
use Ramsey\Uuid\Uuid;
use Support\Builder\AbstractAggregateBuilder;
use Symfony\Component\Clock\Clock;

/**
 * @phpstan-type Attributes = array{
 *     id: TrustedDeviceId,
 *     identityId: string,
 *     userAgent: string,
 *     ip: string,
 *     trustedAt: \DateTimeImmutable,
 *     revokedAt: \DateTimeImmutable,
 * }
 *
 * @extends AbstractAggregateBuilder<TrustedDevice, Attributes>
 */
final class TrustedDeviceBuilder extends AbstractAggregateBuilder
{
    public function withIdentityId(string $identityId): self
    {
        return $this->withAttributes(identityId: $identityId);
    }

    public function withTrustedAt(\DateTimeImmutable $trustedAt): self
    {
        return $this->withAttributes(trustedAt: $trustedAt);
    }

    public function revoked(?\DateTimeImmutable $revokedAt = null): self
    {
        $builder = null !== $revokedAt ? $this->withAttributes(revokedAt: $revokedAt) : $this;

        return $builder->withModifier(static function (TrustedDevice $trustedDevice, self $builder): void {
            $trustedDevice->revoke($builder['identityId'], $builder['revokedAt']);
        });
    }

    protected static function defaults(): array
    {
        $now = Clock::get()->now();

        return [
            'id' => static fn (): TrustedDeviceId => TrustedDeviceId::fromString(Uuid::uuid7()->toString()),
            'identityId' => static fn (): string => Uuid::uuid7()->toString(),
            'userAgent' => static fn (): string => 'Mozilla/5.0',
            'ip' => static fn (): string => '203.0.113.42',
            'trustedAt' => static fn (): \DateTimeImmutable => $now,
            'revokedAt' => static fn (): \DateTimeImmutable => $now->modify('+1 day'),
        ];
    }

    protected function build(): TrustedDevice
    {
        return TrustedDevice::trust(
            id: $this['id'],
            identityId: $this['identityId'],
            userAgent: $this['userAgent'],
            ip: $this['ip'],
            trustedAt: $this['trustedAt'],
        );
    }
}
