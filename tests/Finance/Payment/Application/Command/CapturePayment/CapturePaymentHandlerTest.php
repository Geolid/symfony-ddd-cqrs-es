<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Application\Command\CapturePayment;

use Finance\Payment\Application\Command\CapturePayment\CapturePayment;
use Finance\Payment\Application\Finder\Payment\PaymentFinderInterface;
use Finance\Payment\Application\PaymentStatus;
use Finance\Tests\Payment\Support\Factory\PaymentFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

final class CapturePaymentHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itCapturesWhenAuthorized(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $orderPayment = PaymentFactory::new()->authorized()->create();
        $this->store($orderPayment);

        // When
        $this->dispatch(new CapturePayment($orderPayment->id->toString(), $orderId));

        // Then
        $result = $this->service(PaymentFinderInterface::class)->ofReference($orderPayment->reference->value);
        self::assertSame(PaymentStatus::CAPTURED, $result->status);
        self::assertSame($orderId, $result->orderId);
    }

    #[Test]
    public function itIgnoresWhenAlreadyCaptured(): void
    {
        // Given
        $orderPayment = PaymentFactory::new()->authorized()->captured()->create();
        $this->store($orderPayment);

        // When
        $this->dispatch(new CapturePayment($orderPayment->id->toString(), Uuid::uuid7()->toString()));

        // Then
        self::expectNotToPerformAssertions();
    }
}
