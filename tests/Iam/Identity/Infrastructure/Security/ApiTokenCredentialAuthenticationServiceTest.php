<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Infrastructure\Security;

use Iam\Identity\Application\Port\AuthenticateApiTokenCredentialInterface;
use Iam\Tests\Identity\Support\Factory\ApiTokenCredentialTestFactory;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class ApiTokenCredentialAuthenticationServiceTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itAuthenticatesAValidIdentifierAndSecret(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);
        $this->store(ApiTokenCredentialTestFactory::new()
            ->forIdentity($identity->id()->toString())
            ->withIdentifier('key_abc123')
            ->withSecret('super-secret')
            ->create());

        // When
        $result = $this->service(AuthenticateApiTokenCredentialInterface::class)->authenticate('key_abc123', 'super-secret');

        // Then
        self::assertSame($identity->id()->toString(), $result);
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
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);
        $this->store(ApiTokenCredentialTestFactory::new()
            ->forIdentity($identity->id()->toString())
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
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);
        $this->store(ApiTokenCredentialTestFactory::new()
            ->forIdentity($identity->id()->toString())
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
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);
        $this->store(ApiTokenCredentialTestFactory::new()
            ->forIdentity($identity->id()->toString())
            ->withIdentifier('key_abc123')
            ->withSecret('super-secret')
            ->expired()
            ->create());

        // When
        $result = $this->service(AuthenticateApiTokenCredentialInterface::class)->authenticate('key_abc123', 'super-secret');

        // Then
        self::assertNull($result);
    }

    #[Test]
    public function itRefusesASuspendedIdentity(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->suspended()->create();
        $this->store($identity);
        $this->store(ApiTokenCredentialTestFactory::new()
            ->forIdentity($identity->id()->toString())
            ->withIdentifier('key_abc123')
            ->withSecret('super-secret')
            ->create());

        // When
        $result = $this->service(AuthenticateApiTokenCredentialInterface::class)->authenticate('key_abc123', 'super-secret');

        // Then
        self::assertNull($result);
    }
}
