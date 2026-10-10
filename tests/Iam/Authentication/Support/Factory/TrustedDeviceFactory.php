<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Support\Factory;

use Iam\Authentication\Domain\TrustedDevice\TrustedDevice;
use Iam\Authentication\Domain\TrustedDevice\ValueObject\TrustedDeviceId;
use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Ramsey\Uuid\Uuid;
use Support\Foundry\AbstractAggregateFactory;
use Symfony\Component\Clock\Clock;

use function Zenstruck\Foundry\faker;

/**
 * @phpstan-type Inputs = array{
 *     id: TrustedDeviceId,
 *     identityId: string,
 *     userAgent: string,
 *     ip: string,
 *     trustedAt: \DateTimeImmutable,
 *     revokedAt: \DateTimeImmutable,
 * }
 *
 * @extends AbstractAggregateFactory<TrustedDevice, Inputs>
 */
final class TrustedDeviceFactory extends AbstractAggregateFactory
{
    public static function class(): string
    {
        return TrustedDevice::class;
    }

    public function withId(TrustedDeviceId $id): self
    {
        return $this->with(['id' => $id]);
    }

    public function withIdentityId(string $identityId): self
    {
        return $this->with(['identityId' => $identityId]);
    }

    public function withTrustedAt(\DateTimeImmutable $trustedAt): self
    {
        return $this->with(['trustedAt' => $trustedAt]);
    }

    public function revoked(?\DateTimeImmutable $revokedAt = null): self
    {
        return $this->with(array_filter(['revokedAt' => $revokedAt]))->transition(
            static function (TrustedDevice $trustedDevice, array $inputs): void {
                $trustedDevice->revoke($inputs['identityId'], $inputs['revokedAt']);
            },
        );
    }

    protected static function build(array $parameters): AggregateRoot
    {
        return TrustedDevice::trust(
            id: $parameters['id'],
            identityId: $parameters['identityId'],
            userAgent: $parameters['userAgent'],
            ip: $parameters['ip'],
            trustedAt: $parameters['trustedAt'],
        );
    }

    protected function defaults(): array
    {
        $now = Clock::get()->now();

        return [
            'id' => TrustedDeviceIdFactory::new(),
            'identityId' => Uuid::uuid7()->toString(),
            'userAgent' => faker()->userAgent(),
            'ip' => faker()->ipv4(),
            'trustedAt' => $now,
            'revokedAt' => $now->modify('+1 day'),
        ];
    }
}
