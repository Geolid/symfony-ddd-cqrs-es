<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Command\CancelOrder;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Command\CancelOrder\CancelOrder;
use Sales\Order\Application\Exception\OrderPaymentAlreadyRequestedException;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Domain\Exception\OrderAlreadyCancelledException;
use Sales\Order\Domain\Exception\OrderNotFoundException;
use Sales\Order\Domain\ValueObject\OrderId;
use Sales\Tests\Order\Support\Factory\OrderPaymentTestFactory;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

final class CancelOrderHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itCancelsAPlacedOrder(): void
    {
        // Given
        $order = OrderTestFactory::new()->create();
        $this->store($order);

        // When
        $this->dispatch(new CancelOrder($order->id()->toString()));

        // Then
        $results = array_values(iterator_to_array($this->service(OrderFinderInterface::class)));
        self::assertCount(1, $results);
        self::assertSame('cancelled', $results[0]->status);
        self::assertNotNull($results[0]->cancelledAt);
    }

    #[Test]
    public function itFailsWhenTheOrderIsAlreadyCancelled(): void
    {
        // Given
        $order = OrderTestFactory::new()->cancelled()->create();
        $this->store($order);

        // Then
        $this->expectException(OrderAlreadyCancelledException::class);

        // When
        $this->dispatch(new CancelOrder($order->id()->toString()));
    }

    #[Test]
    public function itFailsWhenTheOrderDoesNotExist(): void
    {
        // Then
        $this->expectException(OrderNotFoundException::class);

        // When
        $this->dispatch(new CancelOrder(OrderId::generate()->toString()));
    }

    #[Test]
    public function itFailsWhenAPaymentHasAlreadyBeenRequested(): void
    {
        // Given
        $order = OrderTestFactory::new()->create();
        $this->store($order);
        $this->store(OrderPaymentTestFactory::new()->withOrderId($order->id()->toString())->create());

        // Then
        $this->expectException(OrderPaymentAlreadyRequestedException::class);

        // When
        $this->dispatch(new CancelOrder($order->id()->toString()));
    }
}
