<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Command\ChangePassword;

use Iam\Authentication\Application\Command\ChangePassword\ChangePassword;
use Iam\Authentication\Application\Exception\AuthenticatableIdentityResultNotFoundException;
use Iam\Authentication\Application\Exception\IdentityNotAuthenticatableException;
use Iam\Authentication\Application\Finder\PasswordCredential\PasswordCredentialFinderInterface;
use Iam\Authentication\Domain\PasswordCredential\Exception\PasswordCredentialNotFoundException;
use Iam\Authentication\Domain\PasswordCredential\Exception\SamePasswordException;
use Iam\Authentication\Domain\PasswordCredential\Exception\WeakPasswordException;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordPolicyInterface;
use Iam\Tests\Authentication\Support\Factory\PasswordCredentialTestFactory;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\AbstractIntegrationTestCase;

final class ChangePasswordHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itChanges(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->store();
        PasswordCredentialTestFactory::new()
            ->withIdentityId($identity->id->toString())
            ->withPolicy($this->service(PasswordPolicyInterface::class))
            ->withHasher($this->service(PasswordHasherInterface::class))
            ->store();

        // When
        $this->dispatch(new ChangePassword($identity->id->toString(), 'Qm3&nJ8wXv5Tz1p!'));

        // Then
        $result = $this->service(PasswordCredentialFinderInterface::class)->ofIdentityId($identity->id->toString());
        self::assertTrue($this->service(PasswordHasherInterface::class)->verify($result->passwordHash, 'Qm3&nJ8wXv5Tz1p!'));
    }

    #[Test]
    public function itFailsWhenIdentityNotFound(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();

        // Then
        $this->expectException(AuthenticatableIdentityResultNotFoundException::class);

        // When
        $this->dispatch(new ChangePassword($identityId, 'Qm3&nJ8wXv5Tz1p!'));
    }

    #[Test]
    public function itFailsWhenIdentityNotAuthenticatable(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->suspended()->store();

        // Then
        $this->expectException(IdentityNotAuthenticatableException::class);

        // When
        $this->dispatch(new ChangePassword($identity->id->toString(), 'Qm3&nJ8wXv5Tz1p!'));
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->store();

        // Then
        $this->expectException(PasswordCredentialNotFoundException::class);

        // When
        $this->dispatch(new ChangePassword($identity->id->toString(), 'Qm3&nJ8wXv5Tz1p!'));
    }

    #[Test]
    public function itFailsWhenPasswordWeak(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->store();
        PasswordCredentialTestFactory::new()
            ->withIdentityId($identity->id->toString())
            ->withPolicy($this->service(PasswordPolicyInterface::class))
            ->withHasher($this->service(PasswordHasherInterface::class))
            ->store();

        // Then
        $this->expectException(WeakPasswordException::class);

        // When
        $this->dispatch(new ChangePassword($identity->id->toString(), 'aaaaaaaaaaaa'));
    }

    #[Test]
    public function itFailsWhenPasswordSame(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->store();
        PasswordCredentialTestFactory::new()
            ->withIdentityId($identity->id->toString())
            ->withPassword('Xk9$mQ2vLp7&zR4w')
            ->withPolicy($this->service(PasswordPolicyInterface::class))
            ->withHasher($this->service(PasswordHasherInterface::class))
            ->store();

        // Then
        $this->expectException(SamePasswordException::class);

        // When
        $this->dispatch(new ChangePassword($identity->id->toString(), 'Xk9$mQ2vLp7&zR4w'));
    }
}
