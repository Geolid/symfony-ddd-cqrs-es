<?php

declare(strict_types=1);

namespace Web\Security;

use Sales\Customer\Application\Query\GetCustomerByIdentityId\GetCustomerByIdentityId;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Query\QueryBusInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final readonly class CustomerIdentityProvider
{
    private const string ATTRIBUTE = 'customer_id';

    public function __construct(private QueryBusInterface $queryBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     */
    public function resolveCustomerId(TokenInterface $token): ?string
    {
        if ($token->hasAttribute(self::ATTRIBUTE)) {
            $customerId = $token->getAttribute(self::ATTRIBUTE);
            \assert(null === $customerId || \is_string($customerId));

            return $customerId;
        }

        $customer = $this->queryBus->ask(new GetCustomerByIdentityId($token->getUserIdentifier()));
        $customerId = $customer?->id;

        $token->setAttribute(self::ATTRIBUTE, $customerId);

        return $customerId;
    }
}
