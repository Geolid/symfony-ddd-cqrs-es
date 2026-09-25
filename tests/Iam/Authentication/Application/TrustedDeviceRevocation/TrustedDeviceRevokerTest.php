<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\TrustedDeviceRevocation;

use Iam\Authentication\Application\Finder\TrustedDevice\TrustedDeviceFinderInterface;
use Iam\Authentication\Application\TrustedDeviceRevocation\TrustedDeviceRevokerInterface;
use Iam\Tests\Authentication\Support\Builder\TrustedDeviceBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class TrustedDeviceRevokerTest extends AbstractIntegrationTestCase
{
    private TrustedDeviceRevokerInterface $revoker;
    private TrustedDeviceFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->revoker = $this->service(TrustedDeviceRevokerInterface::class);
        $this->finder = $this->service(TrustedDeviceFinderInterface::class);
    }

    #[Test]
    public function itRevokesAllFor(): void
    {
        // Given
        $otherBuilder = TrustedDeviceBuilder::new();
        $other = $otherBuilder->create();

        $identityId = TrustedDeviceBuilder::sample('identityId');
        $first = TrustedDeviceBuilder::new()->withIdentityId($identityId)->create();
        $second = TrustedDeviceBuilder::new()->withIdentityId($identityId)->create();

        $this->store($other, $first, $second);

        // When
        $this->revoker->revokeAllFor($identityId);

        // Then
        self::assertCount(0, $this->finder->activeByIdentity($identityId));
        self::assertCount(1, $this->finder->activeByIdentity($otherBuilder['identityId']));
    }
}
