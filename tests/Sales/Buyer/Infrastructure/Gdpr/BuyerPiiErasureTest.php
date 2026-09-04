<?php

declare(strict_types=1);

namespace Sales\Tests\Buyer\Infrastructure\Gdpr;

use Patchlevel\EventSourcing\Message\Message;
use Patchlevel\EventSourcing\Serializer\EventSerializer;
use PHPUnit\Framework\Attributes\Test;
use Sales\Buyer\Domain\Event\BuyerErased;
use Sales\Buyer\Domain\Event\BuyerRegistered;
use Sales\Buyer\Domain\Event\BuyerShippingAddressRegistered;
use Sales\Tests\Buyer\Support\Builder\BuyerBuilder;
use Shared\Domain\Gdpr\ErasedFieldSentinel;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\PostalAddress;
use Shared\Infrastructure\Gdpr\DataSubjectEraserProcessor;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class BuyerPiiErasureTest extends AbstractIntegrationTestCase
{
    private DataSubjectEraserProcessor $eraser;

    private EventSerializer $serializer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eraser = $this->service(DataSubjectEraserProcessor::class);
        $this->serializer = $this->service(EventSerializer::class);
    }

    #[Test]
    public function itCryptoShredsEmailOnErasure(): void
    {
        // Given
        $buyer = BuyerBuilder::new()->create();
        $this->store($buyer);
        $serialized = $this->serializedEventOf(
            BuyerRegistered::class,
            static fn (BuyerRegistered $event): bool => $event->id === $buyer->id->toString(),
        );

        // When
        ($this->eraser)(Message::create(new BuyerErased($buyer->id->toString(), Clock::get()->now())));

        // Then
        $rehydrated = $this->serializer->deserialize($serialized);
        self::assertInstanceOf(BuyerRegistered::class, $rehydrated);
        $sentinel = new ErasedFieldSentinel('%s@erased.invalid');
        self::assertSame($sentinel($buyer->id->toString()), $rehydrated->email);
    }

    #[Test]
    public function itCryptoShredsShippingAddressOnErasure(): void
    {
        // Given
        $buyer = BuyerBuilder::new()
            ->shippingAddressRegistered(PostalAddress::of('Ada Lovelace', Address::of('12 rue des Lilas', '75001', 'Paris', 'FR')))
            ->create();
        $this->store($buyer);
        $serialized = $this->serializedEventOf(
            BuyerShippingAddressRegistered::class,
            static fn (BuyerShippingAddressRegistered $event): bool => $event->id === $buyer->id->toString(),
        );

        // When
        ($this->eraser)(Message::create(new BuyerErased($buyer->id->toString(), Clock::get()->now())));

        // Then
        $rehydrated = $this->serializer->deserialize($serialized);
        self::assertInstanceOf(BuyerShippingAddressRegistered::class, $rehydrated);
        self::assertSame($this->erasedAddress(), $rehydrated->address);
    }

    /**
     * @return array{recipientName: string, street: string, postalCode: string, city: string, countryCode: string}
     */
    private function erasedAddress(): array
    {
        return ['recipientName' => 'erased', 'street' => 'erased', 'postalCode' => '00000', 'city' => 'erased', 'countryCode' => 'ZZ'];
    }
}
