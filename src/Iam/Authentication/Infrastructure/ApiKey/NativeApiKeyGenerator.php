<?php

declare(strict_types=1);

namespace Iam\Authentication\Infrastructure\ApiKey;

use Iam\Authentication\Domain\ApiKeyCredential\Service\ApiKeyGeneratorInterface;
use Iam\Authentication\Domain\ApiKeyCredential\Service\GeneratedApiKey;
use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\KeyId;

final readonly class NativeApiKeyGenerator implements ApiKeyGeneratorInterface
{
    public function generate(): GeneratedApiKey
    {
        $keyIdRandomBytes = (int) ((KeyId::LENGTH - \strlen(KeyId::PREFIX)) / 2);

        return new GeneratedApiKey(
            keyId: KeyId::PREFIX.bin2hex(random_bytes($keyIdRandomBytes)),
            secret: bin2hex(random_bytes(self::SECRET_BYTES)),
        );
    }
}
