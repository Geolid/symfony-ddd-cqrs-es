<?php

declare(strict_types=1);

namespace Sales\Tests\Buyer\Application\Policy;

use Iam\Identity\Application\IntegrationEvent\IdentityErased\IdentityErasedIntegrationEvent;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Buyer\Application\Exception\BuyerResultNotFoundException;
use Sales\Buyer\Application\Finder\Buyer\BuyerFinderInterface;
use Sales\Buyer\Application\Policy\EraseBuyerOnIdentityErased;
use Sales\Tests\Buyer\Support\Builder\BuyerBuilder;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class EraseBuyerOnIdentityErasedTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itErases(): void
    {
        // Given
        $id = Uuid::uuid7()->toString();
        $buyer = BuyerBuilder::new()->withId($id)->create();
        $this->store($buyer);

        // Then
        $this->expectException(BuyerResultNotFoundException::class);

        // When
        $this->trigger(EraseBuyerOnIdentityErased::class, new IdentityErasedIntegrationEvent($id, Clock::get()->now()));
        $this->service(BuyerFinderInterface::class)->ofId($id);
    }

    #[Test]
    public function itIgnoresWhenNoneExist(): void
    {
        // When
        $this->trigger(EraseBuyerOnIdentityErased::class, new IdentityErasedIntegrationEvent(Uuid::uuid7()->toString(), Clock::get()->now()));

        // Then
        self::expectNotToPerformAssertions();
    }
}
