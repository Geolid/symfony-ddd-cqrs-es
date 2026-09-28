<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Domain\TrustedDevice;

use Iam\Authentication\Domain\TrustedDevice\Event\TrustedDeviceRevoked;
use Iam\Authentication\Domain\TrustedDevice\Event\TrustedDeviceTrusted;
use Iam\Authentication\Domain\TrustedDevice\Exception\TrustedDeviceOwnedByAnotherIdentityException;
use Iam\Authentication\Domain\TrustedDevice\TrustedDevice;
use Iam\Authentication\Domain\TrustedDevice\ValueObject\TrustedDeviceId;
use Iam\Tests\Authentication\Support\Builder\TrustedDeviceBuilder;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;

final class TrustedDeviceTest extends AggregateRootTestCase
{
    private TrustedDeviceId $id;
    private string $identityId;
    private string $userAgent;
    private string $ip;
    private \DateTimeImmutable $trustedAt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->id = TrustedDeviceId::fromString(Uuid::uuid7()->toString());
        $this->identityId = TrustedDeviceBuilder::sample('identityId');
        $this->userAgent = TrustedDeviceBuilder::sample('userAgent');
        $this->ip = TrustedDeviceBuilder::sample('ip');
        $this->trustedAt = TrustedDeviceBuilder::sample('trustedAt');
    }

    #[Test]
    public function itTrusts(): void
    {
        $this
            ->given()
            ->when(fn (): TrustedDevice => TrustedDevice::trust(
                $this->id,
                $this->identityId,
                $this->userAgent,
                $this->ip,
                $this->trustedAt,
            ))
            ->then($this->trusted());
    }

    #[Test]
    public function itRevokes(): void
    {
        $revokedAt = TrustedDeviceBuilder::sample('revokedAt');

        $this
            ->given($this->trusted())
            ->when(fn (TrustedDevice $trustedDevice) => $trustedDevice->revoke($this->identityId, $revokedAt))
            ->then(new TrustedDeviceRevoked($this->id, $revokedAt));
    }

    #[Test]
    public function itDoesNotRevokeWhenAlreadyRevoked(): void
    {
        $revokedAt = TrustedDeviceBuilder::sample('revokedAt');

        $this
            ->given(
                $this->trusted(),
                new TrustedDeviceRevoked($this->id, $revokedAt),
            )
            ->when(fn (TrustedDevice $trustedDevice) => $trustedDevice->revoke($this->identityId, $revokedAt))
            ->then();
    }

    #[Test]
    public function itCannotRevokeWhenOwnedByAnotherIdentity(): void
    {
        $anotherIdentityId = TrustedDeviceBuilder::sample('identityId');

        $this
            ->given($this->trusted())
            ->when(static fn (TrustedDevice $trustedDevice) => $trustedDevice->revoke($anotherIdentityId, TrustedDeviceBuilder::sample('revokedAt')))
            ->expectsException(TrustedDeviceOwnedByAnotherIdentityException::class);
    }

    protected function aggregateClass(): string
    {
        return TrustedDevice::class;
    }

    private function trusted(): TrustedDeviceTrusted
    {
        return new TrustedDeviceTrusted(
            $this->id,
            $this->identityId,
            $this->userAgent,
            $this->ip,
            $this->trustedAt,
        );
    }
}
