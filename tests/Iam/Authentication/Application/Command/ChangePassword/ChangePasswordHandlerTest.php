<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Command\ChangePassword;

use Iam\Authentication\Application\Command\ChangePassword\ChangePassword;
use Iam\Authentication\Application\Credential\CompromisedPasswordGatewayInterface;
use Iam\Authentication\Application\Exception\CompromisedPasswordException;
use Iam\Authentication\Application\Finder\PasswordCredential\PasswordCredentialFinderInterface;
use Iam\Authentication\Domain\PasswordCredential\Exception\PasswordCredentialNotFoundException;
use Iam\Authentication\Domain\PasswordCredential\Exception\SamePasswordException;
use Iam\Authentication\Domain\PasswordCredential\Exception\WeakPasswordException;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordStrengthInterface;
use Iam\Tests\Authentication\Support\Doubles\StubCompromisedPasswordGateway;
use Iam\Tests\Authentication\Support\Factory\PasswordCredentialTestFactory;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class ChangePasswordHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itChanges(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->create();
        $credential = PasswordCredentialTestFactory::new()
            ->withIdentityId($identity->id->toString())
            ->withPasswordStrength($this->service(PasswordStrengthInterface::class))
            ->withHasher($this->service(PasswordHasherInterface::class))
            ->create();
        $this->store($identity, $credential);

        // When
        $this->dispatch(new ChangePassword($identity->id->toString(), 'Qm3&nJ8wXv5Tz1p!'));

        // Then
        $result = $this->service(PasswordCredentialFinderInterface::class)->ofIdentityId($identity->id->toString());
        self::assertTrue($this->service(PasswordHasherInterface::class)->verify($result->passwordHash, 'Qm3&nJ8wXv5Tz1p!'));
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);

        // Then
        $this->expectException(PasswordCredentialNotFoundException::class);

        // When
        $this->dispatch(new ChangePassword($identity->id->toString(), 'Qm3&nJ8wXv5Tz1p!'));
    }

    #[Test]
    public function itFailsWhenCompromisedPassword(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->create();
        $credential = PasswordCredentialTestFactory::new()
            ->withIdentityId($identity->id->toString())
            ->withPasswordStrength($this->service(PasswordStrengthInterface::class))
            ->withHasher($this->service(PasswordHasherInterface::class))
            ->create();
        $this->store($identity, $credential);
        self::getContainer()->set(CompromisedPasswordGatewayInterface::class, new StubCompromisedPasswordGateway(compromised: true));

        // Then
        $this->expectException(CompromisedPasswordException::class);

        // When
        $this->dispatch(new ChangePassword($identity->id->toString(), 'Qm3&nJ8wXv5Tz1p!'));
    }

    #[Test]
    public function itFailsWhenWeakPassword(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->create();
        $credential = PasswordCredentialTestFactory::new()
            ->withIdentityId($identity->id->toString())
            ->withPasswordStrength($this->service(PasswordStrengthInterface::class))
            ->withHasher($this->service(PasswordHasherInterface::class))
            ->create();
        $this->store($identity, $credential);

        // Then
        $this->expectException(WeakPasswordException::class);

        // When
        $this->dispatch(new ChangePassword($identity->id->toString(), 'aaaaaaaaaaaa'));
    }

    #[Test]
    public function itFailsWhenSamePassword(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->create();
        $credential = PasswordCredentialTestFactory::new()
            ->withIdentityId($identity->id->toString())
            ->withPassword('Xk9$mQ2vLp7&zR4w')
            ->withPasswordStrength($this->service(PasswordStrengthInterface::class))
            ->withHasher($this->service(PasswordHasherInterface::class))
            ->create();
        $this->store($identity, $credential);

        // Then
        $this->expectException(SamePasswordException::class);

        // When
        $this->dispatch(new ChangePassword($identity->id->toString(), 'Xk9$mQ2vLp7&zR4w'));
    }
}
