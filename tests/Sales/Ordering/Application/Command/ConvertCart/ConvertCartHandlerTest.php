<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Application\Command\ConvertCart;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Ordering\Application\Command\ConvertCart\ConvertCart;
use Sales\Ordering\Domain\Cart\Event\CartConverted;
use Sales\Ordering\Domain\Cart\Exception\CartNotFoundException;
use Sales\Ordering\Domain\Cart\ValueObject\CartUniqueKey;
use Sales\Tests\Ordering\Support\Builder\CartBuilder;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniqueValueRegistryInterface;
use Support\TestCase\AbstractIntegrationTestCase;

final class ConvertCartHandlerTest extends AbstractIntegrationTestCase
{
    private UniqueValueRegistryInterface $uniqueValues;

    protected function setUp(): void
    {
        parent::setUp();

        $this->uniqueValues = $this->service(UniqueValueRegistryInterface::class);
    }

    #[Test]
    public function itConverts(): void
    {
        // Given
        $cartBuilder = CartBuilder::new()->lineAdded()->checkedOut();
        $cart = $cartBuilder->create();
        $this->store($cart);
        $buyerKey = UniqueKey::for(CartUniqueKey::BUYER);
        $this->uniqueValues->reserve($buyerKey, $cartBuilder['buyerId'], $cart->id->toString());

        // When
        $this->dispatch(new ConvertCart($cart->id->toString()));

        // Then
        $event = $this->publishedEventOf(CartConverted::class);
        self::assertSame($cart->id->toString(), $event->id);
        self::assertFalse($this->uniqueValues->exists($buyerKey, $cartBuilder['buyerId']));
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(CartNotFoundException::class);

        // When
        $this->dispatch(new ConvertCart(Uuid::uuid7()->toString()));
    }
}
