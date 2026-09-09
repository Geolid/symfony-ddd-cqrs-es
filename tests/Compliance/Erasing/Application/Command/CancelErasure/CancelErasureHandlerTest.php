<?php

declare(strict_types=1);

namespace Compliance\Tests\Erasing\Application\Command\CancelErasure;

use Compliance\Erasing\Application\Command\CancelErasure\CancelErasure;
use Compliance\Erasing\Application\ErasureRequestStatus;
use Compliance\Erasing\Application\Finder\Erasure\ErasureFinderInterface;
use Compliance\Erasing\Domain\Exception\ErasureNotFoundException;
use Compliance\Erasing\Domain\ValueObject\ErasureUniqueKey;
use Compliance\Tests\Erasing\Support\Builder\ErasureBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniqueValueRegistryInterface;
use Support\TestCase\AbstractIntegrationTestCase;

final class CancelErasureHandlerTest extends AbstractIntegrationTestCase
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
    public function itCancels(): void
    {
        // Given
        $builder = ErasureBuilder::new();
        $erasure = $builder->create();
        $this->store($erasure);
        $identityKey = UniqueKey::for(ErasureUniqueKey::IDENTITY);
        $this->uniqueValues->reserve($identityKey, $builder['identityId'], $erasure->id->toString());

        // When
        $this->dispatch(new CancelErasure($erasure->id->toString()));

        // Then
        $result = $this->finder->ofId($erasure->id->toString());
        self::assertSame(ErasureRequestStatus::CANCELLED, $result->status);
        self::assertFalse($this->uniqueValues->exists($identityKey, $builder['identityId']));
    }

    #[Test]
    public function itIgnoresWhenAlreadyApproved(): void
    {
        // Given
        $builder = ErasureBuilder::new()->approved();
        $erasure = $builder->create();
        $this->store($erasure);
        $identityKey = UniqueKey::for(ErasureUniqueKey::IDENTITY);
        $this->uniqueValues->reserve($identityKey, $builder['identityId'], $erasure->id->toString());

        // When
        $this->dispatch(new CancelErasure($erasure->id->toString()));

        // Then
        $result = $this->finder->ofId($erasure->id->toString());
        self::assertSame(ErasureRequestStatus::APPROVED, $result->status);
        self::assertTrue($this->uniqueValues->exists($identityKey, $builder['identityId']));
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(ErasureNotFoundException::class);

        // When
        $this->dispatch(new CancelErasure(Uuid::uuid7()->toString()));
    }
}
