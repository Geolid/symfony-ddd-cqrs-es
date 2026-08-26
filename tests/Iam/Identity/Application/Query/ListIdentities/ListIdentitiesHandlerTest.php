<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Query\ListIdentities;

use Iam\Identity\Application\Query\ListIdentities\ListIdentities;
use Iam\Identity\Application\Status\IdentityStatus;
use Iam\Identity\Domain\Identity;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class ListIdentitiesHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itLists(): void
    {
        // Given
        $active = IdentityTestFactory::new()->create();
        $suspended = IdentityTestFactory::new()
            ->suspended('Suspected fraudulent activity', new \DateTimeImmutable('2026-01-02T00:00:00+00:00'))
            ->create();
        $others = IdentityTestFactory::new()->many(3)->create();
        $identities = [$active, $suspended, ...$others];
        $this->store(...$identities);

        // When
        $firstPage = $this->ask(new ListIdentities(page: 1, itemsPerPage: 2));
        $secondPage = $this->ask(new ListIdentities(page: 2, itemsPerPage: 2));
        $lastPage = $this->ask(new ListIdentities(page: 3, itemsPerPage: 2));
        $outOfBoundsPage = $this->ask(new ListIdentities(page: 4, itemsPerPage: 2));

        // Then
        [$activeResult, $suspendedResult] = $firstPage->items;

        self::assertSame($active->id->toString(), $activeResult->id);
        self::assertSame(IdentityStatus::ACTIVE, $activeResult->status);
        self::assertNull($activeResult->reason);
        self::assertNull($activeResult->suspendedAt);
        self::assertNull($activeResult->reactivatedAt);

        self::assertSame($suspended->id->toString(), $suspendedResult->id);
        self::assertSame(IdentityStatus::SUSPENDED, $suspendedResult->status);
        self::assertSame('Suspected fraudulent activity', $suspendedResult->reason);
        self::assertSame('2026-01-02T00:00:00+00:00', $suspendedResult->suspendedAt?->format('c'));
        self::assertNull($suspendedResult->reactivatedAt);

        self::assertSame($this->ids($active, $suspended), array_column($firstPage->items, 'id'));
        self::assertSame($this->ids($identities[2], $identities[3]), array_column($secondPage->items, 'id'));
        self::assertSame($this->ids($identities[4]), array_column($lastPage->items, 'id'));
        self::assertEmpty($outOfBoundsPage->items);

        self::assertSame(5, $firstPage->pagination->totalItems);
        self::assertSame(3, $firstPage->pagination->lastPage);
        self::assertSame(1, $firstPage->pagination->currentPage);
        self::assertSame(2, $secondPage->pagination->currentPage);
        self::assertSame(3, $lastPage->pagination->currentPage);
        self::assertSame(4, $outOfBoundsPage->pagination->currentPage);
    }

    #[Test]
    public function itListsWhenEmpty(): void
    {
        // When
        $result = $this->ask(new ListIdentities());

        // Then
        self::assertCount(0, $result->items);
        self::assertSame(0, $result->pagination->totalItems);
        self::assertSame(1, $result->pagination->currentPage);
        self::assertSame(20, $result->pagination->itemsPerPage);
        self::assertSame(1, $result->pagination->lastPage);
    }

    /**
     * @return list<string>
     */
    private function ids(Identity ...$identities): array
    {
        $ids = [];
        foreach ($identities as $identity) {
            $ids[] = $identity->id->toString();
        }

        return $ids;
    }
}
