<?php

declare(strict_types=1);

namespace Shared\Application\Validation;

use Shared\Domain\ValueObject\PostalAddress;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Compound;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
final class ValidRecipientName extends Compound
{
    protected function getConstraints(array $options): array
    {
        return [
            new Assert\NotBlank(normalizer: 'trim'),
            new Assert\Length(max: PostalAddress::RECIPIENT_NAME_MAX_LENGTH),
        ];
    }
}
