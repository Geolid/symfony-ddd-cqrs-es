<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Command\TrustDevice;

use Iam\Authentication\Application\Command\TrustDevice\TrustDevice;
use Iam\Authentication\Application\Finder\TrustedDevice\TrustedDeviceFinderInterface;
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
        $identityId = Uuid::uuid7()->toString();
        $userAgent = 'Mozilla/5.0';
        $ip = '203.0.113.42';

        // When
        $this->dispatch(new TrustDevice($id, $identityId, $userAgent, $ip));

        // Then
        $results = iterator_to_array($this->service(TrustedDeviceFinderInterface::class)->activeByIdentity($identityId), false);
        self::assertCount(1, $results);

        $result = $results[0];
        self::assertSame($id, $result->id);
        self::assertSame($identityId, $result->identityId);
        self::assertSame($userAgent, $result->userAgent);
        self::assertSame($ip, $result->ip);
    }
}
