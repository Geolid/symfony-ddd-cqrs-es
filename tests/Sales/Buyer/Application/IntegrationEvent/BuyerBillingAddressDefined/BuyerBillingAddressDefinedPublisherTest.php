<?php

declare(strict_types=1);

namespace Sales\Tests\Buyer\Application\IntegrationEvent\BuyerBillingAddressDefined;

use PHPUnit\Framework\Attributes\Test;
use Sales\Buyer\Application\IntegrationEvent\BuyerBillingAddressDefined\BuyerBillingAddressDefinedIntegrationEvent;
use Sales\Tests\Buyer\Support\Builder\BuyerBuilder;
use Shared\Application\Mapper\PostalAddressMapper;
use Support\TestCase\AbstractIntegrationTestCase;

final class BuyerBillingAddressDefinedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $builder = BuyerBuilder::new()->billingAddressDefined();
        $buyer = $builder->create();

        // When
        $this->store($buyer);

        // Then
        $event = $this->publishedEventOf(BuyerBillingAddressDefinedIntegrationEvent::class);
        self::assertSame($buyer->id->toString(), $event->buyerId);
        self::assertSame($builder['identityId'], $event->identityId);
        self::assertSame(PostalAddressMapper::toArray($builder['billingAddress']), $event->postalAddress);
        self::assertSame($builder['billingAddressDefinedAt']->format(\DateTimeInterface::ATOM), $event->definedAt->format(\DateTimeInterface::ATOM));
    }
}
