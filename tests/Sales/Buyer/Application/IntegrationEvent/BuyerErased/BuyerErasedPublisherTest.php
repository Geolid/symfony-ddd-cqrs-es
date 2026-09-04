<?php

declare(strict_types=1);

namespace Sales\Tests\Buyer\Application\IntegrationEvent\BuyerErased;

use PHPUnit\Framework\Attributes\Test;
use Sales\Buyer\Application\IntegrationEvent\BuyerErased\BuyerErasedIntegrationEvent;
use Sales\Tests\Buyer\Support\Builder\BuyerBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class BuyerErasedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $builder = BuyerBuilder::new()->erased();
        $buyer = $builder->create();

        // When
        $this->store($buyer);

        // Then
        $event = $this->publishedEventOf(BuyerErasedIntegrationEvent::class);
        self::assertSame($buyer->id->toString(), $event->buyerId);
        self::assertSame($builder['erasedAt']->format(\DateTimeInterface::ATOM), $event->erasedAt->format(\DateTimeInterface::ATOM));
    }
}
