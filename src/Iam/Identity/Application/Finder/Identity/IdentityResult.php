<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Finder\Identity;

use Shared\Application\Query\Result\ResultInterface;

final readonly class IdentityResult implements ResultInterface
{
    public function __construct(
        public string $id,
        public string $status,
        public \DateTimeImmutable $registeredAt,
    ) {
    }
}
