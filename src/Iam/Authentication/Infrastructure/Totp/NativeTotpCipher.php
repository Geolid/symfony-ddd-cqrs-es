<?php

declare(strict_types=1);

namespace Iam\Authentication\Infrastructure\Totp;

use Iam\Authentication\Domain\TotpCredential\Service\TotpCipherInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class NativeTotpCipher implements TotpCipherInterface
{
    public function __construct(
        #[Autowire('%env(TOTP_ENCRYPTION_SECRET)%')]
        #[\SensitiveParameter]
        private string $secret,
    ) {
    }

    public function encrypt(#[\SensitiveParameter] string $secret): string
    {
        $nonce = random_bytes(\SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        return base64_encode($nonce.sodium_crypto_secretbox($secret, $nonce, $this->key()));
    }

    public function decrypt(string $encryptedSecret): string
    {
        $decoded = base64_decode($encryptedSecret, true);
        \assert(false !== $decoded);

        $nonce = substr($decoded, 0, \SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = substr($decoded, \SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        $secret = sodium_crypto_secretbox_open($ciphertext, $nonce, $this->key());
        if (false === $secret) {
            throw new \RuntimeException('Unable to decrypt the TOTP secret: the ciphertext or key is invalid.');
        }

        return $secret;
    }

    private function key(): string
    {
        return sodium_crypto_generichash($this->secret, '', \SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
    }
}
