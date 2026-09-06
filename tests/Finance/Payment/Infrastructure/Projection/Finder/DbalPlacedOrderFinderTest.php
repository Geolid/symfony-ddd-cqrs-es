<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Infrastructure\Projection\Finder;

use Finance\Payment\Application\Finder\PlacedOrder\Exception\PlacedOrderResultNotFoundException;
use Finance\Payment\Application\Finder\PlacedOrder\PlacedOrderFinderInterface;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Domain\ValueObject\OrderLine;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class DbalPlacedOrderFinderTest extends AbstractIntegrationTestCase
{
    private PlacedOrderFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(PlacedOrderFinderInterface::class);
    }

    #[Test]
    public function itGetsById(): void
    {
        // Given
        $other = OrderBuilder::new()->create();
        $builder = OrderBuilder::new();
        $order = $builder->create();
        $this->store($other, $order);

        // When
        $result = $this->finder->ofId($order->id->toString());

        // Then
        $billingAddress = $builder['billingAddress']->toArray();
        self::assertSame($order->id->toString(), $result->orderId);
        self::assertSame($this->totalAmountInCents($builder['lines']), $result->amountInCents);
        self::assertSame($billingAddress['recipientName'], $result->billingAddress->recipientName);
        self::assertSame($billingAddress['address']['street'], $result->billingAddress->address->street);
        self::assertSame($billingAddress['address']['postalCode'], $result->billingAddress->address->postalCode);
        self::assertSame($billingAddress['address']['city'], $result->billingAddress->address->city);
        self::assertSame($billingAddress['address']['countryCode'], $result->billingAddress->address->countryCode);
        self::assertFalse($result->cancelled);
    }

    #[Test]
    public function itThrowsWhenIdNotFound(): void
    {
        // Then
        $this->expectException(PlacedOrderResultNotFoundException::class);

        // When
        $this->finder->ofId(Uuid::uuid7()->toString());
    }

    /**
     * @param list<OrderLine> $lines
     */
    private function totalAmountInCents(array $lines): int
    {
        return array_sum(array_map(static fn (OrderLine $line): int => $line->total()->cents, $lines));
    }
}
