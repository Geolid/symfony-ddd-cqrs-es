<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Command\ConsumeBackupCode;

use Iam\Authentication\Application\Command\ConsumeBackupCode\ConsumeBackupCode;
use Iam\Authentication\Application\CredentialVerification\BackupCodeCredentialVerifierInterface;
use Iam\Authentication\Domain\BackupCodeCredential\Exception\BackupCodeCredentialNotFoundException;
use Iam\Authentication\Domain\BackupCodeCredential\Exception\InvalidBackupCodeException;
use Iam\Authentication\Domain\BackupCodeCredential\Service\BackupCodeHasherInterface;
use Iam\Tests\Authentication\Support\Builder\BackupCodeCredentialBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

final class ConsumeBackupCodeHandlerTest extends AbstractIntegrationTestCase
{
    private BackupCodeHasherInterface $backupCodeHasher;
    private BackupCodeCredentialVerifierInterface $verifier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->backupCodeHasher = $this->service(BackupCodeHasherInterface::class);
        $this->verifier = $this->service(BackupCodeCredentialVerifierInterface::class);
    }

    #[Test]
    public function itConsumes(): void
    {
        // Given
        $builder = BackupCodeCredentialBuilder::new()->withBackupCodeHasher($this->backupCodeHasher);
        $credential = $builder->create();
        $this->store($credential);

        // When
        $this->dispatch(new ConsumeBackupCode($builder['identityId'], $builder['plainBackupCodes'][0]));

        // Then
        self::assertFalse($this->verifier->verify($builder['identityId'], $builder['plainBackupCodes'][0]));
        self::assertTrue($this->verifier->verify($builder['identityId'], $builder['plainBackupCodes'][1]));
    }

    #[Test]
    public function itFailsWhenInvalid(): void
    {
        // Given
        $builder = BackupCodeCredentialBuilder::new()->withBackupCodeHasher($this->backupCodeHasher);
        $credential = $builder->create();
        $this->store($credential);

        // Then
        $this->expectException(InvalidBackupCodeException::class);

        // When
        $this->dispatch(new ConsumeBackupCode($builder['identityId'], 'INVALIDCODE'));
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(BackupCodeCredentialNotFoundException::class);

        // When
        $this->dispatch(new ConsumeBackupCode(Uuid::uuid7()->toString(), 'INVALIDCODE'));
    }
}
