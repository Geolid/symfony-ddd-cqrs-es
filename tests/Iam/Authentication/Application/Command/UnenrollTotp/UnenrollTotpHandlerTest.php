<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Command\UnenrollTotp;

use Iam\Authentication\Application\AuthenticationUniqueKey;
use Iam\Authentication\Application\Command\UnenrollTotp\UnenrollTotp;
use Iam\Authentication\Application\Finder\TotpCredential\TotpCredentialFinderInterface;
use Iam\Authentication\Domain\TotpCredential\Exception\TotpCredentialNotFoundException;
use Iam\Authentication\Domain\TotpCredential\Exception\TotpCredentialOwnedByAnotherIdentityException;
use Iam\Authentication\Domain\TotpCredential\Service\TotpCipherInterface;
use Iam\Tests\Authentication\Support\Factory\TotpCredentialFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniquenessRegistryInterface;
use Support\TestCase\AbstractIntegrationTestCase;

final class UnenrollTotpHandlerTest extends AbstractIntegrationTestCase
{
    private TotpCredentialFinderInterface $finder;
    private TotpCipherInterface $cipher;
    private UniquenessRegistryInterface $uniqueness;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(TotpCredentialFinderInterface::class);
        $this->cipher = $this->service(TotpCipherInterface::class);
        $this->uniqueness = $this->service(UniquenessRegistryInterface::class);
    }

    #[Test]
    public function itUnenrolls(): void
    {
        // Given
        $credential = TotpCredentialFactory::new()->withCipher($this->cipher)->create();
        $this->store($credential);

        $identityKey = UniqueKey::for(AuthenticationUniqueKey::TOTP_CREDENTIAL_IDENTITY);
        $this->uniqueness->claim($identityKey, $credential->identityId, $credential->id->toString());

        // When
        $this->dispatch(new UnenrollTotp($credential->id->toString(), $credential->identityId));

        // Then
        $result = $this->finder->ofId($credential->id->toString());
        self::assertTrue($result->unenrolled);

        self::assertFalse($this->uniqueness->isClaimed($identityKey, $credential->identityId));
    }

    #[Test]
    public function itIgnoresWhenAlreadyUnenrolled(): void
    {
        // Given
        $credential = TotpCredentialFactory::new()->withCipher($this->cipher)->unenrolled()->create();
        $this->store($credential);

        // When
        $this->dispatch(new UnenrollTotp($credential->id->toString(), $credential->identityId));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(TotpCredentialNotFoundException::class);

        // When
        $this->dispatch(new UnenrollTotp(
            Uuid::uuid7()->toString(),
            Uuid::uuid7()->toString(),
        ));
    }

    #[Test]
    public function itFailsWhenOwnedByAnotherIdentity(): void
    {
        // Given
        $credential = TotpCredentialFactory::new()->withCipher($this->cipher)->create();
        $this->store($credential);

        // Then
        $this->expectException(TotpCredentialOwnedByAnotherIdentityException::class);

        // When
        $this->dispatch(new UnenrollTotp(
            $credential->id->toString(),
            Uuid::uuid7()->toString(),
        ));
    }
}
