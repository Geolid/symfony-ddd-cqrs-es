<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Application\Validation;

use PHPUnit\Framework\Attributes\Test;
use Sales\Customer\Application\Validation\ValidUniqueCustomerEmail;
use Sales\Customer\Domain\ValueObject\CustomerUniqueKey;
use Shared\Application\Validation\UniqueValueValidator;
use Shared\Application\Validation\ValidUniqueValue;
use Shared\Domain\Exception\UniqueValueAlreadyTakenException;
use Shared\Domain\Service\UniqueValueRegistryInterface;
use Shared\Domain\ValueObject\UniqueKey;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\Test\CompoundConstraintTestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @extends CompoundConstraintTestCase<ValidUniqueCustomerEmail>
 */
final class ValidUniqueCustomerEmailTest extends CompoundConstraintTestCase
{
    private DummyUniqueValueRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new DummyUniqueValueRegistry();

        parent::setUp();
    }

    #[Test]
    public function itAccepts(): void
    {
        // When
        $this->validateValue('buyer@example.com');

        // Then
        $this->assertNoViolation();
    }

    #[Test]
    public function itRefusesAnEmailAlreadyReserved(): void
    {
        // Given
        $this->registry->reserve(UniqueKey::for(CustomerUniqueKey::EMAIL), 'buyer@example.com', 'owner-id');

        // When
        $this->validateValue('buyer@example.com');

        // Then
        $this->assertViolationsCount(1);
        $this->assertViolationsRaisedByCompound([new ValidUniqueValue(CustomerUniqueKey::EMAIL)]);
    }

    protected function createCompound(): ValidUniqueCustomerEmail
    {
        return new ValidUniqueCustomerEmail();
    }

    protected function createValidator(): ValidatorInterface
    {
        return Validation::createValidatorBuilder()
            ->setConstraintValidatorFactory(new ConstraintValidatorFactory([
                UniqueValueValidator::class => new UniqueValueValidator($this->registry),
            ]))
            ->getValidator();
    }
}

final class DummyUniqueValueRegistry implements UniqueValueRegistryInterface
{
    /** @var array<string, string> */
    private array $reserved = [];

    public function reserve(UniqueKey $key, string $value, string $ownerId, ?string $subjectId = null): void
    {
        if ($this->exists($key, $value)) {
            throw UniqueValueAlreadyTakenException::forValue($key, $value);
        }

        $this->reserved[self::normalize($key, $value)] = $ownerId;
    }

    public function release(UniqueKey $key, string $value, string $ownerId): void
    {
        unset($this->reserved[self::normalize($key, $value)]);
    }

    public function exists(UniqueKey $key, string $value, ?string $excludeOwnerId = null): bool
    {
        $existingOwnerId = $this->reserved[self::normalize($key, $value)] ?? null;

        if (null === $existingOwnerId) {
            return false;
        }

        return $existingOwnerId !== $excludeOwnerId;
    }

    public function releaseAllForSubject(string $subjectId): void
    {
    }

    private static function normalize(UniqueKey $key, string $value): string
    {
        return \sprintf('%s:%s', $key->toString(), $value);
    }
}
