<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Command\PlaceOrder;

use Catalog\Tests\Product\Support\Factory\ProductTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Customer\Domain\Customer;
use Sales\Customer\Domain\ValueObject\CustomerId;
use Sales\Order\Application\Command\PlaceOrder\PlaceOrder;
use Sales\Order\Application\Enum\OrderStatus;
use Sales\Order\Application\Exception\BuyerNotRegisteredException;
use Sales\Order\Application\Exception\ProductNotAvailableException;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Domain\Repository\OrderRepositoryInterface;
use Sales\Order\Domain\ValueObject\OrderId;
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
        $this->dispatch(new PlaceOrder($id, $customer->id()->toString(), $this->lines()));

        // Then
        $result = $this->service(OrderFinderInterface::class)->ofId($id);
        self::assertSame($id, $result->id);
        self::assertSame($customer->id()->toString(), $result->customerId);
        self::assertSame(1_999, $result->totalAmountInCents);
        self::assertSame(OrderStatus::PLACED, $result->status);
        self::assertSame('buyer@example.com', $this->buyerAddressOf($id));
    }

    #[Test]
    public function itFailsWhenTheBuyerIsNotRegistered(): void
    {
        // Given
        $customerId = CustomerId::generate()->toString();

        // Then
        $this->expectException(BuyerNotRegisteredException::class);

        // When
        $this->dispatch(new PlaceOrder(OrderId::generate()->toString(), $customerId, $this->lines()));
    }

    #[Test]
    public function itFailsWhenTheBuyerIsErased(): void
    {
        // Given
        $customer = CustomerTestFactory::new()->withEmail('buyer@example.com')->erased()->create();
        $this->store($customer);

        // Then
        $this->expectException(BuyerNotRegisteredException::class);

        // When
        $this->dispatch(new PlaceOrder(OrderId::generate()->toString(), $customer->id()->toString(), $this->lines()));
    }

    #[Test]
    public function itFailsWhenAProductIsNotAvailable(): void
    {
        // Given
        $customer = $this->registeredCustomer('buyer@example.com');

        // Then
        $this->expectException(ProductNotAvailableException::class);

        // When
        $this->dispatch(new PlaceOrder(
            OrderId::generate()->toString(),
            $customer->id()->toString(),
            [['productId' => Uuid::uuid7()->toString(), 'quantity' => 1]],
        ));
    }

    /**
     * @return list<array{productId: string, quantity: int}>
     */
    private function lines(): array
    {
        $cups = ProductTestFactory::new()->withLabel('Espresso cups, set of 6')->withUnitAmountInCents(1_750)->create();
        $this->store($cups);
        $saucer = ProductTestFactory::new()->withLabel('Saucer')->withUnitAmountInCents(83)->create();
        $this->store($saucer);

        return [
            ['productId' => $cups->id()->toString(), 'quantity' => 1],
            ['productId' => $saucer->id()->toString(), 'quantity' => 3],
        ];
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
