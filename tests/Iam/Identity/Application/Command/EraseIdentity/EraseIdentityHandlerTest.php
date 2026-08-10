<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Command\EraseIdentity;

use Iam\Identity\Application\Command\EraseIdentity\EraseIdentity;
use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Identity\Domain\Exception\IdentityNotFoundException;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\AbstractIntegrationTestCase;

final class EraseIdentityHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itErasesTheIdentity(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);

        // When
        $this->dispatch(new EraseIdentity($identity->id()->toString()));

        // Then
        $result = $this->service(IdentityFinderInterface::class)->ofId($identity->id()->toString());
        self::assertNotNull($result->erasedAt);
    }

    #[Test]
    public function itIgnoresAnAlreadyErasedIdentity(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->erased()->create();
        $this->store($identity);

        // When
        $this->dispatch(new EraseIdentity($identity->id()->toString()));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenTheIdentityDoesNotExist(): void
    {
        // Given
        $id = Uuid::uuid7()->toString();

        // Then
        $this->expectException(IdentityNotFoundException::class);

        // When
        $this->dispatch(new EraseIdentity($id));
    }
}
