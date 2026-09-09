<?php

declare(strict_types=1);

namespace Compliance\Tests\Erasing\Application\Command\CancelErasure;

use Compliance\Erasing\Application\Command\CancelErasure\CancelErasure;
use Compliance\Erasing\Application\ErasureRequestStatus;
use Compliance\Erasing\Application\Finder\Erasure\ErasureFinderInterface;
use Compliance\Erasing\Domain\Exception\ErasureNotFoundException;
use Compliance\Tests\Erasing\Support\Builder\ErasureBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

final class CancelErasureHandlerTest extends AbstractIntegrationTestCase
{
    private ErasureFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(ErasureFinderInterface::class);
    }

    #[Test]
    public function itCancels(): void
    {
        // Given
        $builder = ErasureBuilder::new();
        $erasure = $builder->create();
        $this->store($erasure);

        // When
        $this->dispatch(new CancelErasure($builder['identityId']));

        // Then
        $result = $this->finder->ofId($erasure->id->toString());
        self::assertSame(ErasureRequestStatus::RETAINED, $result->status);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();

        // Then
        $this->expectException(ErasureNotFoundException::class);

        // When
        $this->dispatch(new CancelErasure($identityId));
    }
}
