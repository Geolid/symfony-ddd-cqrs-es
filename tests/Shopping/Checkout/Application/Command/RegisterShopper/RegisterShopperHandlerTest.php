<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Application\Command\RegisterShopper;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniquenessRegistryInterface;
use Shopping\Checkout\Application\Command\RegisterShopper\Exception\ShopperEmailAlreadyInUseException;
use Shopping\Checkout\Application\Command\RegisterShopper\RegisterShopper;
use Shopping\Checkout\Application\Finder\Shopper\ShopperFinderInterface;
use Shopping\Checkout\Application\ShopperUniqueKey;
use Shopping\Checkout\Domain\ValueObject\ShopperId;
use Shopping\Tests\Checkout\Support\Builder\ShopperBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class RegisterShopperHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itRegisters(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();
        $email = ShopperBuilder::sample('email')->value;

        // When
        $this->dispatch(new RegisterShopper($identityId, $email));

        // Then
        $id = ShopperId::forIdentity($identityId)->toString();
        $result = $this->service(ShopperFinderInterface::class)->ofId($id);
        self::assertSame($id, $result->id);
        self::assertSame($email, $result->email);
    }

    #[Test]
    public function itFailsWhenEmailAlreadyInUse(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();
        $existingId = Uuid::uuid7()->toString();
        $email = ShopperBuilder::sample('email')->value;
        $this->service(UniquenessRegistryInterface::class)->claim(UniqueKey::for(ShopperUniqueKey::EMAIL), $email, $existingId);

        // Then
        $this->expectException(ShopperEmailAlreadyInUseException::class);

        // When
        $this->dispatch(new RegisterShopper($identityId, $email));
    }
}
