<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Infrastructure\Projection\Finder;

use Iam\Identity\Application\Exception\IdentityResultNotFoundException;
use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Identity\Application\Finder\Identity\IdentityResult;
use Iam\Identity\Application\IdentityStatus;
use Iam\Identity\Domain\Identity;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Finder\PaginationMetadata;
use Shared\Application\Finder\PaginatorInterface;
use Shared\Tests\Support\PaginationTrait;
use Shared\Tests\Support\TestCase\AbstractIterableFinderTestCase;

/**
 * @extends AbstractIterableFinderTestCase<IdentityResult>
 */
final class DbalIdentityFinderTest extends AbstractIterableFinderTestCase
{
    /** @use PaginationTrait<PaginatorInterface<IdentityResult>> */
    use PaginationTrait;

    #[Test]
    public function itGetsById(): void
    {
        // Given
        $other = IdentityBuilder::new()->create();
        $builder = IdentityBuilder::new();
        $identity = $builder->create();
        $this->store($other, $identity);

        // When
        $result = $this->finder()->ofId($identity->id->toString());

        // Then
        self::assertSame($identity->id->toString(), $result->id);
        self::assertSame(IdentityStatus::ACTIVE, $result->status);
        self::assertNull($result->reason);
        self::assertSame(
            $builder['registeredAt']->format(\DateTimeInterface::ATOM),
            $result->registeredAt->format(\DateTimeInterface::ATOM),
        );
        self::assertNull($result->suspendedAt);
        self::assertNull($result->reactivatedAt);
    }

    #[Test]
    public function itThrowsWhenIdNotFound(): void
    {
        // Then
        $this->expectException(IdentityResultNotFoundException::class);

        // When
        $this->finder()->ofId(IdentityId::generate()->toString());
    }

    #[Test]
    public function itPaginates(): void
    {
        // Given
        $finder = $this->finder();
        $ids = $this->seed(5);

        // When
        $this->traversePages(
            expectedIds: $ids,
            pageSize: 2,
            askPage: static fn (int $page, int $itemsPerPage): PaginatorInterface => $finder->paginate($page, $itemsPerPage),
            idsOf: $this->resultIds(...),
            metadataOf: PaginationMetadata::fromPaginator(...),
        );
    }

    #[Test]
    public function itPaginatesWhenEmpty(): void
    {
        // Given
        $finder = $this->finder();

        // When
        $this->traverseEmptyPage(
            askPage: static fn (int $page, int $itemsPerPage): PaginatorInterface => $finder->paginate($page, $itemsPerPage),
            idsOf: $this->resultIds(...),
            metadataOf: PaginationMetadata::fromPaginator(...),
            itemsPerPage: 20,
        );
    }

    protected function finder(): IdentityFinderInterface
    {
        return $this->service(IdentityFinderInterface::class);
    }

    /**
     * @return list<string>
     */
    protected function seed(int $count): array
    {
        $identities = IdentityBuilder::new()->many($count)->create();
        $this->store(...$identities);

        return array_map(static fn (Identity $identity): string => $identity->id->toString(), $identities);
    }

    protected function idOf(object $result): string
    {
        return $result->id;
    }
}
