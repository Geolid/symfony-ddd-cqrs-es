<?php

declare(strict_types=1);

namespace Iam\Authentication\Infrastructure\Credential;

use Iam\Authentication\Application\Credential\CompromisedPasswordGatewayInterface;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\Password;
use Symfony\Component\Validator\Constraints\NotCompromisedPassword;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class CompromisedPasswordGateway implements CompromisedPasswordGatewayInterface
{
    public function __construct(private ValidatorInterface $validator)
    {
    }

    public function isCompromised(#[\SensitiveParameter] Password $password): bool
    {
        $violations = $this->validator->validate($password->value, new NotCompromisedPassword(skipOnError: true));

        return $violations->count() > 0;
    }
}
