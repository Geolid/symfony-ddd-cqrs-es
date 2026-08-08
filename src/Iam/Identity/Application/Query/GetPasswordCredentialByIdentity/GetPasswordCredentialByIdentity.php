<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Query\GetPasswordCredentialByIdentity;

use Iam\Identity\Application\Finder\PasswordCredential\PasswordCredentialResult;
use Shared\Application\Query\QueryInterface;

/**
 * @implements QueryInterface<PasswordCredentialResult>
 */
final readonly class GetPasswordCredentialByIdentity implements QueryInterface
{
    public function __construct(public string $identityId)
    {
    }
}
