<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Command\RegenerateBackupCodes;

use Iam\Authentication\Application\Command\RegenerateBackupCodes\RegenerateBackupCodes;
use Iam\Authentication\Application\CredentialVerification\TotpCredentialVerifierInterface;
use Iam\Authentication\Domain\TotpCredential\Exception\TotpCredentialNotFoundException;
use Iam\Authentication\Domain\TotpCredential\Exception\TotpCredentialOwnedByAnotherIdentityException;
use Iam\Authentication\Domain\TotpCredential\Service\TotpBackupCodeHasherInterface;
use Iam\Authentication\Domain\TotpCredential\Service\TotpCipherInterface;
use Iam\Tests\Authentication\Support\Builder\TotpCredentialBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

final class RegenerateBackupCodesHandlerTest extends AbstractIntegrationTestCase
{
    private TotpCipherInterface $cipher;
    private TotpBackupCodeHasherInterface $backupCodeHasher;
    private TotpCredentialVerifierInterface $verifier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cipher = $this->service(TotpCipherInterface::class);
        $this->backupCodeHasher = $this->service(TotpBackupCodeHasherInterface::class);
        $this->verifier = $this->service(TotpCredentialVerifierInterface::class);
    }

    #[Test]
    public function itRegenerates(): void
    {
        // Given
        $builder = TotpCredentialBuilder::new()->withCipher($this->cipher)->withBackupCodeHasher($this->backupCodeHasher);
        $credential = $builder->create();
        $this->store($credential);

        $newBackupCodes = TotpCredentialBuilder::sample('regeneratedBackupCodes');

        // When
        $this->dispatch(new RegenerateBackupCodes($credential->id->toString(), $builder['identityId'], $newBackupCodes));

        // Then
        self::assertFalse($this->verifier->verify($builder['identityId'], $builder['plainBackupCodes'][0]));
        self::assertTrue($this->verifier->verify($builder['identityId'], $newBackupCodes[0]));
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(TotpCredentialNotFoundException::class);

        // When
        $this->dispatch(new RegenerateBackupCodes(
            Uuid::uuid7()->toString(),
            TotpCredentialBuilder::sample('identityId'),
            TotpCredentialBuilder::sample('regeneratedBackupCodes'),
        ));
    }

    #[Test]
    public function itFailsWhenOwnedByAnotherIdentity(): void
    {
        // Given
        $credential = TotpCredentialBuilder::new()->withCipher($this->cipher)->withBackupCodeHasher($this->backupCodeHasher)->create();
        $this->store($credential);

        // Then
        $this->expectException(TotpCredentialOwnedByAnotherIdentityException::class);

        // When
        $this->dispatch(new RegenerateBackupCodes(
            $credential->id->toString(),
            TotpCredentialBuilder::sample('identityId'),
            TotpCredentialBuilder::sample('regeneratedBackupCodes'),
        ));
    }
}
