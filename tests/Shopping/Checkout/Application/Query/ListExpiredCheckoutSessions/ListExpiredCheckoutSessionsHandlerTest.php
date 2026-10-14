<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Application\Query\ListExpiredCheckoutSessions;

use PHPUnit\Framework\Attributes\Test;
use Shopping\Checkout\Application\Query\ListExpiredCheckoutSessions\ListExpiredCheckoutSessions;
use Shopping\Tests\Checkout\Support\Factory\CheckoutSessionFactory;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class ListExpiredCheckoutSessionsHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itLists(): void
    {
        // Given
        $now = Clock::get()->now();
        $expirySeconds = 30 * 60;
        $expiredOpenedAt = $now->modify(\sprintf('-%d seconds', $expirySeconds + 1));
        $expired = CheckoutSessionFactory::new()
            ->withExpirySeconds($expirySeconds)
            ->withOpenedAt($expiredOpenedAt)
            ->create();
        $fresh = CheckoutSessionFactory::new()
            ->withExpirySeconds($expirySeconds)
            ->withOpenedAt($now->modify(\sprintf('-%d seconds', $expirySeconds - 1)))
            ->create();
        $completed = CheckoutSessionFactory::new()
            ->withExpirySeconds($expirySeconds)
            ->withOpenedAt($expiredOpenedAt)
            ->completed()
            ->create();
        $this->store($fresh, $completed, $expired);

        // When
        $results = iterator_to_array($this->ask(new ListExpiredCheckoutSessions()), false);

        // Then
        self::assertCount(1, $results);
        self::assertSame($expired->id->toString(), $results[0]->id);
    }

    #[Test]
    public function itListsWhenEmpty(): void
    {
        // When
        $results = iterator_to_array($this->ask(new ListExpiredCheckoutSessions()), false);

        // Then
        self::assertEmpty($results);
    }
}
