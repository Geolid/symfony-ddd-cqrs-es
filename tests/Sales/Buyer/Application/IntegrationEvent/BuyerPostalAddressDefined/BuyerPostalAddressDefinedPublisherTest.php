<?php

declare(strict_types=1);

namespace Sales\Tests\Buyer\Application\IntegrationEvent\BuyerPostalAddressDefined;

use PHPUnit\Framework\Attributes\Test;
use Sales\Buyer\Application\IntegrationEvent\BuyerPostalAddressDefined\BuyerPostalAddressDefinedIntegrationEvent;
use Sales\Tests\Buyer\Support\Builder\BuyerBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class BuyerPostalAddressDefinedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $builder = BuyerBuilder::new()->postalAddressDefined();
        $buyer = $builder->create();

        // When
        $this->store($buyer);

        // Then
        $event = $this->publishedEventOf(BuyerPostalAddressDefinedIntegrationEvent::class);
        self::assertSame($buyer->id->toString(), $event->buyerId);
        self::assertSame($builder['postalAddress']->toArray(), $event->postalAddress);
        self::assertSame($builder['postalAddressDefinedAt']->format(\DateTimeInterface::ATOM), $event->definedAt->format(\DateTimeInterface::ATOM));
    }
}
