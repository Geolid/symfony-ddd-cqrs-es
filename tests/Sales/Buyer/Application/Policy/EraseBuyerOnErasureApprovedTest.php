<?php

declare(strict_types=1);

namespace Sales\Tests\Buyer\Application\Policy;

use Compliance\Erasing\Application\IntegrationEvent\ErasureApproved\ErasureApprovedIntegrationEvent;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Buyer\Application\Finder\Buyer\BuyerFinderInterface;
use Sales\Buyer\Application\Finder\Buyer\Exception\BuyerResultNotFoundException;
use Sales\Buyer\Application\Policy\EraseBuyerOnErasureApproved;
use Sales\Tests\Buyer\Support\Builder\BuyerBuilder;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class EraseBuyerOnErasureApprovedTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itErases(): void
    {
        // Given
        $builder = BuyerBuilder::new()->erasureRequested();
        $buyer = $builder->create();
        $this->store($buyer);

        // Then
        $this->expectException(BuyerResultNotFoundException::class);

        // When
        $this->trigger(EraseBuyerOnErasureApproved::class, new ErasureApprovedIntegrationEvent($builder['identityId'], Clock::get()->now()));
        $this->service(BuyerFinderInterface::class)->ofId($buyer->id->toString());
    }

    #[Test]
    public function itIgnoresWhenNoneExist(): void
    {
        // When
        $this->trigger(EraseBuyerOnErasureApproved::class, new ErasureApprovedIntegrationEvent(Uuid::uuid7()->toString(), Clock::get()->now()));

        // Then
        self::expectNotToPerformAssertions();
    }
}
