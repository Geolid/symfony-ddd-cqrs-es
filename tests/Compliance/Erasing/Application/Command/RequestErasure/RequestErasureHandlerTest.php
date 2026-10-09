<?php

declare(strict_types=1);

namespace Compliance\Tests\Erasing\Application\Command\RequestErasure;

use Compliance\Erasing\Application\Command\RequestErasure\Exception\ErasureAlreadyClaimedException;
use Compliance\Erasing\Application\Command\RequestErasure\RequestErasure;
use Compliance\Erasing\Application\ErasingUniqueKey;
use Compliance\Erasing\Application\ErasureRequestStatus;
use Compliance\Erasing\Application\Finder\Erasure\ErasureFinderInterface;
use Compliance\Tests\Erasing\Support\Factory\ErasureFactory;
use Compliance\Tests\Erasing\Support\Factory\ErasureIdFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniquenessRegistryInterface;
use Support\TestCase\AbstractIntegrationTestCase;

final class RequestErasureHandlerTest extends AbstractIntegrationTestCase
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
    public function itRequests(): void
    {
        // Given
        $id = ErasureIdFactory::new()->create()->toString();
        $identityId = Uuid::uuid7()->toString();

        // When
        $this->dispatch(new RequestErasure($id, $identityId));

        // Then
        $result = $this->finder->ofId($id);
        self::assertSame(ErasureRequestStatus::REQUESTED, $result->status);
    }

    #[Test]
    public function itRequestsWhenPreviouslyCancelled(): void
    {
        // Given
        $erasure = ErasureFactory::new()->cancelled()->create();
        $this->store($erasure);
        $id = ErasureIdFactory::new()->create()->toString();

        // When
        $this->dispatch(new RequestErasure($id, $erasure->identityId));

        // Then
        $result = $this->finder->ofId($id);
        self::assertSame(ErasureRequestStatus::REQUESTED, $result->status);
    }

    #[Test]
    public function itFailsWhenAlreadyClaimed(): void
    {
        // Given
        $erasure = ErasureFactory::new()->create();
        $this->store($erasure);
        $this->uniqueness->claim(UniqueKey::for(ErasingUniqueKey::IDENTITY), $erasure->identityId, $erasure->id->toString());

        // Then
        $this->expectException(ErasureAlreadyClaimedException::class);

        // When
        $this->dispatch(new RequestErasure(ErasureIdFactory::new()->create()->toString(), $erasure->identityId));
    }
}
