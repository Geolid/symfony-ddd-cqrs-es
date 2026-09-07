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
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\PostalAddress;
use Support\TestCase\AbstractIntegrationTestCase;

final class DefineBuyerBillingAddressHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itDefines(): void
    {
        // Given
        $buyer = BuyerBuilder::new()->create();
        $this->store($buyer);

        // When
        $this->dispatch(new DefineBuyerBillingAddress(
            $buyer->id->toString(),
            'Jane Doe',
            '10 Downing Street',
            'SW1A2AA',
            'London',
            'GB',
        ));

        // Then
        $reloaded = $this->service(BuyerRepositoryInterface::class)->load(BuyerId::fromString($buyer->id->toString()));
        self::assertTrue($reloaded->billingAddress?->equals(
            PostalAddress::of('Jane Doe', Address::of('10 Downing Street', 'SW1A2AA', 'London', 'GB')),
        ));
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(BuyerNotFoundException::class);

        // When
        $this->dispatch(new DefineBuyerBillingAddress(Uuid::uuid7()->toString(), 'Jane Doe', '10 Downing Street', 'SW1A2AA', 'London', 'GB'));
    }
}
