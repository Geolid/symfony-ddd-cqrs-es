<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Application\Command\ConfirmOrder;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Ordering\Application\Command\ConfirmOrder\ConfirmOrder;
use Sales\Ordering\Application\Finder\Order\OrderFinderInterface;
use Sales\Ordering\Application\Mapper\OrderItemMapper;
use Sales\Ordering\Application\OrderStatus;
use Sales\Ordering\Domain\Order\Exception\OrderWithoutLineException;
use Sales\Tests\Ordering\Support\Factory\OrderIdFactory;
use Sales\Tests\Ordering\Support\Factory\OrderItemFactory;
use Sales\Tests\Ordering\Support\PostalAddressResultMapper;
use Shared\Application\Mapper\PostalAddressMapper;
use Shared\Domain\ValueObject\Currency;
use Shared\Tests\Support\Factory\PostalAddressFactory;
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
        $id = OrderIdFactory::new()->create()->toString();
        $customerId = Uuid::uuid7()->toString();
        $checkoutSessionId = Uuid::uuid7()->toString();
        $item = OrderItemFactory::new()->create();
        $currency = Currency::EUR;
        $shippingAddress = PostalAddressMapper::toArray(PostalAddressFactory::new()->create());

        // When
        $this->dispatch(new ConfirmOrder(
            id: $id,
            cartId: Uuid::uuid7()->toString(),
            customerId: $customerId,
            checkoutSessionId: $checkoutSessionId,
            lines: [OrderItemMapper::toArray($item)],
            currency: $currency->value,
            shippingAddress: $shippingAddress,
        ));

        // Then
        $result = $this->finder->ofId($id);
        self::assertSame($customerId, $result->customerId);
        self::assertSame($checkoutSessionId, $result->checkoutSessionId);
        self::assertSame(
            $shippingAddress,
            PostalAddressResultMapper::toArray($result->shippingAddress),
        );
        self::assertSame($item->taxedTotal()->excludingTax->cents, $result->totalExcludingTaxInCents);
        self::assertSame($item->taxedTotal()->taxAmount->cents, $result->totalTaxAmountInCents);
        self::assertSame($item->taxedTotal()->includingTax->cents, $result->totalIncludingTaxInCents);
        self::assertSame($currency->value, $result->currency);
        self::assertSame(OrderStatus::CONFIRMED, $result->status);
    }

    #[Test]
    public function itFailsWhenWithoutLine(): void
    {
        // Given
        $id = OrderIdFactory::new()->create()->toString();
        $cartId = Uuid::uuid7()->toString();
        $customerId = Uuid::uuid7()->toString();
        $checkoutSessionId = Uuid::uuid7()->toString();
        $shippingAddress = PostalAddressMapper::toArray(PostalAddressFactory::new()->create());

        // Then
        $this->expectException(OrderWithoutLineException::class);

        // When
        $this->dispatch(new ConfirmOrder(
            id: $id,
            cartId: $cartId,
            customerId: $customerId,
            checkoutSessionId: $checkoutSessionId,
            lines: [],
            currency: 'EUR',
            shippingAddress: $shippingAddress,
        ));
    }
}
