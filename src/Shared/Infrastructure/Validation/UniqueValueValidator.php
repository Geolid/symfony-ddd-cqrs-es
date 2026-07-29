<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Validation;

use Shared\Domain\Service\UniqueValueRegistryInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class UniqueValueValidator extends ConstraintValidator
{
    public function __construct(private readonly UniqueValueRegistryInterface $registry)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidUniqueValue) {
            throw new UnexpectedTypeException($constraint, ValidUniqueValue::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!\is_string($value) && !$value instanceof \Stringable) {
            throw new UnexpectedValueException($value, 'string');
        }

        if ($this->registry->exists($constraint->type, (string) $value)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', (string) $value)
                ->setParameter('{{ type }}', $constraint->type->name)
                ->setCode(ValidUniqueValue::DOMAIN_UNIQUE_CONSTRAINT)
                ->addViolation();
        }
    }

    public function validatedBy(): string
    {
        return self::class;
    }
}
