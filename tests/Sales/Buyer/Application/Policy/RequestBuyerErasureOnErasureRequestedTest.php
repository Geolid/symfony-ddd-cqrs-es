<?php

declare(strict_types=1);

namespace Sales\Tests\Buyer\Application\Policy;

use Compliance\Erasing\Application\IntegrationEvent\ErasureRequested\ErasureRequestedIntegrationEvent;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Buyer\Application\Finder\Buyer\BuyerFinderInterface;
use Sales\Buyer\Application\Policy\RequestBuyerErasureOnErasureRequested;
use Sales\Tests\Buyer\Support\Builder\BuyerBuilder;
use Shared\Application\ErasureStatus;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class RequestBuyerErasureOnErasureRequestedTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itRequests(): void
    {
        // Given
        $builder = BuyerBuilder::new();
        $buyer = $builder->create();
        $this->store($buyer);

        // When
        $this->trigger(RequestBuyerErasureOnErasureRequested::class, new ErasureRequestedIntegrationEvent($builder['identityId'], Clock::get()->now()));

        // Then
        $result = $this->service(BuyerFinderInterface::class)->ofId($buyer->id->toString());
        self::assertSame(ErasureStatus::REQUESTED, $result->erasureStatus);
    }

    #[Test]
    public function itIgnoresWhenNoneExist(): void
    {
        // When
        $this->trigger(RequestBuyerErasureOnErasureRequested::class, new ErasureRequestedIntegrationEvent(Uuid::uuid7()->toString(), Clock::get()->now()));

        // Then
        self::expectNotToPerformAssertions();
    }
}
