<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Policy;

use Compliance\Erasing\Application\IntegrationEvent\ErasureCancelled\ErasureCancelledIntegrationEvent;
use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Identity\Application\Policy\CancelIdentityErasureOnErasureCancelled;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\ErasureStatus;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class CancelIdentityErasureOnErasureCancelledTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itCancels(): void
    {
        // Given
        $identity = IdentityBuilder::new()->erasureRequested()->create();
        $this->store($identity);

        // When
        $this->trigger(CancelIdentityErasureOnErasureCancelled::class, new ErasureCancelledIntegrationEvent($identity->id->toString(), Clock::get()->now()));

        // Then
        $result = $this->service(IdentityFinderInterface::class)->ofId($identity->id->toString());
        self::assertSame(ErasureStatus::RETAINED, $result->erasureStatus);
    }
}
