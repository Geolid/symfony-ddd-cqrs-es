<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Command\IssueApiKeyCredential;

use Shared\Application\Command\ActingIdentityAware;
use Shared\Application\Command\CommandInterface;

final readonly class IssueApiKeyCredential implements CommandInterface, ActingIdentityAware
{
    public function __construct(
        public string $id,
        public string $identityId,
        public string $label,
        public string $keyId,
        #[\SensitiveParameter]
        public string $secret,
    ) {
    }

    public function actingIdentityId(): string
    {
        return $this->identityId;
    }
}
