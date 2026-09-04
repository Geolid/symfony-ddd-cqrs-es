<?php

declare(strict_types=1);

namespace AfterSales\Withdrawal\Domain\ValueObject;

enum WithdrawalState: string
{
    case REQUESTED = 'requested';
    case RECEIVED = 'received';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    /**
     * @phpstan-pure
     */
    public function isReceived(): bool
    {
        return self::RECEIVED === $this;
    }

    /**
     * @phpstan-pure
     */
    public function isApproved(): bool
    {
        return self::APPROVED === $this;
    }

    /**
     * @phpstan-pure
     */
    public function isRejected(): bool
    {
        return self::REJECTED === $this;
    }
}
