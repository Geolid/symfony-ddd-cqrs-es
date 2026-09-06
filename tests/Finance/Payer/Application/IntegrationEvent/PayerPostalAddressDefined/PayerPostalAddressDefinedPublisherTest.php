<?php

declare(strict_types=1);

namespace Finance\Tests\Payer\Application\IntegrationEvent\PayerPostalAddressDefined;

use Finance\Payer\Application\IntegrationEvent\PayerPostalAddressDefined\PayerPostalAddressDefinedIntegrationEvent;
use Finance\Tests\Payer\Support\Builder\PayerBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class PayerPostalAddressDefinedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $builder = PayerBuilder::new()->postalAddressDefined();
        $payer = $builder->create();

        // When
        $this->store($payer);

        // Then
        $event = $this->publishedEventOf(PayerPostalAddressDefinedIntegrationEvent::class);
        self::assertSame($payer->id->toString(), $event->payerId);
        self::assertSame($builder['postalAddress']->toArray(), $event->postalAddress);
        self::assertSame($builder['postalAddressDefinedAt']->format(\DateTimeInterface::ATOM), $event->definedAt->format(\DateTimeInterface::ATOM));
    }
}
