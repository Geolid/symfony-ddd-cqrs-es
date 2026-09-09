<?php

declare(strict_types=1);

namespace Compliance\Tests\Erasing\Application\Command\ApproveErasure;

use Compliance\Erasing\Application\Command\ApproveErasure\ApproveErasure;
use Compliance\Erasing\Application\ErasureRequestStatus;
use Compliance\Erasing\Application\Finder\Erasure\ErasureFinderInterface;
use Compliance\Erasing\Application\Uniqueness\ErasureUniqueKey;
use Compliance\Erasing\Domain\Exception\ErasureNotFoundException;
use Compliance\Tests\Erasing\Support\Builder\ErasureBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniqueValueRegistryInterface;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class ApproveErasureHandlerTest extends AbstractIntegrationTestCase
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
    public function itApproves(): void
    {
        // Given
        $builder = ErasureBuilder::new()->withRequestedAt(Clock::get()->now()->modify('-31 days'));
        $erasure = $builder->create();
        $this->store($erasure);
        $identityKey = UniqueKey::for(ErasureUniqueKey::IDENTITY);
        $this->uniqueValues->reserve($identityKey, $builder['identityId'], $erasure->id->toString());

        // When
        $this->dispatch(new ApproveErasure($erasure->id->toString()));

        // Then
        $result = $this->finder->ofId($erasure->id->toString());
        self::assertSame(ErasureRequestStatus::APPROVED, $result->status);
        self::assertTrue($this->uniqueValues->exists($identityKey, $builder['identityId']));
    }

    #[Test]
    public function itIgnoresWhenRetentionNotExpired(): void
    {
        // Given
        $builder = ErasureBuilder::new()->withRequestedAt(Clock::get()->now()->modify('-1 day'));
        $erasure = $builder->create();
        $this->store($erasure);
        $identityKey = UniqueKey::for(ErasureUniqueKey::IDENTITY);
        $this->uniqueValues->reserve($identityKey, $builder['identityId'], $erasure->id->toString());

        // When
        $this->dispatch(new ApproveErasure($erasure->id->toString()));

        // Then
        $result = $this->finder->ofId($erasure->id->toString());
        self::assertSame(ErasureRequestStatus::REQUESTED, $result->status);
        self::assertTrue($this->uniqueValues->exists($identityKey, $builder['identityId']));
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(ErasureNotFoundException::class);

        // When
        $this->dispatch(new ApproveErasure(Uuid::uuid7()->toString()));
    }
}
