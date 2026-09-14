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
use Sales\Ordering\Domain\Order\ValueObject\OrderId;
use Sales\Ordering\Domain\Order\ValueObject\OrderItem;
use Sales\Tests\Ordering\Support\Builder\OrderBuilder;
use Sales\Tests\Ordering\Support\PostalAddressResultMapper;
use Shared\Application\ErasureStatus;
use Shared\Application\Mapper\PostalAddressMapper;
use Shared\Domain\ValueObject\TaxedAmount;
use Shared\Tests\Support\TestCase\AbstractIterableFinderTestCase;
use Shared\Tests\Support\TestCase\RealColumnLeadsTrait;
use Symfony\Component\Clock\Clock;

/**
 * @extends AbstractIterableFinderTestCase<OrderResult>
 */
final class DbalOrderFinderTest extends AbstractIterableFinderTestCase
{
    use RealColumnLeadsTrait;

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
        self::assertSame($builder['customerId'], $result->customerId);
        self::assertSame($builder['checkoutSessionId'], $result->checkoutSessionId);
        self::assertSame(
            PostalAddressMapper::toArray($builder['shippingAddress']),
            PostalAddressResultMapper::toArray($result->shippingAddress),
        );
        $total = array_reduce(
            $builder['items'],
            static fn (TaxedAmount $carry, OrderItem $item): TaxedAmount => $carry->plus($item->taxedTotal()),
            TaxedAmount::zero($builder['currency']),
        );
        self::assertSame($total->excludingTax->cents, $result->totalExcludingTaxInCents);
        self::assertSame($total->taxAmount->cents, $result->totalTaxAmountInCents);
        self::assertSame($total->includingTax->cents, $result->totalIncludingTaxInCents);
        self::assertSame($builder['currency']->value, $result->currency);
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
    public function itFiltersByCustomer(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $other = OrderBuilder::new()->create();
        $order = OrderBuilder::new()->withCustomerId($customerId)->create();
        $this->store($other, $order);

        // When
        $results = iterator_to_array($this->finder()->byCustomer($customerId), false);

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

    protected function indexOf(object $result): string
    {
        return $result->id;
    }

    /**
     * @return array{string, string}
     */
    protected function seedConflictingOrder(): array
    {
        $checkoutSessionIdByOrderId = [];
        foreach ([Uuid::uuid7()->toString(), Uuid::uuid7()->toString()] as $checkoutSessionId) {
            $checkoutSessionIdByOrderId[OrderId::forCheckoutSession($checkoutSessionId)->toString()] = $checkoutSessionId;
        }
        ksort($checkoutSessionIdByOrderId);
        [$smallerId, $largerId] = array_keys($checkoutSessionIdByOrderId);

        $now = Clock::get()->now();
        $first = OrderBuilder::new()->withCheckoutSessionId($checkoutSessionIdByOrderId[$largerId])->withConfirmedAt($now)->create();
        $second = OrderBuilder::new()->withCheckoutSessionId($checkoutSessionIdByOrderId[$smallerId])->withConfirmedAt($now->modify('+1 hour'))->create();
        $this->store($first, $second);

        return [$largerId, $smallerId];
    }
}
