<?php

declare(strict_types=1);

namespace Finance\Tests\Payer\Infrastructure\Gdpr;

use Finance\Payer\Domain\Event\PayerAddressRegistered;
use Finance\Payer\Domain\Event\PayerErased;
use Finance\Tests\Payer\Support\Builder\PayerBuilder;
use Patchlevel\EventSourcing\Message\Message;
use Patchlevel\EventSourcing\Serializer\EventSerializer;
use PHPUnit\Framework\Attributes\Test;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\PostalAddress;
use Shared\Infrastructure\Gdpr\DataSubjectEraserProcessor;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class PayerPiiErasureTest extends AbstractIntegrationTestCase
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
    public function itCryptoShredsAddressOnErasure(): void
    {
        // Given
        $payer = PayerBuilder::new()
            ->addressRegistered(PostalAddress::of('Ada Lovelace', Address::of('12 rue des Lilas', '75001', 'Paris', 'FR')))
            ->create();
        $this->store($payer);
        $serialized = $this->serializedEventOf(
            PayerAddressRegistered::class,
            static fn (PayerAddressRegistered $event): bool => $event->id === $payer->id->toString(),
        );

        // When
        ($this->eraser)(Message::create(new PayerErased($payer->id->toString(), Clock::get()->now())));

        // Then
        $rehydrated = $this->serializer->deserialize($serialized);
        self::assertInstanceOf(PayerAddressRegistered::class, $rehydrated);
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
