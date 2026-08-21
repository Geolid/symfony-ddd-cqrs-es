<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Credential;

interface ApiKeyGeneratorInterface
{
    public function generate(): GeneratedApiKey;
}
