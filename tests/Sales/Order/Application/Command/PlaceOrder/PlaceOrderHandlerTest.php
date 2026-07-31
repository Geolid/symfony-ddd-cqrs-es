<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Command\PlaceOrder;

use PHPUnit\Framework\Attributes\Test;
use Sales\Customer\Domain\Customer;
use Sales\Order\Application\Command\PlaceOrder\PlaceOrder;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Domain\OrderId;
use Sales\Order\Domain\Repository\OrderRepositoryInterface;
use Sales\Tests\Customer\Support\Factory\CustomerTestFactory;
use Support\AbstractIntegrationTestCase;

final class PlaceOrderHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPlacesAnOrder(): void
    {
        // Given
        $customer = $this->registeredCustomer('buyer@example.com');
        $id = OrderId::generate()->toString();

        // When
        $this->dispatch(new PlaceOrder($id, $customer->id()->toString(), 1_999));

        // Then
        $results = array_values(iterator_to_array($this->service(OrderFinderInterface::class)));
        self::assertCount(1, $results);
        self::assertSame($id, $results[0]->id);
        self::assertSame($customer->id()->toString(), $results[0]->customerId);
        self::assertSame(1_999, $results[0]->totalAmountInCents);
        self::assertSame('placed', $results[0]->status);
        self::assertSame('buyer@example.com', $this->buyerAddressOf($id));
    }

    #[Test]
    public function itPlacesAnOrderWithoutAddressForAnErasedBuyer(): void
    {
        // Given
        $customer = CustomerTestFactory::new()->withEmail('buyer@example.com')->erased()->create();
        $this->store($customer);
        $id = OrderId::generate()->toString();

        // When
        $this->dispatch(new PlaceOrder($id, $customer->id()->toString(), 1_999));

        // Then
        self::assertNull($this->buyerAddressOf($id));
    }

    #[Test]
    public function itPlacesAnOrderWithoutAddressForAnUnknownBuyer(): void
    {
        // Given
        $id = OrderId::generate()->toString();

        // When
        $this->dispatch(new PlaceOrder($id, CustomerId::generate()->toString(), 1_999));

        // Then
        self::assertNull($this->buyerAddressOf($id));
    }

    private function registeredCustomer(string $email): Customer
    {
        $customer = CustomerTestFactory::new()->withEmail($email)->create();

        $this->store($customer);

        return $customer;
    }

    private function buyerAddressOf(string $id): ?string
    {
        return $this->service(OrderRepositoryInterface::class)
            ->load(OrderId::fromString($id))
            ->buyerAddress();
    }
}
