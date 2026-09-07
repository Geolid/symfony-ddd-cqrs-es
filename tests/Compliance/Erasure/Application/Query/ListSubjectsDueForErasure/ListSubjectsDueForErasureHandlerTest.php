<?php

declare(strict_types=1);

namespace Compliance\Tests\Erasure\Application\Query\ListSubjectsDueForErasure;

use Compliance\Erasure\Application\Query\ListSubjectsDueForErasure\ListSubjectsDueForErasure;
use Compliance\Tests\Erasure\Support\Builder\SubjectBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class ListSubjectsDueForErasureHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itLists(): void
    {
        // Given
        $now = Clock::get()->now();
        $fresh = SubjectBuilder::new()->withRequestedAt($now->modify('-1 day'))->create();
        $due = SubjectBuilder::new()->withRequestedAt($now->modify('-31 days'))->create();
        $cancelled = SubjectBuilder::new()->withRequestedAt($now->modify('-31 days'))->cancelled()->create();
        $this->store($fresh, $due, $cancelled);

        // When
        $results = iterator_to_array($this->ask(new ListSubjectsDueForErasure()), false);

        // Then
        self::assertCount(1, $results);
        self::assertSame($due->id->toString(), $results[0]->id);
    }

    #[Test]
    public function itListsWhenEmpty(): void
    {
        // When
        $results = iterator_to_array($this->ask(new ListSubjectsDueForErasure()), false);

        // Then
        self::assertEmpty($results);
    }
}
