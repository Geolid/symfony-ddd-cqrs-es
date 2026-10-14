<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Query\ListExpiredPendingIdentities;

use Iam\Identity\Application\Query\ListExpiredPendingIdentities\ListExpiredPendingIdentities;
use Iam\Tests\Identity\Support\Factory\IdentityFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class ListExpiredPendingIdentitiesHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itLists(): void
    {
        // Given
        $now = Clock::get()->now();
        $expiredRegisteredAt = $now->modify('-1 day');
        $fresh = IdentityFactory::new()->create();
        $active = IdentityFactory::new()->withRegisteredAt($expiredRegisteredAt)->confirmed()->create();
        $expired = IdentityFactory::new()->withRegisteredAt($expiredRegisteredAt)->create();
        $this->store($fresh, $active, $expired);

        // When
        $results = iterator_to_array($this->ask(new ListExpiredPendingIdentities()), false);

        // Then
        self::assertCount(1, $results);
        self::assertSame($expired->id->toString(), $results[0]->id);
    }

    #[Test]
    public function itListsWhenEmpty(): void
    {
        // When
        $results = iterator_to_array($this->ask(new ListExpiredPendingIdentities()), false);

        // Then
        self::assertEmpty($results);
    }
}
