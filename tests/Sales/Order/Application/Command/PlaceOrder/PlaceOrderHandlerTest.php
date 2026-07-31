<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Command\PlaceOrder;

use PHPUnit\Framework\Attributes\Test;
use Sales\Customer\Application\Command\EraseCustomer\EraseCustomer;
use Sales\Customer\Application\Command\RegisterCustomer\RegisterCustomer;
use Sales\Customer\Domain\CustomerId;
use Sales\Order\Application\Command\PlaceOrder\PlaceOrder;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Domain\OrderId;
use Sales\Order\Domain\Repository\OrderRepositoryInterface;
use Support\AbstractIntegrationTestCase;

final class PlaceOrderHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPlacesAnOrder(): void
    {
        // Given
        $id = OrderId::generate()->toString();
        $command = new PlaceOrder($id, 'customer-1', 1_999);

        // When
        $this->dispatch($command);

        // Then
        $results = array_values(iterator_to_array($this->service(OrderFinderInterface::class)));
        self::assertCount(1, $results);
        self::assertSame($id, $results[0]->id);
        self::assertSame('customer-1', $results[0]->customerId);
        self::assertSame(1_999, $results[0]->totalAmountInCents);
        self::assertSame('placed', $results[0]->status);
    }

    #[Test]
    public function itRecordsTheAddressOfTheBuyerBehindTheOrder(): void
    {
        // Given
        $customerId = CustomerId::generate()->toString();
        $this->dispatch(new RegisterCustomer($customerId, 'buyer@example.com'));
        $id = OrderId::generate()->toString();

        // When
        $this->dispatch(new PlaceOrder($id, $customerId, 1_999));

        // Then
        self::assertSame('buyer@example.com', $this->buyerAddressOf($id));
    }

    #[Test]
    public function itRecordsNoAddressWhenTheBuyerIsErased(): void
    {
        // Given
        $customerId = CustomerId::generate()->toString();
        $this->dispatch(new RegisterCustomer($customerId, 'buyer@example.com'));
        $this->dispatch(new EraseCustomer($customerId));
        $id = OrderId::generate()->toString();

        // When
        $this->dispatch(new PlaceOrder($id, $customerId, 1_999));

        // Then
        self::assertNull($this->buyerAddressOf($id));
    }

    private function buyerAddressOf(string $id): ?string
    {
        return $this->service(OrderRepositoryInterface::class)
            ->load(OrderId::fromString($id))
            ->buyerAddress();
    }
}
