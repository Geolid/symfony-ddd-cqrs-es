<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Application\IntegrationEvent\CheckoutSessionOpened;

use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Mapper\PostalAddressMapper;
use Shopping\Checkout\Application\IntegrationEvent\CheckoutSessionOpened\CheckoutSessionOpenedIntegrationEvent;
use Shopping\Tests\Checkout\Support\Builder\CheckoutSessionBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class CheckoutSessionOpenedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $builder = CheckoutSessionBuilder::new()->withLines([CheckoutSessionBuilder::sample('lines')[0]]);
        $checkoutSession = $builder->create();

        // When
        $this->store($checkoutSession);

        // Then
        $event = $this->publishedEventOf(CheckoutSessionOpenedIntegrationEvent::class);
        self::assertSame($checkoutSession->id->toString(), $event->checkoutSessionId);
        self::assertSame($builder['cartId'], $event->cartId);
        self::assertSame($builder['shopperId'], $event->shopperId);
        self::assertSame($builder['lines'], $event->lines);
        self::assertSame(PostalAddressMapper::toArray($builder['shippingAddress']), $event->shippingAddress);
        self::assertSame(PostalAddressMapper::toArray($builder['billingAddress']), $event->billingAddress);
        self::assertSame($builder['totalAmountInCents'], $event->totalAmountInCents);
        self::assertSame(
            $builder['openedAt']->format(\DateTimeInterface::ATOM),
            $event->openedAt->format(\DateTimeInterface::ATOM),
        );
    }
}
