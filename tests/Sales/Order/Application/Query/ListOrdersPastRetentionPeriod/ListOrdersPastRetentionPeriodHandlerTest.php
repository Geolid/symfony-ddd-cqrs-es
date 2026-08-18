<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Query\ListOrdersPastRetentionPeriod;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Query\ListOrdersPastRetentionPeriod\ListOrdersPastRetentionPeriod;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;
use Symfony\Component\Clock\MockClock;

final class ListOrdersPastRetentionPeriodHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itListsOrdersPastTheRetentionPeriod(): void
    {
        // Given
        self::getContainer()->set('clock', new MockClock('2036-01-01T00:00:00+00:00'));
        $expired = OrderTestFactory::new()->cancelled(new \DateTimeImmutable('2010-01-01T00:00:00+00:00'))->store();
        OrderTestFactory::new()->cancelled(new \DateTimeImmutable('2035-01-01T00:00:00+00:00'))->store();
        OrderTestFactory::new()->store();

        // When
        $results = iterator_to_array($this->ask(new ListOrdersPastRetentionPeriod()), false);

        // Then
        self::assertCount(1, $results);
        self::assertSame($expired->id()->toString(), $results[0]->id);
    }
}
