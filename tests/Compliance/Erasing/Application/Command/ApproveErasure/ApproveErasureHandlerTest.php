<?php

declare(strict_types=1);

namespace Compliance\Tests\Erasing\Application\Command\ApproveErasure;

use Compliance\Erasing\Application\Command\ApproveErasure\ApproveErasure;
use Compliance\Erasing\Application\ErasureRequestStatus;
use Compliance\Erasing\Application\Finder\Erasure\ErasureFinderInterface;
use Compliance\Erasing\Domain\Exception\ErasureNotFoundException;
use Compliance\Tests\Erasing\Support\Builder\ErasureBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class ApproveErasureHandlerTest extends AbstractIntegrationTestCase
{
    private ErasureFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(ErasureFinderInterface::class);
    }

    #[Test]
    public function itApproves(): void
    {
        // Given
        $builder = ErasureBuilder::new()->withRequestedAt(Clock::get()->now()->modify('-31 days'));
        $erasure = $builder->create();
        $this->store($erasure);

        // When
        $this->dispatch(new ApproveErasure($builder['identityId']));

        // Then
        $result = $this->finder->ofId($erasure->id->toString());
        self::assertSame(ErasureRequestStatus::APPROVED, $result->status);
    }

    #[Test]
    public function itIgnoresWhenRetentionNotExpired(): void
    {
        // Given
        $builder = ErasureBuilder::new()->withRequestedAt(Clock::get()->now()->modify('-1 day'));
        $erasure = $builder->create();
        $this->store($erasure);

        // When
        $this->dispatch(new ApproveErasure($builder['identityId']));

        // Then
        $result = $this->finder->ofId($erasure->id->toString());
        self::assertSame(ErasureRequestStatus::REQUESTED, $result->status);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();

        // Then
        $this->expectException(ErasureNotFoundException::class);

        // When
        $this->dispatch(new ApproveErasure($identityId));
    }
}
