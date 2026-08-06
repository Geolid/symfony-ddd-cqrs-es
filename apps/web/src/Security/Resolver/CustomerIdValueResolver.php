<?php

declare(strict_types=1);

namespace Web\Security\Resolver;

use Shared\Application\Exception\ApplicationExceptionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsTargetedValueResolver;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Web\Security\CustomerIdentityProvider;

#[AsTargetedValueResolver]
final readonly class CustomerIdValueResolver implements ValueResolverInterface
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private CustomerIdentityProvider $customerIdentityProvider,
    ) {
    }

    /**
     * @return iterable<string>
     *
     * @throws ApplicationExceptionInterface
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            throw new AccessDeniedException('No authenticated token to resolve a customer from.');
        }

        $customerId = $this->customerIdentityProvider->resolveCustomerId($token);
        if (null === $customerId) {
            throw new AccessDeniedException('No customer is linked to this identity.');
        }

        return [$customerId];
    }
}
