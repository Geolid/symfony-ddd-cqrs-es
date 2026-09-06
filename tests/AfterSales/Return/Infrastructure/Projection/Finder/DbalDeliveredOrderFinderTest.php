<?php

declare(strict_types=1);

namespace AfterSales\Tests\Return\Infrastructure\Projection\Finder;

use AfterSales\Return\Application\Finder\DeliveredOrder\DeliveredOrderFinderInterface;
use AfterSales\Return\Application\Finder\DeliveredOrder\Exception\DeliveredOrderResultNotFoundException;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class DbalDeliveredOrderFinderTest extends AbstractIntegrationTestCase
{
    private DeliveredOrderFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(DeliveredOrderFinderInterface::class);
    }

    #[Test]
    public function itGetsById(): void
    {
        // Given
        $other = OrderBuilder::new()->confirmed()->prepared()->dispatched()->delivered()->create();
        $this->store($other);
        $builder = OrderBuilder::new()->confirmed()->prepared()->dispatched()->delivered();
        $order = $builder->create();
        $this->store($order);

        // When
        $result = $this->finder->ofId($order->id->toString());

        // Then
        $shippingAddress = $builder['shippingAddress']->toArray();
        self::assertSame($order->id->toString(), $result->orderId);
        self::assertSame($builder['buyerId'], $result->buyerId);
        self::assertSame($shippingAddress['recipientName'], $result->shippingAddress->recipientName);
        self::assertSame($shippingAddress['address']['street'], $result->shippingAddress->address->street);
        self::assertSame($shippingAddress['address']['postalCode'], $result->shippingAddress->address->postalCode);
        self::assertSame($shippingAddress['address']['city'], $result->shippingAddress->address->city);
        self::assertSame($shippingAddress['address']['countryCode'], $result->shippingAddress->address->countryCode);
        self::assertSame($builder['deliveredAt']->format(\DateTimeInterface::ATOM), $result->deliveredAt->format(\DateTimeInterface::ATOM));
    }

    #[Test]
    public function itThrowsWhenIdNotFound(): void
    {
        // Then
        $this->expectException(DeliveredOrderResultNotFoundException::class);

        // When
        $this->finder->ofId(Uuid::uuid7()->toString());
    }

    #[Test]
    public function itFiltersByIds(): void
    {
        // Given
        $other = OrderBuilder::new()->confirmed()->prepared()->dispatched()->delivered()->create();
        $order = OrderBuilder::new()->confirmed()->prepared()->dispatched()->delivered()->create();
        $this->store($other, $order);

        // When
        $results = iterator_to_array($this->finder->byIds($order->id->toString()));

        // Then
        self::assertCount(1, $results);
        self::assertSame($order->id->toString(), $results[0]->orderId);
    }
}
