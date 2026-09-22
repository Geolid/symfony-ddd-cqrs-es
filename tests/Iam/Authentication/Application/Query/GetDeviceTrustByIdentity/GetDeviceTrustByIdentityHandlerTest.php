<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Query\GetDeviceTrustByIdentity;

use Iam\Authentication\Application\Query\GetDeviceTrustByIdentity\GetDeviceTrustByIdentity;
use Iam\Tests\Authentication\Support\Builder\DeviceTrustBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class GetDeviceTrustByIdentityHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itFinds(): void
    {
        // Given
        $builder = DeviceTrustBuilder::new();
        $deviceTrust = $builder->create();
        $this->store($deviceTrust);

        // When
        $result = $this->ask(new GetDeviceTrustByIdentity($builder['identityId']));
        $nothing = $this->ask(new GetDeviceTrustByIdentity(DeviceTrustBuilder::sample('identityId')));

        // Then
        self::assertNotNull($result);
        self::assertSame($builder['identityId'], $result->identityId);
        self::assertSame(
            $builder['revokedAt']->format(\DateTimeInterface::ATOM),
            $result->revokedAt->format(\DateTimeInterface::ATOM),
        );

        self::assertNull($nothing);
    }
}
