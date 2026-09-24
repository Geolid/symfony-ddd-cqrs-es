<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Support\Builder;

use Iam\Authentication\Domain\DeviceTrust\DeviceTrust;
use Iam\Authentication\Domain\DeviceTrust\ValueObject\DeviceTrustId;
use Ramsey\Uuid\Uuid;
use Support\Builder\AbstractAggregateBuilder;
use Symfony\Component\Clock\Clock;

/**
 * @phpstan-type Attributes = array{
 *     id: DeviceTrustId,
 *     identityId: string,
 *     revokedAt: \DateTimeImmutable,
 *     revokedAgainAt: \DateTimeImmutable,
 * }
 *
 * @extends AbstractAggregateBuilder<DeviceTrust, Attributes>
 */
final class DeviceTrustBuilder extends AbstractAggregateBuilder
{
    public function withIdentityId(string $identityId): self
    {
        return $this->withAttributes(identityId: $identityId);
    }

    public function withRevokedAt(\DateTimeImmutable $revokedAt): self
    {
        return $this->withAttributes(revokedAt: $revokedAt);
    }

    public function revokedAgain(?\DateTimeImmutable $revokedAgainAt = null): self
    {
        $builder = null !== $revokedAgainAt ? $this->withAttributes(revokedAgainAt: $revokedAgainAt) : $this;

        return $builder->withModifier(static function (DeviceTrust $deviceTrust, self $builder): void {
            $deviceTrust->revoke($builder['identityId'], $builder['revokedAgainAt']);
        });
    }

    protected static function defaults(): array
    {
        $now = Clock::get()->now();

        return [
            'id' => static fn (?self $builder): DeviceTrustId => DeviceTrustId::forIdentity(
                null !== $builder ? $builder['identityId'] : self::sample('identityId'),
            ),
            'identityId' => static fn (): string => Uuid::uuid7()->toString(),
            'revokedAt' => static fn (): \DateTimeImmutable => $now,
            'revokedAgainAt' => static fn (): \DateTimeImmutable => $now->modify('+1 day'),
        ];
    }

    protected function build(): DeviceTrust
    {
        return DeviceTrust::establish(
            id: $this['id'],
            identityId: $this['identityId'],
            revokedAt: $this['revokedAt'],
        );
    }
}
