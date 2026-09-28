<?php

declare(strict_types=1);

namespace Shared\Application\Validation;

use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniquenessRegistryInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Webmozart\Assert\Assert;

final class UniqueValueValidator extends ConstraintValidator
{
    public function __construct(private readonly UniquenessRegistryInterface $uniqueness)
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

        $key = UniqueKey::for($constraint->key, ...$constraint->scope);

        $excludeSubjectId = null;
        if (null !== $constraint->excludeSubjectIdPropertyPath) {
            // ?? null: isset()-style access is exempt from PHP's uninitialized-typed-property error, plain access isn't.
            $excludeSubjectId = $this->context->getObject()->{$constraint->excludeSubjectIdPropertyPath} ?? null;
            Assert::string($excludeSubjectId);
        }

        if ($this->uniqueness->isClaimed($key, (string) $value, $excludeSubjectId)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', (string) $value)
                ->setCode(ValidUniqueValue::DOMAIN_UNIQUE_CONSTRAINT)
                ->addViolation();
        }
    }
}
