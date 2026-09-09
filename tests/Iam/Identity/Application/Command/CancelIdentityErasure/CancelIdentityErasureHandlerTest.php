<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Command\CancelIdentityErasure;

use Iam\Identity\Application\Command\CancelIdentityErasure\CancelIdentityErasure;
use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Identity\Domain\Exception\IdentityNotFoundException;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\ErasureStatus;
use Support\TestCase\AbstractIntegrationTestCase;

final class CancelIdentityErasureHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itCancels(): void
    {
        // Given
        $identity = IdentityBuilder::new()->erasureRequested()->create();
        $this->store($identity);

        // When
        $this->dispatch(new CancelIdentityErasure($identity->id->toString()));

        // Then
        $result = $this->service(IdentityFinderInterface::class)->ofId($identity->id->toString());
        self::assertSame(ErasureStatus::RETAINED, $result->erasureStatus);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $id = Uuid::uuid7()->toString();

        // Then
        $this->expectException(IdentityNotFoundException::class);

        // When
        $this->dispatch(new CancelIdentityErasure($id));
    }
}
