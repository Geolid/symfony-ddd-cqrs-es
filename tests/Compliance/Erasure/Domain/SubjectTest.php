<?php

declare(strict_types=1);

namespace Compliance\Tests\Erasure\Domain;

use Compliance\Erasure\Domain\Event\SubjectErased;
use Compliance\Erasure\Domain\Event\SubjectErasureCancelled;
use Compliance\Erasure\Domain\Event\SubjectErasureHoldLifted;
use Compliance\Erasure\Domain\Event\SubjectErasureHoldPlaced;
use Compliance\Erasure\Domain\Event\SubjectErasureRequested;
use Compliance\Erasure\Domain\Event\SubjectRegistered;
use Compliance\Erasure\Domain\Subject;
use Compliance\Erasure\Domain\ValueObject\ErasureHoldReference;
use Compliance\Erasure\Domain\ValueObject\SubjectId;
use Compliance\Tests\Erasure\Support\Builder\SubjectBuilder;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Clock\Clock;

final class SubjectTest extends AggregateRootTestCase
{
    private SubjectId $id;
    private ErasureHoldReference $reference;
    private \DateTimeImmutable $registeredAt;
    private \DateTimeImmutable $requestedAt;
    private \DateTimeImmutable $placedAt;
    private \DateTimeImmutable $liftedAt;
    private \DateTimeImmutable $cancelledAt;
    private \DateTimeImmutable $erasedAt;

    protected function setUp(): void
    {
        parent::setUp();

        $now = Clock::get()->now();

        $this->id = SubjectId::fromString(Uuid::uuid7()->toString());
        $this->reference = ErasureHoldReference::for('compliance.tests.source', Uuid::uuid7()->toString());
        $this->registeredAt = SubjectBuilder::sample('registeredAt');
        $this->requestedAt = SubjectBuilder::sample('requestedAt');
        $this->placedAt = $now->modify('+1 day');
        $this->liftedAt = $now->modify('+3 days');
        $this->cancelledAt = SubjectBuilder::sample('cancelledAt');
        $this->erasedAt = SubjectBuilder::sample('erasedAt');
    }

    #[Test]
    public function itRegisters(): void
    {
        $this
            ->given()
            ->when(fn (): Subject => Subject::register($this->id, $this->registeredAt))
            ->then($this->registered());
    }

    #[Test]
    public function itPlacesErasureHold(): void
    {
        $this
            ->given($this->registered())
            ->when(fn (Subject $subject) => $subject->placeErasureHold($this->reference, $this->placedAt))
            ->then($this->placed());
    }

    #[Test]
    public function itDoesNotPlaceErasureHoldWhenAlreadyActive(): void
    {
        $this
            ->given($this->registered(), $this->placed())
            ->when(fn (Subject $subject) => $subject->placeErasureHold($this->reference, $this->placedAt->modify('+1 hour')))
            ->then();
    }

    #[Test]
    public function itLiftsErasureHold(): void
    {
        $this
            ->given($this->registered(), $this->placed())
            ->when(fn (Subject $subject) => $subject->liftErasureHold($this->reference, $this->liftedAt))
            ->then($this->lifted());
    }

    #[Test]
    public function itDoesNotLiftErasureHoldWhenNotActive(): void
    {
        $this
            ->given($this->registered())
            ->when(fn (Subject $subject) => $subject->liftErasureHold($this->reference, $this->liftedAt))
            ->then();
    }

    #[Test]
    public function itRequestsErasure(): void
    {
        $this
            ->given($this->registered())
            ->when(fn (Subject $subject) => $subject->requestErasure($this->requestedAt))
            ->then($this->requested());
    }

    #[Test]
    public function itDoesNotRequestErasureWhenAlreadyErasing(): void
    {
        $this
            ->given($this->registered(), $this->requested())
            ->when(static fn (Subject $subject) => $subject->requestErasure(SubjectBuilder::sample('requestedAt')))
            ->then();
    }

    #[Test]
    public function itCancelsErasure(): void
    {
        $this
            ->given($this->registered(), $this->requested())
            ->when(fn (Subject $subject) => $subject->cancelErasure($this->cancelledAt))
            ->then(new SubjectErasureCancelled($this->id->toString(), $this->cancelledAt));
    }

    #[Test]
    public function itDoesNotCancelErasureWhenRetained(): void
    {
        $this
            ->given($this->registered())
            ->when(static fn (Subject $subject) => $subject->cancelErasure(SubjectBuilder::sample('cancelledAt')))
            ->then();
    }

    #[Test]
    public function itErases(): void
    {
        $this
            ->given($this->registered(), $this->requested())
            ->when(fn (Subject $subject) => $subject->erase($this->erasedAt))
            ->then(new SubjectErased($this->id->toString(), $this->erasedAt));
    }

    #[Test]
    public function itDoesNotEraseWhenRetained(): void
    {
        $this
            ->given($this->registered())
            ->when(fn (Subject $subject) => $subject->erase($this->erasedAt))
            ->then();
    }

    #[Test]
    public function itDoesNotEraseWhenRetentionNotExpired(): void
    {
        $this
            ->given($this->registered(), $this->requested())
            ->when(fn (Subject $subject) => $subject->erase($this->requestedAt->modify('+1 day')))
            ->then();
    }

    #[Test]
    public function itDoesNotEraseWhenHoldsActive(): void
    {
        $this
            ->given($this->registered(), $this->requested(), $this->placed())
            ->when(fn (Subject $subject) => $subject->erase($this->erasedAt))
            ->then();
    }

    #[Test]
    public function itErasesAfterErasureHoldLifted(): void
    {
        $this
            ->given($this->registered(), $this->requested(), $this->placed(), $this->lifted())
            ->when(fn (Subject $subject) => $subject->erase($this->erasedAt))
            ->then(new SubjectErased($this->id->toString(), $this->erasedAt));
    }

    protected function aggregateClass(): string
    {
        return Subject::class;
    }

    private function registered(): SubjectRegistered
    {
        return new SubjectRegistered($this->id->toString(), $this->registeredAt);
    }

    private function placed(): SubjectErasureHoldPlaced
    {
        return new SubjectErasureHoldPlaced($this->id->toString(), $this->reference, $this->placedAt);
    }

    private function lifted(): SubjectErasureHoldLifted
    {
        return new SubjectErasureHoldLifted($this->id->toString(), $this->reference, $this->liftedAt);
    }

    private function requested(): SubjectErasureRequested
    {
        return new SubjectErasureRequested($this->id->toString(), $this->requestedAt);
    }
}
