<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\BreachDatabase;

use Iam\Authentication\Domain\PasswordCredential\ValueObject\Password;
use Iam\Authentication\Infrastructure\BreachDatabase\SymfonyCompromisedPasswordGateway;
use Iam\Tests\Authentication\Support\ValidatorFactoryTrait;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\Validator\Constraints\NotCompromisedPasswordValidator;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class SymfonyCompromisedPasswordGatewayTest extends TestCase
{
    use ValidatorFactoryTrait;

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
        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('request')->willThrowException(new TransportException('Simulated network failure.'));

        return $this->validatorUsing(NotCompromisedPasswordValidator::class, new NotCompromisedPasswordValidator($httpClient));
    }
}
