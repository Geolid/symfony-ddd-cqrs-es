<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Application\Command\CompleteCheckoutSession;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Mapper\PostalAddressMapper;
use Shopping\Checkout\Application\CheckoutSessionStatus;
use Shopping\Checkout\Application\Command\CompleteCheckoutSession\CompleteCheckoutSession;
use Shopping\Checkout\Application\Finder\CheckoutSession\CheckoutSessionFinderInterface;
use Shopping\Checkout\Domain\CheckoutSession\Exception\CheckoutSessionNotFoundException;
use Shopping\Checkout\Domain\CheckoutSession\ValueObject\CheckoutItem;
use Shopping\Tests\Checkout\Support\Builder\CheckoutSessionBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class CompleteCheckoutSessionHandlerTest extends AbstractIntegrationTestCase
{
    private CheckoutSessionFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(CheckoutSessionFinderInterface::class);
    }

    #[Test]
    public function itCompletes(): void
    {
        // Given
        $checkoutSessionBuilder = CheckoutSessionBuilder::new();
        $checkoutSession = $checkoutSessionBuilder->create();
        $this->store($checkoutSession);

        // When
        $this->dispatch(new CompleteCheckoutSession(
            id: $checkoutSession->id->toString(),
            cartId: $checkoutSessionBuilder['cartId'],
            shopperId: $checkoutSessionBuilder['shopperId'],
            items: array_map($this->toArray(...), $checkoutSessionBuilder['items']),
            shippingAddress: PostalAddressMapper::toArray($checkoutSessionBuilder['shippingAddress']),
            billingAddress: PostalAddressMapper::toArray($checkoutSessionBuilder['billingAddress']),
            paymentId: $checkoutSessionBuilder['paymentId'],
        ));

        // Then
        $result = $this->finder->ofCartOrNull($checkoutSessionBuilder['cartId']);
        self::assertNotNull($result);
        self::assertSame(CheckoutSessionStatus::COMPLETED, $result->status);
    }

    #[Test]
    public function itIgnoresWhenAlreadyCompleted(): void
    {
        // Given
        $checkoutSession = CheckoutSessionBuilder::new()->completed()->create();
        $this->store($checkoutSession);

        // When
        $this->dispatch(new CompleteCheckoutSession(
            id: $checkoutSession->id->toString(),
            cartId: CheckoutSessionBuilder::sample('cartId'),
            shopperId: CheckoutSessionBuilder::sample('shopperId'),
            items: array_map($this->toArray(...), CheckoutSessionBuilder::sample('items')),
            shippingAddress: PostalAddressMapper::toArray(CheckoutSessionBuilder::sample('shippingAddress')),
            billingAddress: PostalAddressMapper::toArray(CheckoutSessionBuilder::sample('billingAddress')),
            paymentId: CheckoutSessionBuilder::sample('paymentId'),
        ));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(CheckoutSessionNotFoundException::class);

        // When
        $this->dispatch(new CompleteCheckoutSession(
            id: Uuid::uuid7()->toString(),
            cartId: CheckoutSessionBuilder::sample('cartId'),
            shopperId: CheckoutSessionBuilder::sample('shopperId'),
            items: array_map($this->toArray(...), CheckoutSessionBuilder::sample('items')),
            shippingAddress: PostalAddressMapper::toArray(CheckoutSessionBuilder::sample('shippingAddress')),
            billingAddress: PostalAddressMapper::toArray(CheckoutSessionBuilder::sample('billingAddress')),
            paymentId: CheckoutSessionBuilder::sample('paymentId'),
        ));
    }

    /**
     * @return array{productId: string, label: string, unitPriceInCents: int, quantity: int}
     */
    private function toArray(CheckoutItem $item): array
    {
        return [
            'productId' => $item->productId,
            'label' => $item->label->value,
            'unitPriceInCents' => $item->unitPrice->cents,
            'quantity' => $item->quantity->value,
        ];
    }
}
