<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\Password;

use Iam\Authentication\Domain\PasswordCredential\ValueObject\Password;
use Iam\Authentication\Infrastructure\Password\SymfonyPasswordStrength;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class SymfonyPasswordStrengthTest extends TestCase
{
    private SymfonyPasswordStrength $passwordStrength;

    protected function setUp(): void
    {
        parent::setUp();

        $this->passwordStrength = new SymfonyPasswordStrength(Validation::createValidator());
    }

    #[Test]
    #[DataProvider('providePasswords')]
    public function itEvaluates(string $rawPassword, bool $expected): void
    {
        // When
        $isSufficient = $this->passwordStrength->isSufficient(Password::fromString($rawPassword));

        // Then
        self::assertSame($expected, $isSufficient);
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function providePasswords(): iterable
    {
        yield 'sufficient password' => ['Marmoset-42-Zephyr!', true];
        yield 'weak password' => [str_repeat('a', Password::MIN_LENGTH), false];
    }
}
