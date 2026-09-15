<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Validation;

use Iam\Authentication\Application\Validation\ValidPassword;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordStrengthInterface;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\Password;
use Iam\Tests\Authentication\Support\ValidatorFactoryTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\NotCompromisedPasswordValidator;
use Symfony\Component\Validator\Constraints\PasswordStrength;
use Symfony\Component\Validator\Test\CompoundConstraintTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @extends CompoundConstraintTestCase<ValidPassword>
 */
final class ValidPasswordTest extends CompoundConstraintTestCase
{
    use ValidatorFactoryTrait;

    #[Test]
    #[DataProvider('provideAcceptedValues')]
    public function itAccepts(string $password): void
    {
        // When
        $this->validateValue($password);

        // Then
        $this->assertNoViolation();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideAcceptedValues(): iterable
    {
        yield 'strong password' => ['Correct-Horse-Battery-42!'];
        yield 'maximum length' => [str_pad('Marmoset-42-Zephyr!', Password::MAX_LENGTH, '*')];
    }

    /**
     * @param list<Constraint> $rules
     */
    #[Test]
    #[DataProvider('provideRefusedValues')]
    public function itRefuses(mixed $password, array $rules): void
    {
        // When
        $this->validateValue($password);

        // Then
        $this->assertViolationsCount(\count($rules));
        $this->assertViolationsRaisedByCompound($rules);
    }

    /**
     * @return iterable<string, array{mixed, list<Constraint>}>
     */
    public static function provideRefusedValues(): iterable
    {
        yield 'empty string' => ['', [new Assert\NotBlank()]];
        yield 'not a string' => [42, [new Assert\Type('string')]];
        yield 'too short' => [str_repeat('a', Password::MIN_LENGTH - 1), [new Assert\Length(min: Password::MIN_LENGTH, max: Password::MAX_LENGTH)]];
        yield 'too weak' => ['passwordpassword', [new PasswordStrength(minScore: PasswordStrengthInterface::MIN_REQUIRED_SCORE)]];
    }

    #[Test]
    public function itSkipsWhenCompromisedCheckFails(): void
    {
        // Given
        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('request')->willThrowException(new TransportException('Simulated network failure.'));
        $validator = $this->validatorUsing(NotCompromisedPasswordValidator::class, new NotCompromisedPasswordValidator($httpClient));

        // When
        $violations = $validator->validate('Correct-Horse-Battery-42!', new ValidPassword());

        // Then
        self::assertCount(0, $violations);
    }

    protected function createCompound(): ValidPassword
    {
        return new ValidPassword();
    }

    protected function createValidator(): ValidatorInterface
    {
        // NotCompromisedPassword otherwise calls the real HIBP API over HTTP on every run.
        return $this->validatorUsing(NotCompromisedPasswordValidator::class, new NotCompromisedPasswordValidator(enabled: false));
    }
}
