<?php

declare(strict_types=1);

namespace Compliance\Tests\Erasing\Application\Command\ApproveErasure;

use Compliance\Erasing\Application\Command\ApproveErasure\ApproveErasure;
use Compliance\Erasing\Application\ErasingUniqueKey;
use Compliance\Erasing\Application\ErasureRequestStatus;
use Compliance\Erasing\Application\Finder\Erasure\ErasureFinderInterface;
use Compliance\Erasing\Domain\Exception\ErasureNotFoundException;
use Compliance\Tests\Erasing\Support\Factory\ErasureFactory;
use Compliance\Tests\Erasing\Support\Factory\ErasureIdFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniquenessRegistryInterface;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class ApproveErasureHandlerTest extends AbstractIntegrationTestCase
{
    private ErasureFinderInterface $finder;
    private UniquenessRegistryInterface $uniqueness;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(ErasureFinderInterface::class);
        $this->uniqueness = $this->service(UniquenessRegistryInterface::class);
    }

    #[Test]
    #[DataProvider('provideRetention')]
    public function itApproves(string $offset, ErasureRequestStatus $expectedStatus): void
    {
        // Given
        $retention = '30 days';
        $erasure = ErasureFactory::new()
            ->withRetention($retention)
            ->withRequestedAt(Clock::get()->now()->modify(\sprintf('-%s %s', $retention, $offset)))
            ->create();
        $this->store($erasure);
        $identityKey = UniqueKey::for(ErasingUniqueKey::IDENTITY);
        $this->uniqueness->claim($identityKey, $erasure->identityId, $erasure->id->toString());

        // When
        $this->dispatch(new ApproveErasure($erasure->id->toString()));

        // Then
        $result = $this->finder->ofId($erasure->id->toString());
        self::assertSame($expectedStatus, $result->status);
        self::assertTrue($this->uniqueness->isClaimed($identityKey, $erasure->identityId));
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $unknownId = ErasureIdFactory::new()->create()->toString();

        // Then
        $this->expectException(ErasureNotFoundException::class);

        // When
        $this->dispatch(new ApproveErasure($unknownId));
    }

    /**
     * @return iterable<string, array{string, ErasureRequestStatus}>
     */
    public static function provideRetention(): iterable
    {
        yield 'before effective date' => ['+1 second', ErasureRequestStatus::REQUESTED];
        yield 'after effective date' => ['-1 second', ErasureRequestStatus::APPROVED];
    }
}
