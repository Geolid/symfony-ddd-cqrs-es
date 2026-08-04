<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Infrastructure\Security;

use Iam\Identity\Application\Security\AuthenticateApiTokenCredentialInterface;
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
            ->forIdentity($identityId)
            ->withIdentifier('key_abc123')
            ->withSecret('super-secret')
            ->create());

        // When
        $result = $this->service(AuthenticateApiTokenCredentialInterface::class)->authenticate('key_abc123', 'super-secret');

        // Then
        self::assertSame($identityId, $result);
    }

    #[Test]
    public function itRefusesAnUnknownIdentifier(): void
    {
        // When
        $result = $this->service(AuthenticateApiTokenCredentialInterface::class)->authenticate('key_ghost', 'whatever');

        // Then
        self::assertNull($result);
    }

    #[Test]
    public function itRefusesAnIncorrectSecret(): void
    {
        // Given
        $this->store(ApiTokenCredentialTestFactory::new()
            ->forIdentity(IdentityId::generate()->toString())
            ->withIdentifier('key_abc123')
            ->withSecret('super-secret')
            ->create());

        // When
        $result = $this->service(AuthenticateApiTokenCredentialInterface::class)->authenticate('key_abc123', 'wrong-secret');

        // Then
        self::assertNull($result);
    }

    #[Test]
    public function itRefusesARevokedToken(): void
    {
        // Given
        $this->store(ApiTokenCredentialTestFactory::new()
            ->forIdentity(IdentityId::generate()->toString())
            ->withIdentifier('key_abc123')
            ->withSecret('super-secret')
            ->revoked()
            ->create());

        // When
        $result = $this->service(AuthenticateApiTokenCredentialInterface::class)->authenticate('key_abc123', 'super-secret');

        // Then
        self::assertNull($result);
    }

    #[Test]
    public function itRefusesAnExpiredToken(): void
    {
        // Given
        $this->store(ApiTokenCredentialTestFactory::new()
            ->forIdentity(IdentityId::generate()->toString())
            ->withIdentifier('key_abc123')
            ->withSecret('super-secret')
            ->expired()
            ->create());

        // When
        $result = $this->service(AuthenticateApiTokenCredentialInterface::class)->authenticate('key_abc123', 'super-secret');

        // Then
        self::assertNull($result);
    }
}
