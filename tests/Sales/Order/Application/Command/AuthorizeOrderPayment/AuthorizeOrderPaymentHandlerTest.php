<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Command\AuthorizeOrderPayment;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Command\AuthorizeOrderPayment\AuthorizeOrderPayment;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentFinderInterface;
use Sales\Order\Application\Status\OrderPaymentStatus;
use Sales\Order\Domain\Exception\OrderPaymentNotFoundException;
use Sales\Order\Domain\ValueObject\OrderPaymentId;
use Sales\Tests\Order\Support\Factory\OrderPaymentTestFactory;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

final class AuthorizeOrderPaymentHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itAuthorizesWhenRequested(): void
    {
        // Given
        $order = OrderTestFactory::new()->create();
        $orderPayment = OrderPaymentTestFactory::new()->withOrderId($order->id->toString())->withReference('GLBX-9F3K2M1P')->create();
        $this->store($order, $orderPayment);

        // When
        $this->dispatch(new AuthorizeOrderPayment($orderPayment->id->toString()));

        // Then
        $result = $this->service(OrderPaymentFinderInterface::class)->ofReference('GLBX-9F3K2M1P');
        self::assertSame(OrderPaymentStatus::AUTHORIZED, $result->status);
    }

    #[Test]
    public function itIgnoresWhenAlreadyAuthorized(): void
    {
        // Given
        $order = OrderTestFactory::new()->create();
        $orderPayment = OrderPaymentTestFactory::new()->withOrderId($order->id->toString())->authorized()->create();
        $this->store($order, $orderPayment);

        // When
        $this->dispatch(new AuthorizeOrderPayment($orderPayment->id->toString()));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $id = OrderPaymentId::forOrder(Uuid::uuid7()->toString())->toString();

        // Then
        $this->expectException(OrderPaymentNotFoundException::class);

        // When
        $this->dispatch(new AuthorizeOrderPayment($id));
    }
}
