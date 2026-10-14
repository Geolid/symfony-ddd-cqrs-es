<?php

declare(strict_types=1);

namespace Compliance\Tests\Erasing\Domain;

use Compliance\Erasing\Domain\Erasure;
use Compliance\Erasing\Domain\Event\ErasureApproved;
use Compliance\Erasing\Domain\Event\ErasureCancelled;
use Compliance\Erasing\Domain\Event\ErasureRequested;
use Compliance\Erasing\Domain\ValueObject\ErasureId;
use Compliance\Tests\Erasing\Support\Factory\ErasureIdFactory;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Clock\Clock;

final class ErasureTest extends AggregateRootTestCase
{
    private ErasureId $id;
    private string $identityId;
    private \DateTimeImmutable $requestedAt;
    private \DateTimeImmutable $cancelledAt;
    private \DateTimeImmutable $approvedAt;
    private \DateTimeImmutable $effectiveAt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->id = ErasureIdFactory::new()->create();
        $this->identityId = Uuid::uuid7()->toString();
        $this->requestedAt = Clock::get()->now();
        $this->cancelledAt = $this->requestedAt->modify('+1 hour');
        $this->effectiveAt = $this->requestedAt->modify('+30 days');
        $this->approvedAt = $this->effectiveAt->modify('+1 day');
    }

    #[Test]
    public function itRequests(): void
    {
        $this
            ->given()
            ->when(fn (): Erasure => Erasure::request($this->id, $this->identityId, $this->requestedAt, $this->effectiveAt))
            ->then($this->requested());
    }

    #[Test]
    public function itCancels(): void
    {
        $this
            ->given($this->requested())
            ->when(fn (Erasure $erasure) => $erasure->cancel($this->cancelledAt))
            ->then(new ErasureCancelled($this->id, $this->identityId, $this->cancelledAt));
    }

    #[Test]
    public function itDoesNotCancelWhenAlreadyCancelled(): void
    {
        $this
            ->given($this->requested(), $this->cancelled())
            ->when(fn (Erasure $erasure) => $erasure->cancel($this->cancelledAt))
            ->then();
    }

    #[Test]
    public function itApproves(): void
    {
        $this
            ->given($this->requested())
            ->when(fn (Erasure $erasure) => $erasure->approve($this->approvedAt))
            ->then(new ErasureApproved($this->id, $this->identityId, $this->approvedAt));
    }

    #[Test]
    public function itDoesNotApproveWhenCancelled(): void
    {
        $this
            ->given($this->requested(), $this->cancelled())
            ->when(fn (Erasure $erasure) => $erasure->approve($this->approvedAt))
            ->then();
    }

    #[Test]
    public function itDoesNotApproveWhenNotEffective(): void
    {
        $earlyApprovedAt = $this->effectiveAt;

        $this
            ->given($this->requested())
            ->when(static fn (Erasure $erasure) => $erasure->approve($earlyApprovedAt))
            ->then();
    }

    protected function aggregateClass(): string
    {
        return Erasure::class;
    }

    private function requested(): ErasureRequested
    {
        return new ErasureRequested($this->id, $this->identityId, $this->requestedAt, $this->effectiveAt);
    }

    private function cancelled(): ErasureCancelled
    {
        return new ErasureCancelled($this->id, $this->identityId, $this->cancelledAt);
    }
}
