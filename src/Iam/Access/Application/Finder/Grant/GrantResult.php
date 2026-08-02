<?php

declare(strict_types=1);

namespace Iam\Access\Application\Finder\Grant;

use Shared\Application\Query\Result\ResultInterface;

final readonly class GrantResult implements ResultInterface
{
    public function __construct(
        public string $id,
        public string $identityId,
        public string $permission,
        public bool $revoked,
    ) {
    }
}
