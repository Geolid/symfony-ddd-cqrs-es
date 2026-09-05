<?php

declare(strict_types=1);

namespace AfterSales\Return\Application\Command\ApproveWithdrawal;

use Shared\Application\Command\CommandInterface;

final readonly class ApproveWithdrawal implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
