<?php

declare(strict_types=1);

namespace Padosoft\Iam\Contracts\Delegation;

/**
 * Lato client dello scambio (RFC 8693): implementato dagli SDK/runtime che agiscono
 * come agente (flow-ai `DelegatedIdentity`, backend orchestrator mobile). Il lato
 * emissione è il grant registrato nell'authorization server dal modulo
 * padosoft/laravel-iam-agents.
 *
 * Fallimenti (grant assente/revocata, agente non attivo, sessione utente morta,
 * scope fuori intersezione) ⇒ eccezione del chiamante, mai un token degradato.
 */
interface TokenExchanger
{
    public function exchange(TokenExchangeRequest $request): TokenExchangeResult;
}
