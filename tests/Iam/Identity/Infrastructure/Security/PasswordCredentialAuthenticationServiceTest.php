<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Infrastructure\Security;

use Iam\Identity\Application\Security\AuthenticatePasswordCredentialInterface;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Iam\Tests\Identity\Support\Factory\PasswordCredentialTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class PasswordCredentialAuthenticationServiceTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itAuthenticatesAValidLoginAndPassword(): void
    {
        // Given
        $identityId = IdentityId::generate()->toString();
        $this->store(PasswordCredentialTestFactory::new()
            ->forIdentity($identityId)
            ->withLogin('buyer@example.com')
            ->withPassword('correct horse battery staple')
            ->create());

        // When
        $result = $this->service(AuthenticatePasswordCredentialInterface::class)->authenticate('buyer@example.com', 'correct horse battery staple');

        // Then
        self::assertSame($identityId, $result);
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
        $this->store(PasswordCredentialTestFactory::new()
            ->forIdentity(IdentityId::generate()->toString())
            ->withLogin('buyer@example.com')
            ->withPassword('correct horse battery staple')
            ->create());

        // When
        $result = $this->service(AuthenticatePasswordCredentialInterface::class)->authenticate('buyer@example.com', 'wrong password');

        // Then
        self::assertNull($result);
    }
}
