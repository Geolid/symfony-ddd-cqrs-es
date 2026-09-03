<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Query\ListOrdersPastRetentionPeriod;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Query\ListOrdersPastRetentionPeriod\ListOrdersPastRetentionPeriod;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class ListOrdersPastRetentionPeriodHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itListsPastRetentionPeriod(): void
    {
        // Given
        $now = Clock::get()->now();
        $expired = OrderBuilder::new()->cancelled($now->modify('-11 years'))->create();
        $withinRetention = OrderBuilder::new()->cancelled($now->modify('-1 year'))->create();
        $notClosed = OrderBuilder::new()->create();
        $this->store($withinRetention, $notClosed, $expired);

        // When
        $results = iterator_to_array($this->ask(new ListOrdersPastRetentionPeriod()), false);

        // Then
        self::assertCount(1, $results);
        self::assertSame($expired->id->toString(), $results[0]->id);
    }
}
