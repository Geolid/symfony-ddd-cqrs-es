<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\Totp;

use Iam\Authentication\Infrastructure\Totp\OtphpTotpProvisioning;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\Clock;

final class OtphpTotpProvisioningTest extends TestCase
{
    private const string SECRET = 'GEZDGNBVGY3TQOJQ';

    private OtphpTotpProvisioning $provisioning;

    protected function setUp(): void
    {
        parent::setUp();

        $this->provisioning = new OtphpTotpProvisioning(Clock::get());
    }

    #[Test]
    public function itGeneratesASecret(): void
    {
        // When
        $secret = $this->provisioning->generateSecret();

        // Then
        self::assertNotSame('', $secret);
        self::assertSame(32, \strlen($secret));
        self::assertNotSame($secret, $this->provisioning->generateSecret());
    }

    #[Test]
    public function itBuildsAProvisioningUri(): void
    {
        // When
        $provisioningUri = $this->provisioning->provisioningUri(self::SECRET, 'john.doe', 'Storefront');

        // Then
        self::assertStringContainsString('secret='.self::SECRET, $provisioningUri);
        self::assertStringContainsString('issuer=Storefront', $provisioningUri);
        self::assertStringStartsWith('otpauth://totp/Storefront%3Ajohn.doe', $provisioningUri);
    }
}
