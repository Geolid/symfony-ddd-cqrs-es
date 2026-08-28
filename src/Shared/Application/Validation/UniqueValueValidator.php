<?php

declare(strict_types=1);

namespace Shared\Application\Validation;

use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniqueValueRegistryInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Webmozart\Assert\Assert;

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

        $key = UniqueKey::for($constraint->key, ...$constraint->scope);

        $excludeOwnerId = null;
        if (null !== $constraint->excludeOwnerIdPropertyPath) {
            // ?? null: isset()-style access is exempt from PHP's uninitialized-typed-property error, plain access isn't.
            $excludeOwnerId = $this->context->getObject()->{$constraint->excludeOwnerIdPropertyPath} ?? null;
            Assert::string($excludeOwnerId);
        }

        if ($this->registry->exists($key, (string) $value, $excludeOwnerId)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', (string) $value)
                ->setParameter('{{ key }}', $key->discriminator->name)
                ->setCode(ValidUniqueValue::DOMAIN_UNIQUE_CONSTRAINT)
                ->addViolation();
        }
    }
}
