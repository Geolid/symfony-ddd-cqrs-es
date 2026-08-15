<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Infrastructure\Persistence\Projection\Finder;

use Iam\Identity\Application\Enum\IdentityStatus;
use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Exception\ResultNotFoundException;
use Support\AbstractIntegrationTestCase;

final class DbalIdentityFinderTest extends AbstractIntegrationTestCase
{
    private IdentityFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(IdentityFinderInterface::class);
    }

    #[Test]
    public function itGetsById(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->erased()->store();

        // When
        $result = $this->finder->ofId($identity->id()->toString());

        // Then
        self::assertSame($identity->id()->toString(), $result->id);
        self::assertSame(IdentityStatus::ACTIVE, $result->status);
        self::assertNotNull($result->erasedAt);
    }

    #[Test]
    public function itThrowsOnAnUnknown(): void
    {
        // Then
        $this->expectException(ResultNotFoundException::class);

        // When
        $this->finder->ofId(Uuid::uuid7()->toString());
    }
}
