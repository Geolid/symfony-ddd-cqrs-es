<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Command\ResetPassword;

use Shared\Application\Command\CommandInterface;

final readonly class ResetPassword implements CommandInterface
{
    public function __construct(
        public string $identityId,
        public string $code,
        #[\SensitiveParameter]
        public string $newPassword,
    ) {
    }
}
