<?php

declare(strict_types=1);

namespace Cli\Console\Input;

use Shared\Application\Validation\ValidLabel;
use Symfony\Component\Console\Attribute\Option;

final class RegisterIdentityInput
{
    #[Option(description: 'A human-readable label for the issued API key, to tell it apart from other keys')]
    #[ValidLabel]
    public string $label = '';
}
