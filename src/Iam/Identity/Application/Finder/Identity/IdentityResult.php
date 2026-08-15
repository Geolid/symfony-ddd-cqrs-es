<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Finder\Identity;

use Iam\Identity\Application\Status\IdentityStatus;
use Shared\Application\Result\ResultInterface;

final readonly class IdentityResult implements ResultInterface
{
    public function __construct(
        public string $id,
        public IdentityStatus $status,
        public \DateTimeImmutable $registeredAt,
    ) {
    }
}
