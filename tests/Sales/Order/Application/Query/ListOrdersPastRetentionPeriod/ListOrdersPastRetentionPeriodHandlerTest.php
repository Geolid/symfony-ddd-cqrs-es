<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Query\ListOrdersPastRetentionPeriod;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Query\ListOrdersPastRetentionPeriod\ListOrdersPastRetentionPeriod;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class ListOrdersPastRetentionPeriodHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itListsPastRetentionPeriod(): void
    {
        // Given
        $now = Clock::get()->now();
        $expired = OrderTestFactory::new()->cancelled($now->modify('-11 years'))->create();
        $this->store(
            OrderTestFactory::new()->cancelled($now->modify('-1 year'))->create(),
            OrderTestFactory::new()->create(),
            $expired,
        );

        // When
        $results = iterator_to_array($this->ask(new ListOrdersPastRetentionPeriod()), false);

        // Then
        self::assertCount(1, $results);
        self::assertSame($expired->id->toString(), $results[0]->id);
    }
}
