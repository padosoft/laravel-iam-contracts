<?php

declare(strict_types=1);

namespace Padosoft\Iam\Contracts\Crypto;

/**
 * Gestione chiavi: wrap/unwrap delle DEK (envelope encryption) e firma/verifica dei token.
 * Driver: LocalKeyProvider (v1), AwsKmsKeyProvider (v1), Vault/Azure/GCP/HSM (v2).
 *
 * Vedi laravel-iam-docs/11-crypto-and-key-management.md §3–§6.
 *
 * NOTE(M3): in M3 i tipi `array` qui sotto diventano value object dedicati
 * (WrappedKey, DataKeyPair, Signature, SigningKeyRef, JwkSet).
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

    /**
     * Firma un payload (es. JWT) con la chiave di firma attiva.
     *
     * @return array{value: string, kid: string, alg: string}
     */
    public function sign(string $payload): array;

    /** Verifica una firma dato il `kid` (per rotazione/overlap). */
    public function verify(string $payload, string $signature, string $kid): bool;

    /**
     * Chiave di firma attiva (kid, alg, JWK pubblica per l'endpoint JWKS).
     *
     * @return array{kid: string, alg: string, public_jwk: array<string, mixed>}
     */
    public function activeSigningKey(): array;

    /**
     * Tutte le chiavi pubblicabili nel JWKS (attiva + in overlap).
     *
     * @return array<int, array<string, mixed>>
     */
    public function publishableJwks(): array;

    /**
     * Ruota la chiave di firma (nuovo kid; la vecchia resta in overlap).
     *
     * @return array{kid: string, alg: string, public_jwk: array<string, mixed>}
     */
    public function rotateSigningKey(): array;
}
