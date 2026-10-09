<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Support\Factory;

use Iam\Identity\Domain\Identity;
use Iam\Identity\Domain\ValueObject\Email;
use Iam\Identity\Domain\ValueObject\FullName;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Iam\Identity\Domain\ValueObject\Reason;
use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Shared\Tests\Support\Double\FakeCodeChallenger;
use Support\Foundry\AbstractAggregateFactory;
use Symfony\Component\Clock\Clock;

/**
 * @phpstan-type Inputs = array{
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
 * @extends AbstractAggregateFactory<Identity, Inputs>
 */
final class IdentityFactory extends AbstractAggregateFactory
{
    public static function class(): string
    {
        return Identity::class;
    }

    public function withId(string $id): self
    {
        return $this->with(['id' => IdentityIdFactory::new(['id' => $id])]);
    }

    public function withFullName(string $fullName): self
    {
        return $this->with(['fullName' => FullNameFactory::new(['value' => $fullName])]);
    }

    public function withEmail(string $email): self
    {
        return $this->with(['email' => EmailFactory::new(['value' => $email])]);
    }

    public function withRegisteredAt(\DateTimeImmutable $registeredAt): self
    {
        return $this->with(['registeredAt' => $registeredAt]);
    }

    public function confirmed(?string $confirmationCode = null, ?\DateTimeImmutable $confirmedAt = null): self
    {
        return $this->with(array_filter(['confirmationCode' => $confirmationCode, 'confirmedAt' => $confirmedAt]))->afterInstantiate(
            static fn (Identity $identity, array $inputs) => $identity->confirm($inputs['confirmationCode'], new FakeCodeChallenger(), $inputs['confirmedAt']),
        );
    }

    public function fullNameChanged(string $newFullName, ?\DateTimeImmutable $fullNameChangedAt = null): self
    {
        return $this->with(array_filter(['fullNameChangedAt' => $fullNameChangedAt]))->afterInstantiate(
            static fn (Identity $identity, array $inputs) => $identity->changeFullName(FullName::fromString($newFullName), $inputs['fullNameChangedAt']),
        );
    }

    public function emailChangeRequested(string $newEmail, ?\DateTimeImmutable $requestedAt = null): self
    {
        return $this->with(array_filter(['emailChangeRequestedAt' => $requestedAt]))->afterInstantiate(
            static fn (Identity $identity, array $inputs) => $identity->requestEmailChange(Email::fromString($newEmail), $inputs['emailChangeRequestedAt']),
        );
    }

    public function emailChanged(string $newEmail, ?\DateTimeImmutable $changedAt = null): self
    {
        return $this->with(array_filter(['emailChangedAt' => $changedAt]))->afterInstantiate(
            static fn (Identity $identity, array $inputs) => $identity->changeEmail(FakeCodeChallenger::CODE, new FakeCodeChallenger(), Email::fromString($newEmail), $inputs['emailChangedAt']),
        );
    }

    public function suspended(?string $reason = null, ?\DateTimeImmutable $suspendedAt = null): self
    {
        return $this->with(array_filter([
            'reason' => null !== $reason ? Reason::fromString($reason) : null,
            'suspendedAt' => $suspendedAt,
        ]))->afterInstantiate(
            static fn (Identity $identity, array $inputs) => $identity->suspend($inputs['reason'], $inputs['suspendedAt']),
        );
    }

    public function reactivated(?string $reason = null, ?\DateTimeImmutable $reactivatedAt = null): self
    {
        return $this->with(array_filter([
            'reason' => null !== $reason ? Reason::fromString($reason) : null,
            'reactivatedAt' => $reactivatedAt,
        ]))->afterInstantiate(
            static fn (Identity $identity, array $inputs) => $identity->reactivate($inputs['reason'], $inputs['reactivatedAt']),
        );
    }

    public function confirmationRequested(?\DateTimeImmutable $requestedAt = null): self
    {
        return $this->with(array_filter(['confirmationRequestedAt' => $requestedAt]))->afterInstantiate(
            static fn (Identity $identity, array $inputs) => $identity->requestConfirmation($inputs['confirmationRequestedAt']),
        );
    }

    public function erasureRequested(?\DateTimeImmutable $requestedAt = null): self
    {
        return $this->with(array_filter(['requestedAt' => $requestedAt]))->afterInstantiate(
            static fn (Identity $identity, array $inputs) => $identity->requestErasure($inputs['requestedAt']),
        );
    }

    public function erasureCancelled(?\DateTimeImmutable $cancelledAt = null): self
    {
        return $this->with(array_filter(['cancelledAt' => $cancelledAt]))->afterInstantiate(
            static fn (Identity $identity, array $inputs) => $identity->cancelErasure($inputs['cancelledAt']),
        );
    }

    public function erased(?\DateTimeImmutable $erasedAt = null): self
    {
        return $this->with(array_filter(['erasedAt' => $erasedAt]))->afterInstantiate(
            static fn (Identity $identity, array $inputs) => $identity->erase($inputs['erasedAt']),
        );
    }

    protected static function build(array $parameters): AggregateRoot
    {
        return Identity::register(
            id: $parameters['id'],
            fullName: $parameters['fullName'],
            email: $parameters['email'],
            registeredAt: $parameters['registeredAt'],
        );
    }

    protected function defaults(): array
    {
        $now = Clock::get()->now();

        return [
            'id' => IdentityIdFactory::new(),
            'fullName' => FullNameFactory::new(),
            'email' => EmailFactory::new(),
            'registeredAt' => $now,
            'confirmedAt' => $now->modify('+1 hour'),
            'confirmationCode' => FakeCodeChallenger::CODE,
            'confirmationRequestedAt' => $now->modify('+30 minutes'),
            'fullNameChangedAt' => $now->modify('+45 minutes'),
            'emailChangeRequestedAt' => $now->modify('+50 minutes'),
            'emailChangedAt' => $now->modify('+55 minutes'),
            'reason' => ReasonFactory::new(),
            'suspendedAt' => $now->modify('+1 day'),
            'reactivatedAt' => $now->modify('+2 day'),
            'requestedAt' => $now->modify('+3 day'),
            'cancelledAt' => $now->modify('+4 day'),
            'erasedAt' => $now->modify('+5 day'),
        ];
    }
}
