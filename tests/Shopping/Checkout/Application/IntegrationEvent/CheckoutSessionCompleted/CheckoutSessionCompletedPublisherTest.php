<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Application\IntegrationEvent\CheckoutSessionCompleted;

use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Mapper\PostalAddressMapper;
use Shared\Domain\ValueObject\Money;
use Shopping\Checkout\Application\IntegrationEvent\CheckoutSessionCompleted\CheckoutSessionCompletedIntegrationEvent;
use Shopping\Checkout\Domain\ValueObject\CheckoutItem;
use Shopping\Tests\Checkout\Support\Builder\CheckoutSessionBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class CheckoutSessionCompletedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $builder = CheckoutSessionBuilder::new()->completed();
        $checkoutSession = $builder->create();

        // When
        $this->store($checkoutSession);

        // Then
        $event = $this->publishedEventOf(CheckoutSessionCompletedIntegrationEvent::class);
        self::assertSame($checkoutSession->id->toString(), $event->checkoutSessionId);
        self::assertSame($builder['cartId'], $event->cartId);
        self::assertSame($builder['customerId'], $event->customerId);
        self::assertSame(array_map($this->toArray(...), $builder['items']), $event->items);
        self::assertSame(PostalAddressMapper::toArray($builder['shippingAddress']), $event->shippingAddress);
        self::assertSame(PostalAddressMapper::toArray($builder['billingAddress']), $event->billingAddress);
        $totalAmountInCents = array_reduce(
            $builder['items'],
            static fn (Money $carry, CheckoutItem $item): Money => $carry->plus($item->subtotal()),
            Money::fromCents(0),
        )->cents;
        self::assertSame($totalAmountInCents, $event->totalAmountInCents);
        self::assertSame($builder['paymentId'], $event->paymentId);
        self::assertSame(
            $builder['completedAt']->format(\DateTimeInterface::ATOM),
            $event->completedAt->format(\DateTimeInterface::ATOM),
        );
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
