<?php

declare(strict_types=1);

namespace Shared\Application\Validation;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
final class ValidValueObject extends Constraint
{
    public const string DOMAIN_VALIDATION_ERROR = 'f4a13d78-b1a8-4c47-920f-62024b3353b1';

    protected const array ERROR_NAMES = [
        self::DOMAIN_VALIDATION_ERROR => 'DOMAIN_VALIDATION_ERROR',
    ];

    public string $message = 'Domain validation failed: {{ reason }}';

    /**
     * @param class-string $class
     */
    public function __construct(
        public readonly string $class,
        public readonly string $method,
        mixed $options = null,
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct($options, $groups, $payload);
    }

    public function validatedBy(): string
    {
        return ValueObjectValidator::class;
    }
}
