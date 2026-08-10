<?php

declare(strict_types=1);

namespace Iam\Identity\Infrastructure\Security;

use Iam\Identity\Application\Security\ApiTokenGeneratorInterface;
use Iam\Identity\Application\Security\GeneratedApiToken;

final class ApiTokenGenerator implements ApiTokenGeneratorInterface
{
    public function generate(): GeneratedApiToken
    {
        return new GeneratedApiToken(
            identifier: 'key_'.bin2hex(random_bytes(8)),
            secret: bin2hex(random_bytes(32)),
        );
    }
}
