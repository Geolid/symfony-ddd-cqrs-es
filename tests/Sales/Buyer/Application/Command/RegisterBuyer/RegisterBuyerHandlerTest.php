<?php

declare(strict_types=1);

namespace Sales\Tests\Buyer\Application\Command\RegisterBuyer;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Buyer\Application\Command\RegisterBuyer\RegisterBuyer;
use Sales\Buyer\Application\Finder\Buyer\BuyerFinderInterface;
use Sales\Buyer\Application\Uniqueness\BuyerUniqueKey;
use Sales\Buyer\Application\Uniqueness\Exception\BuyerEmailAlreadyInUseException;
use Sales\Buyer\Domain\ValueObject\BuyerId;
use Sales\Tests\Buyer\Support\Builder\BuyerBuilder;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniquenessRegistryInterface;
use Support\TestCase\AbstractIntegrationTestCase;

final class RegisterBuyerHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itRegisters(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();
        $email = BuyerBuilder::sample('email')->value;

        // When
        $this->dispatch(new RegisterBuyer($identityId, $email));

        // Then
        $id = BuyerId::forIdentity($identityId)->toString();
        $result = $this->service(BuyerFinderInterface::class)->ofId($id);
        self::assertSame($id, $result->id);
        self::assertSame($email, $result->email);
    }

    #[Test]
    public function itFailsWhenEmailAlreadyInUse(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();
        $existingId = Uuid::uuid7()->toString();
        $email = BuyerBuilder::sample('email')->value;
        $this->service(UniquenessRegistryInterface::class)->claim(UniqueKey::for(BuyerUniqueKey::EMAIL), $email, $existingId);

        // Then
        $this->expectException(BuyerEmailAlreadyInUseException::class);

        // When
        $this->dispatch(new RegisterBuyer($identityId, $email));
    }
}
