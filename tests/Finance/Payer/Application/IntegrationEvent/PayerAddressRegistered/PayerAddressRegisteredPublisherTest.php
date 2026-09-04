<?php

declare(strict_types=1);

namespace Finance\Tests\Payer\Application\IntegrationEvent\PayerAddressRegistered;

use Finance\Payer\Application\IntegrationEvent\PayerAddressRegistered\PayerAddressRegisteredIntegrationEvent;
use Finance\Tests\Payer\Support\Builder\PayerBuilder;
use PHPUnit\Framework\Attributes\Test;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\PostalAddress;
use Support\TestCase\AbstractIntegrationTestCase;

final class PayerAddressRegisteredPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $address = PostalAddress::of('Ada Lovelace', Address::of('12 rue des Lilas', '75001', 'Paris', 'FR'));
        $payer = PayerBuilder::new()
            ->addressRegistered($address)
            ->create();

        // When
        $this->store($payer);

        // Then
        $event = $this->publishedEventOf(PayerAddressRegisteredIntegrationEvent::class);
        self::assertSame($payer->id->toString(), $event->payerId);
        self::assertSame($address->toArray(), $event->address);
    }
}
