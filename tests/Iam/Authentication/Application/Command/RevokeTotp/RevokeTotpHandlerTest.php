<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Command\RevokeTotp;

use Iam\Authentication\Application\AuthenticationUniqueKey;
use Iam\Authentication\Application\Command\RevokeTotp\RevokeTotp;
use Iam\Authentication\Application\Finder\TotpCredential\TotpCredentialFinderInterface;
use Iam\Authentication\Domain\TotpCredential\Exception\TotpCredentialNotFoundException;
use Iam\Authentication\Domain\TotpCredential\Exception\TotpCredentialOwnedByAnotherIdentityException;
use Iam\Authentication\Domain\TotpCredential\Service\TotpBackupCodeHasherInterface;
use Iam\Authentication\Domain\TotpCredential\Service\TotpCipherInterface;
use Iam\Tests\Authentication\Support\Builder\TotpCredentialBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniquenessRegistryInterface;
use Support\TestCase\AbstractIntegrationTestCase;

final class RevokeTotpHandlerTest extends AbstractIntegrationTestCase
{
    private TotpCredentialFinderInterface $finder;
    private TotpCipherInterface $cipher;
    private TotpBackupCodeHasherInterface $backupCodeHasher;
    private UniquenessRegistryInterface $uniqueness;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(TotpCredentialFinderInterface::class);
        $this->cipher = $this->service(TotpCipherInterface::class);
        $this->backupCodeHasher = $this->service(TotpBackupCodeHasherInterface::class);
        $this->uniqueness = $this->service(UniquenessRegistryInterface::class);
    }

    #[Test]
    public function itRevokes(): void
    {
        // Given
        $builder = TotpCredentialBuilder::new()->withCipher($this->cipher)->withBackupCodeHasher($this->backupCodeHasher);
        $credential = $builder->create();
        $this->store($credential);

        $identityKey = UniqueKey::for(AuthenticationUniqueKey::TOTP_CREDENTIAL_IDENTITY);
        $this->uniqueness->claim($identityKey, $builder['identityId'], $credential->id->toString());

        // When
        $this->dispatch(new RevokeTotp($credential->id->toString(), $builder['identityId']));

        // Then
        $result = $this->finder->ofId($credential->id->toString());
        self::assertTrue($result->revoked);

        self::assertFalse($this->uniqueness->isClaimed($identityKey, $builder['identityId']));
    }

    #[Test]
    public function itIgnoresWhenAlreadyRevoked(): void
    {
        // Given
        $builder = TotpCredentialBuilder::new()->withCipher($this->cipher)->withBackupCodeHasher($this->backupCodeHasher)->revoked();
        $credential = $builder->create();
        $this->store($credential);

        // When
        $this->dispatch(new RevokeTotp($credential->id->toString(), $builder['identityId']));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(TotpCredentialNotFoundException::class);

        // When
        $this->dispatch(new RevokeTotp(
            Uuid::uuid7()->toString(),
            TotpCredentialBuilder::sample('identityId'),
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
        $this->dispatch(new RevokeTotp(
            $credential->id->toString(),
            TotpCredentialBuilder::sample('identityId'),
        ));
    }
}
