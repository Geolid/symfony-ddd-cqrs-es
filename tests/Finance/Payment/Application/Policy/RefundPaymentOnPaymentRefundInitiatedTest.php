<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Application\Policy;

use Finance\Payment\Application\IntegrationEvent\PaymentRefundFailed\PaymentRefundFailedIntegrationEvent;
use Finance\Payment\Application\Policy\RefundPaymentOnPaymentRefundInitiated;
use Finance\Payment\Application\PSP\Exception\PaymentFatalFailureException;
use Finance\Payment\Application\PSP\Exception\PaymentTransientFailureException;
use Finance\Payment\Application\PSP\PaymentGatewayInterface;
use Finance\Payment\Application\PSP\PaymentGatewayStatus;
use Finance\Payment\Domain\Event\PaymentRefundInitiated;
use Finance\Refund\Domain\ValueObject\RefundId;
use Finance\Tests\Payment\Support\Builder\PaymentBuilder;
use Patchlevel\EventSourcing\Message\Message;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class RefundPaymentOnPaymentRefundInitiatedTest extends AbstractIntegrationTestCase
{
    private PaymentGatewayInterface&MockObject $paymentGateway;

    private RefundPaymentOnPaymentRefundInitiated $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentGateway = $this->createMock(PaymentGatewayInterface::class);
        $this->replace(PaymentGatewayInterface::class, $this->paymentGateway);
        $this->policy = $this->service(RefundPaymentOnPaymentRefundInitiated::class);
    }

    #[Test]
    public function itRefunds(): void
    {
        // Given
        $reference = PaymentBuilder::sample('reference');
        $this->paymentGateway->expects(self::once())->method('refund')->with($reference->value)->willReturn(PaymentGatewayStatus::REFUNDING);

        // When
        $this->trigger(RefundPaymentOnPaymentRefundInitiated::class, new PaymentRefundInitiated(
            Uuid::uuid7()->toString(),
            Uuid::uuid7()->toString(),
            RefundId::generate()->toString(),
            $reference,
            Clock::get()->now(),
        ));
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function itFailsRefundWhenGatewayFailureFatal(): void
    {
        // Given
        $refundId = RefundId::generate()->toString();
        $builder = PaymentBuilder::new()->authorized()->captured()->refundRequested($refundId);
        $payment = $builder->create();
        $this->store($payment);
        $message = Message::create(new PaymentRefundInitiated($payment->id->toString(), $builder['orderId'], $refundId, $builder['reference'], Clock::get()->now()));
        $error = PaymentFatalFailureException::forReason('rejected');

        // When
        $this->policy->onGatewayFailure($message, $error);

        // Then
        $event = $this->publishedEventOf(PaymentRefundFailedIntegrationEvent::class);
        self::assertSame($builder['orderId'], $event->orderId);
        self::assertSame($refundId, $event->refundId);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function itRethrowsTransientGatewayFailure(): void
    {
        // Given
        $message = Message::create(new PaymentRefundInitiated(Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), RefundId::generate()->toString(), PaymentBuilder::sample('reference'), Clock::get()->now()));
        $error = PaymentTransientFailureException::forReason('unreachable');

        // Then
        $this->expectExceptionObject($error);

        // When
        $this->policy->onGatewayFailure($message, $error);
    }
}
