<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Application\IntegrationEvent\CheckoutSessionCompleted;

use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Mapper\PostalAddressMapper;
use Shopping\Checkout\Application\IntegrationEvent\CheckoutSessionCompleted\CheckoutSessionCompletedIntegrationEvent;
use Shopping\Checkout\Application\Mapper\CheckoutItemMapper;
use Shopping\Checkout\Domain\ValueObject\CheckoutItem;
use Shopping\Tests\Checkout\Support\Factory\CheckoutSessionFactory;
use Support\TestCase\AbstractIntegrationTestCase;

final class CheckoutSessionCompletedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $checkoutSession = CheckoutSessionFactory::new()->completed()->create();

        // When
        $this->store($checkoutSession);

        // Then
        $event = $this->publishedEventOf(CheckoutSessionCompletedIntegrationEvent::class);
        self::assertSame($checkoutSession->id->toString(), $event->checkoutSessionId);
        self::assertSame($checkoutSession->cartId, $event->cartId);
        self::assertSame($checkoutSession->customerId, $event->customerId);
        self::assertSame(
            array_map(
                static fn (CheckoutItem $item): array => [
                    ...CheckoutItemMapper::toArray($item),
                    'taxAmountInCents' => $item->taxedTotal()->taxAmount->cents,
                ],
                $checkoutSession->items,
            ),
            $event->items,
        );
        self::assertSame($checkoutSession->total->excludingTax->currency->value, $event->currency);
        self::assertSame(PostalAddressMapper::toArray($checkoutSession->shippingAddress), $event->shippingAddress);
        self::assertSame(PostalAddressMapper::toArray($checkoutSession->billingAddress), $event->billingAddress);
        self::assertSame($checkoutSession->paymentId, $event->paymentId);
        self::assertSame(
            $checkoutSession->completedAt?->format(\DateTimeInterface::ATOM),
            $event->completedAt->format(\DateTimeInterface::ATOM),
        );
    }
}
