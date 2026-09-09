<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Application\Command\FailPayment;

use Finance\Payment\Application\Command\FailPayment\FailPayment;
use Finance\Payment\Application\Finder\Payment\PaymentFinderInterface;
use Finance\Payment\Application\PaymentStatus;
use Finance\Payment\Domain\Exception\PaymentNotFoundException;
use Finance\Tests\Payment\Support\Builder\PaymentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

final class FailPaymentHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itFailsWhenAuthorized(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $paymentFactory = PaymentBuilder::new()->authorized();
        $orderPayment = $paymentFactory->create();
        $this->store($orderPayment);

        // When
        $this->dispatch(new FailPayment($orderPayment->id->toString(), $orderId));

        // Then
        $result = $this->service(PaymentFinderInterface::class)->ofReference($paymentFactory['reference']->value);
        self::assertSame(PaymentStatus::FAILED, $result->status);
        self::assertSame($orderId, $result->orderId);
    }

    #[Test]
    public function itIgnoresWhenRequested(): void
    {
        // Given
        $orderPayment = PaymentBuilder::new()->create();
        $this->store($orderPayment);

        // When
        $this->dispatch(new FailPayment($orderPayment->id->toString(), Uuid::uuid7()->toString()));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $id = Uuid::uuid7()->toString();

        // Then
        $this->expectException(PaymentNotFoundException::class);

        // When
        $this->dispatch(new FailPayment($id, Uuid::uuid7()->toString()));
    }
}
