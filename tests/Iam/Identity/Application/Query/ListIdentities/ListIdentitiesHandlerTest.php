<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Query\ListIdentities;

use Iam\Identity\Application\Finder\Identity\IdentityResult;
use Iam\Identity\Application\IdentityStatus;
use Iam\Identity\Application\Query\ListIdentities\ListIdentities;
use Iam\Identity\Domain\Identity;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Finder\PaginationMetadata;
use Shared\Application\Query\Result\PaginatedResult;
use Shared\Tests\Support\PaginationTrait;
use Support\TestCase\AbstractIntegrationTestCase;

final class ListIdentitiesHandlerTest extends AbstractIntegrationTestCase
{
    /** @use PaginationTrait<PaginatedResult<IdentityResult>> */
    use PaginationTrait;

    #[Test]
    public function itPaginates(): void
    {
        // Given
        $suspendedBuilder = IdentityBuilder::new()->suspended();
        $activeBuilder = IdentityBuilder::new();
        $active = $activeBuilder->create();
        $suspended = $suspendedBuilder->create();
        $others = IdentityBuilder::new()->many(3)->create();
        $identities = [$active, $suspended, ...$others];
        $this->store(...$identities);

        // When
        $pages = $this->traversePages(
            expectedIds: array_map(static fn (Identity $identity): string => $identity->id->toString(), $identities),
            pageSize: 2,
            askPage: $this->askPage(...),
            idsOf: $this->idsOf(...),
            metadataOf: $this->metadataOf(...),
        );

        // Then
        [$activeResult, $suspendedResult] = $pages[1]->items;

        self::assertSame($active->id->toString(), $activeResult->id);
        self::assertSame(IdentityStatus::ACTIVE, $activeResult->status);
        self::assertNull($activeResult->reason);
        self::assertSame(
            $activeBuilder['registeredAt']->format(\DateTimeInterface::ATOM),
            $activeResult->registeredAt->format(\DateTimeInterface::ATOM),
        );
        self::assertNull($activeResult->suspendedAt);
        self::assertNull($activeResult->reactivatedAt);

        self::assertSame($suspended->id->toString(), $suspendedResult->id);
        self::assertSame(IdentityStatus::SUSPENDED, $suspendedResult->status);
        self::assertSame($suspendedBuilder['reason']->value, $suspendedResult->reason);
        self::assertSame(
            $suspendedBuilder['registeredAt']->format(\DateTimeInterface::ATOM),
            $suspendedResult->registeredAt->format(\DateTimeInterface::ATOM),
        );
        self::assertSame(
            $suspendedBuilder['suspendedAt']->format(\DateTimeInterface::ATOM),
            $suspendedResult->suspendedAt?->format(\DateTimeInterface::ATOM),
        );
        self::assertNull($suspendedResult->reactivatedAt);
    }

    #[Test]
    public function itPaginatesWhenEmpty(): void
    {
        // When
        $this->traverseEmptyPage(
            askPage: $this->askPage(...),
            idsOf: $this->idsOf(...),
            metadataOf: $this->metadataOf(...),
            itemsPerPage: 20,
        );
    }

    /**
     * @return PaginatedResult<IdentityResult>
     */
    private function askPage(int $page, int $itemsPerPage): PaginatedResult
    {
        return $this->ask(new ListIdentities($page, $itemsPerPage));
    }

    /**
     * @param PaginatedResult<IdentityResult> $result
     *
     * @return list<string>
     */
    private function idsOf(PaginatedResult $result): array
    {
        return array_map(static fn (IdentityResult $item): string => $item->id, $result->items);
    }

    /**
     * @param PaginatedResult<IdentityResult> $result
     */
    private function metadataOf(PaginatedResult $result): PaginationMetadata
    {
        return $result->pagination;
    }
}
