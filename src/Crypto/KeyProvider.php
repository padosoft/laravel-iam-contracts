<?php

declare(strict_types=1);

namespace Padosoft\Iam\Contracts\Crypto;

/**
 * Gestione DEK (envelope encryption): wrap/unwrap. La firma dei token è in {@see TokenSigner}.
 * Driver: LocalKeyProvider (v1), AwsKmsKeyProvider (M3.x), Vault/Azure/GCP/HSM (v2).
 *
 * Vedi laravel-iam-docs/11-crypto-and-key-management.md §3–§5.
 */
interface KeyProvider
{
    /**
     * Incarta (cifra) una DEK con la KEK attiva.
     *
     * @return array{ciphertext: string, key_id: string, key_version: int}
     */
    public function wrapDataKey(string $plaintextDek): array;

    /**
     * Scarta (decifra) una DEK precedentemente incartata.
     *
     * @param  array{ciphertext: string, key_id: string, key_version: int}  $wrapped
     */
    public function unwrapDataKey(array $wrapped): string;

    /**
     * Genera una nuova DEK (in chiaro + già incartata).
     *
     * @return array{plaintext: string, wrapped: array{ciphertext: string, key_id: string, key_version: int}}
     */
    public function generateDataKey(): array;
}
