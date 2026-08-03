<?php

declare(strict_types=1);

namespace Cli\Console\Input;

use Iam\Access\Application\Validation\ValidPermission;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Validator\Constraints as Assert;

final class RegisterIdentityInput
{
    /** @var list<string> */
    #[Option(description: 'A permission to grant, formatted "<subdomain>:<action>"; repeat for several')]
    #[Assert\All([new ValidPermission()])]
    public array $permission = [];

    #[Option(description: 'How many days until the issued API key expires')]
    #[Assert\Positive]
    public int $expiresInDays = 365;
}
