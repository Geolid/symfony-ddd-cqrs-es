<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Application\Policy;

use Finance\Payment\Application\Finder\Payment\PaymentFinderInterface;
use Finance\Payment\Application\PaymentStatus;
use Finance\Payment\Application\Policy\CapturePaymentOnShipmentManifested;
use Finance\Tests\Payment\Support\Builder\PaymentBuilder;
use Fulfilment\Shipping\Application\IntegrationEvent\ShipmentManifested\ShipmentManifestedIntegrationEvent;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Support\SeededFaker;
use Support\TestCase\AbstractIntegrationTestCase;

final class CapturePaymentOnShipmentManifestedTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itCaptures(): void
    {
        // Given
        $order = OrderBuilder::new()->create();
        $paymentBuilder = PaymentBuilder::new()->withOrderId($order->id->toString())->authorized();
        $payment = $paymentBuilder->create();
        $this->store($order, $payment);

        // When
        $this->trigger(CapturePaymentOnShipmentManifested::class, new ShipmentManifestedIntegrationEvent(
            Uuid::uuid7()->toString(),
            $order->id->toString(),
            SeededFaker::get()->regexify('ACME-[A-Z0-9]{8}'),
        ));

        // Then
        $result = $this->service(PaymentFinderInterface::class)->ofReference($paymentBuilder['reference']->value);
        self::assertSame(PaymentStatus::CAPTURED, $result->status);
    }
}
