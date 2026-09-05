<?php

declare(strict_types=1);

namespace Sales\Tests\Buyer\Application\Command\RegisterBuyer;

use PHPUnit\Framework\Attributes\Test;
use Sales\Buyer\Application\Command\RegisterBuyer\Exception\BuyerEmailAlreadyTakenException;
use Sales\Buyer\Application\Command\RegisterBuyer\RegisterBuyer;
use Sales\Buyer\Application\Finder\Buyer\BuyerFinderInterface;
use Sales\Buyer\Domain\ValueObject\BuyerId;
use Sales\Buyer\Domain\ValueObject\BuyerUniqueKey;
use Sales\Tests\Buyer\Support\Builder\BuyerBuilder;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniqueValueRegistryInterface;
use Support\TestCase\AbstractIntegrationTestCase;

final class RegisterBuyerHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itRegisters(): void
    {
        // Given
        $id = BuyerId::generate()->toString();
        $email = BuyerBuilder::sample('email')->value;

        // When
        $this->dispatch(new RegisterBuyer($id, $email));

        // Then
        $result = $this->service(BuyerFinderInterface::class)->ofId($id);
        self::assertSame($id, $result->id);
        self::assertSame($email, $result->email);
    }

    #[Test]
    public function itFailsWhenEmailAlreadyTaken(): void
    {
        // Given
        $id = BuyerId::generate()->toString();
        $existingId = BuyerId::generate()->toString();
        $email = BuyerBuilder::sample('email')->value;
        $this->service(UniqueValueRegistryInterface::class)->reserve(UniqueKey::for(BuyerUniqueKey::EMAIL), $email, $existingId);

        // Then
        $this->expectException(BuyerEmailAlreadyTakenException::class);

        // When
        $this->dispatch(new RegisterBuyer($id, $email));
    }
}
