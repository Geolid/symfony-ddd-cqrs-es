<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Application\Command\RemoveCartLine;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shopping\Checkout\Application\Command\RemoveCartLine\RemoveCartLine;
use Shopping\Checkout\Application\Finder\Cart\CartFinderInterface;
use Shopping\Checkout\Domain\Cart\Exception\CartNotFoundException;
use Shopping\Checkout\Domain\Cart\ValueObject\LineId;
use Shopping\Tests\Checkout\Support\Builder\CartBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class RemoveCartLineHandlerTest extends AbstractIntegrationTestCase
{
    private CartFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(CartFinderInterface::class);
    }

    #[Test]
    public function itRemoves(): void
    {
        // Given
        $builder = CartBuilder::new()->lineAdded();
        $cart = $builder->create();
        $this->store($cart);
        $lineId = LineId::forProduct($cart->id->toString(), $builder['product']->id);

        // When
        $this->dispatch(new RemoveCartLine($cart->id->toString(), $lineId->toString()));

        // Then
        $result = $this->finder->ofId($cart->id->toString());
        self::assertSame([], $result->lines);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(CartNotFoundException::class);

        // When
        $this->dispatch(new RemoveCartLine(Uuid::uuid7()->toString(), Uuid::uuid7()->toString()));
    }
}
