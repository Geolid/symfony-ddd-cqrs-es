<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Credential;

interface ApiTokenGeneratorInterface
{
    public function generate(): GeneratedApiToken;
}
