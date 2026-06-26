<?php

declare(strict_types=1);

namespace Padosoft\Iam\Contracts\Crypto;

/**
 * Cifratura/decifratura di segreti applicativi (client secret, secret upstream, PII)
 * tramite envelope encryption. `scope` abilita DEK per-tenant/per-soggetto e quindi
 * il crypto-shredding (GDPR right-to-erasure).
 *
 * Vedi laravel-iam-docs/11-crypto-and-key-management.md §4, §8.
 */
interface SecretCipher
{
    /**
     * Cifra un valore. `scope` permette DEK per-tenant (crypto-shredding).
     *
     * @return array{ciphertext: string, wrapped_dek: string, key_id: string, key_version: int, scope: string|null}
     */
    public function encrypt(string $plaintext, ?string $scope = null): array;

    /**
     * Decifra un valore precedentemente cifrato.
     *
     * @param  array{ciphertext: string, wrapped_dek: string, key_id: string, key_version: int, scope: string|null}  $value
     */
    public function decrypt(array $value): string;

    /** Distrugge la/le DEK di uno scope → crypto-shredding irreversibile. */
    public function shred(string $scope): void;
}
