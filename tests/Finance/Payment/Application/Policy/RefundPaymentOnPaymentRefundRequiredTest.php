<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Application\Policy;

use Finance\Payment\Application\IntegrationEvent\PaymentRefundRejected\PaymentRefundRejectedIntegrationEvent;
use Finance\Payment\Application\Policy\RefundPaymentOnPaymentRefundRequired;
use Finance\Payment\Application\PSP\PaymentFatalFailureException;
use Finance\Payment\Application\PSP\PaymentGatewayInterface;
use Finance\Payment\Application\PSP\PaymentTransientFailureException;
use Finance\Payment\Domain\Event\PaymentRefundRequired;
use Finance\Payment\Domain\ValueObject\PaymentReference;
use Finance\Tests\Payment\Support\Builder\PaymentBuilder;
use Patchlevel\EventSourcing\Message\Message;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class RefundPaymentOnPaymentRefundRequiredTest extends AbstractIntegrationTestCase
{
    private PaymentGatewayInterface&MockObject $paymentGateway;

    private RefundPaymentOnPaymentRefundRequired $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentGateway = $this->createMock(PaymentGatewayInterface::class);
        $this->replace(PaymentGatewayInterface::class, $this->paymentGateway);
        $this->policy = $this->service(RefundPaymentOnPaymentRefundRequired::class);
    }

    #[Test]
    public function itRefunds(): void
    {
        // Given
        $reference = 'GLBX-'.Uuid::uuid7()->toString();
        $this->paymentGateway->expects(self::once())->method('refund')->with($reference);

        // When
        $this->trigger(RefundPaymentOnPaymentRefundRequired::class, new PaymentRefundRequired(Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), PaymentReference::fromString($reference), Clock::get()->now()));
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function itRejectsRefundWhenGatewayFailureFatal(): void
    {
        // Given
        $builder = PaymentBuilder::new()->authorized()->captured();
        $payment = $builder->create();
        $this->store($payment);
        $message = Message::create(new PaymentRefundRequired($payment->id->toString(), $builder['orderId'], $builder['reference'], Clock::get()->now()));
        $error = PaymentFatalFailureException::forReason('rejected');

        // When
        $this->policy->onGatewayFailure($message, $error);

        // Then
        $event = $this->publishedEventOf(PaymentRefundRejectedIntegrationEvent::class);
        self::assertSame($builder['orderId'], $event->orderId);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function itRethrowsTransientGatewayFailure(): void
    {
        // Given
        $message = Message::create(new PaymentRefundRequired(Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), PaymentReference::fromString('GLBX-x'), Clock::get()->now()));
        $error = PaymentTransientFailureException::forReason('unreachable');

        // Then
        $this->expectExceptionObject($error);

        // When
        $this->policy->onGatewayFailure($message, $error);
    }
}
