<?php

declare(strict_types=1);

namespace Shopping\Tests\Cart\Application\Policy;

use Catalog\Listing\Application\IntegrationEvent\ProductDelisted\ProductDelistedIntegrationEvent;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Command\CommandBusInterface;
use Shopping\Cart\Application\Command\RemoveCartProduct\RemoveCartProduct;
use Shopping\Cart\Application\Policy\RemoveCartProductOnProductDelisted;
use Shopping\Tests\Cart\Support\Builder\CartBuilder;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class RemoveCartProductOnProductDelistedTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itRemoves(): void
    {
        // Given
        $commandBus = $this->createMock(CommandBusInterface::class);
        $this->replace(CommandBusInterface::class, $commandBus);

        $productId = Uuid::uuid7()->toString();
        $other = CartBuilder::new()->productAdded()->create();
        $cart = CartBuilder::new()->productAdded($productId)->create();
        $this->store($other, $cart);

        $commandBus->expects(self::once())->method('dispatch')->with(new RemoveCartProduct($cart->id->toString(), $productId));

        // When
        $this->trigger(RemoveCartProductOnProductDelisted::class, new ProductDelistedIntegrationEvent(
            productId: $productId,
            delistedAt: Clock::get()->now(),
        ));
    }

    #[Test]
    public function itIgnoresWhenCartAlreadyPurchased(): void
    {
        // Given
        $commandBus = $this->createMock(CommandBusInterface::class);
        $this->replace(CommandBusInterface::class, $commandBus);

        $productId = Uuid::uuid7()->toString();
        $cart = CartBuilder::new()->productAdded($productId)->purchased()->create();
        $this->store($cart);

        $commandBus->expects(self::never())->method('dispatch');

        // When
        $this->trigger(RemoveCartProductOnProductDelisted::class, new ProductDelistedIntegrationEvent(
            productId: $productId,
            delistedAt: Clock::get()->now(),
        ));
    }
}
