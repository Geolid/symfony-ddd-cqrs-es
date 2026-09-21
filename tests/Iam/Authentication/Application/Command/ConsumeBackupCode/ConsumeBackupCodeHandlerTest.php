<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Command\ConsumeBackupCode;

use Iam\Authentication\Application\Command\ConsumeBackupCode\ConsumeBackupCode;
use Iam\Authentication\Application\CredentialVerification\TotpCredentialVerifierInterface;
use Iam\Authentication\Domain\TotpCredential\Exception\InvalidBackupCodeException;
use Iam\Authentication\Domain\TotpCredential\Exception\TotpCredentialNotFoundException;
use Iam\Authentication\Domain\TotpCredential\Service\TotpBackupCodeHasherInterface;
use Iam\Authentication\Domain\TotpCredential\Service\TotpCipherInterface;
use Iam\Tests\Authentication\Support\Builder\TotpCredentialBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

final class ConsumeBackupCodeHandlerTest extends AbstractIntegrationTestCase
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
    public function itConsumes(): void
    {
        // Given
        $builder = TotpCredentialBuilder::new()->withCipher($this->cipher)->withBackupCodeHasher($this->backupCodeHasher);
        $credential = $builder->create();
        $this->store($credential);

        // When
        $this->dispatch(new ConsumeBackupCode($credential->id->toString(), $builder['plainBackupCodes'][0]));

        // Then
        self::assertFalse($this->verifier->verify($builder['identityId'], $builder['plainBackupCodes'][0]));
        self::assertTrue($this->verifier->verify($builder['identityId'], $builder['plainBackupCodes'][1]));
    }

    #[Test]
    public function itFailsWhenInvalid(): void
    {
        // Given
        $credential = TotpCredentialBuilder::new()->withCipher($this->cipher)->withBackupCodeHasher($this->backupCodeHasher)->create();
        $this->store($credential);

        // Then
        $this->expectException(InvalidBackupCodeException::class);

        // When
        $this->dispatch(new ConsumeBackupCode($credential->id->toString(), 'INVALIDCODE'));
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(TotpCredentialNotFoundException::class);

        // When
        $this->dispatch(new ConsumeBackupCode(Uuid::uuid7()->toString(), 'INVALIDCODE'));
    }
}
