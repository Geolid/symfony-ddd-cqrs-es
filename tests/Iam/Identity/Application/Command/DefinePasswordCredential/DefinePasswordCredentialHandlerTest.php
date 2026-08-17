<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Command\DefinePasswordCredential;

use Iam\Identity\Application\Command\DefinePasswordCredential\DefinePasswordCredential;
use Iam\Identity\Application\Exception\LoginAlreadyTakenException;
use Iam\Identity\Application\Finder\PasswordCredential\PasswordCredentialFinderInterface;
use Iam\Identity\Domain\Exception\IdentityNotActiveException;
use Iam\Identity\Domain\Exception\IdentityNotFoundException;
use Iam\Identity\Domain\Exception\PasswordUnchangedException;
use Iam\Identity\Domain\Exception\WeakPasswordException;
use Iam\Identity\Domain\Service\PasswordPolicyInterface;
use Iam\Identity\Domain\Service\SecretHasherInterface;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Iam\Identity\Domain\ValueObject\PasswordCredentialUniqueKey;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use Iam\Tests\Identity\Support\Factory\PasswordCredentialTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Domain\Service\UniqueValueRegistryInterface;
use Shared\Domain\ValueObject\UniqueKey;
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
        $this->dispatch(new DefinePasswordCredential($identity->id()->toString(), 'operator', 'MyStr0ngP@ssw0rd123!'));

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
            ->withPassword('OldStr0ngP@ssw0rd123!')
            ->withHasher($this->service(SecretHasherInterface::class))
            ->withPolicy($this->service(PasswordPolicyInterface::class))
            ->store();

        // When
        $this->dispatch(new DefinePasswordCredential($identity->id()->toString(), 'operator', 'NewStr0ngP@ssw0rd456!'));

        // Then
        $result = $this->finder->ofIdentityId($identity->id()->toString());
        self::assertSame('operator', $result->login);
    }

    #[Test]
    public function itFailsWhenTheNewPasswordMatchesTheCurrentOne(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->store();
        PasswordCredentialTestFactory::new()
            ->withIdentityId($identity->id()->toString())
            ->withLogin('operator')
            ->withPassword('MyStr0ngP@ssw0rd123!')
            ->withHasher($this->service(SecretHasherInterface::class))
            ->withPolicy($this->service(PasswordPolicyInterface::class))
            ->store();

        // Then
        $this->expectException(PasswordUnchangedException::class);

        // When
        $this->dispatch(new DefinePasswordCredential($identity->id()->toString(), 'operator', 'MyStr0ngP@ssw0rd123!'));
    }

    #[Test]
    public function itFailsWhenTheNewPasswordIsTooWeak(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->store();

        // Then
        $this->expectException(WeakPasswordException::class);

        // When
        $this->dispatch(new DefinePasswordCredential($identity->id()->toString(), 'operator', 'passwordpassword'));
    }

    #[Test]
    public function itFailsWhenTheLoginIsAlreadyTaken(): void
    {
        // Given
        $this->service(UniqueValueRegistryInterface::class)->reserve(UniqueKey::for(PasswordCredentialUniqueKey::LOGIN), 'operator', Uuid::uuid7()->toString());
        $identity = IdentityTestFactory::new()->store();

        // Then
        $this->expectException(LoginAlreadyTakenException::class);

        // When
        $this->dispatch(new DefinePasswordCredential($identity->id()->toString(), 'operator', 'NewStr0ngP@ssw0rd456!'));
    }

    #[Test]
    public function itFailsWhenTheIdentityDoesNotExist(): void
    {
        // Then
        $this->expectException(IdentityNotFoundException::class);

        // When
        $this->dispatch(new DefinePasswordCredential(IdentityId::generate()->toString(), 'operator', 'MyStr0ngP@ssw0rd123!'));
    }

    #[Test]
    public function itFailsWhenTheIdentityIsSuspended(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->suspended()->store();

        // Then
        $this->expectException(IdentityNotActiveException::class);

        // When
        $this->dispatch(new DefinePasswordCredential($identity->id()->toString(), 'operator', 'MyStr0ngP@ssw0rd123!'));
    }

    #[Test]
    public function itFailsWhenTheIdentityIsErased(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->erased()->store();

        // Then
        $this->expectException(IdentityNotActiveException::class);

        // When
        $this->dispatch(new DefinePasswordCredential($identity->id()->toString(), 'operator', 'MyStr0ngP@ssw0rd123!'));
    }
}
