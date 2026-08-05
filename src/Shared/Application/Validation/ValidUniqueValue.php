<?php

declare(strict_types=1);

namespace Shared\Application\Validation;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class ValidUniqueValue extends Constraint
{
    public const string DOMAIN_UNIQUE_CONSTRAINT = 'd07b3b72-74c1-4b7b-b1a8-c89b27521c7a';

    protected const array ERROR_NAMES = [
        self::DOMAIN_UNIQUE_CONSTRAINT => 'DOMAIN_UNIQUE_CONSTRAINT',
    ];

    public string $message = 'Value "{{ value }}" is already in use for {{ type }}.';

    public function __construct(
        public \BackedEnum $type,
        mixed $options = null,
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct($options, $groups, $payload);
    }

    public function validatedBy(): string
    {
        return UniqueValueValidator::class;
    }
}
