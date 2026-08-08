<?php

declare(strict_types=1);

namespace Web\Security;

use Iam\Identity\Application\Command\RehashPasswordCredential\RehashPasswordCredential;
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

        $secret = $event->getPassport()->getBadge(PlainSecretBadge::class)?->secret();

        if (null === $secret) {
            return;
        }

        $this->commandBus->dispatch(new RehashPasswordCredential($user->identityId(), $secret));
    }
}
