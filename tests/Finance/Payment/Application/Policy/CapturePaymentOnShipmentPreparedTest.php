<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Application\Policy;

use Finance\Payment\Application\Finder\Payment\PaymentFinderInterface;
use Finance\Payment\Application\PaymentStatus;
use Finance\Payment\Application\Policy\CapturePaymentOnShipmentPrepared;
use Finance\Payment\Application\PSP\Exception\PaymentFatalFailureException;
use Finance\Payment\Application\PSP\Exception\PaymentTransientFailureException;
use Finance\Payment\Application\PSP\PaymentGatewayInterface;
use Finance\Payment\Application\PSP\PaymentGatewayStatus;
use Finance\Tests\Payment\Support\Builder\PaymentBuilder;
use Fulfilment\Shipping\Application\IntegrationEvent\ShipmentPrepared\ShipmentPreparedIntegrationEvent;
use Patchlevel\EventSourcing\Message\Message;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Ramsey\Uuid\Uuid;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class CapturePaymentOnShipmentPreparedTest extends AbstractIntegrationTestCase
{
    private PaymentGatewayInterface&MockObject $paymentGateway;

    private PaymentFinderInterface $finder;

    private CapturePaymentOnShipmentPrepared $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentGateway = $this->createMock(PaymentGatewayInterface::class);
        $this->replace(PaymentGatewayInterface::class, $this->paymentGateway);

        $this->finder = $this->service(PaymentFinderInterface::class);
        $this->policy = $this->service(CapturePaymentOnShipmentPrepared::class);
    }

    #[Test]
    public function itCaptures(): void
    {
        // Given
        $order = OrderBuilder::new()->create();
        $paymentBuilder = PaymentBuilder::new()->withOrderId($order->id->toString())->authorized();
        $payment = $paymentBuilder->create();
        $this->store($order, $payment);
        $this->paymentGateway->expects(self::once())->method('capture')
            ->with($paymentBuilder['reference']->value)
            ->willReturn(PaymentGatewayStatus::CAPTURED);

        // When
        $this->trigger(CapturePaymentOnShipmentPrepared::class, new ShipmentPreparedIntegrationEvent(
            Uuid::uuid7()->toString(),
            $order->id->toString(),
            Clock::get()->now(),
        ));

        // Then
        $result = $this->finder->ofReference($paymentBuilder['reference']->value);
        self::assertSame(PaymentStatus::CAPTURED, $result->status);
    }

    #[Test]
    public function itIgnoresWhenNotFound(): void
    {
        // Given
        $this->paymentGateway->expects(self::never())->method('capture');

        // When
        $this->trigger(CapturePaymentOnShipmentPrepared::class, new ShipmentPreparedIntegrationEvent(
            Uuid::uuid7()->toString(),
            Uuid::uuid7()->toString(),
            Clock::get()->now(),
        ));
    }

    #[Test]
    public function itIgnoresWhenGatewayReturnsUnexpectedStatus(): void
    {
        // Given
        $order = OrderBuilder::new()->create();
        $paymentBuilder = PaymentBuilder::new()->withOrderId($order->id->toString())->authorized();
        $payment = $paymentBuilder->create();
        $this->store($order, $payment);
        $this->paymentGateway->expects(self::once())->method('capture')->willReturn(PaymentGatewayStatus::AUTHORIZED);

        // When
        $this->trigger(CapturePaymentOnShipmentPrepared::class, new ShipmentPreparedIntegrationEvent(
            Uuid::uuid7()->toString(),
            $order->id->toString(),
            Clock::get()->now(),
        ));

        // Then
        $result = $this->finder->ofReference($paymentBuilder['reference']->value);
        self::assertSame(PaymentStatus::AUTHORIZED, $result->status);
    }

    #[Test]
    public function itFailsPaymentWhenGatewayDeclines(): void
    {
        // Given
        $order = OrderBuilder::new()->create();
        $paymentBuilder = PaymentBuilder::new()->withOrderId($order->id->toString())->authorized();
        $payment = $paymentBuilder->create();
        $this->store($order, $payment);
        $this->paymentGateway->expects(self::once())->method('capture')->willReturn(PaymentGatewayStatus::DECLINED);

        // When
        $this->trigger(CapturePaymentOnShipmentPrepared::class, new ShipmentPreparedIntegrationEvent(
            Uuid::uuid7()->toString(),
            $order->id->toString(),
            Clock::get()->now(),
        ));

        // Then
        $result = $this->finder->ofReference($paymentBuilder['reference']->value);
        self::assertSame(PaymentStatus::FAILED, $result->status);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function itIgnoresFatalGatewayFailure(): void
    {
        // Given
        $order = OrderBuilder::new()->create();
        $paymentBuilder = PaymentBuilder::new()->withOrderId($order->id->toString())->authorized();
        $payment = $paymentBuilder->create();
        $this->store($order, $payment);
        $message = Message::create(new ShipmentPreparedIntegrationEvent(Uuid::uuid7()->toString(), $order->id->toString(), Clock::get()->now()));
        $error = PaymentFatalFailureException::forReason('rejected');

        // When
        $this->policy->onGatewayFailure($message, $error);

        // Then
        $result = $this->finder->ofReference($paymentBuilder['reference']->value);
        self::assertSame(PaymentStatus::FAILED, $result->status);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function itRethrowsTransientGatewayFailure(): void
    {
        // Given
        $message = Message::create(new ShipmentPreparedIntegrationEvent(Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), Clock::get()->now()));
        $error = PaymentTransientFailureException::forReason('unreachable');

        // Then
        $this->expectExceptionObject($error);

        // When
        $this->policy->onGatewayFailure($message, $error);
    }
}
