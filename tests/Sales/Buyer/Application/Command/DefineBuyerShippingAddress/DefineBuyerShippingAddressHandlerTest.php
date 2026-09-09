<?php

declare(strict_types=1);

namespace Sales\Tests\Buyer\Application\Command\DefineBuyerShippingAddress;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Buyer\Application\Command\DefineBuyerShippingAddress\DefineBuyerShippingAddress;
use Sales\Buyer\Domain\Exception\BuyerNotFoundException;
use Sales\Buyer\Domain\Repository\BuyerRepositoryInterface;
use Sales\Buyer\Domain\ValueObject\BuyerId;
use Sales\Tests\Buyer\Support\Builder\BuyerBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class DefineBuyerShippingAddressHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itDefines(): void
    {
        // Given
        $buyer = BuyerBuilder::new()->create();
        $this->store($buyer);
        $shippingAddress = BuyerBuilder::sample('shippingAddress')->toArray();

        // When
        $this->dispatch(new DefineBuyerShippingAddress($buyer->id->toString(), $shippingAddress));

        // Then
        $reloaded = $this->service(BuyerRepositoryInterface::class)->load(BuyerId::fromString($buyer->id->toString()));
        self::assertSame($shippingAddress, $reloaded->shippingAddress?->toArray());
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(BuyerNotFoundException::class);

        // When
        $this->dispatch(new DefineBuyerShippingAddress(Uuid::uuid7()->toString(), BuyerBuilder::sample('shippingAddress')->toArray()));
    }
}
