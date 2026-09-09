<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Application\Command\ConfirmOrder;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Ordering\Application\Command\ConfirmOrder\ConfirmOrder;
use Sales\Ordering\Application\Command\ConfirmOrder\Exception\BuyerAddressesNotCompletedException;
use Sales\Ordering\Application\Command\ConfirmOrder\Exception\BuyerNotRegisteredException;
use Sales\Ordering\Application\Finder\Order\OrderFinderInterface;
use Sales\Ordering\Application\OrderStatus;
use Sales\Tests\Buyer\Support\Builder\BuyerBuilder;
use Shared\Domain\ValueObject\Money;
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
        $buyer = BuyerBuilder::new()->shippingAddressDefined()->billingAddressDefined()->create();
        $this->store($buyer);
        $id = Uuid::uuid7()->toString();
        $paymentId = Uuid::uuid7()->toString();
        $unitPriceInCents = SeededFaker::get()->numberBetween(500, 5_000);
        $quantity = SeededFaker::get()->numberBetween(1, 5);

        // When
        $this->dispatch(new ConfirmOrder(
            id: $id,
            buyerId: $buyer->id->toString(),
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
        self::assertSame($buyer->id->toString(), $result->buyerId);
        self::assertSame($paymentId, $result->paymentId);
        self::assertSame(Money::fromCents($unitPriceInCents * $quantity)->cents, $result->totalAmountInCents);
        self::assertSame(OrderStatus::CONFIRMED, $result->status);
    }

    #[Test]
    public function itFailsWhenBuyerNotRegistered(): void
    {
        // Then
        $this->expectException(BuyerNotRegisteredException::class);

        // When
        $this->dispatch(new ConfirmOrder(
            id: Uuid::uuid7()->toString(),
            buyerId: Uuid::uuid7()->toString(),
            paymentId: Uuid::uuid7()->toString(),
            lines: [],
        ));
    }

    #[Test]
    public function itFailsWhenBuyerAddressesNotCompleted(): void
    {
        // Given
        $buyer = BuyerBuilder::new()->create();
        $this->store($buyer);

        // Then
        $this->expectException(BuyerAddressesNotCompletedException::class);

        // When
        $this->dispatch(new ConfirmOrder(
            id: Uuid::uuid7()->toString(),
            buyerId: $buyer->id->toString(),
            paymentId: Uuid::uuid7()->toString(),
            lines: [],
        ));
    }
}
