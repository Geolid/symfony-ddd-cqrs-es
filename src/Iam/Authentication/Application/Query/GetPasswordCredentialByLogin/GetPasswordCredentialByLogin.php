<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Query\GetPasswordCredentialByLogin;

use Iam\Authentication\Application\Finder\PasswordCredential\PasswordCredentialResult;
use Shared\Application\Query\QueryInterface;

/**
 * @implements QueryInterface<PasswordCredentialResult>
 */
final readonly class GetPasswordCredentialByLogin implements QueryInterface
{
    public function __construct(public string $login)
    {
    }
}
