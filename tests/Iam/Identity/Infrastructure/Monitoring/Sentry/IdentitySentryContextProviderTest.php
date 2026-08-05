<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Infrastructure\Monitoring\Sentry;

use Iam\Identity\Infrastructure\Monitoring\Sentry\IdentitySentryContextProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\InMemoryUser;

final class IdentitySentryContextProviderTest extends TestCase
{
    private TokenStorageInterface&Stub $tokenStorage;

    protected function setUp(): void
    {
        $this->tokenStorage = $this->createStub(TokenStorageInterface::class);
    }

    #[Test]
    public function itProvidesTheAuthenticatedIdentityId(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn(new InMemoryUser($identityId, null));
        $this->tokenStorage->method('getToken')->willReturn($token);
        $provider = new IdentitySentryContextProvider($this->tokenStorage);

        // When
        $context = $provider->provide();

        // Then
        self::assertSame(['identityId' => $identityId], $context);
    }

    #[Test]
    public function itProvidesNothingWithoutATokenStorage(): void
    {
        // Given
        $provider = new IdentitySentryContextProvider();

        // When
        $context = $provider->provide();

        // Then
        self::assertNull($context);
    }

    #[Test]
    public function itProvidesNothingWithoutAnAuthenticatedToken(): void
    {
        // Given
        $this->tokenStorage->method('getToken')->willReturn(null);
        $provider = new IdentitySentryContextProvider($this->tokenStorage);

        // When
        $context = $provider->provide();

        // Then
        self::assertNull($context);
    }

    #[Test]
    public function itProvidesNothingWhenTheTokenHasNoUser(): void
    {
        // Given
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn(null);
        $this->tokenStorage->method('getToken')->willReturn($token);
        $provider = new IdentitySentryContextProvider($this->tokenStorage);

        // When
        $context = $provider->provide();

        // Then
        self::assertNull($context);
    }
}
