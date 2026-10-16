<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Application\Query\ListCheckoutSessionsDueForExpiry;

use PHPUnit\Framework\Attributes\Test;
use Shopping\Checkout\Application\Query\ListCheckoutSessionsDueForExpiry\ListCheckoutSessionsDueForExpiry;
use Shopping\Tests\Checkout\Support\Factory\CheckoutSessionFactory;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class ListCheckoutSessionsDueForExpiryHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itLists(): void
    {
        // Given
        $now = Clock::get()->now();
        $expiry = '30 minutes';
        $expiredOpenedAt = $now->modify(\sprintf('-%s -1 second', $expiry));
        $expired = CheckoutSessionFactory::new()
            ->withExpiry($expiry)
            ->withOpenedAt($expiredOpenedAt)
            ->create();
        $fresh = CheckoutSessionFactory::new()
            ->withExpiry($expiry)
            ->withOpenedAt($now->modify(\sprintf('-%s +1 second', $expiry)))
            ->create();
        $completed = CheckoutSessionFactory::new()
            ->withExpiry($expiry)
            ->withOpenedAt($expiredOpenedAt)
            ->completed()
            ->create();
        $this->store($fresh, $completed, $expired);

        // When
        $results = iterator_to_array($this->ask(new ListCheckoutSessionsDueForExpiry()), false);

        // Then
        self::assertCount(1, $results);
        self::assertSame($expired->id->toString(), $results[0]->id);
    }

    #[Test]
    public function itListsWhenEmpty(): void
    {
        // When
        $results = iterator_to_array($this->ask(new ListCheckoutSessionsDueForExpiry()), false);

        // Then
        self::assertEmpty($results);
    }
}
