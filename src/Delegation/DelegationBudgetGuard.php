<?php

declare(strict_types=1);

namespace Padosoft\Iam\Contracts\Delegation;

/**
 * Il meter dei budget di delega (implementazione di riferimento: laravel-ai-finops).
 * Interrogato dall'authorization server A OGNI exchange di una grant che dichiara
 * un budget: spesa/token/chiamate già attribuiti alla grant contro i cap consentiti.
 *
 * Contratto FAIL-CLOSED sul lato chiamante: una grant CON budget in un deployment
 * SENZA guard bindato non è enforceable ⇒ l'exchange va rifiutato (il vincolo che
 * l'utente ha consentito non è negoziabile per assenza di infrastruttura). Le grant
 * senza budget non interrogano mai il guard.
 */
interface DelegationBudgetGuard
{
    public function verdict(DelegationGrant $grant): BudgetVerdict;
}
