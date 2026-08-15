<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Command\DefinePasswordCredential;

use Iam\Identity\Application\Command\DefinePasswordCredential\DefinePasswordCredential;
use Iam\Identity\Application\Exception\LoginAlreadyTakenException;
use Iam\Identity\Application\Finder\PasswordCredential\PasswordCredentialFinderInterface;
use Iam\Identity\Domain\Exception\IdentityNotActiveException;
use Iam\Identity\Domain\Exception\IdentityNotFoundException;
use Iam\Identity\Domain\Service\SecretHasherInterface;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Iam\Identity\Domain\ValueObject\Login;
use Iam\Identity\Domain\ValueObject\PasswordCredentialUniqueValue;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use Iam\Tests\Identity\Support\Factory\PasswordCredentialTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Shared\Domain\Service\UniqueValueRegistryInterface;
use Support\AbstractIntegrationTestCase;

final class DefinePasswordCredentialHandlerTest extends AbstractIntegrationTestCase
{
    private PasswordCredentialFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(PasswordCredentialFinderInterface::class);
    }

    #[Test]
    public function itDefinesAPasswordCredentialForAnActiveIdentity(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->store();

        // When
        $this->dispatch(new DefinePasswordCredential($identity->id()->toString(), 'operator', 'S3cr3t!'));

        // Then
        $result = $this->finder->ofIdentityId($identity->id()->toString());
        self::assertSame('operator', $result->login);
    }

    #[Test]
    public function itChangesAnExistingPasswordCredential(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->store();
        PasswordCredentialTestFactory::new()
            ->withIdentityId($identity->id()->toString())
            ->withLogin('operator')
            ->withHasher($this->service(SecretHasherInterface::class))
            ->store();

        // When
        $this->dispatch(new DefinePasswordCredential($identity->id()->toString(), 'operator', 'NewS3cr3t!'));

        // Then
        $result = $this->finder->ofIdentityId($identity->id()->toString());
        self::assertSame('operator', $result->login);
    }

    #[Test]
    public function itFailsWhenTheLoginIsAlreadyTaken(): void
    {
        // Given
        $this->service(UniqueValueRegistryInterface::class)->reserve(PasswordCredentialUniqueValue::LOGIN, Login::fromString('operator')->fingerprint());
        $identity = IdentityTestFactory::new()->store();

        // Then
        $this->expectException(LoginAlreadyTakenException::class);

        // When
        $this->dispatch(new DefinePasswordCredential($identity->id()->toString(), 'operator', 'NewS3cr3t!'));
    }

    #[Test]
    public function itFailsWhenTheIdentityDoesNotExist(): void
    {
        // Then
        $this->expectException(IdentityNotFoundException::class);

        // When
        $this->dispatch(new DefinePasswordCredential(IdentityId::generate()->toString(), 'operator', 'S3cr3t!'));
    }

    #[Test]
    public function itFailsWhenTheIdentityIsSuspended(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->suspended()->store();

        // Then
        $this->expectException(IdentityNotActiveException::class);

        // When
        $this->dispatch(new DefinePasswordCredential($identity->id()->toString(), 'operator', 'S3cr3t!'));
    }

    #[Test]
    public function itFailsWhenTheIdentityIsErased(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->erased()->store();

        // Then
        $this->expectException(IdentityNotActiveException::class);

        // When
        $this->dispatch(new DefinePasswordCredential($identity->id()->toString(), 'operator', 'S3cr3t!'));
    }
}
