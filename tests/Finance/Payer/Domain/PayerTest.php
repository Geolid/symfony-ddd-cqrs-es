<?php

declare(strict_types=1);

namespace Finance\Tests\Payer\Domain;

use Finance\Payer\Domain\Event\PayerErased;
use Finance\Payer\Domain\Event\PayerPostalAddressDefined;
use Finance\Payer\Domain\Event\PayerRegistered;
use Finance\Payer\Domain\Payer;
use Finance\Payer\Domain\ValueObject\PayerId;
use Finance\Tests\Payer\Support\Builder\PayerBuilder;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;
use Shared\Domain\ValueObject\PostalAddress;

final class PayerTest extends AggregateRootTestCase
{
    private PayerId $id;
    private \DateTimeImmutable $registeredAt;
    private PostalAddress $postalAddress;

    protected function setUp(): void
    {
        parent::setUp();

        $this->id = PayerId::generate();
        $this->registeredAt = PayerBuilder::sample('registeredAt');
        $this->postalAddress = PayerBuilder::sample('postalAddress');
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
    public function itDefinesPostalAddress(): void
    {
        $definedAt = PayerBuilder::sample('postalAddressDefinedAt');
        $postalAddress = $this->postalAddress;

        $this
            ->given($this->registered())
            ->when(static fn (Payer $payer) => $payer->definePostalAddress($postalAddress, $definedAt))
            ->then(new PayerPostalAddressDefined(
                id: $this->id->toString(),
                postalAddress: $postalAddress,
                definedAt: $definedAt,
            ));
    }

    #[Test]
    public function itDoesNotDefineWhenIdenticalPostalAddress(): void
    {
        $postalAddress = $this->postalAddress;
        $definedAt = PayerBuilder::sample('postalAddressDefinedAt');

        $this
            ->given(
                $this->registered(),
                new PayerPostalAddressDefined($this->id->toString(), $postalAddress, $definedAt),
            )
            ->when(static fn (Payer $payer) => $payer->definePostalAddress($postalAddress, PayerBuilder::sample('postalAddressDefinedAt')))
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
}
