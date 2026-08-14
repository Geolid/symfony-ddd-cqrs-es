<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Command\PlaceOrder;

use Catalog\Tests\Product\Support\Factory\ProductTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Customer\Domain\Customer;
use Sales\Customer\Domain\Repository\CustomerAddressesRepositoryInterface;
use Sales\Customer\Domain\ValueObject\CustomerId;
use Sales\Order\Application\Command\PlaceOrder\PlaceOrder;
use Sales\Order\Application\Enum\OrderStatus;
use Sales\Order\Application\Exception\BuyerAddressesNotCompletedException;
use Sales\Order\Application\Exception\BuyerNotRegisteredException;
use Sales\Order\Application\Exception\OutdatedOrderException;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Domain\Order;
use Sales\Order\Domain\Repository\OrderRepositoryInterface;
use Sales\Order\Domain\ValueObject\OrderId;
use Sales\Tests\Customer\Support\Factory\CustomerTestFactory;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\FullName;
use Shared\Domain\ValueObject\PostalAddress;
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
        $order = $this->orderOf($id);
        self::assertSame('12 rue des Lilas', $order->shippingAddress()->address->street);
        self::assertSame('8 avenue Foch', $order->billingAddress()->address->street);
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
        $customer = CustomerTestFactory::new()->withEmail('buyer@example.com')->erased()->store();

        // Then
        $this->expectException(BuyerNotRegisteredException::class);

        // When
        $this->dispatch(new PlaceOrder(OrderId::generate()->toString(), $customer->id()->toString(), $this->lines()));
    }

    #[Test]
    public function itFailsWhenTheBuyerHasNotCompletedTheirAddresses(): void
    {
        // Given
        $customer = CustomerTestFactory::new()->withEmail('buyer@example.com')->store();

        // Then
        $this->expectException(BuyerAddressesNotCompletedException::class);

        // When
        $this->dispatch(new PlaceOrder(OrderId::generate()->toString(), $customer->id()->toString(), $this->lines()));
    }

    #[Test]
    public function itFailsWhenAProductIsNotAvailable(): void
    {
        // Given
        $customer = $this->registeredCustomer('buyer@example.com');

        // Then
        $this->expectException(OutdatedOrderException::class);

        // When
        $this->dispatch(new PlaceOrder(
            OrderId::generate()->toString(),
            $customer->id()->toString(),
            [['productId' => Uuid::uuid7()->toString(), 'quantity' => 1, 'label' => 'Ghost mug', 'unitAmountInCents' => 500]],
        ));
    }

    #[Test]
    public function itFailsWhenAProductHasChangedSinceItWasDisplayed(): void
    {
        // Given
        $customer = $this->registeredCustomer('buyer@example.com');
        $cups = ProductTestFactory::new()->withLabel('Espresso cups, set of 6')->withUnitAmountInCents(1_750)->store();

        // Then
        $this->expectException(OutdatedOrderException::class);

        // When
        $this->dispatch(new PlaceOrder(
            OrderId::generate()->toString(),
            $customer->id()->toString(),
            [['productId' => $cups->id()->toString(), 'quantity' => 1, 'label' => 'Espresso cups, set of 6', 'unitAmountInCents' => 1_500]],
        ));
    }

    /**
     * @return list<array{productId: string, quantity: int, label: string, unitAmountInCents: int}>
     */
    private function lines(): array
    {
        $cups = ProductTestFactory::new()->withLabel('Espresso cups, set of 6')->withUnitAmountInCents(1_750)->store();
        $saucer = ProductTestFactory::new()->withLabel('Saucer')->withUnitAmountInCents(83)->store();

        return [
            ['productId' => $cups->id()->toString(), 'quantity' => 1, 'label' => 'Espresso cups, set of 6', 'unitAmountInCents' => 1_750],
            ['productId' => $saucer->id()->toString(), 'quantity' => 3, 'label' => 'Saucer', 'unitAmountInCents' => 83],
        ];
    }

    private function registeredCustomer(string $email): Customer
    {
        $customer = CustomerTestFactory::new()->withEmail($email)->store();
        $customerAddresses = $this->service(CustomerAddressesRepositoryInterface::class)->load($customer->id());
        $customerAddresses->setShippingAddress(
            PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('12 rue des Lilas', '75001', 'Paris')),
            new \DateTimeImmutable('now +00:00'),
        );
        $customerAddresses->setBillingAddress(
            PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('8 avenue Foch', '75116', 'Paris')),
            new \DateTimeImmutable('now +00:00'),
        );
        $this->store($customerAddresses);

        return $customer;
    }

    private function orderOf(string $id): Order
    {
        return $this->service(OrderRepositoryInterface::class)->load(OrderId::fromString($id));
    }
}
