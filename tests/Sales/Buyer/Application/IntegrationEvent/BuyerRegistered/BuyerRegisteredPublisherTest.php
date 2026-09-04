<?php

declare(strict_types=1);

namespace Sales\Tests\Buyer\Application\IntegrationEvent\BuyerRegistered;

use PHPUnit\Framework\Attributes\Test;
use Sales\Buyer\Application\IntegrationEvent\BuyerRegistered\BuyerRegisteredIntegrationEvent;
use Sales\Tests\Buyer\Support\Builder\BuyerBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class BuyerRegisteredPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $builder = BuyerBuilder::new();
        $buyer = $builder->create();

        // When
        $this->store($buyer);

        // Then
        $event = $this->publishedEventOf(BuyerRegisteredIntegrationEvent::class);
        self::assertSame($buyer->id->toString(), $event->buyerId);
        self::assertSame($builder['email']->value, $event->email);
        self::assertSame($builder['registeredAt']->format(\DateTimeInterface::ATOM), $event->registeredAt->format(\DateTimeInterface::ATOM));
    }
}
