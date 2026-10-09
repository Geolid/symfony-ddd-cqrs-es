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
    public function itApproves(): void
    {
        // Given
        $erasure = ErasureFactory::new()->withRequestedAt(Clock::get()->now()->modify('-31 days'))->create();
        $this->store($erasure);
        $identityKey = UniqueKey::for(ErasingUniqueKey::IDENTITY);
        $this->uniqueness->claim($identityKey, $erasure->identityId, $erasure->id->toString());

        // When
        $this->dispatch(new ApproveErasure($erasure->id->toString()));

        // Then
        $result = $this->finder->ofId($erasure->id->toString());
        self::assertSame(ErasureRequestStatus::APPROVED, $result->status);
        self::assertTrue($this->uniqueness->isClaimed($identityKey, $erasure->identityId));
    }

    #[Test]
    public function itIgnoresWhenRetentionNotExpired(): void
    {
        // Given
        $erasure = ErasureFactory::new()->withRequestedAt(Clock::get()->now()->modify('-1 day'))->create();
        $this->store($erasure);
        $identityKey = UniqueKey::for(ErasingUniqueKey::IDENTITY);
        $this->uniqueness->claim($identityKey, $erasure->identityId, $erasure->id->toString());

        // When
        $this->dispatch(new ApproveErasure($erasure->id->toString()));

        // Then
        $result = $this->finder->ofId($erasure->id->toString());
        self::assertSame(ErasureRequestStatus::REQUESTED, $result->status);
        self::assertTrue($this->uniqueness->isClaimed($identityKey, $erasure->identityId));
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(ErasureNotFoundException::class);

        // When
        $this->dispatch(new ApproveErasure(ErasureIdFactory::new()->create()->toString()));
    }
}
