<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Finder\ApiTokenCredential;

use Shared\Application\Finder\FinderInterface;

interface ApiTokenCredentialFinderInterface extends FinderInterface
{
    public function ofIdentifier(string $identifier): ?ApiTokenCredentialResult;
}
