<?php

declare(strict_types=1);

namespace Iam\Tests\Access\Application\Command\GrantPermission;

use Iam\Access\Application\Command\GrantPermission\GrantPermission;
use Iam\Access\Application\Finder\Grant\GrantFinderInterface;
use Iam\Access\Domain\ValueObject\GrantId;
use Iam\Tests\Access\Support\Factory\GrantTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\AbstractIntegrationTestCase;

final class GrantPermissionHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itGrantsAPermission(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();
        $command = new GrantPermission($identityId, 'fixture.widget:read');

        // When
        $this->dispatch($command);

        // Then
        $results = array_values(iterator_to_array($this->service(GrantFinderInterface::class)->withIdentity($identityId)));
        self::assertCount(1, $results);
        self::assertSame(GrantId::forIdentityAndPermission($identityId, 'fixture.widget:read')->toString(), $results[0]->id);
        self::assertSame('fixture.widget:read', $results[0]->permission);
    }

    #[Test]
    public function itReactivatesARevokedGrantWhenGrantedAgain(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();
        $grant = GrantTestFactory::new()->withIdentityId($identityId)->withPermission('fixture.widget:read')->revoked()->create();
        $this->store($grant);

        // When
        $this->dispatch(new GrantPermission($identityId, 'fixture.widget:read'));

        // Then
        $results = array_values(iterator_to_array($this->service(GrantFinderInterface::class)->withIdentity($identityId)));
        self::assertCount(1, $results);
        self::assertSame($grant->id()->toString(), $results[0]->id);
    }

    #[Test]
    public function itIgnoresAnAlreadyGrantedPermission(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();
        $grant = GrantTestFactory::new()->withIdentityId($identityId)->withPermission('fixture.widget:read')->create();
        $this->store($grant);

        // When
        $this->dispatch(new GrantPermission($identityId, 'fixture.widget:read'));

        // Then
        $results = array_values(iterator_to_array($this->service(GrantFinderInterface::class)->withIdentity($identityId)));
        self::assertCount(1, $results);
    }
}
