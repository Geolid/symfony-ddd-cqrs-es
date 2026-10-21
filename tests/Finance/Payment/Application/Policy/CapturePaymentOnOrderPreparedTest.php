<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Application\Policy;

use Finance\Payment\Application\Finder\Payment\Exception\PaymentResultNotFoundException;
use Finance\Payment\Application\Finder\Payment\PaymentFinderInterface;
use Finance\Payment\Application\PaymentStatus;
use Finance\Payment\Application\Policy\CapturePaymentOnOrderPrepared;
use Finance\Payment\Application\PSP\Exception\PaymentFatalFailureException;
use Finance\Payment\Application\PSP\Exception\PaymentTransientFailureException;
use Finance\Payment\Application\PSP\PaymentGatewayInterface;
use Finance\Payment\Application\PSP\PaymentGatewayStatus;
use Finance\Tests\Payment\Support\Factory\PaymentFactory;
use Patchlevel\EventSourcing\Message\Message;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Ramsey\Uuid\Uuid;
use Sales\Ordering\Application\IntegrationEvent\OrderPrepared\OrderPreparedIntegrationEvent;
use Sales\Tests\Ordering\Support\Factory\OrderFactory;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class CapturePaymentOnOrderPreparedTest extends AbstractIntegrationTestCase
{
    private PaymentGatewayInterface&MockObject $paymentGateway;

    private PaymentFinderInterface $finder;

    private CapturePaymentOnOrderPrepared $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentGateway = $this->createMock(PaymentGatewayInterface::class);
        $this->replace(PaymentGatewayInterface::class, $this->paymentGateway);

        $this->finder = $this->service(PaymentFinderInterface::class);
        $this->policy = $this->service(CapturePaymentOnOrderPrepared::class);
    }

    #[Test]
    public function itCaptures(): void
    {
        // Given
        $checkoutSessionId = Uuid::uuid7()->toString();
        $payment = PaymentFactory::new()->withCheckoutSessionId($checkoutSessionId)->authorized()->create();
        $order = OrderFactory::new()->withCheckoutSessionId($checkoutSessionId)->create();
        $this->store($payment, $order);
        $this->paymentGateway->expects(self::once())->method('capture')
            ->with($payment->reference->value)
            ->willReturn(PaymentGatewayStatus::CAPTURED);

        // When
        $this->trigger(CapturePaymentOnOrderPrepared::class, new OrderPreparedIntegrationEvent(
            $order->id->toString(),
            Clock::get()->now(),
        ));

        // Then
        $result = $this->finder->ofReference($payment->reference->value);
        self::assertSame(PaymentStatus::CAPTURED, $result->status);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();

        $this->paymentGateway->expects(self::never())->method('capture');

        // Then
        $this->expectException(PaymentResultNotFoundException::class);

        // When
        $this->trigger(CapturePaymentOnOrderPrepared::class, new OrderPreparedIntegrationEvent($orderId, Clock::get()->now()));
    }

    #[Test]
    public function itIgnoresWhenGatewayReturnsUnexpectedStatus(): void
    {
        // Given
        $checkoutSessionId = Uuid::uuid7()->toString();
        $payment = PaymentFactory::new()->withCheckoutSessionId($checkoutSessionId)->authorized()->create();
        $order = OrderFactory::new()->withCheckoutSessionId($checkoutSessionId)->create();
        $this->store($payment, $order);
        $this->paymentGateway->expects(self::once())->method('capture')->willReturn(PaymentGatewayStatus::AUTHORIZED);

        // When
        $this->trigger(CapturePaymentOnOrderPrepared::class, new OrderPreparedIntegrationEvent(
            $order->id->toString(),
            Clock::get()->now(),
        ));

        // Then
        $result = $this->finder->ofReference($payment->reference->value);
        self::assertSame(PaymentStatus::AUTHORIZED, $result->status);
    }

    #[Test]
    public function itFailsPaymentWhenGatewayDeclines(): void
    {
        // Given
        $checkoutSessionId = Uuid::uuid7()->toString();
        $payment = PaymentFactory::new()->withCheckoutSessionId($checkoutSessionId)->authorized()->create();
        $order = OrderFactory::new()->withCheckoutSessionId($checkoutSessionId)->create();
        $this->store($payment, $order);
        $this->paymentGateway->expects(self::once())->method('capture')->willReturn(PaymentGatewayStatus::DECLINED);

        // When
        $this->trigger(CapturePaymentOnOrderPrepared::class, new OrderPreparedIntegrationEvent(
            $order->id->toString(),
            Clock::get()->now(),
        ));

        // Then
        $result = $this->finder->ofReference($payment->reference->value);
        self::assertSame(PaymentStatus::FAILED, $result->status);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function itIgnoresFatalGatewayFailure(): void
    {
        // Given
        $checkoutSessionId = Uuid::uuid7()->toString();
        $payment = PaymentFactory::new()->withCheckoutSessionId($checkoutSessionId)->authorized()->create();
        $order = OrderFactory::new()->withCheckoutSessionId($checkoutSessionId)->create();
        $this->store($payment, $order);
        $message = Message::create(new OrderPreparedIntegrationEvent($order->id->toString(), Clock::get()->now()));
        $error = PaymentFatalFailureException::forReason('rejected');

        // When
        $this->policy->onGatewayFailure($message, $error);

        // Then
        $result = $this->finder->ofReference($payment->reference->value);
        self::assertSame(PaymentStatus::FAILED, $result->status);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function itRethrowsTransientGatewayFailure(): void
    {
        // Given
        $message = Message::create(new OrderPreparedIntegrationEvent(Uuid::uuid7()->toString(), Clock::get()->now()));
        $error = PaymentTransientFailureException::forReason('unreachable');

        // Then
        $this->expectExceptionObject($error);

        // When
        $this->policy->onGatewayFailure($message, $error);
    }
}
