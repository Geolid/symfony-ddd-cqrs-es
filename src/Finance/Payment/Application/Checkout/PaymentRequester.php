<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Checkout;

use Finance\Payment\Application\Command\RequestPayment\Exception\PaymentAlreadyClaimedException;
use Finance\Payment\Application\Command\RequestPayment\RequestPayment;
use Finance\Payment\Application\Finder\Payment\PaymentFinderInterface;
use Finance\Payment\Application\PSP\PaymentGatewayInterface;
use Finance\Payment\Application\Uniqueness\PaymentUniqueKey;
use Ramsey\Uuid\Uuid;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniquenessRegistryInterface;
use Shared\Domain\ValueObject\PostalAddress;

final readonly class PaymentRequester implements PaymentRequesterInterface
{
    public function __construct(
        private UniquenessRegistryInterface $uniqueValues,
        private PaymentFinderInterface $paymentFinder,
        private PaymentGatewayInterface $paymentGateway,
        private CommandBusInterface $commandBus,
    ) {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    public function requestFor(string $cartId, int $amountInCents, PostalAddress $billingAddress, string $returnUrl): string
    {
        $cartKey = UniqueKey::for(PaymentUniqueKey::CART);

        if ($this->uniqueValues->isClaimed($cartKey, $cartId)) {
            return $this->paymentFinder->ofCartId($cartId)->checkoutUrl;
        }

        $session = $this->paymentGateway->requestPayment($cartId, $amountInCents, $returnUrl, $billingAddress);

        try {
            $this->commandBus->dispatch(new RequestPayment(
                id: Uuid::uuid7()->toString(),
                cartId: $cartId,
                amountInCents: $amountInCents,
                reference: $session->reference,
                checkoutUrl: $session->checkoutUrl,
            ));
        } catch (PaymentAlreadyClaimedException) {
            return $this->paymentFinder->ofCartId($cartId)->checkoutUrl;
        }

        return $session->checkoutUrl;
    }
}
