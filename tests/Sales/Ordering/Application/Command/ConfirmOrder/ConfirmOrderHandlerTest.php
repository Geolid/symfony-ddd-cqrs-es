<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Application\Command\ConfirmOrder;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Ordering\Application\Command\ConfirmOrder\ConfirmOrder;
use Sales\Ordering\Application\Finder\Order\OrderFinderInterface;
use Sales\Ordering\Application\OrderStatus;
use Sales\Ordering\Domain\Order\Exception\OrderWithoutLineException;
use Shared\Application\Mapper\PostalAddressMapper;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\Money;
use Shared\Domain\ValueObject\PostalAddress;
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
        $id = Uuid::uuid7()->toString();
        $customerId = Uuid::uuid7()->toString();
        $checkoutSessionId = Uuid::uuid7()->toString();
        $unitPriceInCents = SeededFaker::get()->numberBetween(500, 5_000);
        $quantity = SeededFaker::get()->numberBetween(1, 5);
        $shippingAddress = PostalAddressMapper::toArray(PostalAddress::of('John Doe', Address::of('1 rue de Paris', '75001', 'Paris', 'FR')));

        // When
        $this->dispatch(new ConfirmOrder(
            id: $id,
            cartId: Uuid::uuid7()->toString(),
            customerId: $customerId,
            checkoutSessionId: $checkoutSessionId,
            lines: [[
                'productId' => Uuid::uuid7()->toString(),
                'label' => SeededFaker::get()->sentence(3),
                'unitPriceInCents' => $unitPriceInCents,
                'quantity' => $quantity,
            ]],
            shippingAddress: $shippingAddress,
        ));

        // Then
        $result = $this->finder->ofId($id);
        self::assertSame($customerId, $result->customerId);
        self::assertSame($checkoutSessionId, $result->checkoutSessionId);
        self::assertSame(
            $shippingAddress,
            ['recipientName' => $result->shippingAddress->recipientName, 'address' => (array) $result->shippingAddress->address],
        );
        self::assertSame(Money::fromCents($unitPriceInCents * $quantity)->cents, $result->totalAmountInCents);
        self::assertSame(OrderStatus::CONFIRMED, $result->status);
    }

    #[Test]
    public function itFailsWhenWithoutLine(): void
    {
        // Then
        $this->expectException(OrderWithoutLineException::class);

        // When
        $this->dispatch(new ConfirmOrder(
            id: Uuid::uuid7()->toString(),
            cartId: Uuid::uuid7()->toString(),
            customerId: Uuid::uuid7()->toString(),
            checkoutSessionId: Uuid::uuid7()->toString(),
            lines: [],
            shippingAddress: PostalAddressMapper::toArray(PostalAddress::of('John Doe', Address::of('1 rue de Paris', '75001', 'Paris', 'FR'))),
        ));
    }
}
