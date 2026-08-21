<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\Security;

use Iam\Authentication\Domain\PasswordCredential\ValueObject\Password;
use Iam\Authentication\Infrastructure\Security\PasswordPolicy;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotCompromisedPasswordValidator;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class PasswordPolicyTest extends TestCase
{
    #[Test]
    public function itEvaluatesStrength(): void
    {
        // Given
        $policy = new PasswordPolicy(Validation::createValidator());

        // Then
        self::assertTrue($policy->isStrongEnough(Password::fromString('Xk9$mQ2vLp7&zR4w')));
        self::assertFalse($policy->isStrongEnough(Password::fromString(str_repeat('a', 12))));
    }

    #[Test]
    public function itDetectsCompromise(): void
    {
        // Given
        $compromised = new PasswordPolicy($this->validatorStubbingNotCompromised(violates: true));
        $safe = new PasswordPolicy($this->validatorStubbingNotCompromised(violates: false));

        // Then
        self::assertTrue($compromised->isCompromised(Password::fromString('Xk9$mQ2vLp7&zR4w')));
        self::assertFalse($safe->isCompromised(Password::fromString('Xk9$mQ2vLp7&zR4w')));
    }

    private function validatorStubbingNotCompromised(bool $violates): ValidatorInterface
    {
        return Validation::createValidatorBuilder()
            ->setConstraintValidatorFactory(new ConstraintValidatorFactory([
                NotCompromisedPasswordValidator::class => new StubNotCompromisedPasswordValidator($violates),
            ]))
            ->getValidator();
    }
}

final class StubNotCompromisedPasswordValidator extends ConstraintValidator
{
    public function __construct(private readonly bool $violates)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if ($this->violates) {
            $this->context->buildViolation('')->addViolation();
        }
    }
}
