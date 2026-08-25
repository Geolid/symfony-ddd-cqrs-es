<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\Security;

use Iam\Authentication\Domain\PasswordCredential\ValueObject\Password;
use Iam\Authentication\Infrastructure\Security\PasswordStrength;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class PasswordStrengthTest extends TestCase
{
    #[Test]
    public function itEvaluates(): void
    {
        // Given
        $passwordStrength = new PasswordStrength(Validation::createValidator());

        // When
        $strong = $passwordStrength->isSufficient(Password::fromString('Xk9$mQ2vLp7&zR4w'));
        $weak = $passwordStrength->isSufficient(Password::fromString(str_repeat('a', 12)));

        // Then
        self::assertTrue($strong);
        self::assertFalse($weak);
    }
}
