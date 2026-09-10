<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Application\Command\ConvertCart;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Ordering\Application\Command\ConvertCart\ConvertCart;
use Sales\Ordering\Application\Uniqueness\CartUniqueKey;
use Sales\Ordering\Domain\Cart\Event\CartConverted;
use Sales\Ordering\Domain\Cart\Exception\CartNotFoundException;
use Sales\Tests\Ordering\Support\Builder\CartBuilder;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniquenessRegistryInterface;
use Support\TestCase\AbstractIntegrationTestCase;

final class ConvertCartHandlerTest extends AbstractIntegrationTestCase
{
    private UniquenessRegistryInterface $uniqueValues;

    protected function setUp(): void
    {
        parent::setUp();

        $this->uniqueValues = $this->service(UniquenessRegistryInterface::class);
    }

    #[Test]
    public function itConverts(): void
    {
        // Given
        $cartBuilder = CartBuilder::new()->lineAdded()->checkedOut();
        $cart = $cartBuilder->create();
        $this->store($cart);
        $buyerKey = UniqueKey::for(CartUniqueKey::BUYER);
        $this->uniqueValues->claim($buyerKey, $cartBuilder['buyerId'], $cart->id->toString());

        // When
        $this->dispatch(new ConvertCart($cart->id->toString()));

        // Then
        $event = $this->publishedEventOf(CartConverted::class);
        self::assertSame($cart->id->toString(), $event->id);
        self::assertFalse($this->uniqueValues->isClaimed($buyerKey, $cartBuilder['buyerId']));
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
