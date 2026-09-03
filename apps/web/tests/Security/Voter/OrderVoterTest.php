<?php

declare(strict_types=1);

namespace Web\Tests\Security\Voter;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Sales\OrderSummary\Application\Finder\OrderSummary\OrderSummaryResult;
use Sales\OrderSummary\Application\OrderSummaryStatus;
use Symfony\Component\Clock\Clock;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Web\Security\PasswordUser;
use Web\Security\Voter\OrderVoter;

final class OrderVoterTest extends TestCase
{
    #[Test]
    public function itGrantsAccessWhenTheOrderBelongsToTheCustomer(): void
    {
        // Given
        $id = Uuid::uuid7()->toString();
        $order = $this->summary($id);
        $token = new UsernamePasswordToken(new PasswordUser($id, 'buyer@example.com', true, '2026-01-01T00:00:00+00:00'), 'main', ['ROLE_USER']);

        // When
        $vote = new OrderVoter()->vote($token, $order, [OrderVoter::VIEW]);

        // Then
        self::assertSame(VoterInterface::ACCESS_GRANTED, $vote);
    }

    #[Test]
    public function itDeniesAccessWhenTheOrderBelongsToAnotherCustomer(): void
    {
        // Given
        $id = Uuid::uuid7()->toString();
        $order = $this->summary(Uuid::uuid7()->toString());
        $token = new UsernamePasswordToken(new PasswordUser($id, 'buyer@example.com', true, '2026-01-01T00:00:00+00:00'), 'main', ['ROLE_USER']);

        // When
        $vote = new OrderVoter()->vote($token, $order, [OrderVoter::VIEW]);

        // Then
        self::assertSame(VoterInterface::ACCESS_DENIED, $vote);
    }

    #[Test]
    public function itAbstainsOnASubjectThatIsNotAnOrderSummary(): void
    {
        // Given
        $token = new UsernamePasswordToken(new PasswordUser(Uuid::uuid7()->toString(), 'buyer@example.com', true, '2026-01-01T00:00:00+00:00'), 'main', ['ROLE_USER']);

        // When
        $vote = new OrderVoter()->vote($token, new \stdClass(), [OrderVoter::VIEW]);

        // Then
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $vote);
    }

    private function summary(string $customerId): OrderSummaryResult
    {
        return new OrderSummaryResult(
            orderId: Uuid::uuid7()->toString(),
            customerId: $customerId,
            totalAmountInCents: 4_200,
            status: OrderSummaryStatus::PLACED,
            placedAt: Clock::get()->now(),
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
}
