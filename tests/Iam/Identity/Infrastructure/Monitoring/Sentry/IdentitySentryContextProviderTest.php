<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Infrastructure\Monitoring\Sentry;

use Iam\Identity\Infrastructure\Monitoring\Sentry\IdentitySentryContextProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\InMemoryUser;

final class IdentitySentryContextProviderTest extends TestCase
{
    #[Test]
    public function itProvidesTheAuthenticatedIdentityId(): void
    {
        // Given
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn(new InMemoryUser('identity-id', null));
        $tokenStorage = $this->createStub(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);
        $provider = new IdentitySentryContextProvider($tokenStorage);

        // When
        $context = $provider->provide();

        // Then
        self::assertSame(['identityId' => 'identity-id'], $context);
    }

    #[Test]
    public function itProvidesNothingWithoutAnAuthenticatedToken(): void
    {
        // Given
        $tokenStorage = $this->createStub(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn(null);
        $provider = new IdentitySentryContextProvider($tokenStorage);

        // When
        $context = $provider->provide();

        // Then
        self::assertNull($context);
    }
}
