<?php

declare(strict_types=1);

namespace Compliance\Tests\Erasing\Application\Query\ListErasuresDueForApproval;

use Compliance\Erasing\Application\Query\ListErasuresDueForApproval\ListErasuresDueForApproval;
use Compliance\Tests\Erasing\Support\Factory\ErasureFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class ListErasuresDueForApprovalHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itLists(): void
    {
        // Given
        $now = Clock::get()->now();
        $dueRequestedAt = $now->modify(\sprintf('-%d days', ErasureFactory::RETENTION_DAYS + 1));
        $fresh = ErasureFactory::new()->withRequestedAt($now->modify('-1 day'))->create();
        $atEffectiveAt = ErasureFactory::new()->withRequestedAt($now->modify(\sprintf('-%d days', ErasureFactory::RETENTION_DAYS)))->create();
        $due = ErasureFactory::new()->withRequestedAt($dueRequestedAt)->create();
        $cancelled = ErasureFactory::new()->withRequestedAt($dueRequestedAt)->cancelled()->create();
        $this->store($fresh, $atEffectiveAt, $due, $cancelled);

        // When
        $results = iterator_to_array($this->ask(new ListErasuresDueForApproval()), false);

        // Then
        self::assertCount(1, $results);
        self::assertSame($due->id->toString(), $results[0]->id);
    }

    #[Test]
    public function itListsWhenEmpty(): void
    {
        // When
        $results = iterator_to_array($this->ask(new ListErasuresDueForApproval()), false);

        // Then
        self::assertEmpty($results);
    }
}
