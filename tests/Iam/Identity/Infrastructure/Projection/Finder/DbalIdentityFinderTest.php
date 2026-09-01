<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Infrastructure\Projection\Finder;

use Iam\Identity\Application\Exception\IdentityResultNotFoundException;
use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Identity\Application\Finder\Identity\IdentityResult;
use Iam\Identity\Application\IdentityStatus;
use Iam\Identity\Domain\Identity;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class DbalIdentityFinderTest extends AbstractIntegrationTestCase
{
    private IdentityFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(IdentityFinderInterface::class);
    }

    #[Test]
    public function itGetsById(): void
    {
        // Given
        $other = IdentityTestFactory::new()->create();
        $identity = IdentityTestFactory::new()->create();
        $this->store($other, $identity);

        // When
        $result = $this->finder->ofId($identity->id->toString());

        // Then
        self::assertSame($identity->id->toString(), $result->id);
        self::assertSame(IdentityStatus::ACTIVE, $result->status);
        self::assertNull($result->reason);
        self::assertNull($result->suspendedAt);
        self::assertNull($result->reactivatedAt);
    }

    #[Test]
    public function itThrowsWhenIdNotFound(): void
    {
        // Then
        $this->expectException(IdentityResultNotFoundException::class);

        // When
        $this->finder->ofId(IdentityTestFactory::sample('id')->toString());
    }

    #[Test]
    public function itLists(): void
    {
        // Given
        $identities = IdentityTestFactory::new()->many(5)->create();
        $this->store(...$identities);

        // When
        $results = iterator_to_array($this->finder);

        // Then
        self::assertSame($this->ids(...$identities), $this->resultIds($results));
    }

    #[Test]
    public function itListsWhenEmpty(): void
    {
        // When
        $results = iterator_to_array($this->finder);

        // Then
        self::assertEmpty($results);
    }

    #[Test]
    public function itPaginates(): void
    {
        // Given
        $identities = IdentityTestFactory::new()->many(5)->create();
        $this->store(...$identities);

        // When
        $firstPage = $this->finder->paginate(page: 1, itemsPerPage: 2);
        $secondPage = $this->finder->paginate(page: 2, itemsPerPage: 2);
        $lastPage = $this->finder->paginate(page: 3, itemsPerPage: 2);
        $outOfBoundsPage = $this->finder->paginate(page: 4, itemsPerPage: 2);

        // Then
        self::assertSame($this->ids($identities[0], $identities[1]), $this->resultIds($firstPage));
        self::assertSame($this->ids($identities[2], $identities[3]), $this->resultIds($secondPage));
        self::assertSame($this->ids($identities[4]), $this->resultIds($lastPage));
        self::assertCount(0, $outOfBoundsPage);

        self::assertSame(5, $firstPage->totalItems());
        self::assertSame(3, $firstPage->lastPage());
        self::assertSame(1, $firstPage->currentPage());
        self::assertSame(2, $firstPage->itemsPerPage());
        self::assertSame(2, $secondPage->currentPage());
        self::assertSame(3, $lastPage->currentPage());
        self::assertSame(4, $outOfBoundsPage->currentPage());
    }

    #[Test]
    public function itPaginatesWhenEmpty(): void
    {
        // When
        $paginator = $this->finder->paginate(page: 1, itemsPerPage: 20);

        // Then
        self::assertCount(0, $paginator);
        self::assertSame(0, $paginator->totalItems());
        self::assertSame(1, $paginator->lastPage());
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

    /**
     * @param iterable<IdentityResult> $results
     *
     * @return list<string>
     */
    private function resultIds(iterable $results): array
    {
        $ids = [];
        foreach ($results as $result) {
            $ids[] = $result->id;
        }

        return $ids;
    }
}
