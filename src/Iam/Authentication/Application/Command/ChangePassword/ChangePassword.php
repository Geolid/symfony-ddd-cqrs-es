<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Command\ChangePassword;

use Shared\Application\Command\CommandInterface;

final readonly class ChangePassword implements CommandInterface
{
    public function __construct(
        public string $identityId,
        #[\SensitiveParameter]
        public string $password,
    ) {
    }
}
