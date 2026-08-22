<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Validation;

use Iam\Authentication\Application\Validation\ValidPassword;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\NotCompromisedPasswordValidator;
use Symfony\Component\Validator\Constraints\PasswordStrength;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\Test\CompoundConstraintTestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

/**
 * @extends CompoundConstraintTestCase<ValidPassword>
 */
final class ValidPasswordTest extends CompoundConstraintTestCase
{
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
        yield 'strong password' => ['MyStr0ngP@ssw0rd123!'];
        yield 'maximum length' => [str_repeat('Xk9$mQ2vLp7&zR4w', 256)];
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
        yield 'empty string' => ['', [new Assert\NotBlank(), self::length(), self::passwordStrength()]];
        yield 'not a string' => [42, [new Assert\Type('string'), self::length(), self::passwordStrength()]];
        yield 'too short' => ['Sh0rt!', [self::length(), self::passwordStrength()]];
        yield 'too weak' => ['passwordpassword', [self::passwordStrength()]];
    }

    #[Test]
    public function itSkipsWhenCompromisedCheckFails(): void
    {
        // Given
        $validator = $this->validatorWith(new NotCompromisedPasswordValidator(new StubFailingHttpClient()));

        // When
        $violations = $validator->validate('MyStr0ngP@ssw0rd123!', new ValidPassword());

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
        return $this->validatorWith(new NotCompromisedPasswordValidator(enabled: false));
    }

    private function validatorWith(NotCompromisedPasswordValidator $notCompromisedPasswordValidator): ValidatorInterface
    {
        return Validation::createValidatorBuilder()
            ->setConstraintValidatorFactory(new ConstraintValidatorFactory([
                NotCompromisedPasswordValidator::class => $notCompromisedPasswordValidator,
            ]))
            ->getValidator();
    }

    private static function length(): Assert\Length
    {
        return new Assert\Length(min: 12, max: 4_096);
    }

    private static function passwordStrength(): PasswordStrength
    {
        return new PasswordStrength(minScore: PasswordStrength::STRENGTH_STRONG);
    }
}

final class StubFailingHttpClient implements HttpClientInterface
{
    /**
     * @param array<string, mixed> $options
     */
    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        throw new TransportException('Simulated network failure.');
    }

    public function stream(ResponseInterface|iterable $responses, ?float $timeout = null): ResponseStreamInterface
    {
        throw new TransportException('Simulated network failure.');
    }

    /**
     * @param array<string, mixed> $options
     */
    public function withOptions(array $options): static
    {
        return $this;
    }
}
