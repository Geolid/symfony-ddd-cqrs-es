<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Command\AnonymizeExpiredOrder;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Command\AnonymizeExpiredOrder\AnonymizeExpiredOrder;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\Status\OrderStatus;
use Sales\Order\Domain\Exception\OrderNotFoundException;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

final class AnonymizeExpiredOrderHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itAnonymizesAPlacedOrder(): void
    {
        // Given
        $order = OrderTestFactory::new()->store();

        // When
        $this->dispatch(new AnonymizeExpiredOrder($order->id()->toString()));

        // Then
        $result = $this->service(OrderFinderInterface::class)->ofId($order->id()->toString());
        self::assertSame(OrderStatus::PLACED, $result->status);
        self::assertNotNull($result->anonymizedAt);
    }

    #[Test]
    public function itFailsWhenTheOrderDoesNotExist(): void
    {
        // Then
        $this->expectException(OrderNotFoundException::class);

        // When
        $this->dispatch(new AnonymizeExpiredOrder(Uuid::uuid7()->toString()));
    }
}
