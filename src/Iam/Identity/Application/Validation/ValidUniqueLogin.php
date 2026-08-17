<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Validation;

use Iam\Identity\Domain\ValueObject\PasswordCredentialUniqueKey;
use Shared\Application\Validation\ValidUniqueValue;
use Symfony\Component\Validator\Constraints\Compound;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
final class ValidUniqueLogin extends Compound
{
    protected function getConstraints(array $options): array
    {
        return [
            new ValidUniqueValue(PasswordCredentialUniqueKey::LOGIN),
        ];
    }
}
