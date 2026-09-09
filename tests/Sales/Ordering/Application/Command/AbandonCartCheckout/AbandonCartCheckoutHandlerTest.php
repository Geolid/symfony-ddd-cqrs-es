<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Application\Command\AbandonCartCheckout;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Ordering\Application\Command\AbandonCartCheckout\AbandonCartCheckout;
use Sales\Ordering\Domain\Cart\Event\CartCheckoutAbandoned;
use Sales\Ordering\Domain\Cart\Exception\CartNotFoundException;
use Sales\Tests\Ordering\Support\Builder\CartBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class AbandonCartCheckoutHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itAbandons(): void
    {
        // Given
        $cart = CartBuilder::new()->lineAdded()->checkedOut()->create();
        $this->store($cart);

        // When
        $this->dispatch(new AbandonCartCheckout($cart->id->toString()));

        // Then
        $event = $this->publishedEventOf(CartCheckoutAbandoned::class);
        self::assertSame($cart->id->toString(), $event->id);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(CartNotFoundException::class);

        // When
        $this->dispatch(new AbandonCartCheckout(Uuid::uuid7()->toString()));
    }
}
