<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Infrastructure\Security;

use Iam\Identity\Application\Port\AuthenticatePasswordCredentialInterface;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use Iam\Tests\Identity\Support\Factory\PasswordCredentialTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class PasswordCredentialAuthenticationServiceTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itAuthenticatesAValidLoginAndPassword(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);
        $credential = PasswordCredentialTestFactory::new()
            ->forIdentity($identity->id()->toString())
            ->withLogin('buyer@example.com')
            ->withPassword('correct horse battery staple')
            ->create();
        $this->store($credential);

        // When
        $result = $this->service(AuthenticatePasswordCredentialInterface::class)->authenticate('buyer@example.com', 'correct horse battery staple');

        // Then
        self::assertSame($identity->id()->toString(), $result);
    }

    #[Test]
    public function itRefusesAnUnknownLogin(): void
    {
        // When
        $result = $this->service(AuthenticatePasswordCredentialInterface::class)->authenticate('ghost@example.com', 'whatever');

        // Then
        self::assertNull($result);
    }

    #[Test]
    public function itRefusesAnIncorrectPassword(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);
        $this->store(PasswordCredentialTestFactory::new()
            ->forIdentity($identity->id()->toString())
            ->withLogin('buyer@example.com')
            ->withPassword('correct horse battery staple')
            ->create());

        // When
        $result = $this->service(AuthenticatePasswordCredentialInterface::class)->authenticate('buyer@example.com', 'wrong password');

        // Then
        self::assertNull($result);
    }

    #[Test]
    public function itRefusesASuspendedIdentity(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->suspended()->create();
        $this->store($identity);
        $this->store(PasswordCredentialTestFactory::new()
            ->forIdentity($identity->id()->toString())
            ->withLogin('buyer@example.com')
            ->withPassword('correct horse battery staple')
            ->create());

        // When
        $result = $this->service(AuthenticatePasswordCredentialInterface::class)->authenticate('buyer@example.com', 'correct horse battery staple');

        // Then
        self::assertNull($result);
    }
}
