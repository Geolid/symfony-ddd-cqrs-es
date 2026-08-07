<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Infrastructure\Security;

use Iam\Identity\Application\Exception\ApiTokenCredentialAuthenticationFailedException;
use Iam\Identity\Application\Security\ApiTokenCredentialAuthenticatorInterface;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Iam\Tests\Identity\Support\Factory\ApiTokenCredentialTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class ApiTokenCredentialAuthenticationServiceTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itAuthenticatesAValidIdentifierAndSecret(): void
    {
        // Given
        $identityId = IdentityId::generate()->toString();
        $this->store(ApiTokenCredentialTestFactory::new()
            ->withIdentityId($identityId)
            ->withIdentifier('key_abc123')
            ->withSecret('super-secret')
            ->create());

        // When
        $result = $this->service(ApiTokenCredentialAuthenticatorInterface::class)->authenticate('key_abc123', 'super-secret');

        // Then
        self::assertSame($identityId, $result);
    }

    #[Test]
    public function itRefusesAnUnknownIdentifier(): void
    {
        // Then
        $this->expectException(ApiTokenCredentialAuthenticationFailedException::class);

        // When
        $this->service(ApiTokenCredentialAuthenticatorInterface::class)->authenticate('key_ghost', 'whatever');
    }

    #[Test]
    public function itRefusesAnIncorrectSecret(): void
    {
        // Given
        $this->store(ApiTokenCredentialTestFactory::new()
            ->withIdentityId(IdentityId::generate()->toString())
            ->withIdentifier('key_abc123')
            ->withSecret('super-secret')
            ->create());

        // Then
        $this->expectException(ApiTokenCredentialAuthenticationFailedException::class);

        // When
        $this->service(ApiTokenCredentialAuthenticatorInterface::class)->authenticate('key_abc123', 'wrong-secret');
    }

    #[Test]
    public function itRefusesARevokedToken(): void
    {
        // Given
        $this->store(ApiTokenCredentialTestFactory::new()
            ->withIdentityId(IdentityId::generate()->toString())
            ->withIdentifier('key_abc123')
            ->withSecret('super-secret')
            ->revoked()
            ->create());

        // Then
        $this->expectException(ApiTokenCredentialAuthenticationFailedException::class);

        // When
        $this->service(ApiTokenCredentialAuthenticatorInterface::class)->authenticate('key_abc123', 'super-secret');
    }

    #[Test]
    public function itRefusesAnExpiredToken(): void
    {
        // Given
        $this->store(ApiTokenCredentialTestFactory::new()
            ->withIdentityId(IdentityId::generate()->toString())
            ->withIdentifier('key_abc123')
            ->withSecret('super-secret')
            ->expired()
            ->create());

        // Then
        $this->expectException(ApiTokenCredentialAuthenticationFailedException::class);

        // When
        $this->service(ApiTokenCredentialAuthenticatorInterface::class)->authenticate('key_abc123', 'super-secret');
    }
}
