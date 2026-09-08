<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Policy;

use Compliance\Erasing\Application\IntegrationEvent\ErasureRequested\ErasureRequestedIntegrationEvent;
use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Identity\Application\Policy\RequestIdentityErasureOnErasureRequested;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\ErasureStatus;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class RequestIdentityErasureOnErasureRequestedTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itRequests(): void
    {
        // Given
        $identity = IdentityBuilder::new()->create();
        $this->store($identity);

        // When
        $this->trigger(RequestIdentityErasureOnErasureRequested::class, new ErasureRequestedIntegrationEvent($identity->id->toString(), Clock::get()->now()));

        // Then
        $result = $this->service(IdentityFinderInterface::class)->ofId($identity->id->toString());
        self::assertSame(ErasureStatus::PENDING, $result->erasureStatus);
    }
}
