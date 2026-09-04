<?php

declare(strict_types=1);

namespace Finance\Tests\Payer\Domain;

use Finance\Payer\Domain\Event\PayerAddressRegistered;
use Finance\Payer\Domain\Event\PayerErased;
use Finance\Payer\Domain\Event\PayerRegistered;
use Finance\Payer\Domain\Payer;
use Finance\Payer\Domain\ValueObject\PayerId;
use Finance\Tests\Payer\Support\Builder\PayerBuilder;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\PostalAddress;

final class PayerTest extends AggregateRootTestCase
{
    private PayerId $id;
    private \DateTimeImmutable $registeredAt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->id = PayerId::generate();
        $this->registeredAt = PayerBuilder::sample('registeredAt');
    }

    #[Test]
    public function itRegisters(): void
    {
        $this
            ->given()
            ->when(fn (): Payer => Payer::register($this->id, $this->registeredAt))
            ->then(new PayerRegistered($this->id->toString(), $this->registeredAt));
    }

    #[Test]
    public function itRegistersAddress(): void
    {
        $setAt = PayerBuilder::sample('addressRegisteredAt');
        $address = $this->address();

        $this
            ->given($this->registered())
            ->when(static fn (Payer $payer) => $payer->registerAddress($address, $setAt))
            ->then(new PayerAddressRegistered(
                id: $this->id->toString(),
                address: $address->toArray(),
                setAt: $setAt,
            ));
    }

    #[Test]
    public function itDoesNotRegisterWhenIdenticalAddress(): void
    {
        $address = $this->address();
        $setAt = PayerBuilder::sample('addressRegisteredAt');

        $this
            ->given(
                $this->registered(),
                new PayerAddressRegistered($this->id->toString(), $address->toArray(), $setAt),
            )
            ->when(static fn (Payer $payer) => $payer->registerAddress($address, PayerBuilder::sample('addressRegisteredAt')))
            ->then();
    }

    #[Test]
    public function itErases(): void
    {
        $erasedAt = PayerBuilder::sample('erasedAt');

        $this
            ->given($this->registered())
            ->when(static fn (Payer $payer) => $payer->erase($erasedAt))
            ->then(new PayerErased($this->id->toString(), $erasedAt));
    }

    #[Test]
    public function itDoesNotEraseWhenAlreadyErased(): void
    {
        $erasedAt = PayerBuilder::sample('erasedAt');

        $this
            ->given($this->registered(), new PayerErased($this->id->toString(), $erasedAt))
            ->when(static fn (Payer $payer) => $payer->erase(PayerBuilder::sample('erasedAt')))
            ->then();
    }

    protected function aggregateClass(): string
    {
        return Payer::class;
    }

    private function registered(): PayerRegistered
    {
        return new PayerRegistered($this->id->toString(), $this->registeredAt);
    }

    private function address(): PostalAddress
    {
        return PostalAddress::of('Ada Lovelace', Address::of('12 rue des Lilas', '75001', 'Paris', 'FR'));
    }
}
