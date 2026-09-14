<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Application\IntegrationEvent\CheckoutSessionCompleted;

use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Mapper\PostalAddressMapper;
use Shopping\Checkout\Application\IntegrationEvent\CheckoutSessionCompleted\CheckoutSessionCompletedIntegrationEvent;
use Shopping\Checkout\Application\Mapper\CheckoutItemMapper;
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
        self::assertSame(
            array_map(
                static fn (CheckoutItem $item): array => [
                    ...CheckoutItemMapper::toArray($item),
                    'taxAmountInCents' => $item->taxedTotal()->taxAmount->cents,
                ],
                $builder['items'],
            ),
            $event->items,
        );
        self::assertSame($builder['currency']->value, $event->currency);
        self::assertSame(PostalAddressMapper::toArray($builder['shippingAddress']), $event->shippingAddress);
        self::assertSame(PostalAddressMapper::toArray($builder['billingAddress']), $event->billingAddress);
        self::assertSame($builder['paymentId'], $event->paymentId);
        self::assertSame(
            $builder['completedAt']->format(\DateTimeInterface::ATOM),
            $event->completedAt->format(\DateTimeInterface::ATOM),
        );
    }
}
