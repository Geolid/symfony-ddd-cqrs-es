<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Application\Command\ConfirmOrder;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Ordering\Application\Command\ConfirmOrder\ConfirmOrder;
use Sales\Ordering\Application\Command\ConfirmOrder\Exception\ShopperAddressesNotCompletedException;
use Sales\Ordering\Application\Command\ConfirmOrder\Exception\ShopperNotRegisteredException;
use Sales\Ordering\Application\Finder\Order\OrderFinderInterface;
use Sales\Ordering\Application\OrderStatus;
use Shared\Domain\ValueObject\Money;
use Shopping\Tests\Checkout\Support\Builder\ShopperBuilder;
use Support\SeededFaker;
use Support\TestCase\AbstractIntegrationTestCase;

final class ConfirmOrderHandlerTest extends AbstractIntegrationTestCase
{
    private OrderFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(OrderFinderInterface::class);
    }

    #[Test]
    public function itConfirms(): void
    {
        // Given
        $shopper = ShopperBuilder::new()->shippingAddressDefined()->billingAddressDefined()->create();
        $this->store($shopper);
        $id = Uuid::uuid7()->toString();
        $paymentId = Uuid::uuid7()->toString();
        $unitPriceInCents = SeededFaker::get()->numberBetween(500, 5_000);
        $quantity = SeededFaker::get()->numberBetween(1, 5);

        // When
        $this->dispatch(new ConfirmOrder(
            id: $id,
            shopperId: $shopper->id->toString(),
            paymentId: $paymentId,
            lines: [[
                'lineId' => Uuid::uuid7()->toString(),
                'productId' => Uuid::uuid7()->toString(),
                'label' => SeededFaker::get()->sentence(3),
                'unitPriceInCents' => $unitPriceInCents,
                'quantity' => $quantity,
            ]],
        ));

        // Then
        $result = $this->finder->ofId($id);
        self::assertSame($shopper->id->toString(), $result->shopperId);
        self::assertSame($paymentId, $result->paymentId);
        self::assertSame(Money::fromCents($unitPriceInCents * $quantity)->cents, $result->totalAmountInCents);
        self::assertSame(OrderStatus::CONFIRMED, $result->status);
    }

    #[Test]
    public function itFailsWhenShopperNotRegistered(): void
    {
        // Then
        $this->expectException(ShopperNotRegisteredException::class);

        // When
        $this->dispatch(new ConfirmOrder(
            id: Uuid::uuid7()->toString(),
            shopperId: Uuid::uuid7()->toString(),
            paymentId: Uuid::uuid7()->toString(),
            lines: [],
        ));
    }

    #[Test]
    public function itFailsWhenShopperAddressesNotCompleted(): void
    {
        // Given
        $shopper = ShopperBuilder::new()->create();
        $this->store($shopper);

        // Then
        $this->expectException(ShopperAddressesNotCompletedException::class);

        // When
        $this->dispatch(new ConfirmOrder(
            id: Uuid::uuid7()->toString(),
            shopperId: $shopper->id->toString(),
            paymentId: Uuid::uuid7()->toString(),
            lines: [],
        ));
    }

    #[Test]
    public function itFailsWhenShippingAddressMissing(): void
    {
        // Given
        $shopper = ShopperBuilder::new()->billingAddressDefined()->create();
        $this->store($shopper);

        // Then
        $this->expectException(ShopperAddressesNotCompletedException::class);

        // When
        $this->dispatch(new ConfirmOrder(
            id: Uuid::uuid7()->toString(),
            shopperId: $shopper->id->toString(),
            paymentId: Uuid::uuid7()->toString(),
            lines: [],
        ));
    }

    #[Test]
    public function itFailsWhenBillingAddressMissing(): void
    {
        // Given
        $shopper = ShopperBuilder::new()->shippingAddressDefined()->create();
        $this->store($shopper);

        // Then
        $this->expectException(ShopperAddressesNotCompletedException::class);

        // When
        $this->dispatch(new ConfirmOrder(
            id: Uuid::uuid7()->toString(),
            shopperId: $shopper->id->toString(),
            paymentId: Uuid::uuid7()->toString(),
            lines: [],
        ));
    }
}
