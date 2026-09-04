<?php

declare(strict_types=1);

namespace Finance\Tests\Payer\Application\IntegrationEvent\PayerErased;

use Finance\Payer\Application\IntegrationEvent\PayerErased\PayerErasedIntegrationEvent;
use Finance\Tests\Payer\Support\Builder\PayerBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class PayerErasedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $builder = PayerBuilder::new()->erased();
        $payer = $builder->create();

        // When
        $this->store($payer);

        // Then
        $event = $this->publishedEventOf(PayerErasedIntegrationEvent::class);
        self::assertSame($payer->id->toString(), $event->payerId);
        self::assertSame($builder['erasedAt']->format(\DateTimeInterface::ATOM), $event->erasedAt->format(\DateTimeInterface::ATOM));
    }
}
