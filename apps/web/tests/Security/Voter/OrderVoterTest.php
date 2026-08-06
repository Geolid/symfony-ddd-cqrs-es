<?php

declare(strict_types=1);

namespace Web\Tests\Security\Voter;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Sales\Customer\Application\Finder\Customer\CustomerResult;
use Sales\Customer\Application\Query\GetCustomerByIdentityId\GetCustomerByIdentityId;
use Sales\OrderSummary\Application\Enum\AppOrderSummaryStatus;
use Sales\OrderSummary\Application\Finder\OrderSummary\OrderSummaryResult;
use Shared\Application\Query\QueryBusInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Web\Security\CustomerIdentityProvider;
use Web\Security\IamUser;
use Web\Security\Voter\OrderVoter;

final class OrderVoterTest extends TestCase
{
    #[Test]
    public function itGrantsAccessWhenTheOrderBelongsToTheCustomer(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();
        $customerId = Uuid::uuid7()->toString();
        $order = $this->summary($customerId);
        $customerIdentityProvider = new CustomerIdentityProvider($this->queryBusResolvingCustomer($identityId, $this->customer($customerId)));
        $token = new UsernamePasswordToken(new IamUser($identityId), 'main', ['ROLE_USER']);

        // When
        $vote = (new OrderVoter($customerIdentityProvider))->vote($token, $order, [OrderVoter::VIEW]);

        // Then
        self::assertSame(VoterInterface::ACCESS_GRANTED, $vote);
    }

    #[Test]
    public function itDeniesAccessWhenTheOrderBelongsToAnotherCustomer(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();
        $customerId = Uuid::uuid7()->toString();
        $order = $this->summary(Uuid::uuid7()->toString());
        $customerIdentityProvider = new CustomerIdentityProvider($this->queryBusResolvingCustomer($identityId, $this->customer($customerId)));
        $token = new UsernamePasswordToken(new IamUser($identityId), 'main', ['ROLE_USER']);

        // When
        $vote = (new OrderVoter($customerIdentityProvider))->vote($token, $order, [OrderVoter::VIEW]);

        // Then
        self::assertSame(VoterInterface::ACCESS_DENIED, $vote);
    }

    #[Test]
    public function itDeniesAccessWhenNoCustomerIsLinkedToTheIdentity(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();
        $order = $this->summary(Uuid::uuid7()->toString());
        $queryBus = $this->createMock(QueryBusInterface::class);
        $queryBus->expects(self::once())
            ->method('ask')
            ->with(self::equalTo(new GetCustomerByIdentityId($identityId)))
            ->willReturn(null);
        $customerIdentityProvider = new CustomerIdentityProvider($queryBus);
        $token = new UsernamePasswordToken(new IamUser($identityId), 'main', ['ROLE_USER']);

        // When
        $vote = (new OrderVoter($customerIdentityProvider))->vote($token, $order, [OrderVoter::VIEW]);

        // Then
        self::assertSame(VoterInterface::ACCESS_DENIED, $vote);
    }

    #[Test]
    public function itAbstainsOnASubjectThatIsNotAnOrderSummary(): void
    {
        // Given
        $queryBus = $this->createMock(QueryBusInterface::class);
        $queryBus->expects(self::never())->method('ask');
        $customerIdentityProvider = new CustomerIdentityProvider($queryBus);
        $token = new UsernamePasswordToken(new IamUser(Uuid::uuid7()->toString()), 'main', ['ROLE_USER']);

        // When
        $vote = (new OrderVoter($customerIdentityProvider))->vote($token, new \stdClass(), [OrderVoter::VIEW]);

        // Then
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $vote);
    }

    private function summary(string $customerId): OrderSummaryResult
    {
        return new OrderSummaryResult(
            orderId: Uuid::uuid7()->toString(),
            customerId: $customerId,
            totalAmountInCents: 4_200,
            status: AppOrderSummaryStatus::PLACED,
            placedAt: new \DateTimeImmutable('2026-01-01T00:00:00+00:00'),
            cancelledAt: null,
            paymentAmountInCents: null,
            paymentReference: null,
            paymentCheckoutUrl: null,
            paidAt: null,
            trackingReference: null,
            dispatchedAt: null,
            deliveredAt: null,
        );
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
