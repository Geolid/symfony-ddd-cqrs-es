<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\Password;

use Iam\Authentication\Domain\PasswordCredential\ValueObject\Password;
use Iam\Authentication\Infrastructure\Password\SymfonyPasswordStrength;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class SymfonyPasswordStrengthTest extends TestCase
{
    #[Test]
    public function itEvaluates(): void
    {
        // Given
        $passwordStrength = new SymfonyPasswordStrength(Validation::createValidator());

        // When
        $strong = $passwordStrength->isSufficient(Password::fromString('Marmoset-42-Zephyr!'));
        $weak = $passwordStrength->isSufficient(Password::fromString(str_repeat('a', 12)));

        // Then
        self::assertTrue($strong);
        self::assertFalse($weak);
    }
}
