<?php

declare(strict_types=1);

namespace Iam\Identity\Infrastructure\Security;

use Iam\Identity\Application\Exception\IdentityResultNotFoundException;
use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Shared\Application\Command\ActingIdentityAware;
use Shared\Application\Exception\ActingIdentityNotActiveException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

final readonly class ActingIdentityMessengerMiddleware implements MiddlewareInterface
{
    public function __construct(private IdentityFinderInterface $finder)
    {
    }

    /**
     * @throws IdentityResultNotFoundException
     * @throws ActingIdentityNotActiveException
     */
    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $message = $envelope->getMessage();

        if ($message instanceof ActingIdentityAware) {
            // Read model, not the aggregate: a few ms of projection lag here is an accepted tradeoff, not a bug.
            $identity = $this->finder->ofId($message->actingIdentityId());

            if (!$identity->status->isActive()) {
                throw ActingIdentityNotActiveException::forIdentity($identity->id);
            }
        }

        return $stack->next()->handle($envelope, $stack);
    }
}
