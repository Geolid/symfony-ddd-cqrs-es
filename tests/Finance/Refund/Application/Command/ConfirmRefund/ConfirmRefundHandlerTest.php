<?php

declare(strict_types=1);

namespace Finance\Tests\Refund\Application\Command\ConfirmRefund;

use Finance\Refund\Application\Command\ConfirmRefund\ConfirmRefund;
use Finance\Refund\Application\Finder\Refund\RefundFinderInterface;
use Finance\Refund\Application\RefundStatus;
use Finance\Refund\Domain\Exception\RefundNotFoundException;
use Finance\Refund\Domain\ValueObject\RefundId;
use Finance\Tests\Refund\Support\Builder\RefundBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

final class ConfirmRefundHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itConfirmsWhenInitiated(): void
    {
        // Given
        $refund = RefundBuilder::new()->create();
        $this->store($refund);

        // When
        $this->dispatch(new ConfirmRefund($refund->id->toString()));

        // Then
        $result = $this->service(RefundFinderInterface::class)->ofId($refund->id->toString());
        self::assertSame(RefundStatus::REFUNDED, $result->status);
    }

    #[Test]
    public function itIgnoresWhenAlreadyConfirmed(): void
    {
        // Given
        $refund = RefundBuilder::new()->confirmed()->create();
        $this->store($refund);

        // When
        $this->dispatch(new ConfirmRefund($refund->id->toString()));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $id = RefundId::forPayment(Uuid::uuid7()->toString())->toString();

        // Then
        $this->expectException(RefundNotFoundException::class);

        // When
        $this->dispatch(new ConfirmRefund($id));
    }
}
