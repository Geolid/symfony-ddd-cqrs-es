<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Finder\PasswordCredential;

use Shared\Application\Result\ResultInterface;

final readonly class PasswordCredentialResult implements ResultInterface
{
    public function __construct(
        public string $id,
        public string $identityId,
        public string $login,
        public string $hash,
        public bool $authenticatable,
    ) {
    }
}
