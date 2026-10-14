<?php

declare(strict_types=1);

namespace Compliance\Tests\Erasing\Application\Query\ListErasuresDueForApproval;

use Compliance\Erasing\Application\Query\ListErasuresDueForApproval\ListErasuresDueForApproval;
use Compliance\Tests\Erasing\Support\Factory\ErasureFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

use function Zenstruck\Foundry\faker;

final class ListErasuresDueForApprovalHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itLists(): void
    {
        // Given
        $now = Clock::get()->now();
        $retentionSeconds = faker()->numberBetween(60, 3600);
        $dueRequestedAt = $now->modify(\sprintf('-%d seconds', $retentionSeconds + 1));
        $fresh = ErasureFactory::new()->create();
        $atEffectiveAt = ErasureFactory::new()->withRetentionSeconds($retentionSeconds)->withRequestedAt($now->modify(\sprintf('-%d seconds', $retentionSeconds)))->create();
        $due = ErasureFactory::new()->withRetentionSeconds($retentionSeconds)->withRequestedAt($dueRequestedAt)->create();
        $cancelled = ErasureFactory::new()->withRetentionSeconds($retentionSeconds)->withRequestedAt($dueRequestedAt)->cancelled()->create();
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
