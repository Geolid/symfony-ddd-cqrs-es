<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Finder\Identity;

use Iam\Identity\Application\Enum\AppIdentityStatus;
use Shared\Application\Result\ResultInterface;

final readonly class IdentityResult implements ResultInterface
{
    public function __construct(
        public string $id,
        public AppIdentityStatus $status,
        public \DateTimeImmutable $registeredAt,
    ) {
    }
}
