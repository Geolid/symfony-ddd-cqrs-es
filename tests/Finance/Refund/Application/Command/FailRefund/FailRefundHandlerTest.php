<?php

declare(strict_types=1);

namespace Finance\Tests\Refund\Application\Command\FailRefund;

use Finance\Refund\Application\Command\FailRefund\FailRefund;
use Finance\Refund\Application\Finder\Refund\RefundFinderInterface;
use Finance\Refund\Application\RefundStatus;
use Finance\Refund\Domain\Exception\RefundNotFoundException;
use Finance\Refund\Domain\ValueObject\RefundId;
use Finance\Tests\Refund\Support\Builder\RefundBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class FailRefundHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itFailsWhenInitiated(): void
    {
        // Given
        $refund = RefundBuilder::new()->create();
        $this->store($refund);

        // When
        $this->dispatch(new FailRefund($refund->id->toString()));

        // Then
        $result = $this->service(RefundFinderInterface::class)->ofId($refund->id->toString());
        self::assertSame(RefundStatus::FAILED, $result->status);
    }

    #[Test]
    public function itIgnoresWhenAlreadyConfirmed(): void
    {
        // Given
        $refund = RefundBuilder::new()->confirmed()->create();
        $this->store($refund);

        // When
        $this->dispatch(new FailRefund($refund->id->toString()));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenRefundNotFound(): void
    {
        // Given
        $id = RefundId::generate()->toString();

        // Then
        $this->expectException(RefundNotFoundException::class);

        // When
        $this->dispatch(new FailRefund($id));
    }
}
