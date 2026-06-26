<?php

declare(strict_types=1);

namespace Padosoft\Iam\Contracts\Crypto;

/**
 * Emissione e verifica di JWT firmati (asimmetrici, ES256) + JWKS e rotazione chiavi.
 * Le chiavi private sono custodite cifrate (via {@see KeyProvider}); la pubblica è esposta nel JWKS.
 *
 * Vedi laravel-iam-docs/13-oauth-oidc-server.md (token) e 11 (JWKS rotation).
 */
interface TokenSigner
{
    /**
     * Emette un JWT firmato con i claims dati (aggiunge iat/exp/jti e il kid nell'header).
     *
     * @param  array<string, mixed>  $claims
     */
    public function issue(array $claims, int $ttlSeconds): string;

    /**
     * Verifica firma + scadenza e ritorna i claims. Lancia un'eccezione se il token è invalido.
     *
     * @return array<string, mixed>
     */
    public function parse(string $jwt): array;

    /**
     * JWKS: chiavi pubbliche attive + in overlap (per la rotazione).
     *
     * @return list<array<string, mixed>>
     */
    public function jwks(): array;

    /** Ruota la chiave di firma (nuova attiva; la precedente resta in overlap). Ritorna il nuovo kid. */
    public function rotate(): string;
}
