<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\TotpIssuance;

use Iam\Authentication\Application\Finder\TotpCredential\TotpCredentialFinderInterface;
use Iam\Authentication\Application\TotpIssuance\TotpIssuerInterface;
use Iam\Authentication\Domain\TotpCredential\Exception\InvalidTotpCodeException;
use Iam\Tests\Authentication\Support\Builder\TotpCredentialBuilder;
use OTPHP\TOTP;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class TotpIssuerTest extends AbstractIntegrationTestCase
{
    private TotpIssuerInterface $issuer;
    private TotpCredentialFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->issuer = $this->service(TotpIssuerInterface::class);
        $this->finder = $this->service(TotpCredentialFinderInterface::class);
    }

    #[Test]
    public function itIssues(): void
    {
        // Given
        $identityId = TotpCredentialBuilder::sample('identityId');
        $secret = TOTP::generate()->getSecret();
        $code = TOTP::createFromSecret($secret, Clock::get())->now();

        // When
        $backupCodes = $this->issuer->issueFor($identityId, $secret, $code);

        // Then
        $result = $this->finder->activeOfIdentityOrNull($identityId);
        self::assertNotNull($result);
        self::assertSame($identityId, $result->identityId);
        self::assertFalse($result->revoked);

        $backupCodeCount = self::getContainer()->getParameter('iam.authentication.backup_code_count');
        self::assertCount($backupCodeCount, $backupCodes);
    }

    #[Test]
    public function itFailsWhenCodeIsInvalid(): void
    {
        // Given
        $identityId = TotpCredentialBuilder::sample('identityId');
        $secret = TOTP::generate()->getSecret();

        // Then
        $this->expectException(InvalidTotpCodeException::class);

        // When
        $this->issuer->issueFor($identityId, $secret, '000000');
    }
}
