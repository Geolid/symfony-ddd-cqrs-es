<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Infrastructure\Security;

use Iam\Identity\Application\Exception\PasswordCredentialAuthenticationFailedException;
use Iam\Identity\Application\Security\PasswordCredentialAuthenticatorInterface;
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
            ->withIdentityId($identityId)
            ->withLogin('buyer@example.com')
            ->withPassword('correct horse battery staple')
            ->create());

        // When
        $result = $this->service(PasswordCredentialAuthenticatorInterface::class)->authenticate('buyer@example.com', 'correct horse battery staple');

        // Then
        self::assertSame($identityId, $result);
    }

    #[Test]
    public function itRefusesAnUnknownLogin(): void
    {
        // Then
        $this->expectException(PasswordCredentialAuthenticationFailedException::class);

        // When
        $this->service(PasswordCredentialAuthenticatorInterface::class)->authenticate('ghost@example.com', 'whatever');
    }

    #[Test]
    public function itRefusesAnIncorrectPassword(): void
    {
        // Given
        $this->store(PasswordCredentialTestFactory::new()
            ->withIdentityId(IdentityId::generate()->toString())
            ->withLogin('buyer@example.com')
            ->withPassword('correct horse battery staple')
            ->create());

        // Then
        $this->expectException(PasswordCredentialAuthenticationFailedException::class);

        // When
        $this->service(PasswordCredentialAuthenticatorInterface::class)->authenticate('buyer@example.com', 'wrong password');
    }
}
