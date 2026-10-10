<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Infrastructure\EventStore;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Sales\Ordering\Domain\Order\Entity\OrderLine;
use Sales\Ordering\Domain\Order\Exception\OrderAlreadyExistsException;
use Sales\Ordering\Domain\Order\Exception\OrderNotFoundException;
use Sales\Ordering\Domain\Order\Order;
use Sales\Ordering\Domain\Order\Repository\OrderRepositoryInterface;
use Sales\Tests\Ordering\Support\Factory\OrderFactory;
use Sales\Tests\Ordering\Support\Factory\OrderIdFactory;
use Shared\Application\Mapper\PostalAddressMapper;
use Support\TestCase\AbstractIntegrationTestCase;

final class PatchlevelOrderRepositoryTest extends AbstractIntegrationTestCase
{
    private OrderRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->service(OrderRepositoryInterface::class);
    }

    #[Test]
    #[DataProvider('provideLifecycle')]
    public function itSavesAndLoads(OrderFactory $factory): void
    {
        // Given
        $order = $factory->create();

        // When
        $this->repository->save($order);
        $loaded = $this->repository->load($order->id);

        // Then
        self::assertSame($this->propertiesOf($order), $this->propertiesOf($loaded));
    }

    /**
     * @return iterable<string, array{OrderFactory}>
     */
    public static function provideLifecycle(): iterable
    {
        yield 'delivered' => [OrderFactory::new()->prepared()->dispatched()->delivered()->erasureApproved()];
        yield 'cancelled' => [OrderFactory::new()->cancelled()];
        yield 'failed' => [OrderFactory::new()->failed()];
    }

    #[Test]
    public function itThrowsWhenAlreadyExists(): void
    {
        // Given
        $order = OrderFactory::new()
            ->create();
        $this->store($order);
        $duplicate = OrderFactory::new()
            ->withCheckoutSessionId($order->checkoutSessionId)
            ->create();

        // Then
        $this->expectException(OrderAlreadyExistsException::class);

        // When
        $this->repository->save($duplicate);
    }

    #[Test]
    public function itThrowsWhenNotFound(): void
    {
        // Then
        $this->expectException(OrderNotFoundException::class);

        // When
        $this->repository->load(OrderIdFactory::new()->create());
    }

    #[Test]
    public function itHas(): void
    {
        // Given
        $order = OrderFactory::new()->create();
        $this->store($order);

        // When
        $exists = $this->repository->has($order->id);

        // Then
        self::assertTrue($exists);
    }

    #[Test]
    public function itHasNot(): void
    {
        // When
        $notExists = $this->repository->has(OrderIdFactory::new()->create());

        // Then
        self::assertFalse($notExists);
    }

    /**
     * @return array<string, mixed>
     */
    private function propertiesOf(Order $order): array
    {
        $atom = static fn (?\DateTimeImmutable $date): ?string => $date?->format(\DateTimeInterface::ATOM);

        return [
            'id' => $order->id->toString(),
            'cartId' => $order->cartId,
            'customerId' => $order->customerId,
            'checkoutSessionId' => $order->checkoutSessionId,
            'shippingAddress' => PostalAddressMapper::toArray($order->shippingAddress),
            'lines' => array_map(static fn (OrderLine $line): array => [
                'id' => $line->id->toString(),
                'productId' => $line->item->product->id,
                'label' => $line->item->product->label->value,
                'priceCents' => $line->item->product->price->cents,
                'quantity' => $line->item->quantity->value,
                'taxAmountCents' => $line->item->taxAmount->cents,
            ], $order->lines),
            'total' => [
                'excludingTax' => $order->total->excludingTax->cents,
                'taxAmount' => $order->total->taxAmount->cents,
                'includingTax' => $order->total->includingTax->cents,
            ],
            'confirmedAt' => $atom($order->confirmedAt),
            'operationalState' => $order->operationalState->value,
            'preparedAt' => $atom($order->preparedAt),
            'cancelledAt' => $atom($order->cancelledAt),
            'failedAt' => $atom($order->failedAt),
            'dispatchedAt' => $atom($order->dispatchedAt),
            'deliveredAt' => $atom($order->deliveredAt),
            'erasureState' => $order->erasureState->value,
            'erasureApprovedAt' => $atom($order->erasureApprovedAt),
            'erasedAt' => $atom($order->erasedAt),
        ];
    }
}
