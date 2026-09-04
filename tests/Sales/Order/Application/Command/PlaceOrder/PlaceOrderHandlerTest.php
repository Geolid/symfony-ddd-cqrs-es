<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Command\PlaceOrder;

use Catalog\Product\Domain\ValueObject\ProductId;
use Catalog\Tests\Product\Support\Builder\ProductBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Sales\Customer\Domain\Customer;
use Sales\Customer\Domain\ValueObject\CustomerId;
use Sales\Order\Application\Command\PlaceOrder\PlaceOrder;
use Sales\Order\Application\Exception\BuyerAddressesNotCompletedException;
use Sales\Order\Application\Exception\BuyerNotRegisteredException;
use Sales\Order\Application\Exception\OutdatedOrderException;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\OrderStatus;
use Sales\Order\Domain\Order;
use Sales\Order\Domain\Repository\OrderRepositoryInterface;
use Sales\Order\Domain\ValueObject\OrderId;
use Sales\Tests\Customer\Support\Builder\CustomerBuilder;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\PostalAddress;
use Support\TestCase\AbstractIntegrationTestCase;

final class PlaceOrderHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPlaces(): void
    {
        // Given
        $customer = $this->registeredCustomer('buyer@example.com');
        $id = OrderId::generate()->toString();
        $lines = $this->lines();

        // When
        $this->dispatch(new PlaceOrder($id, $customer->id->toString(), $lines));

        // Then
        $result = $this->service(OrderFinderInterface::class)->ofId($id);
        self::assertSame($id, $result->id);
        self::assertSame($customer->id->toString(), $result->customerId);
        self::assertSame($this->totalAmountInCents($lines), $result->totalAmountInCents);
        self::assertSame(OrderStatus::PLACED, $result->status);

        $order = $this->orderOf($id);
        $shippingAddress = $order->shippingAddress->toArray();
        $billingAddress = $order->billingAddress->toArray();
        $expectedShippingAddress = $this->shippingAddress()->toArray();
        $expectedBillingAddress = $this->billingAddress()->toArray();
        self::assertSame($expectedShippingAddress, $shippingAddress);
        self::assertSame($expectedBillingAddress, $billingAddress);
    }

    #[Test]
    public function itFailsWhenBuyerNotRegistered(): void
    {
        // Given
        $customerId = CustomerId::generate()->toString();

        // Then
        $this->expectException(BuyerNotRegisteredException::class);

        // When
        $this->dispatch(new PlaceOrder(OrderId::generate()->toString(), $customerId, $this->lines()));
    }

    #[Test]
    public function itFailsWhenBuyerErased(): void
    {
        // Given
        $customer = CustomerBuilder::new()->withEmail('buyer@example.com')->erased()->create();
        $this->store($customer);

        // Then
        $this->expectException(BuyerNotRegisteredException::class);

        // When
        $this->dispatch(new PlaceOrder(OrderId::generate()->toString(), $customer->id->toString(), $this->lines()));
    }

    #[Test]
    #[DataProvider('provideIncompleteAddresses')]
    public function itFailsWhenBuyerAddressesNotCompleted(bool $withShippingAddress, bool $withBillingAddress): void
    {
        // Given
        $customer = CustomerBuilder::new()->withEmail('buyer@example.com');
        if ($withShippingAddress) {
            $customer = $customer->shippingAddressRegistered($this->shippingAddress());
        }
        if ($withBillingAddress) {
            $customer = $customer->billingAddressRegistered($this->billingAddress());
        }
        $customer = $customer->create();
        $this->store($customer);

        // Then
        $this->expectException(BuyerAddressesNotCompletedException::class);

        // When
        $this->dispatch(new PlaceOrder(OrderId::generate()->toString(), $customer->id->toString(), $this->lines()));
    }

    /**
     * @return iterable<string, array{bool, bool}>
     */
    public static function provideIncompleteAddresses(): iterable
    {
        yield 'neither address set' => [false, false];
        yield 'only shipping address set' => [true, false];
        yield 'only billing address set' => [false, true];
    }

    #[Test]
    public function itFailsWhenProductNotAvailable(): void
    {
        // Given
        $customer = $this->registeredCustomer('buyer@example.com');

        // Then
        $this->expectException(OutdatedOrderException::class);

        // When
        $this->dispatch(new PlaceOrder(
            OrderId::generate()->toString(),
            $customer->id->toString(),
            [['productId' => ProductId::generate()->toString(), 'quantity' => 1, 'label' => ProductBuilder::sample('label')->value, 'unitAmountInCents' => ProductBuilder::sample('unitAmount')->cents]],
        ));
    }

    #[Test]
    public function itFailsWhenProductChanged(): void
    {
        // Given
        $customer = $this->registeredCustomer('buyer@example.com');
        $label = ProductBuilder::sample('label');
        $unitAmount = ProductBuilder::sample('unitAmount');
        $cups = ProductBuilder::new()->withLabel($label->value)->withUnitAmountInCents($unitAmount->cents)->create();
        $this->store($cups);

        // Then
        $this->expectException(OutdatedOrderException::class);

        // When
        $this->dispatch(new PlaceOrder(
            OrderId::generate()->toString(),
            $customer->id->toString(),
            [['productId' => $cups->id->toString(), 'quantity' => 1, 'label' => $label->value, 'unitAmountInCents' => $unitAmount->cents - 250]],
        ));
    }

    /**
     * @return list<array{productId: string, quantity: int, label: string, unitAmountInCents: int}>
     */
    private function lines(): array
    {
        $cupsLabel = ProductBuilder::sample('label');
        $cupsUnitAmount = ProductBuilder::sample('unitAmount');
        $cups = ProductBuilder::new()->withLabel($cupsLabel->value)->withUnitAmountInCents($cupsUnitAmount->cents)->create();

        $saucerLabel = ProductBuilder::sample('label');
        $saucerUnitAmount = ProductBuilder::sample('unitAmount');
        $saucer = ProductBuilder::new()->withLabel($saucerLabel->value)->withUnitAmountInCents($saucerUnitAmount->cents)->create();

        $this->store($cups, $saucer);

        return [
            ['productId' => $cups->id->toString(), 'quantity' => 1, 'label' => $cupsLabel->value, 'unitAmountInCents' => $cupsUnitAmount->cents],
            ['productId' => $saucer->id->toString(), 'quantity' => 3, 'label' => $saucerLabel->value, 'unitAmountInCents' => $saucerUnitAmount->cents],
        ];
    }

    /**
     * @param list<array{productId: string, quantity: int, label: string, unitAmountInCents: int}> $lines
     */
    private function totalAmountInCents(array $lines): int
    {
        return array_sum(array_map(static fn (array $line): int => $line['quantity'] * $line['unitAmountInCents'], $lines));
    }

    private function registeredCustomer(string $email): Customer
    {
        $customer = CustomerBuilder::new()
            ->withEmail($email)
            ->shippingAddressRegistered($this->shippingAddress())
            ->billingAddressRegistered($this->billingAddress())
            ->create();
        $this->store($customer);

        return $customer;
    }

    private function shippingAddress(): PostalAddress
    {
        return PostalAddress::of('Ada Lovelace', Address::of('12 rue des Lilas', '75001', 'Paris', 'FR'));
    }

    private function billingAddress(): PostalAddress
    {
        return PostalAddress::of('Ada Lovelace', Address::of('8 avenue Foch', '75116', 'Paris', 'FR'));
    }

    private function orderOf(string $id): Order
    {
        return $this->service(OrderRepositoryInterface::class)->load(OrderId::fromString($id));
    }
}
