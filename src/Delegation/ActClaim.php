<?php

declare(strict_types=1);

namespace Padosoft\Iam\Contracts\Delegation;

/**
 * Costanti del protocollo di delega (RFC 8693 Token Exchange + claim `act`).
 * Un token delegato porta DUE identità: `sub` = utente delegante, `act` = agente
 * che agisce per suo conto (catene annidate per la delega multi-hop, RFC 8693 §4.1).
 *
 * Vedi laravel-iam-docs/09-authorization-and-pdp.md (intersection rule) e il modulo
 * padosoft/laravel-iam-agents che implementa emissione e verifica.
 */
final class ActClaim
{
    /** Claim JWT dell'attore (RFC 8693 §4.1): `{"sub":"agent:…","act":{…}}` annidato per hop. */
    public const string ACT = 'act';

    /** Grant type del Token Exchange (RFC 8693 §2.1). */
    public const string GRANT_TYPE_TOKEN_EXCHANGE = 'urn:ietf:params:oauth:grant-type:token-exchange';

    /** Token type: access token (RFC 8693 §3). */
    public const string TOKEN_TYPE_ACCESS = 'urn:ietf:params:oauth:token-type:access_token';

    /** Token type: id_token (RFC 8693 §3) — subject_token alternativo (v2). */
    public const string TOKEN_TYPE_ID = 'urn:ietf:params:oauth:token-type:id_token';

    /** Token type: Identity Assertion JWT Authorization Grant (interop auth.md / agentic registration). */
    public const string TOKEN_TYPE_ID_JAG = 'urn:ietf:params:oauth:token-type:id-jag';

    /**
     * Claim privato: id della DelegationGrant che ha autorizzato l'emissione.
     * Consente la revoca mirata prima della scadenza naturale del token.
     * NB: nei metadata di audit usare chiavi tipo `grant_id` — MAI `*token*` (redaction).
     */
    public const string CLAIM_DELEGATION_GRANT = 'pds_dgr';

    /**
     * Header `typ` dedicato dei token delegati. Igiene di spec: i verifier act-aware
     * lo pretendono; NON è l'unica difesa (i token delegati sono introspection-mandatory,
     * vedi laravel-iam-agents).
     */
    public const string TYP_DELEGATED = 'delegated+jwt';

    private function __construct() {}
}
