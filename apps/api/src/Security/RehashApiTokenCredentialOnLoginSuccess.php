<?php

declare(strict_types=1);

namespace Api\Security;

use Iam\Identity\Application\Command\RehashApiTokenCredential\RehashApiTokenCredential;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

#[AsEventListener(event: LoginSuccessEvent::class)]
final readonly class RehashApiTokenCredentialOnLoginSuccess
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

        if (!$user instanceof ApiUser) {
            return;
        }

        $secret = $event->getPassport()->getBadge(PlainSecretBadge::class)?->secret();

        if (null === $secret) {
            return;
        }

        $this->commandBus->dispatch(new RehashApiTokenCredential($user->getUserIdentifier(), $secret));
    }
}
