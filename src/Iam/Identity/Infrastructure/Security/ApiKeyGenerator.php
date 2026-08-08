<?php

declare(strict_types=1);

namespace Iam\Identity\Infrastructure\Security;

use Iam\Identity\Application\Security\ApiKeyGeneratorInterface;
use Iam\Identity\Application\Security\GeneratedApiKey;

final class ApiKeyGenerator implements ApiKeyGeneratorInterface
{
    public function generate(): GeneratedApiKey
    {
        return new GeneratedApiKey(
            identifier: 'key_'.bin2hex(random_bytes(8)),
            secret: bin2hex(random_bytes(32)),
        );
    }
}
