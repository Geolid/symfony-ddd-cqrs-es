<?php

declare(strict_types=1);

namespace Web\Tests\Security;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Sales\Customer\Application\Finder\Customer\CustomerResult;
use Sales\Customer\Application\Query\GetCustomerByIdentityId\GetCustomerByIdentityId;
use Shared\Application\Query\QueryBusInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Web\Security\CustomerIdentityProvider;
use Web\Security\IamUser;

final class CustomerIdentityProviderTest extends TestCase
{
    #[Test]
    public function itResolvesTheCustomerLinkedToTheIdentity(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();
        $customerId = Uuid::uuid7()->toString();
        $queryBus = $this->queryBusResolvingCustomer($identityId, $this->customer($customerId));
        $provider = new CustomerIdentityProvider($queryBus);
        $token = new UsernamePasswordToken(new IamUser($identityId), 'main', ['ROLE_USER']);

        // When
        $resolved = $provider->resolveCustomerId($token);

        // Then
        self::assertSame($customerId, $resolved);
    }

    #[Test]
    public function itCachesTheResolvedCustomerIdOnTheToken(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();
        $customerId = Uuid::uuid7()->toString();
        $queryBus = $this->queryBusResolvingCustomer($identityId, $this->customer($customerId));
        $provider = new CustomerIdentityProvider($queryBus);
        $token = new UsernamePasswordToken(new IamUser($identityId), 'main', ['ROLE_USER']);

        // When
        $provider->resolveCustomerId($token);
        $resolved = $provider->resolveCustomerId($token);

        // Then
        self::assertSame($customerId, $resolved);
    }

    #[Test]
    public function itReturnsNullWhenNoCustomerIsLinkedToTheIdentity(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();
        $queryBus = $this->createMock(QueryBusInterface::class);
        $queryBus->expects(self::once())
            ->method('ask')
            ->with(self::equalTo(new GetCustomerByIdentityId($identityId)))
            ->willReturn(null);
        $provider = new CustomerIdentityProvider($queryBus);
        $token = new UsernamePasswordToken(new IamUser($identityId), 'main', ['ROLE_USER']);

        // When
        $resolved = $provider->resolveCustomerId($token);

        // Then
        self::assertNull($resolved);
    }

    private function customer(string $customerId): CustomerResult
    {
        return new CustomerResult(
            id: $customerId,
            email: 'buyer@example.com',
            registeredAt: new \DateTimeImmutable('2026-01-01T00:00:00+00:00'),
            erasedAt: null,
            identityId: null,
        );
    }

    private function queryBusResolvingCustomer(string $identityId, CustomerResult $customer): QueryBusInterface
    {
        $queryBus = $this->createMock(QueryBusInterface::class);
        $queryBus->expects(self::once())
            ->method('ask')
            ->with(self::equalTo(new GetCustomerByIdentityId($identityId)))
            ->willReturn($customer);

        return $queryBus;
    }
}
