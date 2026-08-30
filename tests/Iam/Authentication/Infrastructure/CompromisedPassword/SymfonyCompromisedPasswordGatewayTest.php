<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\CompromisedPassword;

use Iam\Authentication\Domain\PasswordCredential\ValueObject\Password;
use Iam\Authentication\Infrastructure\CompromisedPassword\SymfonyCompromisedPasswordGateway;
use Iam\Tests\Authentication\Support\Doubles\StubFailingHttpClient;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotCompromisedPasswordValidator;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class SymfonyCompromisedPasswordGatewayTest extends TestCase
{
    #[Test]
    public function itDetects(): void
    {
        // Given
        $compromised = new SymfonyCompromisedPasswordGateway($this->validatorStubbingNotCompromised(violates: true));
        $safe = new SymfonyCompromisedPasswordGateway($this->validatorStubbingNotCompromised(violates: false));

        // When
        $isCompromised = $compromised->isCompromised(Password::fromString('Marmoset-42-Zephyr!'));
        $isSafe = $safe->isCompromised(Password::fromString('Marmoset-42-Zephyr!'));

        // Then
        self::assertTrue($isCompromised);
        self::assertFalse($isSafe);
    }

    #[Test]
    public function itSkipsWhenCheckFails(): void
    {
        // Given
        $gateway = new SymfonyCompromisedPasswordGateway($this->validatorWithFailingHttpClient());

        // When
        $isCompromised = $gateway->isCompromised(Password::fromString('Marmoset-42-Zephyr!'));

        // Then
        self::assertFalse($isCompromised);
    }

    private function validatorStubbingNotCompromised(bool $violates): ValidatorInterface
    {
        return Validation::createValidatorBuilder()
            ->setConstraintValidatorFactory(new ConstraintValidatorFactory([
                NotCompromisedPasswordValidator::class => new StubNotCompromisedPasswordValidator($violates),
            ]))
            ->getValidator();
    }

    private function validatorWithFailingHttpClient(): ValidatorInterface
    {
        return Validation::createValidatorBuilder()
            ->setConstraintValidatorFactory(new ConstraintValidatorFactory([
                NotCompromisedPasswordValidator::class => new NotCompromisedPasswordValidator(new StubFailingHttpClient()),
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
