<?php

declare(strict_types=1);

namespace Padosoft\Iam\Contracts\Delegation;

/**
 * Risposta del Token Exchange (RFC 8693 §2.2): token delegato a vita breve
 * (`sub` = utente, `act` = agente, `pds_dgr` = grant), NON refreshable by design —
 * l'agente ri-scambia, e ogni exchange ri-verifica grant e sessione (freshness
 * della revoca). `scopes` è sempre esplicitato: può differire dal richiesto.
 */
final readonly class TokenExchangeResult
{
    /**
     * @param  list<string>  $scopes
     */
    public function __construct(
        public string $accessToken,
        public string $issuedTokenType,
        public int $expiresIn,
        public array $scopes,
        public string $tokenType = 'Bearer',
    ) {}
}
