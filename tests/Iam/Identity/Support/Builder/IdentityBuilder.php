<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Support\Builder;

use Iam\Identity\Domain\Identity;
use Iam\Identity\Domain\ValueObject\Email;
use Iam\Identity\Domain\ValueObject\FullName;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Iam\Identity\Domain\ValueObject\Reason;
use Ramsey\Uuid\Uuid;
use Shared\Tests\Support\Double\FakeCodeChallenger;
use Support\Builder\AbstractAggregateBuilder;
use Support\Faker\SeededFaker;
use Symfony\Component\Clock\Clock;

/**
 * @phpstan-type Attributes = array{
 *     id: IdentityId,
 *     fullName: FullName,
 *     email: Email,
 *     registeredAt: \DateTimeImmutable,
 *     confirmedAt: \DateTimeImmutable,
 *     confirmationCode: string,
 *     confirmationRequestedAt: \DateTimeImmutable,
 *     fullNameChangedAt: \DateTimeImmutable,
 *     emailChangeRequestedAt: \DateTimeImmutable,
 *     emailChangedAt: \DateTimeImmutable,
 *     reason: Reason,
 *     suspendedAt: \DateTimeImmutable,
 *     reactivatedAt: \DateTimeImmutable,
 *     requestedAt: \DateTimeImmutable,
 *     cancelledAt: \DateTimeImmutable,
 *     erasedAt: \DateTimeImmutable,
 * }
 *
 * @extends AbstractAggregateBuilder<Identity, Attributes>
 */
final class IdentityBuilder extends AbstractAggregateBuilder
{
    public function withId(string $id): self
    {
        return $this->withAttributes(id: IdentityId::fromString($id));
    }

    public function withFullName(string $fullName): self
    {
        return $this->withAttributes(fullName: FullName::fromString($fullName));
    }

    public function withEmail(string $email): self
    {
        return $this->withAttributes(email: Email::fromString($email));
    }

    public function withRegisteredAt(\DateTimeImmutable $registeredAt): self
    {
        return $this->withAttributes(registeredAt: $registeredAt);
    }

    public function confirmed(?string $confirmationCode = null, ?\DateTimeImmutable $confirmedAt = null): self
    {
        $builder = $this->withAttributes(...array_filter([
            'confirmationCode' => $confirmationCode,
            'confirmedAt' => $confirmedAt,
        ]));

        return $builder->withModifier(
            static fn (Identity $identity, self $builder) => $identity->confirm(
                $builder['confirmationCode'],
                new FakeCodeChallenger(),
                $builder['confirmedAt'],
            ),
        );
    }

    public function fullNameChanged(string $newFullName, ?\DateTimeImmutable $fullNameChangedAt = null): self
    {
        $builder = null !== $fullNameChangedAt ? $this->withAttributes(fullNameChangedAt: $fullNameChangedAt) : $this;

        return $builder->withModifier(
            static fn (Identity $identity, self $builder) => $identity->changeFullName(FullName::fromString($newFullName), $builder['fullNameChangedAt']),
        );
    }

    public function emailChangeRequested(string $newEmail, ?\DateTimeImmutable $requestedAt = null): self
    {
        $builder = null !== $requestedAt ? $this->withAttributes(emailChangeRequestedAt: $requestedAt) : $this;

        return $builder->withModifier(
            static fn (Identity $identity, self $builder) => $identity->requestEmailChange(Email::fromString($newEmail), $builder['emailChangeRequestedAt']),
        );
    }

    public function emailChanged(string $newEmail, ?\DateTimeImmutable $changedAt = null): self
    {
        $builder = null !== $changedAt ? $this->withAttributes(emailChangedAt: $changedAt) : $this;

        return $builder->withModifier(
            static fn (Identity $identity, self $builder) => $identity->changeEmail(FakeCodeChallenger::CODE, new FakeCodeChallenger(), Email::fromString($newEmail), $builder['emailChangedAt']),
        );
    }

