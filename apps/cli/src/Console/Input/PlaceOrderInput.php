<?php

declare(strict_types=1);

namespace Cli\Console\Input;

use Sales\Customer\Application\Validation\ValidCustomerId;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Validator\Constraints as Assert;

final class PlaceOrderInput
{
    public const string LINE_PATTERN = '/^(?<label>.+):(?<quantity>[0-9]+):(?<euros>[0-9]+)(?:\.(?<cents>[0-9]{2}))?$/';

    #[Argument(description: 'The customer placing the order')]
    #[ValidCustomerId]
    public string $customerId;

    /** @var list<string> */
    #[Option(description: 'An order line, formatted "<label>:<quantity>:<unit amount in euros, e.g. 17.50>"; repeat for several lines')]
    #[Assert\Count(min: 1)]
    #[Assert\All([new Assert\Regex(self::LINE_PATTERN)])]
    public array $line = [];
}
