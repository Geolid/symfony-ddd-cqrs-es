<?php

declare(strict_types=1);

namespace Compliance\Tests\Erasing\Application\Query\ListErasuresDueForApproval;

use Compliance\Erasing\Application\Query\ListErasuresDueForApproval\ListErasuresDueForApproval;
use Compliance\Tests\Erasing\Support\Builder\ErasureBuilder;
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
        $fresh = ErasureBuilder::new()->withRequestedAt($now->modify('-1 day'))->create();
        $due = ErasureBuilder::new()->withRequestedAt($now->modify('-31 days'))->create();
        $cancelled = ErasureBuilder::new()->withRequestedAt($now->modify('-31 days'))->cancelled()->create();
        $this->store($fresh, $due, $cancelled);

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
