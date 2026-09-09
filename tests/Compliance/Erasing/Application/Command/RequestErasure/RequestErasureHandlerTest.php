<?php

declare(strict_types=1);

namespace Compliance\Tests\Erasing\Application\Command\RequestErasure;

use Compliance\Erasing\Application\Command\RequestErasure\RequestErasure;
use Compliance\Erasing\Application\ErasureRequestStatus;
use Compliance\Erasing\Application\Finder\Erasure\ErasureFinderInterface;
use Compliance\Erasing\Domain\ValueObject\ErasureId;
use Compliance\Tests\Erasing\Support\Builder\ErasureBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

final class RequestErasureHandlerTest extends AbstractIntegrationTestCase
{
    private ErasureFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(ErasureFinderInterface::class);
    }

    #[Test]
    public function itRequests(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();

        // When
        $this->dispatch(new RequestErasure($identityId));

        // Then
        $id = ErasureId::forIdentity($identityId)->toString();
        $result = $this->finder->ofId($id);
        self::assertSame(ErasureRequestStatus::REQUESTED, $result->status);
    }

    #[Test]
    public function itReRequestsWhenAlreadyCancelled(): void
    {
        // Given
        $builder = ErasureBuilder::new()->cancelled();
        $erasure = $builder->create();
        $this->store($erasure);

        // When
        $this->dispatch(new RequestErasure($builder['identityId']));

        // Then
        $result = $this->finder->ofId($erasure->id->toString());
        self::assertSame(ErasureRequestStatus::REQUESTED, $result->status);
    }
}
