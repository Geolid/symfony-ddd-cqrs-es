<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Command\ConfirmTotpEnrollment;

use Shared\Application\Command\CommandInterface;

final readonly class ConfirmTotpEnrollment implements CommandInterface
{
    public function __construct(
        public string $id,
        public string $identityId,
        #[\SensitiveParameter]
        public string $code,
    ) {
    }
}
