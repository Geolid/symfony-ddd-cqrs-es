<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Domain\Service;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Sales\Order\Domain\Exception\OutdatedOrderLineException;
use Sales\Order\Domain\Service\OrderLineOffer;
use Sales\Order\Domain\ValueObject\Product;
use Shared\Domain\ValueObject\Label;
use Shared\Domain\ValueObject\Money;

final class OrderLineOfferTest extends TestCase
{
    private OrderLineOffer $orderLineOffer;

    protected function setUp(): void
    {
        $this->orderLineOffer = new OrderLineOffer();
    }

    #[Test]
    public function itAllowsAStillValidLine(): void
    {
        // Given
        $id = Uuid::uuid7()->toString();
        $claimed = Product::of($id, Label::fromString('Saucer'), Money::fromCents(83));
        $current = Product::of($id, Label::fromString('Saucer'), Money::fromCents(83));

        // When
        $this->orderLineOffer->ensureStillValid($claimed, $current); // @phpstan-ignore method.alreadyNarrowedType

        // Then
        $this->addToAssertionCount(1);
    }

    #[Test]
    public function itFailsWhenTheProductIsNoLongerAvailable(): void
    {
        // Given
        $claimed = Product::of(Uuid::uuid7()->toString(), Label::fromString('Saucer'), Money::fromCents(83));

        // Then
        $this->expectException(OutdatedOrderLineException::class);

        // When
        $this->orderLineOffer->ensureStillValid($claimed, null); // @phpstan-ignore method.impossibleType
    }

    #[Test]
    public function itFailsWhenThePriceHasChanged(): void
    {
        // Given
        $id = Uuid::uuid7()->toString();
        $claimed = Product::of($id, Label::fromString('Saucer'), Money::fromCents(83));
        $current = Product::of($id, Label::fromString('Saucer'), Money::fromCents(90));

        // Then
        $this->expectException(OutdatedOrderLineException::class);

        // When
        $this->orderLineOffer->ensureStillValid($claimed, $current); // @phpstan-ignore method.alreadyNarrowedType
    }
}
