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
use Symfony\Component\Clock\Clock;

final class AnonymizeExpiredOrderHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itAnonymizesWhenRetentionPeriodHasElapsed(): void
    {
        // Given
        $order = OrderTestFactory::new()->cancelled(Clock::get()->now()->modify('-11 years'))->create();
        $this->store($order);

        // When
        $this->dispatch(new AnonymizeExpiredOrder($order->id->toString()));

        // Then
        $result = $this->service(OrderFinderInterface::class)->ofId($order->id->toString());
        self::assertSame(OrderStatus::CANCELLED, $result->status);
        self::assertNotNull($result->anonymizedAt);
    }

    #[Test]
    public function itIgnoresWhenNotClosed(): void
    {
        // Given
        $order = OrderTestFactory::new()->create();
        $this->store($order);

        // When
        $this->dispatch(new AnonymizeExpiredOrder($order->id->toString()));

        // Then
        $result = $this->service(OrderFinderInterface::class)->ofId($order->id->toString());
        self::assertNull($result->anonymizedAt);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(OrderNotFoundException::class);

        // When
        $this->dispatch(new AnonymizeExpiredOrder(Uuid::uuid7()->toString()));
    }
}
