<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\TotpIssuance;

use Iam\Authentication\Application\CredentialVerification\TotpCredentialVerifierInterface;
use Iam\Authentication\Application\TotpIssuance\Exception\TotpNotIssuedException;
use Iam\Authentication\Application\TotpIssuance\TotpBackupCodeRegeneratorInterface;
use Iam\Authentication\Domain\TotpCredential\Service\TotpBackupCodeHasherInterface;
use Iam\Authentication\Domain\TotpCredential\Service\TotpCipherInterface;
use Iam\Tests\Authentication\Support\Builder\TotpCredentialBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class TotpBackupCodeRegeneratorTest extends AbstractIntegrationTestCase
{
    private TotpCipherInterface $cipher;
    private TotpBackupCodeHasherInterface $backupCodeHasher;
    private TotpBackupCodeRegeneratorInterface $regenerator;
    private TotpCredentialVerifierInterface $verifier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cipher = $this->service(TotpCipherInterface::class);
        $this->backupCodeHasher = $this->service(TotpBackupCodeHasherInterface::class);
        $this->regenerator = $this->service(TotpBackupCodeRegeneratorInterface::class);
        $this->verifier = $this->service(TotpCredentialVerifierInterface::class);
    }

    #[Test]
    public function itRegenerates(): void
    {
        // Given
        $builder = TotpCredentialBuilder::new()->withCipher($this->cipher)->withBackupCodeHasher($this->backupCodeHasher);
        $credential = $builder->create();
        $this->store($credential);

        // When
        $newBackupCodes = $this->regenerator->regenerateFor($builder['identityId']);

        // Then
        self::assertNotEmpty($newBackupCodes);
        self::assertFalse($this->verifier->verify($builder['identityId'], $builder['plainBackupCodes'][0]));
        self::assertTrue($this->verifier->verify($builder['identityId'], $newBackupCodes[0]));
    }

    #[Test]
    public function itFailsWhenNotIssued(): void
    {
        // Then
        $this->expectException(TotpNotIssuedException::class);

        // When
        $this->regenerator->regenerateFor(TotpCredentialBuilder::sample('identityId'));
    }
}
