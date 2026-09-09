<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Application\Command\PlaceOrder;

use Catalog\Listing\Domain\ValueObject\ProductId;
use Catalog\Tests\Listing\Support\Builder\ProductBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Ordering\Application\Command\PlaceOrder\Exception\BuyerAddressesNotCompletedException;
use Sales\Ordering\Application\Command\PlaceOrder\Exception\BuyerErasureRequestedException;
use Sales\Ordering\Application\Command\PlaceOrder\Exception\BuyerNotRegisteredException;
use Sales\Ordering\Application\Command\PlaceOrder\Exception\OutdatedOrderException;
use Sales\Ordering\Application\Command\PlaceOrder\PlaceOrder;
use Sales\Ordering\Application\Finder\Order\OrderFinderInterface;
use Sales\Ordering\Application\OrderStatus;
use Sales\Ordering\Domain\Order;
use Sales\Ordering\Domain\Repository\OrderRepositoryInterface;
use Sales\Ordering\Domain\ValueObject\OrderId;
use Sales\Tests\Buyer\Support\Builder\BuyerBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class PlaceOrderHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPlaces(): void
    {
        // Given
        $buyerBuilder = BuyerBuilder::new()->shippingAddressDefined()->billingAddressDefined();
        $buyer = $buyerBuilder->create();
        $this->store($buyer);
        $id = Uuid::uuid7()->toString();
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
        self::assertSame($buyerBuilder['shippingAddress']->toArray(), $order->shippingAddress->toArray());
        self::assertSame($buyerBuilder['billingAddress']->toArray(), $order->billingAddress->toArray());
    }

    #[Test]
    public function itFailsWhenBuyerNotRegistered(): void
    {
        // Given
        $buyerId = Uuid::uuid7()->toString();

        // Then
        $this->expectException(BuyerNotRegisteredException::class);

        // When
        $this->dispatch(new PlaceOrder(Uuid::uuid7()->toString(), $buyerId, $this->lines()));
    }

    #[Test]
    public function itFailsWhenBuyerErased(): void
    {
        // Given
        $buyer = BuyerBuilder::new()->erasureRequested()->erased()->create();
        $this->store($buyer);

        // Then
        $this->expectException(BuyerNotRegisteredException::class);

        // When
        $this->dispatch(new PlaceOrder(Uuid::uuid7()->toString(), $buyer->id->toString(), $this->lines()));
    }

    #[Test]
    public function itFailsWhenBuyerErasureRequested(): void
    {
        // Given
        $buyer = BuyerBuilder::new()->erasureRequested()->create();
        $this->store($buyer);

        // Then
        $this->expectException(BuyerErasureRequestedException::class);

        // When
        $this->dispatch(new PlaceOrder(Uuid::uuid7()->toString(), $buyer->id->toString(), $this->lines()));
    }

    #[Test]
    #[DataProvider('provideIncompleteAddresses')]
    public function itFailsWhenBuyerAddressesNotCompleted(bool $withShippingAddress, bool $withBillingAddress): void
    {
        // Given
        $buyerBuilder = BuyerBuilder::new();
        if ($withShippingAddress) {
            $buyerBuilder = $buyerBuilder->shippingAddressDefined();
        }
        if ($withBillingAddress) {
            $buyerBuilder = $buyerBuilder->billingAddressDefined();
        }
        $buyer = $buyerBuilder->create();

        $this->store($buyer);

        // Then
        $this->expectException(BuyerAddressesNotCompletedException::class);

        // When
        $this->dispatch(new PlaceOrder(Uuid::uuid7()->toString(), $buyer->id->toString(), $this->lines()));
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
        $buyer = BuyerBuilder::new()->shippingAddressDefined()->billingAddressDefined()->create();
        $this->store($buyer);

        // Then
        $this->expectException(OutdatedOrderException::class);

        // When
        $this->dispatch(new PlaceOrder(
            Uuid::uuid7()->toString(),
            $buyer->id->toString(),
            [['productId' => Uuid::uuid7()->toString(), 'quantity' => 1, 'label' => ProductBuilder::sample('label')->value, 'unitPriceInCents' => ProductBuilder::sample('unitPrice')->cents]],
        ));
    }

    #[Test]
    public function itFailsWhenProductChanged(): void
    {
        // Given
        $buyer = BuyerBuilder::new()->shippingAddressDefined()->billingAddressDefined()->create();
        $this->store($buyer);
        $label = ProductBuilder::sample('label');
        $unitPrice = ProductBuilder::sample('unitPrice');
        $cups = ProductBuilder::new()->withLabel($label->value)->withUnitPriceInCents($unitPrice->cents)->create();
        $this->store($cups);

        // Then
        $this->expectException(OutdatedOrderException::class);

        // When
        $this->dispatch(new PlaceOrder(
            Uuid::uuid7()->toString(),
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

    private function orderOf(string $id): Order
    {
        return $this->service(OrderRepositoryInterface::class)->load(OrderId::fromString($id));
    }
}
