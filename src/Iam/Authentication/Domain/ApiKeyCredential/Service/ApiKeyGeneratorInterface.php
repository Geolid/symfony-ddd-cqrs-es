<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\ApiKeyCredential\Service;

interface ApiKeyGeneratorInterface
{
    public const int SECRET_BYTES = 32;

    public function generate(): GeneratedApiKey;
}
