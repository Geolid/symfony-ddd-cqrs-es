<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Command\RevokeDeviceTrust;

use Iam\Authentication\Application\Command\RevokeDeviceTrust\RevokeDeviceTrust;
use Iam\Authentication\Application\Finder\DeviceTrust\DeviceTrustFinderInterface;
use Iam\Tests\Authentication\Support\Builder\DeviceTrustBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class RevokeDeviceTrustHandlerTest extends AbstractIntegrationTestCase
{
    private DeviceTrustFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(DeviceTrustFinderInterface::class);
    }

    #[Test]
    public function itEstablishesWhenNoneExists(): void
    {
        // Given
        $identityId = DeviceTrustBuilder::sample('identityId');

        // When
        $this->dispatch(new RevokeDeviceTrust($identityId));

        // Then
        $result = $this->finder->ofIdentityOrNull($identityId);
        self::assertNotNull($result);
        self::assertSame($identityId, $result->identityId);
    }

    #[Test]
    public function itRevokesAgainWhenAlreadyEstablished(): void
    {
        // Given
        $builder = DeviceTrustBuilder::new()->withRevokedAt(Clock::get()->now()->modify('-1 day'));
        $deviceTrust = $builder->create();
        $this->store($deviceTrust);

        $previousRevokedAt = $builder['revokedAt'];

        // When
        $this->dispatch(new RevokeDeviceTrust($builder['identityId']));

        // Then
        $result = $this->finder->ofIdentityOrNull($builder['identityId']);
        self::assertNotNull($result);
        self::assertNotSame($previousRevokedAt->format(\DateTimeInterface::ATOM), $result->revokedAt->format(\DateTimeInterface::ATOM));
    }
}
