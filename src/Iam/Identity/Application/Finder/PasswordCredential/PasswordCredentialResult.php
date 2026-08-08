<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Finder\PasswordCredential;

use Iam\Identity\Application\Enum\AppIdentityStatus;
use Shared\Application\Result\ResultInterface;

final readonly class PasswordCredentialResult implements ResultInterface
{
    public function __construct(
        public string $id,
        public string $identityId,
        public string $login,
        public string $hash,
        public AppIdentityStatus $identityStatus,
    ) {
    }
}
