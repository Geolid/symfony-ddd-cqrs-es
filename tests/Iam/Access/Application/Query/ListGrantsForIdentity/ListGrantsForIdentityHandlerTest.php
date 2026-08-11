<?php

declare(strict_types=1);

namespace Iam\Tests\Access\Application\Query\ListGrantsForIdentity;

use Iam\Access\Application\Query\ListGrantsForIdentity\ListGrantsForIdentity;
use Iam\Tests\Access\Support\Factory\GrantTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\AbstractIntegrationTestCase;

final class ListGrantsForIdentityHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itListsTheNonRevokedGrantsHeldByTheIdentity(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();
        $grant = GrantTestFactory::new()->withIdentityId($identityId)->withPermission('fixture.widget:read')->create();
        $this->store(
            $grant,
            GrantTestFactory::new()->withIdentityId($identityId)->withPermission('fixture.widget:write')->revoked()->create(),
            GrantTestFactory::new()->withIdentityId(Uuid::uuid7()->toString())->withPermission('fixture.widget:read')->create(),
        );

        // When
        $result = $this->ask(new ListGrantsForIdentity($identityId));

        // Then
        self::assertCount(1, $result);
        self::assertSame($grant->id()->toString(), $result[0]->id);
        self::assertSame($identityId, $result[0]->identityId);
        self::assertSame('fixture.widget:read', $result[0]->permission);
    }

    #[Test]
    public function itListsNothingWhenTheIdentityHoldsNoGrant(): void
    {
        // Given
        $this->store(GrantTestFactory::new()->withIdentityId(Uuid::uuid7()->toString())->create());

        // When
        $result = $this->ask(new ListGrantsForIdentity(Uuid::uuid7()->toString()));

        // Then
        self::assertSame([], $result);
    }
}
