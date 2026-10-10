<?php

declare(strict_types=1);

namespace Compliance\Tests\Erasing\Application\Command\CancelErasure;

use Compliance\Erasing\Application\Command\CancelErasure\CancelErasure;
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

final class CancelErasureHandlerTest extends AbstractIntegrationTestCase
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
    public function itCancels(): void
    {
        // Given
        $erasure = ErasureFactory::new()->create();
        $this->store($erasure);
        $identityKey = UniqueKey::for(ErasingUniqueKey::IDENTITY);
        $this->uniqueness->claim($identityKey, $erasure->identityId, $erasure->id->toString());

        // When
        $this->dispatch(new CancelErasure($erasure->id->toString()));

        // Then
        $result = $this->finder->ofId($erasure->id->toString());
        self::assertSame(ErasureRequestStatus::CANCELLED, $result->status);
        self::assertFalse($this->uniqueness->isClaimed($identityKey, $erasure->identityId));
    }

    #[Test]
    public function itIgnoresWhenAlreadyApproved(): void
    {
        // Given
        $erasure = ErasureFactory::new()->approved()->create();
        $this->store($erasure);
        $identityKey = UniqueKey::for(ErasingUniqueKey::IDENTITY);
        $this->uniqueness->claim($identityKey, $erasure->identityId, $erasure->id->toString());

        // When
        $this->dispatch(new CancelErasure($erasure->id->toString()));

        // Then
        $result = $this->finder->ofId($erasure->id->toString());
        self::assertSame(ErasureRequestStatus::APPROVED, $result->status);
        self::assertTrue($this->uniqueness->isClaimed($identityKey, $erasure->identityId));
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $id = ErasureIdFactory::new()->create()->toString();

        // Then
        $this->expectException(ErasureNotFoundException::class);

        // When
        $this->dispatch(new CancelErasure($id));
    }
}
