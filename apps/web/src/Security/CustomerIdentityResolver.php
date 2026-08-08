<?php

declare(strict_types=1);

namespace Web\Security;

use Sales\Customer\Application\Finder\Customer\CustomerResult;
use Sales\Customer\Application\Query\GetCustomerByIdentity\GetCustomerByIdentity;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Query\QueryBusInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final readonly class CustomerIdentityResolver
{
    private const string ATTRIBUTE = 'customer';

    public function __construct(private QueryBusInterface $queryBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     */
    public function resolveFor(TokenInterface $token): CustomerResult
    {
        if ($token->hasAttribute(self::ATTRIBUTE)) {
            $customer = $token->getAttribute(self::ATTRIBUTE);
            \assert($customer instanceof CustomerResult);

            return $customer;
        }

        $user = $token->getUser();
        \assert($user instanceof PasswordUser);

        $customer = $this->queryBus->ask(new GetCustomerByIdentity($user->identityId()));

        $token->setAttribute(self::ATTRIBUTE, $customer);

        return $customer;
    }
}
