<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Command\ChangeFullName;

use Iam\Identity\Application\Command\ChangeFullName\ChangeFullName;
use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Identity\Domain\Exception\IdentityAlreadyErasedException;
use Iam\Identity\Domain\Exception\IdentityNotFoundException;
use Iam\Tests\Identity\Support\Factory\FullNameFactory;
use Iam\Tests\Identity\Support\Factory\IdentityFactory;
use Iam\Tests\Identity\Support\Factory\IdentityIdFactory;
use PHPUnit\Framework\Attributes\Test;
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
        $newFullName = FullNameFactory::new()->create()->value;
        $identity = IdentityFactory::new()->create();
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
        $factory = IdentityFactory::new();
        $identity = $factory->create();
        $this->store($identity);

        // When
        $this->dispatch(new ChangeFullName($identity->id->toString(), $identity->fullName->value));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $id = IdentityIdFactory::new()->create()->toString();

        // Then
        $this->expectException(IdentityNotFoundException::class);

        // When
        $this->dispatch(new ChangeFullName($id, FullNameFactory::new()->create()->value));
    }

    #[Test]
    public function itFailsWhenErased(): void
    {
        // Given
        $identity = IdentityFactory::new()->erasureRequested()->erased()->create();
        $this->store($identity);

        // Then
        $this->expectException(IdentityAlreadyErasedException::class);

        // When
        $this->dispatch(new ChangeFullName($identity->id->toString(), FullNameFactory::new()->create()->value));
    }
}
