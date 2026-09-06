<?php

declare(strict_types=1);

namespace Sales\Tests\Buyer\Domain;

use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;
use Sales\Buyer\Domain\Buyer;
use Sales\Buyer\Domain\Event\BuyerErased;
use Sales\Buyer\Domain\Event\BuyerPostalAddressDefined;
use Sales\Buyer\Domain\Event\BuyerRegistered;
use Sales\Buyer\Domain\ValueObject\BuyerId;
use Sales\Buyer\Domain\ValueObject\Email;
use Sales\Tests\Buyer\Support\Builder\BuyerBuilder;

final class BuyerTest extends AggregateRootTestCase
{
    private BuyerId $id;
    private Email $email;
    private \DateTimeImmutable $registeredAt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->id = BuyerId::generate();
        $this->email = BuyerBuilder::sample('email');
        $this->registeredAt = BuyerBuilder::sample('registeredAt');
    }

    #[Test]
    public function itRegisters(): void
    {
        $this
            ->given()
            ->when(fn (): Buyer => Buyer::register($this->id, $this->email, $this->registeredAt))
            ->then(new BuyerRegistered($this->id->toString(), $this->email, $this->registeredAt));
    }

    #[Test]
    public function itDefinesPostalAddress(): void
    {
        $definedAt = BuyerBuilder::sample('postalAddressDefinedAt');
        $postalAddress = BuyerBuilder::sample('postalAddress');

        $this
            ->given($this->registered())
            ->when(static fn (Buyer $buyer) => $buyer->definePostalAddress($postalAddress, $definedAt))
            ->then(new BuyerPostalAddressDefined(
                id: $this->id->toString(),
                postalAddress: $postalAddress,
                definedAt: $definedAt,
            ));
    }

    #[Test]
    public function itDoesNotDefineWhenIdenticalPostalAddress(): void
    {
        $postalAddress = BuyerBuilder::sample('postalAddress');
        $definedAt = BuyerBuilder::sample('postalAddressDefinedAt');

        $this
            ->given(
                $this->registered(),
                new BuyerPostalAddressDefined($this->id->toString(), $postalAddress, $definedAt),
            )
            ->when(static fn (Buyer $buyer) => $buyer->definePostalAddress($postalAddress, BuyerBuilder::sample('postalAddressDefinedAt')))
            ->then();
    }

    #[Test]
    public function itErases(): void
    {
        $erasedAt = BuyerBuilder::sample('erasedAt');

        $this
            ->given($this->registered())
            ->when(static fn (Buyer $buyer) => $buyer->erase($erasedAt))
            ->then(new BuyerErased($this->id->toString(), $erasedAt));
    }

    #[Test]
    public function itDoesNotEraseWhenAlreadyErased(): void
    {
        $erasedAt = BuyerBuilder::sample('erasedAt');

        $this
            ->given($this->registered(), new BuyerErased($this->id->toString(), $erasedAt))
            ->when(static fn (Buyer $buyer) => $buyer->erase(BuyerBuilder::sample('erasedAt')))
            ->then();
    }

    protected function aggregateClass(): string
    {
        return Buyer::class;
    }

    private function registered(): BuyerRegistered
    {
        return new BuyerRegistered($this->id->toString(), $this->email, $this->registeredAt);
    }
}
