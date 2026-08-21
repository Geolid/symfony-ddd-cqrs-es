<?php

declare(strict_types=1);

namespace Iam\Authentication\Infrastructure\Security;

use Iam\Authentication\Application\Credential\ApiKeyGeneratorInterface;
use Iam\Authentication\Application\Credential\GeneratedApiKey;
use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\KeyId;

final class ApiKeyGenerator implements ApiKeyGeneratorInterface
{
    public function generate(): GeneratedApiKey
    {
        return new GeneratedApiKey(
            keyId: KeyId::PREFIX.bin2hex(random_bytes(8)),
            secret: bin2hex(random_bytes(32)),
        );
    }
}
