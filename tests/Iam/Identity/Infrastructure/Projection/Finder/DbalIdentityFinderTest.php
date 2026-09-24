<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Infrastructure\Projection\Finder;

use Iam\Identity\Application\Finder\Identity\Exception\IdentityResultNotFoundException;
use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Identity\Application\Finder\Identity\IdentityResult;
use Iam\Identity\Application\IdentityModerationStatus;
use Iam\Identity\Application\IdentityVerificationStatus;
use Iam\Identity\Domain\Identity;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\ErasureStatus;
use Shared\Application\Finder\PaginationMetadata;
use Shared\Application\Finder\PaginatorInterface;
use Shared\Tests\Support\TestCase\AbstractPaginatableFinderTestCase;
use Shared\Tests\Support\TestCase\RealColumnLeadsTrait;
use Support\Faker\SeededFaker;
use Symfony\Component\Clock\Clock;

/**
 * @extends AbstractPaginatableFinderTestCase<IdentityResult>
 */
final class DbalIdentityFinderTest extends AbstractPaginatableFinderTestCase
{
    use RealColumnLeadsTrait;

    #[Test]
    public function itGetsById(): void
    {
        // Given
        $other = IdentityBuilder::new()->create();
        $builder = IdentityBuilder::new()->confirmed();
        $identity = $builder->create();
        $this->store($other, $identity);

        // When
        $result = $this->finder()->ofId($identity->id->toString());

        // Then
        self::assertSame($identity->id->toString(), $result->id);
        self::assertSame($builder['fullName']->value, $result->fullName);
        self::assertSame($builder['email']->value, $result->email);
        self::assertSame(IdentityVerificationStatus::CONFIRMED, $result->verificationStatus);
        self::assertSame(IdentityModerationStatus::ACTIVE, $result->moderationStatus);
        self::assertNull($result->reason);
        self::assertSame(
            $builder['registeredAt']->format(\DateTimeInterface::ATOM),
            $result->registeredAt->format(\DateTimeInterface::ATOM),
        );
        self::assertSame(
            $builder['registeredAt']->format(\DateTimeInterface::ATOM),
            $result->confirmationRequestedAt->format(\DateTimeInterface::ATOM),
        );
        self::assertNull($result->suspendedAt);
        self::assertNull($result->reactivatedAt);
        self::assertSame(ErasureStatus::RETAINED, $result->erasureStatus);
    }

    #[Test]
    public function itThrowsWhenIdNotFound(): void
    {
        // Then
        $this->expectException(IdentityResultNotFoundException::class);

        // When
        $this->finder()->ofId(Uuid::uuid7()->toString());
    }

    #[Test]
    public function itFindsByEmail(): void
    {
        // Given
        $builder = IdentityBuilder::new();
        $identity = $builder->create();
        $this->store($identity);

        // When
        $found = $this->finder()->ofEmailOrNull($builder['email']->value);
        $notFound = $this->finder()->ofEmailOrNull(SeededFaker::get()->unique()->safeEmail());

        // Then
        self::assertSame($identity->id->toString(), $found?->id);
        self::assertNull($notFound);
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
            idsOf: $this->resultIndexes(...),
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
            idsOf: $this->resultIndexes(...),
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

    protected function indexOf(object $result): string
    {
        return $result->id;
    }

    /**
     * @return array{string, string}
     */
    protected function seedConflictingOrder(): array
    {
        $now = Clock::get()->now();
        $smallerId = Uuid::uuid7($now)->toString();
        $largerId = Uuid::uuid7($now->modify('+1 hour'))->toString();

        $first = IdentityBuilder::new()->withId($largerId)->withRegisteredAt($now)->create();
        $second = IdentityBuilder::new()->withId($smallerId)->withRegisteredAt($now->modify('+1 hour'))->create();
        $this->store($first, $second);

        return [$largerId, $smallerId];
    }
}
