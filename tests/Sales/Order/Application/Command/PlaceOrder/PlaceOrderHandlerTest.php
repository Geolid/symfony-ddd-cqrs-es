<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Command\PlaceOrder;

use Catalog\Listing\Domain\ValueObject\ProductId;
use Catalog\Tests\Listing\Support\Builder\ProductBuilder;
use Finance\Tests\Payer\Support\Builder\PayerBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Sales\Buyer\Domain\Buyer;
use Sales\Buyer\Domain\ValueObject\BuyerId;
use Sales\Order\Application\Command\PlaceOrder\Exception\BuyerAddressesNotCompletedException;
use Sales\Order\Application\Command\PlaceOrder\Exception\BuyerNotRegisteredException;
use Sales\Order\Application\Command\PlaceOrder\Exception\OutdatedOrderException;
use Sales\Order\Application\Command\PlaceOrder\PlaceOrder;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\OrderStatus;
use Sales\Order\Domain\Order;
use Sales\Order\Domain\Repository\OrderRepositoryInterface;
use Sales\Order\Domain\ValueObject\OrderId;
use Sales\Tests\Buyer\Support\Builder\BuyerBuilder;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\PostalAddress;
use Support\TestCase\AbstractIntegrationTestCase;

final class PlaceOrderHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPlaces(): void
    {
        // Given
        $buyer = $this->registeredBuyer('buyer@example.com');
        $id = OrderId::generate()->toString();
        $lines = $this->lines();

        // When
        $this->dispatch(new PlaceOrder($id, $buyer->id->toString(), $lines));

        // Then
        $result = $this->service(OrderFinderInterface::class)->ofId($id);
        self::assertSame($id, $result->id);
        self::assertSame($buyer->id->toString(), $result->buyerId);
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
        $buyerId = BuyerId::generate()->toString();

        // Then
        $this->expectException(BuyerNotRegisteredException::class);

        // When
        $this->dispatch(new PlaceOrder(OrderId::generate()->toString(), $buyerId, $this->lines()));
    }

    #[Test]
    public function itFailsWhenBuyerErased(): void
    {
        // Given
        $buyer = BuyerBuilder::new()->withEmail('buyer@example.com')->erased()->create();
        $this->store($buyer);

        // Then
        $this->expectException(BuyerNotRegisteredException::class);

        // When
        $this->dispatch(new PlaceOrder(OrderId::generate()->toString(), $buyer->id->toString(), $this->lines()));
    }

    #[Test]
    #[DataProvider('provideIncompleteAddresses')]
    public function itFailsWhenBuyerAddressesNotCompleted(bool $withShippingAddress, bool $withBillingAddress): void
    {
        // Given
        $buyerBuilder = BuyerBuilder::new()->withEmail('buyer@example.com');
        if ($withShippingAddress) {
            $buyerBuilder = $buyerBuilder->shippingAddressRegistered($this->shippingAddress());
        }
        $buyer = $buyerBuilder->create();

        $payerBuilder = PayerBuilder::new()->withId($buyer->id->toString());
        if ($withBillingAddress) {
            $payerBuilder = $payerBuilder->addressRegistered($this->billingAddress());
        }
        $payer = $payerBuilder->create();

        $this->store($buyer, $payer);

        // Then
        $this->expectException(BuyerAddressesNotCompletedException::class);

        // When
        $this->dispatch(new PlaceOrder(OrderId::generate()->toString(), $buyer->id->toString(), $this->lines()));
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
    public function itFailsWhenPayerNotRegistered(): void
    {
        // Given
        $buyer = BuyerBuilder::new()
            ->withEmail('buyer@example.com')
            ->shippingAddressRegistered($this->shippingAddress())
            ->create();
        $this->store($buyer);

        // Then
        $this->expectException(BuyerAddressesNotCompletedException::class);

        // When
        $this->dispatch(new PlaceOrder(OrderId::generate()->toString(), $buyer->id->toString(), $this->lines()));
    }

    #[Test]
    public function itFailsWhenProductNotAvailable(): void
    {
        // Given
        $buyer = $this->registeredBuyer('buyer@example.com');

        // Then
        $this->expectException(OutdatedOrderException::class);

        // When
        $this->dispatch(new PlaceOrder(
            OrderId::generate()->toString(),
            $buyer->id->toString(),
            [['productId' => ProductId::generate()->toString(), 'quantity' => 1, 'label' => ProductBuilder::sample('label')->value, 'unitPriceInCents' => ProductBuilder::sample('unitPrice')->cents]],
        ));
    }

    #[Test]
    public function itFailsWhenProductChanged(): void
    {
        // Given
        $buyer = $this->registeredBuyer('buyer@example.com');
        $label = ProductBuilder::sample('label');
        $unitPrice = ProductBuilder::sample('unitPrice');
        $cups = ProductBuilder::new()->withLabel($label->value)->withUnitPriceInCents($unitPrice->cents)->create();
        $this->store($cups);

        // Then
        $this->expectException(OutdatedOrderException::class);

        // When
        $this->dispatch(new PlaceOrder(
            OrderId::generate()->toString(),
            $buyer->id->toString(),
            [['productId' => $cups->id->toString(), 'quantity' => 1, 'label' => $label->value, 'unitPriceInCents' => $unitPrice->cents - 250]],
        ));
    }

    /**
     * @return list<array{productId: string, quantity: int, label: string, unitPriceInCents: int}>
     */
    private function lines(): array
    {
        $cupsLabel = ProductBuilder::sample('label');
        $cupsUnitPrice = ProductBuilder::sample('unitPrice');
        $cups = ProductBuilder::new()->withLabel($cupsLabel->value)->withUnitPriceInCents($cupsUnitPrice->cents)->create();

        $saucerLabel = ProductBuilder::sample('label');
        $saucerUnitPrice = ProductBuilder::sample('unitPrice');
        $saucer = ProductBuilder::new()->withLabel($saucerLabel->value)->withUnitPriceInCents($saucerUnitPrice->cents)->create();

        $this->store($cups, $saucer);

        return [
            ['productId' => $cups->id->toString(), 'quantity' => 1, 'label' => $cupsLabel->value, 'unitPriceInCents' => $cupsUnitPrice->cents],
            ['productId' => $saucer->id->toString(), 'quantity' => 3, 'label' => $saucerLabel->value, 'unitPriceInCents' => $saucerUnitPrice->cents],
        ];
    }

    /**
     * @param list<array{productId: string, quantity: int, label: string, unitPriceInCents: int}> $lines
     */
    private function totalAmountInCents(array $lines): int
    {
        return array_sum(array_map(static fn (array $line): int => $line['quantity'] * $line['unitPriceInCents'], $lines));
    }

    private function registeredBuyer(string $email): Buyer
    {
        $buyer = BuyerBuilder::new()
            ->withEmail($email)
            ->shippingAddressRegistered($this->shippingAddress())
            ->create();
        $payer = PayerBuilder::new()
            ->withId($buyer->id->toString())
            ->addressRegistered($this->billingAddress())
            ->create();
        $this->store($buyer, $payer);

        return $buyer;
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
