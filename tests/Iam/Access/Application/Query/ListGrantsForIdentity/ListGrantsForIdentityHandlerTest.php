<?php

declare(strict_types=1);

namespace Iam\Tests\Access\Application\Query\ListGrantsForIdentity;

use Iam\Access\Application\Query\ListGrantsForIdentity\ListGrantsForIdentity;
use Iam\Tests\Access\Support\Factory\GrantTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class ListGrantsForIdentityHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itListsTheNonRevokedGrantsHeldByTheIdentity(): void
    {
        // Given
        $this->store(GrantTestFactory::new()->forIdentity('identity-1')->withPermission('sales:read')->create());
        $this->store(GrantTestFactory::new()->forIdentity('identity-1')->withPermission('catalog:write')->revoked()->create());
        $this->store(GrantTestFactory::new()->forIdentity('identity-2')->withPermission('sales:read')->create());

        // When
        $result = $this->ask(new ListGrantsForIdentity('identity-1'));

        // Then
        self::assertCount(1, $result);
        self::assertSame('sales:read', $result[0]->permission);
    }

    #[Test]
    public function itListsNothingWhenTheIdentityHoldsNoGrant(): void
    {
        // Given
        $this->store(GrantTestFactory::new()->forIdentity('identity-2')->create());

        // When
        $result = $this->ask(new ListGrantsForIdentity('identity-1'));

        // Then
        self::assertSame([], $result);
    }
}
