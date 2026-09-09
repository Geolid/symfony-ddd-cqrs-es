<?php

declare(strict_types=1);

namespace Sales\Tests\Buyer\Application\Command\DefineBuyerBillingAddress;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Buyer\Application\Command\DefineBuyerBillingAddress\DefineBuyerBillingAddress;
use Sales\Buyer\Domain\Exception\BuyerNotFoundException;
use Sales\Buyer\Domain\Repository\BuyerRepositoryInterface;
use Sales\Buyer\Domain\ValueObject\BuyerId;
use Sales\Tests\Buyer\Support\Builder\BuyerBuilder;
use Shared\Application\Mapper\PostalAddressMapper;
use Support\TestCase\AbstractIntegrationTestCase;

final class DefineBuyerBillingAddressHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itDefines(): void
    {
        // Given
        $buyer = BuyerBuilder::new()->create();
        $this->store($buyer);
        $billingAddress = PostalAddressMapper::toArray(BuyerBuilder::sample('billingAddress'));

        // When
        $this->dispatch(new DefineBuyerBillingAddress($buyer->id->toString(), $billingAddress));

        // Then
        $reloaded = $this->service(BuyerRepositoryInterface::class)->load(BuyerId::fromString($buyer->id->toString()));
        self::assertNotNull($reloaded->billingAddress);
        self::assertSame($billingAddress, PostalAddressMapper::toArray($reloaded->billingAddress));
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(BuyerNotFoundException::class);

        // When
        $this->dispatch(new DefineBuyerBillingAddress(Uuid::uuid7()->toString(), PostalAddressMapper::toArray(BuyerBuilder::sample('billingAddress'))));
    }
}
