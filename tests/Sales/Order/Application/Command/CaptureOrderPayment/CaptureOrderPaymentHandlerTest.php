<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Command\CaptureOrderPayment;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Command\CaptureOrderPayment\CaptureOrderPayment;
use Sales\Order\Domain\Repository\OrderPaymentRepositoryInterface;
use Sales\Tests\Order\Support\Factory\OrderPaymentTestFactory;
use Support\AbstractIntegrationTestCase;

final class CaptureOrderPaymentHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itCapturesARequestedPayment(): void
    {
        // Given
        $orderPayment = OrderPaymentTestFactory::new()->create();
        $this->store($orderPayment);

        // When
        $this->dispatch(new CaptureOrderPayment($orderPayment->id()->toString()));

        // Then
        $reloaded = $this->service(OrderPaymentRepositoryInterface::class)->load($orderPayment->id());
        self::assertTrue($reloaded->status()->isCaptured());
    }

    #[Test]
    public function itIgnoresAnAlreadyCapturedPayment(): void
    {
        // Given
        $orderPayment = OrderPaymentTestFactory::new()->captured()->create();
        $this->store($orderPayment);

        // When
        $this->dispatch(new CaptureOrderPayment($orderPayment->id()->toString()));

        // Then
        self::expectNotToPerformAssertions();
    }
}
