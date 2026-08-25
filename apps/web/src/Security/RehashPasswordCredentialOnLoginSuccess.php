<?php

declare(strict_types=1);

namespace Web\Security;

use Iam\Authentication\Application\Command\RehashPassword\RehashPassword;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

#[AsEventListener(event: LoginSuccessEvent::class)]
final readonly class RehashPasswordCredentialOnLoginSuccess
{
    public function __construct(private CommandBusInterface $commandBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    public function __invoke(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();

        if (!$user instanceof PasswordUser) {
            return;
        }

        $password = $event->getPassport()->getBadge(PlainSecretBadge::class)?->secret();

        if (null === $password) {
            return;
        }

        $this->commandBus->dispatch(new RehashPassword($user->identityId(), $password));
    }
}
