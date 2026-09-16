<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\Password;

use Iam\Authentication\Domain\PasswordCredential\ValueObject\Password;
use Iam\Authentication\Infrastructure\Password\SymfonyPasswordStrengthSpecification;
use PHPUnit\Framework\Attributes\DataProvider;
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
    #[DataProvider('providePasswords')]
    public function itIsSatisfiedBy(string $rawPassword, bool $expected): void
    {
        // When
        $isSatisfied = $this->passwordStrength->isSatisfiedBy(Password::fromString($rawPassword));

        // Then
        self::assertSame($expected, $isSatisfied);
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
