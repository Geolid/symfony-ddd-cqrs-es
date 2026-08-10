<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Command\SetPasswordCredential;

use Iam\Identity\Application\Command\SetPasswordCredential\SetPasswordCredential;
use Iam\Identity\Application\Exception\LoginAlreadyTakenException;
use Iam\Identity\Application\Finder\PasswordCredential\PasswordCredentialFinderInterface;
use Iam\Identity\Domain\Exception\IdentityNotActiveException;
use Iam\Identity\Domain\Exception\IdentityNotFoundException;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class SetPasswordCredentialHandlerTest extends AbstractIntegrationTestCase
{
    private PasswordCredentialFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(PasswordCredentialFinderInterface::class);
    }

    #[Test]
    public function itSetsAPasswordCredentialForAnActiveIdentity(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);

        // When
        $this->dispatch(new SetPasswordCredential($identity->id()->toString(), 'quentin', 'S3cr3t!'));

        // Then
        $result = $this->finder->ofIdentityId($identity->id()->toString());
        self::assertSame('quentin', $result->login);
    }

    #[Test]
    public function itChangesAnExistingPasswordCredential(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);
        $this->dispatch(new SetPasswordCredential($identity->id()->toString(), 'quentin', 'S3cr3t!'));

        // When
        $this->dispatch(new SetPasswordCredential($identity->id()->toString(), 'quentin', 'An0therS3cr3t!'));

        // Then
        $result = $this->finder->ofIdentityId($identity->id()->toString());
        self::assertSame('quentin', $result->login);
    }

    #[Test]
    public function itFailsWhenTheLoginIsAlreadyTaken(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);
        $this->dispatch(new SetPasswordCredential($identity->id()->toString(), 'quentin', 'S3cr3t!'));

        $other = IdentityTestFactory::new()->create();
        $this->store($other);

        // Then
        $this->expectException(LoginAlreadyTakenException::class);

        // When
        $this->dispatch(new SetPasswordCredential($other->id()->toString(), 'quentin', 'An0therS3cr3t!'));
    }

    #[Test]
    public function itFailsWhenTheIdentityDoesNotExist(): void
    {
        // Then
        $this->expectException(IdentityNotFoundException::class);

        // When
        $this->dispatch(new SetPasswordCredential(IdentityId::generate()->toString(), 'quentin', 'S3cr3t!'));
    }

    #[Test]
    public function itFailsWhenTheIdentityIsSuspended(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->suspended()->create();
        $this->store($identity);

        // Then
        $this->expectException(IdentityNotActiveException::class);

        // When
        $this->dispatch(new SetPasswordCredential($identity->id()->toString(), 'quentin', 'S3cr3t!'));
    }

    #[Test]
    public function itFailsWhenTheIdentityIsErased(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->erased()->create();
        $this->store($identity);

        // Then
        $this->expectException(IdentityNotActiveException::class);

        // When
        $this->dispatch(new SetPasswordCredential($identity->id()->toString(), 'quentin', 'S3cr3t!'));
    }
}
