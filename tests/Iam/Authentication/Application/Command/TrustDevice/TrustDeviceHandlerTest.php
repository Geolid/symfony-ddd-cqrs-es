<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Command\TrustDevice;

use Iam\Authentication\Application\Command\TrustDevice\TrustDevice;
use Iam\Authentication\Application\Finder\TrustedDevice\TrustedDeviceFinderInterface;
use Iam\Tests\Authentication\Support\Builder\TrustedDeviceBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

final class TrustDeviceHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itTrusts(): void
    {
        // Given
        $id = Uuid::uuid7()->toString();
        $identityId = TrustedDeviceBuilder::sample('identityId');
        $version = TrustedDeviceBuilder::sample('version');
        $userAgent = TrustedDeviceBuilder::sample('userAgent');
        $ip = TrustedDeviceBuilder::sample('ip');

        // When
        $this->dispatch(new TrustDevice($id, $identityId, $version, $userAgent, $ip));

        // Then
        $results = iterator_to_array($this->service(TrustedDeviceFinderInterface::class)->activeByIdentity($identityId), false);
        self::assertCount(1, $results);

        $result = $results[0];
        self::assertSame($id, $result->id);
        self::assertSame($identityId, $result->identityId);
        self::assertSame($version, $result->version);
        self::assertSame($userAgent, $result->userAgent);
        self::assertSame($ip, $result->ip);
    }
}
