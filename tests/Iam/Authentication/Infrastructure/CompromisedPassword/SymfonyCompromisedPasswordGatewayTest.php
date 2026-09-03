<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\CompromisedPassword;

use Iam\Authentication\Domain\PasswordCredential\ValueObject\Password;
use Iam\Authentication\Infrastructure\CompromisedPassword\SymfonyCompromisedPasswordGateway;
use Iam\Tests\Authentication\Support\Double\StubFailingHttpClient;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\NotCompromisedPasswordValidator;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class SymfonyCompromisedPasswordGatewayTest extends TestCase
{
    #[Test]
    public function itAccepts(): void
    {
        // Given
        $validator = $this->createStub(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList());
        $gateway = new SymfonyCompromisedPasswordGateway($validator);

        // When
        $isCompromised = $gateway->isCompromised(Password::fromString('Marmoset-42-Zephyr!'));

        // Then
        self::assertFalse($isCompromised);
    }

    #[Test]
    public function itRefuses(): void
    {
        // Given
        $violation = $this->createStub(ConstraintViolationInterface::class);
        $validator = $this->createStub(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList([$violation]));
        $gateway = new SymfonyCompromisedPasswordGateway($validator);

        // When
        $isCompromised = $gateway->isCompromised(Password::fromString('Marmoset-42-Zephyr!'));

        // Then
        self::assertTrue($isCompromised);
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

    private function validatorWithFailingHttpClient(): ValidatorInterface
    {
        return Validation::createValidatorBuilder()
            ->setConstraintValidatorFactory(new ConstraintValidatorFactory([
                NotCompromisedPasswordValidator::class => new NotCompromisedPasswordValidator(new StubFailingHttpClient()),
            ]))
            ->getValidator();
    }
}
