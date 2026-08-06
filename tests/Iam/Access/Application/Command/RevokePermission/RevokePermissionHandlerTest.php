<?php

declare(strict_types=1);

namespace Iam\Tests\Access\Application\Command\RevokePermission;

use Iam\Access\Application\Command\RevokePermission\RevokePermission;
use Iam\Access\Application\Finder\Grant\GrantFinderInterface;
use Iam\Access\Domain\Exception\GrantNotFoundException;
use Iam\Access\Domain\Exception\PermissionAlreadyRevokedException;
use Iam\Access\Domain\ValueObject\GrantId;
use Iam\Tests\Access\Support\Factory\GrantTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class RevokePermissionHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itRevokesAPermission(): void
    {
        // Given
        $grant = GrantTestFactory::new()->forIdentity('identity-1')->create();
        $this->store($grant);

        // When
        $this->dispatch(new RevokePermission($grant->id()->toString()));

        // Then
        $result = $this->service(GrantFinderInterface::class)->withIdentity('identity-1')->withoutRevoked();
        self::assertCount(0, $result);
    }

    #[Test]
    public function itFailsWhenTheGrantDoesNotExist(): void
    {
        // Then
        $this->expectException(GrantNotFoundException::class);

        // When
        $this->dispatch(new RevokePermission(GrantId::generate()->toString()));
    }

    #[Test]
    public function itFailsWhenThePermissionIsAlreadyRevoked(): void
    {
        // Given
        $grant = GrantTestFactory::new()->revoked()->create();
        $this->store($grant);

        // Then
        $this->expectException(PermissionAlreadyRevokedException::class);

        // When
        $this->dispatch(new RevokePermission($grant->id()->toString()));
    }
}
