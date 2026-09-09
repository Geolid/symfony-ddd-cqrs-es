<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Application\Reconciliation;

use Finance\Payment\Application\Finder\Payment\PaymentFinderInterface;
use Finance\Payment\Application\PaymentStatus;
use Finance\Payment\Application\PSP\PaymentGatewayInterface;
use Finance\Payment\Application\PSP\PaymentGatewayStatus;
use Finance\Payment\Application\Reconciliation\RequestedPaymentReconciler;
use Finance\Tests\Payment\Support\Builder\PaymentBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Command\CommandBusInterface;
use Support\TestCase\AbstractIntegrationTestCase;

final class RequestedPaymentReconcilerTest extends AbstractIntegrationTestCase
{
    private PaymentFinderInterface $orderPaymentFinder;

    private CommandBusInterface $commandBus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orderPaymentFinder = $this->service(PaymentFinderInterface::class);
        $this->commandBus = $this->service(CommandBusInterface::class);
    }

    #[Test]
    public function itReconcilesWhenAuthorized(): void
    {
        // Given
        $paymentBuilder = PaymentBuilder::new();
        $orderPayment = $paymentBuilder->create();
        $this->store($orderPayment);
        $carrier = $this->createStub(PaymentGatewayInterface::class);
        $carrier->method('checkStatus')->willReturn(PaymentGatewayStatus::AUTHORIZED);
        $reconciler = new RequestedPaymentReconciler($carrier, $this->commandBus);

        // When
        $reconciled = $reconciler->reconcile($orderPayment->id->toString(), $paymentBuilder['reference']->value);

        // Then
        self::assertTrue($reconciled);
        $result = $this->orderPaymentFinder->ofReference($paymentBuilder['reference']->value);
        self::assertSame(PaymentStatus::AUTHORIZED, $result->status);
    }

    #[Test]
    #[DataProvider('provideNonAuthorizedStatuses')]
    public function itAbandonsWhenNotAuthorized(PaymentGatewayStatus $gatewayStatus): void
    {
        // Given
        $paymentBuilder = PaymentBuilder::new();
        $orderPayment = $paymentBuilder->create();
        $this->store($orderPayment);
        $carrier = $this->createStub(PaymentGatewayInterface::class);
        $carrier->method('checkStatus')->willReturn($gatewayStatus);
        $reconciler = new RequestedPaymentReconciler($carrier, $this->commandBus);

        // When
        $reconciled = $reconciler->reconcile($orderPayment->id->toString(), $paymentBuilder['reference']->value);

        // Then
        self::assertTrue($reconciled);
        $result = $this->orderPaymentFinder->ofReference($paymentBuilder['reference']->value);
        self::assertSame(PaymentStatus::ABANDONED, $result->status);
    }

    /**
     * @return iterable<string, array{PaymentGatewayStatus}>
     */
    public static function provideNonAuthorizedStatuses(): iterable
    {
        yield 'still requested' => [PaymentGatewayStatus::REQUESTED];
        yield 'declined' => [PaymentGatewayStatus::DECLINED];
    }
}
