<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Infrastructure\Security;

use Iam\Identity\Domain\ValueObject\Password;
use Iam\Identity\Infrastructure\Security\PasswordPolicy;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class PasswordPolicyTest extends TestCase
{
    #[Test]
    public function itAcceptsAStrongPassword(): void
    {
        // Given
        $policy = new PasswordPolicy(Validation::createValidator());

        // When
        $strongEnough = $policy->isStrongEnough(Password::fromString('MyStr0ngP@ssw0rd123!'));

        // Then
        self::assertTrue($strongEnough);
    }

    #[Test]
    public function itRejectsAWeakPassword(): void
    {
        // Given
        $policy = new PasswordPolicy(Validation::createValidator());

        // When
        $strongEnough = $policy->isStrongEnough(Password::fromString('passwordpassword'));

        // Then
        self::assertFalse($strongEnough);
    }
}
