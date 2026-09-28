<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Domain\DeviceTrust;

use Iam\Authentication\Domain\DeviceTrust\DeviceTrust;
use Iam\Authentication\Domain\DeviceTrust\Event\DeviceTrustRevoked;
use Iam\Authentication\Domain\DeviceTrust\ValueObject\DeviceTrustId;
use Iam\Tests\Authentication\Support\Builder\DeviceTrustBuilder;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;

final class DeviceTrustTest extends AggregateRootTestCase
{
    private DeviceTrustId $id;
    private string $identityId;
    private \DateTimeImmutable $revokedAt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->identityId = DeviceTrustBuilder::sample('identityId');
        $this->id = DeviceTrustId::forIdentity($this->identityId);
        $this->revokedAt = DeviceTrustBuilder::sample('revokedAt');
    }

    #[Test]
    public function itEstablishes(): void
    {
        $this
            ->given()
            ->when(fn (): DeviceTrust => DeviceTrust::establish($this->id, $this->identityId, $this->revokedAt))
            ->then($this->established());
    }

    #[Test]
    public function itRevokes(): void
    {
        $revokedAgainAt = DeviceTrustBuilder::sample('revokedAgainAt');

        $this
            ->given($this->established())
            ->when(fn (DeviceTrust $deviceTrust) => $deviceTrust->revoke($this->identityId, $revokedAgainAt))
            ->then(new DeviceTrustRevoked($this->id, $this->identityId, $revokedAgainAt));
    }

    protected function aggregateClass(): string
    {
        return DeviceTrust::class;
    }

    private function established(): DeviceTrustRevoked
    {
        return new DeviceTrustRevoked($this->id, $this->identityId, $this->revokedAt);
    }
}
