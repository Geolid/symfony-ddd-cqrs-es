<?php

declare(strict_types=1);

namespace Finance\Tests\Payer\Application\IntegrationEvent\PayerRegistered;

use Finance\Payer\Application\IntegrationEvent\PayerRegistered\PayerRegisteredIntegrationEvent;
use Finance\Tests\Payer\Support\Builder\PayerBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class PayerRegisteredPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $builder = PayerBuilder::new();
        $payer = $builder->create();

        // When
        $this->store($payer);

        // Then
        $event = $this->publishedEventOf(PayerRegisteredIntegrationEvent::class);
        self::assertSame($payer->id->toString(), $event->payerId);
        self::assertSame($builder['registeredAt']->format(\DateTimeInterface::ATOM), $event->registeredAt->format(\DateTimeInterface::ATOM));
    }
}
