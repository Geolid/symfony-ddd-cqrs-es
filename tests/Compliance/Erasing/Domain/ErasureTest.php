<?php

declare(strict_types=1);

namespace Compliance\Tests\Erasing\Domain;

use Compliance\Erasing\Domain\Erasure;
use Compliance\Erasing\Domain\Event\ErasureApproved;
use Compliance\Erasing\Domain\Event\ErasureCancelled;
use Compliance\Erasing\Domain\Event\ErasureRequested;
use Compliance\Erasing\Domain\ValueObject\ErasureId;
use Compliance\Tests\Erasing\Support\Builder\ErasureBuilder;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;

final class ErasureTest extends AggregateRootTestCase
{
    private ErasureId $id;
    private string $identityId;
    private \DateTimeImmutable $requestedAt;
    private \DateTimeImmutable $cancelledAt;
    private \DateTimeImmutable $approvedAt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->identityId = ErasureBuilder::sample('identityId');
        $this->id = ErasureId::forIdentity($this->identityId);
        $this->requestedAt = ErasureBuilder::sample('requestedAt');
        $this->cancelledAt = ErasureBuilder::sample('cancelledAt');
        $this->approvedAt = ErasureBuilder::sample('approvedAt');
    }

    #[Test]
    public function itRequests(): void
    {
        $this
            ->given()
            ->when(fn (): Erasure => Erasure::request($this->id, $this->identityId, $this->requestedAt))
            ->then($this->requested());
    }

    #[Test]
    public function itCancels(): void
    {
        $this
            ->given($this->requested())
            ->when(fn (Erasure $erasure) => $erasure->cancel($this->cancelledAt))
            ->then(new ErasureCancelled($this->id->toString(), $this->identityId, $this->cancelledAt));
    }

    #[Test]
    public function itDoesNotCancelWhenRetained(): void
    {
        $this
            ->given($this->requested(), $this->cancelled())
            ->when(static fn (Erasure $erasure) => $erasure->cancel(ErasureBuilder::sample('cancelledAt')))
            ->then();
    }

    #[Test]
    public function itReRequests(): void
    {
        $reRequestedAt = $this->requestedAt->modify('+2 hours');

        $this
            ->given($this->requested(), $this->cancelled())
            ->when(static fn (Erasure $erasure) => $erasure->reRequest($reRequestedAt))
            ->then(new ErasureRequested($this->id->toString(), $this->identityId, $reRequestedAt));
    }

    #[Test]
    public function itDoesNotReRequestWhenAlreadyRequested(): void
    {
        $this
            ->given($this->requested())
            ->when(fn (Erasure $erasure) => $erasure->reRequest($this->requestedAt->modify('+2 hours')))
            ->then();
    }

    #[Test]
    public function itApproves(): void
    {
        $this
            ->given($this->requested())
            ->when(fn (Erasure $erasure) => $erasure->approve($this->approvedAt))
            ->then(new ErasureApproved($this->id->toString(), $this->identityId, $this->approvedAt));
    }

    #[Test]
    public function itDoesNotApproveWhenRetained(): void
    {
        $this
            ->given($this->requested(), $this->cancelled())
            ->when(fn (Erasure $erasure) => $erasure->approve($this->approvedAt))
            ->then();
    }

    #[Test]
    public function itDoesNotApproveWhenRetentionNotExpired(): void
    {
        $this
            ->given($this->requested())
            ->when(fn (Erasure $erasure) => $erasure->approve($this->requestedAt->modify('+1 day')))
            ->then();
    }

    protected function aggregateClass(): string
    {
        return Erasure::class;
    }

    private function requested(): ErasureRequested
    {
        return new ErasureRequested($this->id->toString(), $this->identityId, $this->requestedAt);
    }

    private function cancelled(): ErasureCancelled
    {
        return new ErasureCancelled($this->id->toString(), $this->identityId, $this->cancelledAt);
    }
}
