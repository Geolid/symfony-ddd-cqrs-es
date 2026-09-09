<?php

declare(strict_types=1);

namespace Sales\Tests\Buyer\Application\Command\CancelBuyerErasure;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Buyer\Application\Command\CancelBuyerErasure\CancelBuyerErasure;
use Sales\Buyer\Application\Finder\Buyer\BuyerFinderInterface;
use Sales\Buyer\Domain\Exception\BuyerNotFoundException;
use Sales\Tests\Buyer\Support\Builder\BuyerBuilder;
use Shared\Application\ErasureStatus;
use Support\TestCase\AbstractIntegrationTestCase;

final class CancelBuyerErasureHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itCancels(): void
    {
        // Given
        $buyer = BuyerBuilder::new()->erasureRequested()->create();
        $this->store($buyer);

        // When
        $this->dispatch(new CancelBuyerErasure($buyer->id->toString()));

        // Then
        $result = $this->service(BuyerFinderInterface::class)->ofId($buyer->id->toString());
        self::assertSame(ErasureStatus::RETAINED, $result->erasureStatus);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $id = Uuid::uuid7()->toString();

        // Then
        $this->expectException(BuyerNotFoundException::class);

        // When
        $this->dispatch(new CancelBuyerErasure($id));
    }
}
