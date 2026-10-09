<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Domain\TrustedDevice;

use Iam\Authentication\Domain\TrustedDevice\Event\TrustedDeviceRevoked;
use Iam\Authentication\Domain\TrustedDevice\Event\TrustedDeviceTrusted;
use Iam\Authentication\Domain\TrustedDevice\Exception\TrustedDeviceOwnedByAnotherIdentityException;
use Iam\Authentication\Domain\TrustedDevice\TrustedDevice;
use Iam\Authentication\Domain\TrustedDevice\ValueObject\TrustedDeviceId;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Clock\Clock;

use function Zenstruck\Foundry\faker;

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
        $this->identityId = Uuid::uuid7()->toString();
        $this->userAgent = faker()->userAgent();
        $this->ip = faker()->ipv4();
        $this->trustedAt = Clock::get()->now();
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
        $revokedAt = Clock::get()->now()->modify('+1 day');

        $this
            ->given($this->trusted())
            ->when(fn (TrustedDevice $trustedDevice) => $trustedDevice->revoke($this->identityId, $revokedAt))
            ->then(new TrustedDeviceRevoked($this->id, $revokedAt));
    }

    #[Test]
    public function itDoesNotRevokeWhenAlreadyRevoked(): void
    {
        $revokedAt = Clock::get()->now()->modify('+1 day');

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
        $anotherIdentityId = Uuid::uuid7()->toString();

        $this
            ->given($this->trusted())
            ->when(static fn (TrustedDevice $trustedDevice) => $trustedDevice->revoke($anotherIdentityId, Clock::get()->now()->modify('+1 day')))
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
