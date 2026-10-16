<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\Password;

use Iam\Authentication\Domain\PasswordCredential\ValueObject\Password;
use Iam\Authentication\Infrastructure\Password\SymfonyPasswordStrengthSpecification;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class SymfonyPasswordStrengthSpecificationTest extends TestCase
{
    private SymfonyPasswordStrengthSpecification $passwordStrength;

    protected function setUp(): void
    {
        parent::setUp();

        $this->passwordStrength = new SymfonyPasswordStrengthSpecification(Validation::createValidator());
    }

    #[Test]
    public function itAcceptsWhenPasswordStrong(): void
    {
        // When
        $result = $this->passwordStrength->isSatisfiedBy(Password::fromString('Marmoset-42-Zephyr!'));

        // Then
        self::assertTrue($result);
    }

    #[Test]
    public function itRefusesWhenPasswordWeak(): void
    {
        // When
        $result = $this->passwordStrength->isSatisfiedBy(Password::fromString(str_repeat('a', Password::MIN_LENGTH)));

        // Then
        self::assertFalse($result);
    }
}
