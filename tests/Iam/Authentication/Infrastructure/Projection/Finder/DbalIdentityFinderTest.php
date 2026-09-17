<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\Projection\Finder;

use Iam\Authentication\Application\Finder\Identity\Exception\IdentityResultNotFoundException;
use Iam\Authentication\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Authentication\Application\IdentityStatus;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

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
        $other = IdentityBuilder::new()->create();
        $builder = IdentityBuilder::new()->activated();
        $identity = $builder->create();
        $this->store($other, $identity);

        // When
        $result = $this->finder->ofId($identity->id->toString());

        // Then
        self::assertSame($identity->id->toString(), $result->identityId);
        self::assertSame($builder['fullName']->value, $result->fullName);
        self::assertSame($builder['email']->value, $result->email);
        self::assertSame(IdentityStatus::ACTIVE, $result->status);
    }

    #[Test]
    public function itThrowsWhenIdNotFound(): void
    {
        // Then
        $this->expectException(IdentityResultNotFoundException::class);

        // When
        $this->finder->ofId(Uuid::uuid7()->toString());
    }
}
