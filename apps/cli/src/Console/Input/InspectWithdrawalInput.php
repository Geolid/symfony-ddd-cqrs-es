<?php

declare(strict_types=1);

namespace Cli\Console\Input;

use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Validator\Constraints as Assert;

final class InspectWithdrawalInput
{
    #[Argument(description: 'ID of the Order whose withdrawal was received')]
    #[Assert\Uuid]
    public string $orderId;

    #[Option(description: 'Approve the withdrawal (quality control passed)')]
    public bool $approve = false;

    #[Option(description: 'Reject the withdrawal, with a reason (quality control failed)')]
    public ?string $reject = null;
}
