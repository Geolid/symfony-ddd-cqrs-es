<?php

declare(strict_types=1);

namespace Shared\Application\Validation;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class ValueObjectValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidValueObject) {
            throw new UnexpectedTypeException($constraint, ValidValueObject::class);
        }

        if (null === $value || '' === $value || [] === $value) {
            return;
        }

        $args = \is_array($value) ? $value : [$value];

        try {
            ($constraint->class)::{$constraint->method}(...$args);
        } catch (\InvalidArgumentException|\TypeError|\ValueError $e) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ reason }}', $e->getMessage())
                ->setCode(ValidValueObject::DOMAIN_VALIDATION_ERROR)
                ->addViolation();
        }
    }
}