    public function suspended(?string $reason = null, ?\DateTimeImmutable $suspendedAt = null): self
    {
        $builder = $this->withAttributes(...array_filter([
            'reason' => null !== $reason ? Reason::fromString($reason) : null,
            'suspendedAt' => $suspendedAt,
        ]));

        return $builder->withModifier(
            static fn (Identity $identity, self $builder) => $identity->suspend($builder['reason'], $builder['suspendedAt']),
        );
    }

    public function reactivated(?string $reason = null, ?\DateTimeImmutable $reactivatedAt = null): self
    {
        $builder = $this->withAttributes(...array_filter([
            'reason' => null !== $reason ? Reason::fromString($reason) : null,
            'reactivatedAt' => $reactivatedAt,
        ]));

        return $builder->withModifier(
            static fn (Identity $identity, self $builder) => $identity->reactivate($builder['reason'], $builder['reactivatedAt']),
        );
    }

    public function confirmationRequested(?\DateTimeImmutable $requestedAt = null): self
    {
        $builder = null !== $requestedAt ? $this->withAttributes(confirmationRequestedAt: $requestedAt) : $this;

        return $builder->withModifier(
            static fn (Identity $identity, self $builder) => $identity->requestConfirmation($builder['confirmationRequestedAt']),
        );
    }

    public function erasureRequested(?\DateTimeImmutable $requestedAt = null): self
    {
        $builder = null !== $requestedAt ? $this->withAttributes(requestedAt: $requestedAt) : $this;

        return $builder->withModifier(
            static fn (Identity $identity, self $builder) => $identity->requestErasure($builder['requestedAt']),
        );
    }

    public function erasureCancelled(?\DateTimeImmutable $cancelledAt = null): self
    {
        $builder = null !== $cancelledAt ? $this->withAttributes(cancelledAt: $cancelledAt) : $this;

        return $builder->withModifier(
            static fn (Identity $identity, self $builder) => $identity->cancelErasure($builder['cancelledAt']),
        );
    }

    public function erased(?\DateTimeImmutable $erasedAt = null): self
    {
        $builder = null !== $erasedAt ? $this->withAttributes(erasedAt: $erasedAt) : $this;

        return $builder->withModifier(
            static fn (Identity $identity, self $builder) => $identity->erase($builder['erasedAt']),
        );
    }

    protected static function defaults(): array
    {
        $now = Clock::get()->now();

        return [
            'id' => static fn (): IdentityId => IdentityId::fromString(Uuid::uuid7()->toString()),
            'fullName' => static fn (): FullName => FullName::fromString(SeededFaker::get()->name()),
            'email' => static fn (): Email => Email::fromString(SeededFaker::get()->unique()->safeEmail()),
            'registeredAt' => static fn (): \DateTimeImmutable => $now,
            'confirmedAt' => static fn (): \DateTimeImmutable => $now->modify('+1 hour'),
            'confirmationCode' => static fn (): string => FakeCodeChallenger::CODE,
            'confirmationRequestedAt' => static fn (): \DateTimeImmutable => $now->modify('+30 minutes'),
            'fullNameChangedAt' => static fn (): \DateTimeImmutable => $now->modify('+45 minutes'),
            'emailChangeRequestedAt' => static fn (): \DateTimeImmutable => $now->modify('+50 minutes'),
            'emailChangedAt' => static fn (): \DateTimeImmutable => $now->modify('+55 minutes'),
            'reason' => static fn (): Reason => Reason::fromString(SeededFaker::get()->sentence(4)),
            'suspendedAt' => static fn (): \DateTimeImmutable => $now->modify('+1 day'),
            'reactivatedAt' => static fn (): \DateTimeImmutable => $now->modify('+2 day'),
            'requestedAt' => static fn (): \DateTimeImmutable => $now->modify('+3 day'),
            'cancelledAt' => static fn (): \DateTimeImmutable => $now->modify('+4 day'),
            'erasedAt' => static fn (): \DateTimeImmutable => $now->modify('+5 day'),
        ];
    }

    protected function build(): Identity
    {
        return Identity::register(
            id: $this['id'],
            fullName: $this['fullName'],
            email: $this['email'],
            registeredAt: $this['registeredAt'],
        );
    }
}
