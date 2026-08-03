<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Finder\ApiTokenCredential;

interface ApiTokenCredentialFinderInterface
{
    public function ofIdentifier(string $identifier): ?ApiTokenCredentialResult;
}
