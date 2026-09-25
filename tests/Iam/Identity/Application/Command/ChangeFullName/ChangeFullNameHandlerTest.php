<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Command\ChangeFullName;

use Iam\Identity\Application\Command\ChangeFullName\ChangeFullName;
use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Identity\Domain\Exception\IdentityAlreadyErasedException;
use Iam\Identity\Domain\Exception\IdentityNotFoundException;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

final class ChangeFullNameHandlerTest extends AbstractIntegrationTestCase
{
    private IdentityFinderInterface $identityFinder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->identityFinder = $this->service(IdentityFinderInterface::class);
    }

    #[Test]
    public function itChanges(): void
    {
        // Given
        $newFullName = IdentityBuilder::sample('fullName')->value;
        $identity = IdentityBuilder::new()->create();
        $this->store($identity);

        // When
        $this->dispatch(new ChangeFullName($identity->id->toString(), $newFullName));

        // Then
        $result = $this->identityFinder->ofId($identity->id->toString());
        self::assertSame($newFullName, $result->fullName);
    }

    #[Test]
    public function itIgnoresWhenSame(): void
    {
        // Given
        $builder = IdentityBuilder::new();
        $identity = $builder->create();
        $this->store($identity);

        // When
        $this->dispatch(new ChangeFullName($identity->id->toString(), $builder['fullName']->value));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(IdentityNotFoundException::class);

        // When
        $this->dispatch(new ChangeFullName(Uuid::uuid7()->toString(), IdentityBuilder::sample('fullName')->value));
    }

    #[Test]
    public function itFailsWhenErased(): void
    {
        // Given
        $identity = IdentityBuilder::new()->erasureRequested()->erased()->create();
        $this->store($identity);

        // Then
        $this->expectException(IdentityAlreadyErasedException::class);

        // When
        $this->dispatch(new ChangeFullName($identity->id->toString(), IdentityBuilder::sample('fullName')->value));
    }
}
