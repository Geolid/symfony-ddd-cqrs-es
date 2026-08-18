<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Query\ListOrdersPastReturnWindow;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Query\ListOrdersPastReturnWindow\ListOrdersPastReturnWindow;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;
use Symfony\Component\Clock\MockClock;

final class ListOrdersPastReturnWindowHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itListsPastReturnWindow(): void
    {
        // Given
        self::getContainer()->set('clock', new MockClock('2026-02-01T00:00:00+00:00'));
        $expired = OrderTestFactory::new()->confirmed()->dispatched()
            ->delivered(new \DateTimeImmutable('2026-01-01T00:00:00+00:00'))
            ->store();
        OrderTestFactory::new()->confirmed()->dispatched()
            ->delivered(new \DateTimeImmutable('2026-01-30T00:00:00+00:00'))
            ->store();

        // When
        $results = iterator_to_array($this->ask(new ListOrdersPastReturnWindow()), false);

        // Then
        self::assertCount(1, $results);
        self::assertSame($expired->id->toString(), $results[0]->id);
    }
}
