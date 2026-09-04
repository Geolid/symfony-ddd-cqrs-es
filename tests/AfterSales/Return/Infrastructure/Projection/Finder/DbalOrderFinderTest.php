<?php

declare(strict_types=1);

namespace AfterSales\Tests\Return\Infrastructure\Projection\Finder;

use AfterSales\Return\Application\Exception\OrderResultNotFoundException;
use AfterSales\Return\Application\Finder\Order\OrderFinderInterface;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class DbalOrderFinderTest extends AbstractIntegrationTestCase
{
    private OrderFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(OrderFinderInterface::class);
    }

    #[Test]
    public function itGetsById(): void
    {
        // Given
        $other = OrderBuilder::new()->confirmed()->dispatched()->delivered()->create();
        $this->store($other);
        $builder = OrderBuilder::new()->confirmed()->dispatched()->delivered();
        $order = $builder->create();
        $this->store($order);

        // When
        $result = $this->finder->ofId($order->id->toString());

        // Then
        $shippingAddress = $builder['shippingAddress']->toArray();
        self::assertSame($order->id->toString(), $result->orderId);
        self::assertSame($builder['buyerId'], $result->buyerId);
        self::assertSame($shippingAddress['recipientName'], $result->shippingAddress->recipientName);
        self::assertSame($shippingAddress['street'], $result->shippingAddress->street);
        self::assertSame($shippingAddress['postalCode'], $result->shippingAddress->postalCode);
        self::assertSame($shippingAddress['city'], $result->shippingAddress->city);
        self::assertSame($shippingAddress['countryCode'], $result->shippingAddress->countryCode);
        self::assertSame($builder['deliveredAt']->format(\DateTimeInterface::ATOM), $result->deliveredAt->format(\DateTimeInterface::ATOM));
    }

    #[Test]
    public function itThrowsWhenIdNotFound(): void
    {
        // Then
        $this->expectException(OrderResultNotFoundException::class);

        // When
        $this->finder->ofId(Uuid::uuid7()->toString());
    }
}
