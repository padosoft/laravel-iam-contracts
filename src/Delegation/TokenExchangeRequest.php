<?php

declare(strict_types=1);

namespace Padosoft\Iam\Contracts\Delegation;

/**
 * Richiesta di Token Exchange (RFC 8693 §2.1) lato client: l'agente — autenticato
 * con le PROPRIE credenziali (private_key_jwt, RFC 7523) — presenta il token
 * dell'utente come `subject_token` e chiede un token delegato down-scoped.
 * Il token dell'utente non è mai in mano all'LLM: lo tiene l'orchestratore backend.
 *
 * `actorToken` serve solo al multi-hop (v2): un token già delegato che estende la
 * catena. In MVP il server lo rifiuta con `invalid_request` pulito (conformance wire).
 */
final readonly class TokenExchangeRequest
{
    /**
     * @param  list<string>  $scopes  down-scoping richiesto ([] = tutti gli scope della grant)
     */
    public function __construct(
        public string $subjectToken,
        public string $subjectTokenType = ActClaim::TOKEN_TYPE_ACCESS,
        public array $scopes = [],
        public ?string $audience = null,
        public ?string $resource = null,
        public string $requestedTokenType = ActClaim::TOKEN_TYPE_ACCESS,
        public ?string $actorToken = null,
        public ?string $actorTokenType = null,
    ) {}
}
