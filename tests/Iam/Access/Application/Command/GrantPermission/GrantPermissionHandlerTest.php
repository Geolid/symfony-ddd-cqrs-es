<?php

declare(strict_types=1);

namespace Iam\Tests\Access\Application\Command\GrantPermission;

use Iam\Access\Application\Command\GrantPermission\GrantPermission;
use Iam\Access\Application\Finder\Grant\GrantFinderInterface;
use Iam\Access\Domain\ValueObject\GrantId;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class GrantPermissionHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itGrantsAPermission(): void
    {
        // Given
        $id = GrantId::generate()->toString();
        $command = new GrantPermission($id, 'identity-1', 'sales:read');

        // When
        $this->dispatch($command);

        // Then
        $results = array_values(iterator_to_array($this->service(GrantFinderInterface::class)->withIdentity('identity-1')));
        self::assertCount(1, $results);
        self::assertSame($id, $results[0]->id);
        self::assertSame('sales:read', $results[0]->permission);
        self::assertFalse($results[0]->revoked);
    }
}
