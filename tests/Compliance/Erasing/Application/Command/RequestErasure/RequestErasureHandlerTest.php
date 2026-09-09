<?php

declare(strict_types=1);

namespace Compliance\Tests\Erasing\Application\Command\RequestErasure;

use Compliance\Erasing\Application\Command\RequestErasure\Exception\ErasureAlreadyRequestedException;
use Compliance\Erasing\Application\Command\RequestErasure\RequestErasure;
use Compliance\Erasing\Application\ErasureRequestStatus;
use Compliance\Erasing\Application\Finder\Erasure\ErasureFinderInterface;
use Compliance\Erasing\Application\Uniqueness\ErasureUniqueKey;
use Compliance\Tests\Erasing\Support\Builder\ErasureBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniqueValueRegistryInterface;
use Support\TestCase\AbstractIntegrationTestCase;

final class RequestErasureHandlerTest extends AbstractIntegrationTestCase
{
    private ErasureFinderInterface $finder;
    private UniqueValueRegistryInterface $uniqueValues;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(ErasureFinderInterface::class);
        $this->uniqueValues = $this->service(UniqueValueRegistryInterface::class);
    }

    #[Test]
    public function itRequests(): void
    {
        // Given
        $id = Uuid::uuid7()->toString();
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
        $builder = ErasureBuilder::new()->cancelled();
        $erasure = $builder->create();
        $this->store($erasure);
        $id = Uuid::uuid7()->toString();

        // When
        $this->dispatch(new RequestErasure($id, $builder['identityId']));

        // Then
        $result = $this->finder->ofId($id);
        self::assertSame(ErasureRequestStatus::REQUESTED, $result->status);
    }

    #[Test]
    public function itFailsWhenAlreadyRequested(): void
    {
        // Given
        $builder = ErasureBuilder::new();
        $erasure = $builder->create();
        $this->store($erasure);
        $this->uniqueValues->reserve(UniqueKey::for(ErasureUniqueKey::IDENTITY), $builder['identityId'], $erasure->id->toString());

        // Then
        $this->expectException(ErasureAlreadyRequestedException::class);

        // When
        $this->dispatch(new RequestErasure(Uuid::uuid7()->toString(), $builder['identityId']));
    }
}
