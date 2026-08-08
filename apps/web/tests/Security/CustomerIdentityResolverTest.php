<?php

declare(strict_types=1);

namespace Web\Tests\Security;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Sales\Customer\Application\Exception\CustomerResultNotFoundException;
use Sales\Customer\Application\Finder\Customer\CustomerResult;
use Sales\Customer\Application\Query\GetCustomerByIdentity\GetCustomerByIdentity;
use Shared\Application\Query\QueryBusInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Web\Security\CustomerIdentityResolver;
use Web\Security\PasswordUser;

final class CustomerIdentityResolverTest extends TestCase
{
    #[Test]
    public function itResolvesTheCustomerLinkedToTheIdentity(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();
        $customerId = Uuid::uuid7()->toString();
        $customer = $this->customer($customerId);
        $queryBus = $this->queryBusResolvingCustomer($identityId, $customer);
        $resolver = new CustomerIdentityResolver($queryBus);
        $token = new UsernamePasswordToken(new PasswordUser($identityId, 'buyer@example.com'), 'main', ['ROLE_USER']);

        // When
        $resolved = $resolver->resolveFor($token);

        // Then
        self::assertSame($customer, $resolved);
    }

    #[Test]
    public function itCachesTheResolvedCustomerOnTheToken(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();
        $customerId = Uuid::uuid7()->toString();
        $customer = $this->customer($customerId);
        $queryBus = $this->queryBusResolvingCustomer($identityId, $customer);
        $resolver = new CustomerIdentityResolver($queryBus);
        $token = new UsernamePasswordToken(new PasswordUser($identityId, 'buyer@example.com'), 'main', ['ROLE_USER']);

        // When
        $resolver->resolveFor($token);
        $resolved = $resolver->resolveFor($token);

        // Then
        self::assertSame($customer, $resolved);
    }

    #[Test]
    public function itThrowsWhenNoCustomerIsLinkedToTheIdentity(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();
        $queryBus = $this->createMock(QueryBusInterface::class);
        $queryBus->expects(self::once())
            ->method('ask')
            ->with(self::equalTo(new GetCustomerByIdentity($identityId)))
            ->willThrowException(CustomerResultNotFoundException::forIdentityId($identityId));
        $resolver = new CustomerIdentityResolver($queryBus);
        $token = new UsernamePasswordToken(new PasswordUser($identityId, 'buyer@example.com'), 'main', ['ROLE_USER']);

        // Then
        $this->expectException(CustomerResultNotFoundException::class);

        // When
        $resolver->resolveFor($token);
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
            ->with(self::equalTo(new GetCustomerByIdentity($identityId)))
            ->willReturn($customer);

        return $queryBus;
    }
}
