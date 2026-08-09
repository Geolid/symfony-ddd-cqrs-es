<?php

declare(strict_types=1);

namespace Cli\Console\Input;

use Iam\Access\Application\Validation\ValidPermissions;
use Iam\Identity\Application\Validation\ValidLabel;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Validator\Constraints as Assert;

final class RegisterIdentityInput
{
    #[Option(description: 'A human-readable label for the issued API key, to tell it apart from other keys')]
    #[ValidLabel]
    public string $label = '';

    /** @var list<string> */
    #[Option(description: 'A permission to grant, formatted "<subdomain>.<bc>:<action>"; repeat for several')]
    #[ValidPermissions]
    public array $permission = [];

    #[Option(description: 'How many days until the issued API key expires')]
    #[Assert\Positive]
    public int $expiresInDays = 365;
}
