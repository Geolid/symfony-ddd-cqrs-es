<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Infrastructure\Projection\Finder;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Ordering\Application\Finder\Order\Exception\OrderResultNotFoundException;
use Sales\Ordering\Application\Finder\Order\OrderFinderInterface;
use Sales\Ordering\Application\Finder\Order\OrderResult;
use Sales\Ordering\Application\OrderStatus;
use Sales\Ordering\Domain\Order\Order;
use Sales\Tests\Ordering\Support\Builder\OrderBuilder;
use Shared\Application\ErasureStatus;
use Shared\Application\Mapper\PostalAddressMapper;
use Shared\Tests\Support\TestCase\AbstractIterableFinderTestCase;

/**
 * @extends AbstractIterableFinderTestCase<OrderResult>
 */
final class DbalOrderFinderTest extends AbstractIterableFinderTestCase
{
    #[Test]
    public function itGets(): void
    {
        // Given
        $builder = OrderBuilder::new()->prepared()->dispatched()->delivered();
        $order = $builder->create();
        $this->store($order);

        // When
        $result = $this->finder()->ofId($order->id->toString());

        // Then
        self::assertSame($order->id->toString(), $result->id);
        self::assertSame($builder['shopperId'], $result->shopperId);
        self::assertSame($builder['paymentId'], $result->paymentId);
        self::assertSame(
            PostalAddressMapper::toArray($builder['shippingAddress']),
            ['recipientName' => $result->shippingAddress->recipientName, 'address' => (array) $result->shippingAddress->address],
        );
        self::assertSame(
            PostalAddressMapper::toArray($builder['billingAddress']),
            ['recipientName' => $result->billingAddress->recipientName, 'address' => (array) $result->billingAddress->address],
        );
        self::assertSame($order->totalAmountInCents, $result->totalAmountInCents);
        self::assertSame(OrderStatus::DELIVERED, $result->status);
        self::assertSame($builder['confirmedAt']->format('Y-m-d H:i:s'), $result->confirmedAt->format('Y-m-d H:i:s'));
        self::assertSame($builder['preparedAt']->format('Y-m-d H:i:s'), $result->preparedAt?->format('Y-m-d H:i:s'));
        self::assertSame($builder['dispatchedAt']->format('Y-m-d H:i:s'), $result->dispatchedAt?->format('Y-m-d H:i:s'));
        self::assertSame($builder['deliveredAt']->format('Y-m-d H:i:s'), $result->deliveredAt?->format('Y-m-d H:i:s'));
        self::assertNull($result->cancelledAt);
        self::assertNull($result->failedAt);
        self::assertSame(ErasureStatus::RETAINED, $result->erasureStatus);
    }

    #[Test]
    public function itThrowsWhenIdNotFound(): void
    {
        // Then
        $this->expectException(OrderResultNotFoundException::class);

        // When
        $this->finder()->ofId(Uuid::uuid7()->toString());
    }

    #[Test]
    public function itFiltersByShopper(): void
    {
        // Given
        $shopperId = Uuid::uuid7()->toString();
        $other = OrderBuilder::new()->create();
        $order = OrderBuilder::new()->withShopperId($shopperId)->create();
        $this->store($other, $order);

        // When
        $results = iterator_to_array($this->finder()->byShopper($shopperId), false);

        // Then
        self::assertCount(1, $results);
        self::assertSame($order->id->toString(), $results[0]->id);
    }

    protected function finder(): OrderFinderInterface
    {
        return $this->service(OrderFinderInterface::class);
    }

    /**
     * @return list<string>
     */
    protected function seed(int $count): array
    {
        $orders = OrderBuilder::new()->many($count)->create();
        $this->store(...$orders);

        return array_map(static fn (Order $order): string => $order->id->toString(), $orders);
    }

    protected function idOf(object $result): string
    {
        return $result->id;
    }
}
