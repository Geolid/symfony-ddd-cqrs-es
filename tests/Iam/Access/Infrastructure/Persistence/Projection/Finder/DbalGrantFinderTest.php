<?php

declare(strict_types=1);

namespace Iam\Tests\Access\Infrastructure\Persistence\Projection\Finder;

use Iam\Access\Application\Finder\Grant\GrantFinderInterface;
use Iam\Access\Application\Finder\Grant\GrantResult;
use Iam\Tests\Access\Support\Factory\GrantTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\AbstractIntegrationTestCase;

final class DbalGrantFinderTest extends AbstractIntegrationTestCase
{
    private GrantFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(GrantFinderInterface::class);
    }

    #[Test]
    public function itLists(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();
        $grant = GrantTestFactory::new()->withIdentityId($identityId)->withPermission('fixture.widget:read')->store();
        GrantTestFactory::new()->withIdentityId($identityId)->withPermission('fixture.widget:write')->revoked()->store();

        // When
        $results = iterator_to_array($this->finder);

        // Then
        self::assertCount(1, $results);
        $result = $results[0];
        self::assertInstanceOf(GrantResult::class, $result);
        self::assertSame($grant->id()->toString(), $result->id);
        self::assertSame($identityId, $result->identityId);
        self::assertSame('fixture.widget:read', $result->permission);
    }

    #[Test]
    public function itFiltersByIdentity(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();
        $grant = GrantTestFactory::new()->withIdentityId($identityId)->withPermission('fixture.widget:read')->store();
        GrantTestFactory::new()->withIdentityId(Uuid::uuid7()->toString())->withPermission('fixture.widget:write')->store();

        // When
        $results = iterator_to_array($this->finder->byIdentity($identityId));

        // Then
        self::assertCount(1, $results);
        self::assertSame($grant->id()->toString(), $results[0]->id);
    }
}
