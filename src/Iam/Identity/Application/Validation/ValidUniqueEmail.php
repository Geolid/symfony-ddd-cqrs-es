<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Validation;

use Iam\Identity\Application\IdentityUniqueKey;
use Shared\Application\Validation\ValidUniqueValue;
use Symfony\Component\Validator\Constraints\Compound;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
final class ValidUniqueEmail extends Compound
{
    protected function getConstraints(array $options): array
    {
        return [
            new ValidUniqueValue(IdentityUniqueKey::EMAIL, message: 'error_email_already_in_use'),
        ];
    }
}
