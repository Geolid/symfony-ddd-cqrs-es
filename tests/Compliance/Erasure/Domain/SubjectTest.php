<?php

declare(strict_types=1);

namespace Compliance\Tests\Erasure\Domain;

use Compliance\Erasure\Domain\Event\HoldLifted;
use Compliance\Erasure\Domain\Event\HoldPlaced;
use Compliance\Erasure\Domain\Event\SubjectErased;
use Compliance\Erasure\Domain\Event\SubjectErasureCancelled;
use Compliance\Erasure\Domain\Event\SubjectErasureRequested;
use Compliance\Erasure\Domain\Event\SubjectRegistered;
use Compliance\Erasure\Domain\Subject;
use Compliance\Erasure\Domain\ValueObject\HoldReference;
use Compliance\Erasure\Domain\ValueObject\SubjectId;
use Compliance\Tests\Erasure\Support\Builder\SubjectBuilder;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;

final class SubjectTest extends AggregateRootTestCase
{
    private SubjectId $id;
    private HoldReference $reference;
    private \DateTimeImmutable $requestedAt;
    private \DateTimeImmutable $placedAt;
    private \DateTimeImmutable $liftedAt;
    private \DateTimeImmutable $cancelledAt;
    private \DateTimeImmutable $releasedAt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->id = SubjectId::fromString(Uuid::uuid7()->toString());
        $this->reference = SubjectBuilder::sample('reference');
        $this->requestedAt = SubjectBuilder::sample('requestedAt');
        $this->placedAt = SubjectBuilder::sample('placedAt');
        $this->liftedAt = SubjectBuilder::sample('liftedAt');
        $this->cancelledAt = SubjectBuilder::sample('cancelledAt');
        $this->releasedAt = SubjectBuilder::sample('releasedAt');
    }

    #[Test]
    public function itPlacesHoldWhenNew(): void
    {
        $this
            ->given()
            ->when(fn (): Subject => Subject::place($this->id, $this->reference, $this->placedAt))
            ->then($this->registered($this->placedAt), $this->placed());
    }

    #[Test]
    public function itRequestsErasureWhenNew(): void
    {
        $this
            ->given()
            ->when(fn (): Subject => Subject::request($this->id, $this->requestedAt))
            ->then($this->registered($this->requestedAt), $this->requested());
    }

    #[Test]
    public function itPlacesHold(): void
    {
        $this
            ->given($this->registered($this->requestedAt))
            ->when(fn (Subject $subject) => $subject->placeHold($this->reference, $this->placedAt))
            ->then($this->placed());
    }

    #[Test]
    public function itDoesNotPlaceHoldWhenAlreadyActive(): void
    {
        $this
            ->given($this->registered($this->requestedAt), $this->placed())
            ->when(fn (Subject $subject) => $subject->placeHold($this->reference, SubjectBuilder::sample('placedAt')))
            ->then();
    }

    #[Test]
    public function itLiftsHold(): void
    {
        $this
            ->given($this->registered($this->requestedAt), $this->placed())
            ->when(fn (Subject $subject) => $subject->liftHold($this->reference, $this->liftedAt))
            ->then($this->lifted());
    }

    #[Test]
    public function itDoesNotLiftHoldWhenNotActive(): void
    {
        $this
            ->given($this->registered($this->requestedAt))
            ->when(fn (Subject $subject) => $subject->liftHold($this->reference, $this->liftedAt))
            ->then();
    }

    #[Test]
    public function itRequestsErasure(): void
    {
        $this
            ->given($this->registered($this->requestedAt))
            ->when(fn (Subject $subject) => $subject->requestErasure($this->requestedAt))
            ->then($this->requested());
    }

    #[Test]
    public function itDoesNotRequestErasureWhenAlreadyErasing(): void
    {
        $this
            ->given($this->registered($this->requestedAt), $this->requested())
            ->when(static fn (Subject $subject) => $subject->requestErasure(SubjectBuilder::sample('requestedAt')))
            ->then();
    }

    #[Test]
    public function itCancelsErasure(): void
    {
        $this
            ->given($this->registered($this->requestedAt), $this->requested())
            ->when(fn (Subject $subject) => $subject->cancelErasure($this->cancelledAt))
            ->then(new SubjectErasureCancelled($this->id->toString(), $this->cancelledAt));
    }

    #[Test]
    public function itDoesNotCancelErasureWhenRetained(): void
    {
        $this
            ->given($this->registered($this->requestedAt))
            ->when(static fn (Subject $subject) => $subject->cancelErasure(SubjectBuilder::sample('cancelledAt')))
            ->then();
    }

    #[Test]
    public function itReleases(): void
    {
        $this
            ->given($this->registered($this->requestedAt), $this->requested())
            ->when(fn (Subject $subject) => $subject->release($this->releasedAt))
            ->then(new SubjectErased($this->id->toString(), $this->releasedAt));
    }

    #[Test]
    public function itDoesNotReleaseWhenRetained(): void
    {
        $this
            ->given($this->registered($this->requestedAt))
            ->when(fn (Subject $subject) => $subject->release($this->releasedAt))
            ->then();
    }

    #[Test]
    public function itDoesNotReleaseWhenRetentionNotExpired(): void
    {
        $this
            ->given($this->registered($this->requestedAt), $this->requested())
            ->when(fn (Subject $subject) => $subject->release($this->requestedAt->modify('+1 day')))
            ->then();
    }

    #[Test]
    public function itDoesNotReleaseWhenHoldsActive(): void
    {
        $this
            ->given($this->registered($this->requestedAt), $this->requested(), $this->placed())
            ->when(fn (Subject $subject) => $subject->release($this->releasedAt))
            ->then();
    }

    #[Test]
    public function itReleasesAfterHoldLifted(): void
    {
        $this
            ->given($this->registered($this->requestedAt), $this->requested(), $this->placed(), $this->lifted())
            ->when(fn (Subject $subject) => $subject->release($this->releasedAt))
            ->then(new SubjectErased($this->id->toString(), $this->releasedAt));
    }

    protected function aggregateClass(): string
    {
        return Subject::class;
    }

    private function registered(\DateTimeImmutable $registeredAt): SubjectRegistered
    {
        return new SubjectRegistered($this->id->toString(), $registeredAt);
    }

    private function placed(): HoldPlaced
    {
        return new HoldPlaced($this->id->toString(), $this->reference, $this->placedAt);
    }

    private function lifted(): HoldLifted
    {
        return new HoldLifted($this->id->toString(), $this->reference, $this->liftedAt);
    }

    private function requested(): SubjectErasureRequested
    {
        return new SubjectErasureRequested($this->id->toString(), $this->requestedAt);
    }
}
