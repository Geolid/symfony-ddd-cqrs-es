<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Credential\ApiKey;

use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\KeyId;

final class ApiKeyGenerator
{
    public function generate(): GeneratedApiKey
    {
        return new GeneratedApiKey(
            keyId: KeyId::PREFIX.bin2hex(random_bytes(8)),
            secret: bin2hex(random_bytes(32)),
        );
    }
}
